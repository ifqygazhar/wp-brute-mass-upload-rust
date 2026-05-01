use colored::*;
use rand::seq::SliceRandom;
use reqwest::{Client, Identity};
use std::fs;
use std::net::ToSocketAddrs;
use std::path::Path;
use tokio::fs::OpenOptions;
use tokio::io::AsyncWriteExt;
use tokio::net::TcpStream;
use tokio::time::{timeout, Duration};
use url::Url;

// ─── User-Agent Pool ─────────────────────────────────────────────────────────

pub fn load_user_agents() -> Vec<String> {
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

pub fn random_user_agent(agents: &[String]) -> String {
    agents
        .choose(&mut rand::thread_rng())
        .cloned()
        .unwrap_or_else(|| {
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/37.0.2062.94 Chrome/37.0.2062.94 Safari/537.36".to_string()
        })
}

// ─── File Saver (thread-safe) ────────────────────────────────────────────────

pub async fn save_content(path: &str, content: &str) {
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

pub fn build_client() -> Client {
    let cert_path = "Files/cert.pem";
    let key_path = "Files/key.pem";

    let base_builder = || {
        Client::builder()
            .danger_accept_invalid_certs(true)
            .timeout(Duration::from_secs(10))
            .cookie_store(true)
            .redirect(reqwest::redirect::Policy::limited(10))
    };

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

    base_builder().build().unwrap_or_else(|e| {
        eprintln!("{}", format!("Failed to build HTTP client: {:?}", e).red());
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

pub async fn check_port(host: &str) -> Option<String> {
    let clean = host.replace("http://", "").replace("https://", "");
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

pub async fn parse_url(raw: &str) -> Option<String> {
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
            if let Some(port) = parsed.port() {
                clean = format!(
                    "{}://{}:{}{}",
                    parsed.scheme(),
                    parsed.host_str().unwrap_or(""),
                    port,
                    parsed.path()
                );
            }
            if clean.ends_with('/') {
                clean.pop();
            }
            Some(clean)
        }
        Err(_) => None,
    }
}
