/*
 * Domain Grabber — Rewrite of cubdomain Python tool to Rust
 * Server 1: cubdomain.com — grab domains by registration date
 * Server 2: all-url.info  — grab domains by extension
 * Original Python by c0del1ar (Arya Kresna)
 */

use colored::*;
use regex::Regex;
use reqwest::header::USER_AGENT;
use scraper::{Html, Selector};
use std::io::{self, Write};
use tokio::fs::OpenOptions;
use tokio::io::AsyncWriteExt;
use tokio::time::Duration;

use crate::common::build_client;

// ─── Output File ─────────────────────────────────────────────────────────────

const OUTPUT_FILE: &str = "grablist.txt";

async fn append_to_file(path: &str, content: &str) {
    match OpenOptions::new()
        .create(true)
        .append(true)
        .open(path)
        .await
    {
        Ok(mut file) => {
            let _ = file.write_all(content.as_bytes()).await;
        }
        Err(e) => {
            eprintln!("{}", format!("Warning: Could not write to {}: {}", path, e).red());
        }
    }
}

// ─── Service 1: cubdomain.com (domains by date) ─────────────────────────────

struct CubDomain {
    client: reqwest::Client,
    base_url: String,
}

impl CubDomain {
    fn new(client: reqwest::Client) -> Self {
        Self {
            client,
            base_url: "https://www.cubdomain.com/domains-registered-by-date/".to_string(),
        }
    }

    /// Count total pages for a given date (YYYY-MM-DD format)
    async fn count_pages(&self, date: &str) -> Result<usize, String> {
        let url = format!("{}{}/1", self.base_url, date);
        let resp = self
            .client
            .get(&url)
            .header(USER_AGENT, "GoogleBot v3")
            .timeout(Duration::from_secs(15))
            .send()
            .await
            .map_err(|e| format!("Request failed: {}", e))?;

        let text = resp
            .text()
            .await
            .map_err(|e| format!("Failed to read body: {}", e))?;

        let doc = Html::parse_document(&text);
        let sel = Selector::parse("a.page-link").unwrap();
        let pages: Vec<String> = doc.select(&sel).map(|el| el.text().collect()).collect();

        if pages.len() < 2 {
            return Ok(1);
        }

        // Second-to-last page-link element contains the max page number
        pages[pages.len() - 2]
            .trim()
            .parse::<usize>()
            .map_err(|_| "Failed to parse page count".to_string())
    }

    /// Dump domains from a specific date/page, optionally filtered by extensions
    async fn dump(
        &self,
        date: &str,
        page: usize,
        extensions: &[String],
    ) -> Result<Vec<String>, String> {
        let url = format!("{}{}/{}", self.base_url, date, page);
        let resp = self
            .client
            .get(&url)
            .header(USER_AGENT, "GoogleBot v3")
            .timeout(Duration::from_secs(15))
            .send()
            .await
            .map_err(|e| format!("Request failed: {}", e))?;

        let text = resp
            .text()
            .await
            .map_err(|e| format!("Failed to read body: {}", e))?;

        let doc = Html::parse_document(&text);
        let sel = Selector::parse("div.col-md-4").unwrap();

        let mut domains = Vec::new();

        for el in doc.select(&sel) {
            let domain: String = el.text().collect::<String>().replace('\n', "").trim().to_string();
            if domain.is_empty() {
                continue;
            }

            if extensions.is_empty() {
                domains.push(domain);
            } else {
                for ext in extensions {
                    let ext_clean = if ext.starts_with('.') {
                        ext.to_string()
                    } else {
                        format!(".{}", ext)
                    };
                    if domain.ends_with(&ext_clean) {
                        domains.push(domain.clone());
                        break;
                    }
                }
            }
        }

        Ok(domains)
    }
}

// ─── Service 2: all-url.info (domains by extension) ──────────────────────────

struct AllUrlInfo {
    client: reqwest::Client,
    base_url: String,
}

impl AllUrlInfo {
    fn new(client: reqwest::Client, extension: &str) -> Self {
        Self {
            client,
            base_url: format!("https://{}.all-url.info/", extension),
        }
    }

    /// Test connection to the server
    async fn connect(&self) -> bool {
        match self
            .client
            .get(&self.base_url)
            .header(USER_AGENT, "Googlebot V3")
            .timeout(Duration::from_secs(10))
            .send()
            .await
        {
            Ok(resp) => resp.status().is_success(),
            Err(_) => false,
        }
    }

