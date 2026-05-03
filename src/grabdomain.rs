/*
 * Domain Grabber — Rewrite of cubdomain Python tool to Rust
 * Server 1: cubdomain.com — grab domains by registration date
 * Server 2: all-url.info  — grab domains by extension
 * Original Python by c0del1ar (Arya Kresna)
 */

use colored::*;
use regex::Regex;
use reqwest::header::{ACCEPT, ACCEPT_LANGUAGE, COOKIE, REFERER, USER_AGENT};
use scraper::{Html, Selector};
use serde_json::Value;
use std::collections::HashSet;
use std::env;
use std::io::{self, Write};
use tokio::fs::OpenOptions;
use tokio::io::AsyncWriteExt;
use tokio::time::Duration;

use crate::common::build_client;

// ─── Output File ─────────────────────────────────────────────────────────────

const OUTPUT_FILE: &str = "grablist.txt";
const CUBDOMAIN_UA: &str = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";
const CUBDOMAIN_PROXY_PREFIX: &str = "https://proxy-bypass-cors.verifwebsitepro.workers.dev/?url=";

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
            eprintln!(
                "{}",
                format!("Warning: Could not write to {}: {}", path, e).red()
            );
        }
    }
}

// ─── Service 1: cubdomain.com (domains by date) ─────────────────────────────

struct CubDomain {
    client: reqwest::Client,
    page_base_url: String,
    api_base_url: String,
}

impl CubDomain {
    fn new(client: reqwest::Client) -> Self {
        Self {
            client,
            page_base_url: env::var("CUBDOMAIN_PAGE_BASE_URL").unwrap_or_else(|_| {
                format!(
                    "{}{}",
                    CUBDOMAIN_PROXY_PREFIX, "https://www.cubdomain.com/domains-registered-by-date/"
                )
            }),
            api_base_url: env::var("CUBDOMAIN_API_BASE_URL").unwrap_or_else(|_| {
                format!(
                    "{}{}",
                    CUBDOMAIN_PROXY_PREFIX,
                    "https://api.cubdomain.com/Home/domains-registered-by-date/"
                )
            }),
        }
    }

