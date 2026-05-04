# Mass XMLRPC Wordpress Brute

XML-RPC &amp; WP-LOGIN WordPress Brute Force + Auto Upload — Rewritten in Rust

## Features

```
Mode 1 — Brute Force:
  1. XML-RPC & WP-Login Brute Force
  2. Custom SSL Identity (cert.pem + key.pem)
  3. Dict Password List with variable substitution
  4. Concurrent password attempts (multi-thread)
  5. Auto Check HTTP/HTTPS
  6. Auto Get Username via WP REST API

Mode 2 — Auto Upload:
  1. Auto Login with credentials
  2. Auto Upload Themes (shell)
  3. Auto Upload Plugins (shell)
  4. Auto Install WP-File-Manager Plugin
  5. Auto Upload Shell via WP File Manager
  6. Auto Verify Shell Access

Mode 4 — React/Next TXT Target Normalizer:
  1. Read Shodan IP/URL TXT files line by line
  2. Normalize IPs into base URLs such as http://1.2.3.4:3000
  3. Deduplicate output safely without running exploit payloads
```

## Installation

### Rust (Recommended)

Make sure you have [Rust](https://rustup.rs/) installed.

```bash
git clone https://github.com/fooster1337/Mass-XMLRPC-Wordpress-Brute/
cd Mass-XMLRPC-Wordpress-Brute
cargo build --release
./target/release/mass-xmlrpc-wordpress-brute
```

### Python (Legacy)

```bash
pip install colorama requests
python3 brute.py   # Brute Force
python3 auto.py    # Auto Upload
```

## Usage

Run the binary and select mode:

```
  [1] Brute Force (XML-RPC & WP-Login)
  [2] Auto Upload (Themes/Plugins/Shell)
  [3] Grab Domain (cubdomain/all-url)
  [4] React/Next TXT Target Normalizer

Select mode ->
```

### Brute Force

- **List**: File containing target URLs (one per line)
- **Password List**: Default `top-830_MCR.txt`, supports `[WPLOGIN]`, `[DOMAIN]`, `[UPPERLOGIN]` variables
- **Thread**: Number of concurrent tasks

### Auto Upload

- **List**: File containing `https://site.com/wp-login.php#user@password` (one per line)
- **Thread**: Number of concurrent tasks
- Requires `config.ini` with paths to `themes.zip`, `plugin.zip`, and shell file

### React/Next TXT Target Normalizer

- **List TXT**: File containing Shodan IPs or URLs, one per line
- **Default scheme**: `http` or `https`
- **Default port**: default `3000`; use `none` for no port
- **Output**: default `react-targets-clean.txt`

## Project Structure

```
src/
  main.rs      — Entry point with mode selection
  brute.rs     — Brute force module
  auto.rs      — Auto upload module
  common.rs    — Shared utilities (HTTP client, user-agent, file saver)
Files/
  user-agent.txt   — User-Agent pool
  cert.pem         — Custom SSL cert (optional)
  key.pem          — Custom SSL key (optional)
  themes.zip       — Theme shell package (for auto mode)
  plugin.zip       — Plugin shell package (for auto mode)
  wp-header.php    — Shell file (for auto mode)
config.ini         — Auto upload configuration
```

## Credits

Author rust : <a href="https://t.me/@fxshellx12">fxshellx12</a>
Author py : <a href="https://t.me/@GrazzMean">fooster1337</a>