    /// Count homepage pages (top-level pagination)
    async fn count_home_pages(&self) -> Result<usize, String> {
        let resp = self
            .client
            .get(&self.base_url)
            .header(USER_AGENT, "Googlebot V3")
            .timeout(Duration::from_secs(15))
            .send()
            .await
            .map_err(|e| format!("Request failed: {}", e))?;

        let text = resp
            .text()
            .await
            .map_err(|e| format!("Failed to read body: {}", e))?;

        let doc = Html::parse_document(&text);
        let sel = Selector::parse("#u1168-4 a").unwrap();
        let count = doc.select(&sel).count();

        if count > 1 {
            Ok(count - 1)
        } else {
            Ok(1)
        }
    }

    /// Count sub-pages within a homepage section
    async fn count_sub_pages(&self, page: usize) -> Result<usize, String> {
        let url = format!("{}{}/0/", self.base_url, page);
        let resp = self
            .client
            .get(&url)
            .header(USER_AGENT, "Googlebot V3")
            .timeout(Duration::from_secs(15))
            .send()
            .await
            .map_err(|e| format!("Request failed: {}", e))?;

        let text = resp
            .text()
            .await
            .map_err(|e| format!("Failed to read body: {}", e))?;

        let doc = Html::parse_document(&text);
        let sel = Selector::parse(r#"td[rowspan="2"]"#).unwrap();

        if let Some(td) = doc.select(&sel).next() {
            // Extract text content, parse the number
            let td_text: String = td.text().collect();
            let re = Regex::new(r"\d+").unwrap();
            if let Some(m) = re.find(&td_text) {
                return m
                    .as_str()
                    .parse::<usize>()
                    .map_err(|_| "Failed to parse sub-page count".to_string());
            }
        }

        Ok(1)
    }

    /// Dump domains from a specific page/sub-page
    async fn dump_site(
        &self,
        in_page: usize,
        at_page: usize,
    ) -> Result<Vec<String>, String> {
        let url = format!("{}{}/{}/", self.base_url, in_page, at_page);
        let resp = self
            .client
            .get(&url)
            .header(USER_AGENT, "Googlebot V3")
            .timeout(Duration::from_secs(15))
            .send()
            .await
            .map_err(|e| format!("Request failed: {}", e))?;

        let text = resp
            .text()
            .await
            .map_err(|e| format!("Failed to read body: {}", e))?;

        let doc = Html::parse_document(&text);
        let sel = Selector::parse(r#"div[align="center"] font"#).unwrap();

        let domains: Vec<String> = doc
            .select(&sel)
            .map(|el| el.text().collect::<String>().trim().to_string())
            .filter(|d| !d.is_empty())
            .collect();

        Ok(domains)
    }
}

// ─── Date Helpers ────────────────────────────────────────────────────────────

/// Parse a date string like "2021-08-10" or "10-08-2021" or "10/08/2021" into (year, month, day)
fn parse_date_input(input: &str) -> Option<(i32, u32, u32)> {
    let parts: Vec<&str> = input
        .split(|c: char| c == '-' || c == '/' || c == ',' || c == ' ')
        .collect();

    if parts.len() != 3 {
        return None;
    }

    // Try YYYY-MM-DD first
    if parts[0].len() == 4 {
        let year = parts[0].parse::<i32>().ok()?;
        let month = parts[1].parse::<u32>().ok()?;
        let day = parts[2].parse::<u32>().ok()?;
        if month >= 1 && month <= 12 && day >= 1 && day <= 31 {
            return Some((year, month, day));
        }
    }

    // Try DD-MM-YYYY
    if parts[2].len() == 4 {
        let day = parts[0].parse::<u32>().ok()?;
        let month = parts[1].parse::<u32>().ok()?;
        let year = parts[2].parse::<i32>().ok()?;
        if month >= 1 && month <= 12 && day >= 1 && day <= 31 {
            return Some((year, month, day));
        }
    }

    None
}

/// Simple date math: add 1 day to (year, month, day)
fn next_day(year: i32, month: u32, day: u32) -> (i32, u32, u32) {
    let days_in_month = match month {
        1 | 3 | 5 | 7 | 8 | 10 | 12 => 31,
        4 | 6 | 9 | 11 => 30,
        2 => {
            if (year % 4 == 0 && year % 100 != 0) || year % 400 == 0 {
                29
            } else {
                28
            }
        }
        _ => 30,
    };

    if day < days_in_month {
        (year, month, day + 1)
    } else if month < 12 {
        (year, month + 1, 1)
    } else {
        (year + 1, 1, 1)
    }
}

/// Format (year, month, day) to "YYYY-MM-DD"
fn format_date(year: i32, month: u32, day: u32) -> String {
    format!("{:04}-{:02}-{:02}", year, month, day)
}

/// Check if date1 <= date2
fn date_lte(y1: i32, m1: u32, d1: u32, y2: i32, m2: u32, d2: u32) -> bool {
    (y1, m1, d1) <= (y2, m2, d2)
}

// ─── Input Helpers ───────────────────────────────────────────────────────────

fn read_line_prompt(prompt: &str) -> String {
    print!("{}", prompt);
    io::stdout().flush().unwrap();
    let mut input = String::new();
    io::stdin().read_line(&mut input).unwrap();
    input.trim().to_string()
}

// ─── Server 1 Flow ───────────────────────────────────────────────────────────

async fn run_server1(client: reqwest::Client) {
    println!(
        "{}",
        "[*] Server 1: cubdomain.com — Grab domains by registration date".yellow()
    );
    println!(
        "{}",
        "    Date format: YYYY-MM-DD (e.g., 2024-01-15)".cyan()
    );
    println!();

    // Get date range
    let from_str = read_line_prompt(&format!("{} From date (YYYY-MM-DD): ", "[?]".blue()));
    let (y1, m1, d1) = match parse_date_input(&from_str) {
        Some(d) => d,
        None => {
            eprintln!("{}", "Invalid date format! Use YYYY-MM-DD".red());
            return;
        }
    };

    let until_str = read_line_prompt(&format!("{} Until date (YYYY-MM-DD): ", "[?]".blue()));
    let (y2, m2, d2) = match parse_date_input(&until_str) {
        Some(d) => d,
        None => {
            eprintln!("{}", "Invalid date format! Use YYYY-MM-DD".red());
            return;
        }
    };

    // Extension filter
    let ext_input = read_line_prompt(&format!(
        "{} Filter extensions (e.g., .com .net) or empty for all: ",
        "[?]".blue()
    ));
    let extensions: Vec<String> = if ext_input.is_empty() {
        vec![]
    } else {
        ext_input.split_whitespace().map(|s| s.to_string()).collect()
    };

    if !extensions.is_empty() {
        println!(
            "{}",
            format!("[*] Filtering: {:?}", extensions).cyan()
        );
    }

    let grabber = CubDomain::new(client);
    let mut total_grabbed: u64 = 0;
    let (mut cy, mut cm, mut cd) = (y1, m1, d1);

    while date_lte(cy, cm, cd, y2, m2, d2) {
        let date_str = format_date(cy, cm, cd);
        eprint!(
            "{}",
            format!("[*] Processing date: {} ... ", date_str).cyan()
        );

        match grabber.count_pages(&date_str).await {
            Ok(total_pages) => {
                eprintln!(
                    "{}",
                    format!("{} pages found", total_pages).green()
                );

                for page in 1..=total_pages {
                    match grabber.dump(&date_str, page, &extensions).await {
                        Ok(domains) => {
                            let count = domains.len();
                            let mut buf = String::new();
                            for domain in &domains {
                                buf.push_str(domain);
                                buf.push('\n');
                            }
                            append_to_file(OUTPUT_FILE, &buf).await;
                            total_grabbed += count as u64;

                            if total_pages <= 10 || page % 10 == 0 || page == total_pages {
                                eprint!(
                                    "\r{}",
                                    format!(
                                        "[*] {} — page {}/{} — {} domains grabbed so far",
                                        date_str, page, total_pages, total_grabbed
                                    )
                                    .cyan()
                                );
                            }
                        }
                        Err(e) => {
                            eprintln!(
                                "{}",
                                format!("[!] Error on page {}: {}", page, e).red()
                            );
                        }
                    }
                }
                eprintln!(); // newline after progress
            }
            Err(e) => {
                eprintln!(
                    "{}",
                    format!("Error: {}. Skipping.", e).red()
                );
            }
        }

        let (ny, nm, nd) = next_day(cy, cm, cd);
        cy = ny;
        cm = nm;
        cd = nd;
    }

    eprintln!(
        "\n{}",
        format!(
            "[✓] Done! Grabbed {} domains total. Saved to {}",
            total_grabbed, OUTPUT_FILE
        )
        .green()
    );
}

// ─── Server 2 Flow ───────────────────────────────────────────────────────────

async fn run_server2(client: reqwest::Client) {
    println!(
        "{}",
        "[*] Server 2: all-url.info — Grab domains by extension".yellow()
    );
    println!(
        "{}",
        "    Enter extension like: com, net, org (without dot)".cyan()
    );
    println!();

    let ext_input = read_line_prompt(&format!("{} Extension: ", "[?]".blue()));
    let extension = ext_input.replace('.', "").to_lowercase();

    if extension.is_empty() {
        eprintln!("{}", "Extension cannot be empty!".red());
        return;
    }

    eprint!(
        "{}",
        format!("[*] Checking connection to {}.all-url.info ... ", extension).cyan()
    );

    let grabber = AllUrlInfo::new(client, &extension);

    if !grabber.connect().await {
        eprintln!(
            "{}",
            "Failed! Extension may not exist.".red()
        );
        return;
    }
    eprintln!("{}", "Connected!".green());

    match grabber.count_home_pages().await {
        Ok(home_pages) => {
            eprintln!(
                "{}",
                format!("[*] Found {} home pages", home_pages).green()
            );

            let mut total_grabbed: u64 = 0;

            for hp in 1..=home_pages {
                match grabber.count_sub_pages(hp).await {
                    Ok(sub_pages) => {
                        eprintln!(
                            "{}",
                            format!(
                                "[*] Home page {}/{} — {} sub-pages",
                                hp, home_pages, sub_pages
                            )
                            .cyan()
                        );

                        for sp in 0..sub_pages {
                            match grabber.dump_site(hp, sp).await {
                                Ok(domains) => {
                                    let count = domains.len();
                                    let mut buf = String::new();
                                    for domain in &domains {
                                        buf.push_str(domain);
                                        buf.push('\n');
                                    }
                                    append_to_file(OUTPUT_FILE, &buf).await;
                                    total_grabbed += count as u64;

                                    if sub_pages <= 20 || sp % 10 == 0 || sp == sub_pages - 1 {
                                        eprint!(
                                            "\r{}",
                                            format!(
                                                "[*] Page {}/{} — sub {}/{} — {} domains",
                                                hp, home_pages, sp + 1, sub_pages, total_grabbed
                                            )
                                            .cyan()
                                        );
                                    }
                                }
                                Err(e) => {
                                    eprintln!(
                                        "{}",
                                        format!("[!] Error on {}/{}: {}", hp, sp, e).red()
                                    );
                                }
                            }
                        }
                        eprintln!(); // newline
                    }
                    Err(e) => {
                        eprintln!(
                            "{}",
                            format!("[!] Error counting sub-pages for {}: {}", hp, e).red()
                        );
                    }
                }
            }

            eprintln!(
                "\n{}",
                format!(
                    "[✓] Done! Grabbed {} domains total. Saved to {}",
                    total_grabbed, OUTPUT_FILE
                )
                .green()
            );
        }
        Err(e) => {
            eprintln!(
                "{}",
                format!("[!] Error counting pages: {}", e).red()
            );
        }
    }
}

// ─── Public Entry Point ──────────────────────────────────────────────────────

pub async fn run() {
    println!(
        "{}",
        "DOMAIN GRABBER — Grab fresh domains from public sources".yellow()
    );
    println!();
    println!("  [{}] Server 1: cubdomain.com (by registration date)", "1".yellow());
    println!("  [{}] Server 2: all-url.info  (by extension)", "2".yellow());
    println!();

    let choice = read_line_prompt(&format!("{} Select server -> ", "[?]".blue()));

    let client = build_client();

    match choice.as_str() {
        "1" => run_server1(client).await,
        "2" => run_server2(client).await,
        _ => {
            eprintln!("{}", "Invalid choice. Use 1 or 2.".red());
        }
    }
}