    fn request(&self, url: &str) -> reqwest::RequestBuilder {
        let mut req = self
            .client
            .get(url)
            .header(USER_AGENT, CUBDOMAIN_UA)
            .header(
                ACCEPT,
                "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            )
            .header(ACCEPT_LANGUAGE, "en-US,en;q=0.9")
            .header(
                REFERER,
                "https://www.cubdomain.com/domains-registered-dates/",
            )
            .timeout(Duration::from_secs(15));

        if let Ok(cookie) = env::var("CUBDOMAIN_COOKIE") {
            let cookie = cookie.trim();
            if !cookie.is_empty() {
                req = req.header(COOKIE, cookie);
            }
        }

        req
    }

    async fn fetch_html(&self, url: &str) -> Result<String, String> {
        let resp = self
            .request(url)
            .send()
            .await
            .map_err(|e| format!("Request failed: {}", e))?;

        let status = resp.status();
        let text = resp
            .text()
            .await
            .map_err(|e| format!("Failed to read body: {}", e))?;

        if looks_like_cloudflare_challenge(&text) {
            return Err(
                "Cubdomain returned a Cloudflare/JavaScript challenge instead of the domain list"
                    .to_string(),
            );
        }

        if !status.is_success() {
            return Err(format!("HTTP status {}", status));
        }

        Ok(text)
    }

    async fn fetch_api_page(&self, date: &str, page: usize) -> Result<CubDomainApiPage, String> {
        let url = format!("{}{}/{}", self.api_base_url, date, page);
        let text = self.fetch_html(&url).await?;
        parse_cubdomain_api_page(&text)
    }

    /// Count total pages for a given date (YYYY-MM-DD format)
    async fn count_pages(&self, date: &str) -> Result<usize, String> {
        if let Ok(api_page) = self.fetch_api_page(date, 1).await {
            return Ok(api_page.total_pages());
        }

        let url = format!("{}{}/1", self.page_base_url, date);
        let text = self.fetch_html(&url).await?;

        let doc = Html::parse_document(&text);

        if let Some(total_pages) = parse_total_pages_from_text(&doc) {
            return Ok(total_pages);
        }

        let sel = Selector::parse("a.page-link").unwrap();
        let pages: Vec<usize> = doc
            .select(&sel)
            .filter_map(|el| parse_usize(&el.text().collect::<String>()))
            .collect();

        if let Some(total_pages) = pages.into_iter().max() {
            return Ok(total_pages);
        }

        Ok(1)
    }

    /// Dump domains from a specific date/page, optionally filtered by extensions
    async fn dump(
        &self,
        date: &str,
        page: usize,
        extensions: &[String],
    ) -> Result<Vec<String>, String> {
        if let Ok(api_page) = self.fetch_api_page(date, page).await {
            return Ok(filter_domains(api_page.domains, extensions));
        }

        let url = format!("{}{}/{}", self.page_base_url, date, page);
        let text = self.fetch_html(&url).await?;

        let doc = Html::parse_document(&text);
        Ok(extract_cubdomain_domains(&doc, extensions))
    }
}

struct CubDomainApiPage {
    domains: Vec<String>,
    total_records: usize,
    page_size: usize,
}

impl CubDomainApiPage {
    fn total_pages(&self) -> usize {
        if self.total_records == 0 {
            return if self.domains.is_empty() { 0 } else { 1 };
        }

        let page_size = self.page_size.max(1);
        self.total_records.div_ceil(page_size)
    }
}

fn looks_like_cloudflare_challenge(html: &str) -> bool {
    let lower = html.to_ascii_lowercase();
    lower.contains("just a moment")
        || lower.contains("__cf_chl_")
        || lower.contains("/cdn-cgi/challenge-platform")
}

fn parse_total_pages_from_text(doc: &Html) -> Option<usize> {
    let text = doc.root_element().text().collect::<Vec<_>>().join(" ");
    let re = Regex::new(r"(?i)\bpage\s+\d+\s+of\s+([0-9][0-9,]*)").unwrap();
    re.captures(&text)
        .and_then(|caps| caps.get(1))
        .and_then(|m| parse_usize(m.as_str()))
}

fn parse_cubdomain_api_page(text: &str) -> Result<CubDomainApiPage, String> {
    let json: Value = serde_json::from_str(text)
        .map_err(|e| format!("Failed to parse Cubdomain API JSON: {e}"))?;

    let status = json
        .get("status")
        .and_then(Value::as_str)
        .unwrap_or_default();
    if !status.eq_ignore_ascii_case("success") {
        return Err(format!("Cubdomain API returned status '{status}'"));
    }

    let output = json
        .get("output")
        .ok_or_else(|| "Cubdomain API response missing output".to_string())?;

    let domains = output
        .get("domains")
        .and_then(Value::as_array)
        .ok_or_else(|| "Cubdomain API response missing domains".to_string())?
        .iter()
        .filter_map(Value::as_str)
        .map(str::to_string)
        .collect::<Vec<_>>();

    let total_records = output
        .get("totalRecords")
        .and_then(Value::as_u64)
        .map(|n| n as usize)
        .unwrap_or(domains.len());

    let page_size = output
        .get("pageSize")
        .and_then(Value::as_u64)
        .map(|n| n as usize)
        .filter(|n| *n > 0)
        .unwrap_or_else(|| domains.len().max(1));

    Ok(CubDomainApiPage {
        domains,
        total_records,
        page_size,
    })
}

fn extract_cubdomain_domains(doc: &Html, extensions: &[String]) -> Vec<String> {
    let mut domains = Vec::new();
    let mut seen = HashSet::new();

    // Old Cubdomain layout used one div.col-md-4 per domain.
    let old_sel = Selector::parse("div.col-md-4").unwrap();
    for el in doc.select(&old_sel) {
        let text = el.text().collect::<Vec<_>>().join("");
        push_domain_if_match(&mut domains, &mut seen, &text, extensions);
    }

    // Current Cubdomain layout renders registered domains as anchors.
    let link_sel = Selector::parse("a[href]").unwrap();
    for el in doc.select(&link_sel) {
        let text = el.text().collect::<Vec<_>>().join("");
        let domain = normalize_domain(&text);
        if !is_probable_domain(&domain) {
            continue;
        }

        let href = el.value().attr("href").unwrap_or("");
        if href.is_empty()
            || href
                .to_ascii_lowercase()
                .contains(&domain.to_ascii_lowercase())
        {
            push_domain_if_match(&mut domains, &mut seen, &domain, extensions);
        }
    }

    domains
}

fn filter_domains(raw_domains: Vec<String>, extensions: &[String]) -> Vec<String> {
    let mut domains = Vec::new();
    let mut seen = HashSet::new();

    for raw in raw_domains {
        push_domain_if_match(&mut domains, &mut seen, &raw, extensions);
    }

    domains
}

fn parse_usize(raw: &str) -> Option<usize> {
    raw.trim().replace(',', "").parse::<usize>().ok()
}

fn normalize_domain(raw: &str) -> String {
    raw.split_whitespace()
        .collect::<String>()
        .trim()
        .trim_matches(|c: char| c == '"' || c == '\'' || c == ',' || c == ';')
        .trim_end_matches('.')
        .to_ascii_lowercase()
}

fn is_probable_domain(domain: &str) -> bool {
    if domain.len() < 4
        || domain.len() > 253
        || !domain.contains('.')
        || domain.contains("..")
        || domain.contains('/')
        || domain.contains('@')
        || domain.contains(':')
    {
        return false;
    }

    let re = Regex::new(
        r"(?i)^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$",
    )
    .unwrap();
    re.is_match(domain)
}

fn matches_extensions(domain: &str, extensions: &[String]) -> bool {
    if extensions.is_empty() {
        return true;
    }

    extensions.iter().any(|ext| {
        let ext = ext.trim().trim_start_matches('.').to_ascii_lowercase();
        !ext.is_empty() && domain.ends_with(&format!(".{}", ext))
    })
}

fn push_domain_if_match(
    domains: &mut Vec<String>,
    seen: &mut HashSet<String>,
    raw: &str,
    extensions: &[String],
) {
    let domain = normalize_domain(raw);
    if is_probable_domain(&domain)
        && matches_extensions(&domain, extensions)
        && seen.insert(domain.clone())
    {
        domains.push(domain);
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
    async fn dump_site(&self, in_page: usize, at_page: usize) -> Result<Vec<String>, String> {
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
        ext_input
            .split_whitespace()
            .map(|s| s.to_string())
            .collect()
    };

    if !extensions.is_empty() {
        println!("{}", format!("[*] Filtering: {:?}", extensions).cyan());
    }

    let grabber = CubDomain::new(client);
    let mut total_grabbed: u64 = 0;
    let mut printed_cloudflare_hint = false;
    let (mut cy, mut cm, mut cd) = (y1, m1, d1);

    while date_lte(cy, cm, cd, y2, m2, d2) {
        let date_str = format_date(cy, cm, cd);
        eprint!(
            "{}",
            format!("[*] Processing date: {} ... ", date_str).cyan()
        );

        match grabber.count_pages(&date_str).await {
            Ok(total_pages) => {
                eprintln!("{}", format!("{} pages found", total_pages).green());

                for page in 1..=total_pages {
                    match grabber.dump(&date_str, page, &extensions).await {
                        Ok(domains) => {
                            let count = domains.len();
                            let mut buf = String::new();
                            for domain in &domains {
                                buf.push_str(domain);
                                buf.push('\n');
                            }
                            if !buf.is_empty() {
                                append_to_file(OUTPUT_FILE, &buf).await;
                            }
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
                            eprintln!("{}", format!("[!] Error on page {}: {}", page, e).red());
                        }
                    }
                }
                eprintln!(); // newline after progress
            }
            Err(e) => {
                eprintln!("{}", format!("Error: {}. Skipping.", e).red());
                if e.contains("Cloudflare") && !printed_cloudflare_hint {
                    eprintln!(
                        "{}",
                        "[!] Cubdomain is blocking non-browser requests. If you already passed \
                         the browser check, export its Cookie header as CUBDOMAIN_COOKIE and rerun."
                            .yellow()
                    );
                    printed_cloudflare_hint = true;
                }
            }
        }

        let (ny, nm, nd) = next_day(cy, cm, cd);
        cy = ny;
        cm = nm;
        cd = nd;
    }

    if total_grabbed == 0 {
        eprintln!(
            "\n{}",
            format!(
                "[✓] Done! Grabbed 0 domains total. Nothing written to {}",
                OUTPUT_FILE
            )
            .green()
        );
    } else {
        eprintln!(
            "\n{}",
            format!(
                "[✓] Done! Grabbed {} domains total. Saved to {}",
                total_grabbed, OUTPUT_FILE
            )
            .green()
        );
    }
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
        eprintln!("{}", "Failed! Extension may not exist.".red());
        return;
    }
    eprintln!("{}", "Connected!".green());

    match grabber.count_home_pages().await {
        Ok(home_pages) => {
            eprintln!("{}", format!("[*] Found {} home pages", home_pages).green());

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
                                    if !buf.is_empty() {
                                        append_to_file(OUTPUT_FILE, &buf).await;
                                    }
                                    total_grabbed += count as u64;

                                    if sub_pages <= 20 || sp % 10 == 0 || sp == sub_pages - 1 {
                                        eprint!(
                                            "\r{}",
                                            format!(
                                                "[*] Page {}/{} — sub {}/{} — {} domains",
                                                hp,
                                                home_pages,
                                                sp + 1,
                                                sub_pages,
                                                total_grabbed
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

            if total_grabbed == 0 {
                eprintln!(
                    "\n{}",
                    format!(
                        "[✓] Done! Grabbed 0 domains total. Nothing written to {}",
                        OUTPUT_FILE
                    )
                    .green()
                );
            } else {
                eprintln!(
                    "\n{}",
                    format!(
                        "[✓] Done! Grabbed {} domains total. Saved to {}",
                        total_grabbed, OUTPUT_FILE
                    )
                    .green()
                );
            }
        }
        Err(e) => {
            eprintln!("{}", format!("[!] Error counting pages: {}", e).red());
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
    println!(
        "  [{}] Server 1: cubdomain.com (by registration date)",
        "1".yellow()
    );
    println!(
        "  [{}] Server 2: all-url.info  (by extension)",
        "2".yellow()
    );
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

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn parses_current_cubdomain_page_count() {
        let html = r#"
            <main>
                <h1>Domains Registered on January 01, 2021</h1>
                <p>Page 1 of 103 &bull; 102,454 domains registered on this date</p>
            </main>
        "#;
        let doc = Html::parse_document(html);

        assert_eq!(parse_total_pages_from_text(&doc), Some(103));
    }

    #[test]
    fn extracts_current_and_old_cubdomain_domains() {
        let html = r#"
            <a href="/">CubDomain.com</a>
            <div class="col-md-4">legacy-example.net</div>
            <a href="https://www.cubdomain.com/domain/2945hu.com">2945hu.com</a>
            <a href="https://www.cubdomain.com/domain/2agentnowager.online">
                2agentnowager.online
            </a>
            <a href="https://www.addtoany.com/share">Share</a>
            <a href="/domains-registered-by-date/2021-01-01/2">2</a>
        "#;
        let doc = Html::parse_document(html);

        assert_eq!(
            extract_cubdomain_domains(&doc, &[]),
            vec![
                "legacy-example.net".to_string(),
                "2945hu.com".to_string(),
                "2agentnowager.online".to_string(),
            ]
        );
    }

    #[test]
    fn filters_extensions_case_insensitively() {
        let html = r#"
            <a href="https://www.cubdomain.com/domain/example.com">Example.COM</a>
            <a href="https://www.cubdomain.com/domain/example.net">example.net</a>
        "#;
        let doc = Html::parse_document(html);
        let extensions = vec![".com".to_string()];

        assert_eq!(
            extract_cubdomain_domains(&doc, &extensions),
            vec!["example.com".to_string()]
        );
    }

    #[test]
    fn parses_cubdomain_api_page() {
        let json = r#"{
            "status": "Success",
            "message": null,
            "output": {
                "domains": ["Example.COM", "legacy-example.net"],
                "countByTLDs": [],
                "pageNo": 1,
                "pageSize": 1000,
                "totalRecords": 102454
            }
        }"#;

        let page = parse_cubdomain_api_page(json).unwrap();

        assert_eq!(page.total_pages(), 103);
        assert_eq!(
            filter_domains(page.domains, &[".com".to_string()]),
            vec!["example.com".to_string()]
        );
    }
}
