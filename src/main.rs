/*
 * XML-RPC && WP-Login Brute Force + Auto Upload + Domain Grabber
 * Original Python version by t.me/@GrazzMean | https://github.com/fooster1337
 * Rewrite in Rust by t.me/@fxshellx12 | https://github.com/ifqygazhar
 * Rust rewrite — edit as much as you like but don't forget to give credit.
 */

mod auto;
mod brute;
mod common;
mod grabdomain;

use colored::*;
use std::io::{self, BufRead, Write};
use std::sync::Arc;

use common::load_user_agents;

const BANNER: &str = r#"
  __     __     ______   ______     ______  
/\ \  _ \ \   /\  == \ /\  == \   /\  ___\ 
\ \ \/ ".\ \  \ \  _-/ \ \  __<   \ \  __\ 
 \ \__/".~\_\  \ \_\    \ \_____\  \ \_\   
  \/_/   \/_/   \/_/     \/_____/   \/_/   
                                           
"#;

#[tokio::main]
async fn main() {
    println!("{}", BANNER.green());
    println!("By @fxshellx12\n");

    // Load shared resources
    let user_agents = Arc::new(load_user_agents());

    // Mode selection
    println!("  [{}] Brute Force (XML-RPC & WP-Login)", "1".yellow());
    println!("  [{}] Auto Upload (Themes/Plugins/Shell)", "2".yellow());
    println!("  [{}] Grab Domain (cubdomain/all-url)", "3".yellow());
    println!();

    print!("Select mode -> ");
    io::stdout().flush().unwrap();
    let mut mode = String::new();
    io::stdin().lock().read_line(&mut mode).unwrap();

    match mode.trim() {
        "1" => brute::run(user_agents).await,
        "2" => auto::run(user_agents).await,
        "3" => grabdomain::run().await,
        _ => {
            eprintln!("{}", "Invalid selection. Use 1, 2, or 3.".red());
        }
    }
}
