/*
 * XML-RPC && WP-Login Brute Force + Auto Upload
 * Original Python version by t.me/@GrazzMean | https://github.com/fooster1337
 * Rewrite in Rust by t.me/@fxshellx12 | https://github.com/ifqygazhar
 * Rust rewrite — edit as much as you like but don't forget to give credit.
 */

mod auto;
mod brute;
mod common;

use colored::*;
use std::io::{self, BufRead, Write};
use std::sync::Arc;

use common::load_user_agents;

const BANNER: &str = r#"
  _                _        
 | |              | |       
 | |__  _ __ _   _| |_ ___  
 | '_ \| '__| | | | __/ _ \ 
 | |_) | |  | |_| | ||  __/ 
 |_.__/|_|   \__,_|\__\___| 
                             
"#;

#[tokio::main]
async fn main() {
    println!("{}", BANNER.green());
    println!("By @GrazzMean\n");

    // Load shared resources
    let user_agents = Arc::new(load_user_agents());

    // Mode selection
    println!("  [{}] Brute Force (XML-RPC & WP-Login)", "1".yellow());
    println!("  [{}] Auto Upload (Themes/Plugins/Shell)", "2".yellow());
    println!();

    print!("Select mode -> ");
    io::stdout().flush().unwrap();
    let mut mode = String::new();
    io::stdin().lock().read_line(&mut mode).unwrap();

    match mode.trim() {
        "1" => brute::run(user_agents).await,
        "2" => auto::run(user_agents).await,
        _ => {
            eprintln!("{}", "Invalid selection. Use 1 or 2.".red());
        }
    }
}
