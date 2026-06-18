# WP Hostnasi Security Hardening

> **Step-by-step WordPress security hardening by [Hostnasi Technologies](https://hostnasi.com)**  
> Version 1.0.0 · Requires WordPress 6.0+ · PHP 8.0+

A WordPress plugin that scans your site against 22 industry-standard security checks, shows a live 0–100% security score, and fixes 15 of those checks automatically — no technical knowledge required.

---

## Table of Contents

- [Why This Plugin](#why-this-plugin)
- [Features](#features)
- [Installation](#installation)
- [Using the Dashboard](#using-the-dashboard)
- [Security Checks Reference](#security-checks-reference)
  - [Authentication & Login](#-authentication--login)
  - [wp-config.php Hardening](#-wp-confighp-hardening)
  - [File & Directory Permissions](#-file--directory-permissions)
  - [Updates & Plugins](#-updates--plugins)
  - [HTTP Security Headers](#-http-security-headers)
  - [Information Leakage](#-information-leakage)
- [Manual Steps Guide](#manual-steps-guide)
- [How Auto-Fixes Work](#how-auto-fixes-work)
- [Compatibility](#compatibility)
- [Troubleshooting](#troubleshooting)
- [File Structure](#file-structure)
- [Changelog](#changelog)
- [Support](#support)

---

## Why This Plugin

A freshly installed WordPress site with default settings typically passes **fewer than 5** of these 22 checks. WordPress powers 43% of the web, making it the most targeted platform for automated attacks. This plugin closes the most common gaps in minutes.

```
Default WordPress site       After Hostnasi Security
────────────────────────     ──────────────────────────
Score: ~18%  ██░░░░░░░░     Score: 85%+  ████████░░
3–4 checks passing           18–22 checks passing
```

---

## Features

| Category | Checks | Auto-fixable |
|---|---|---|
| Authentication & login | 4 | 3 |
| wp-config.php hardening | 5 | 3 |
| File & directory permissions | 4 | 4 |
| Updates & plugins | 4 | 1 |
| HTTP security headers | 3 | 3 |
| Information leakage | 3 | 3 |
| **Total** | **22** | **15** |

**Built-in features (no third-party plugins needed):**

- Login lockout — blocks IPs after 5 failed attempts for 30 minutes
- Hidden login URL — moves `wp-login.php` to a secret random slug
- XML-RPC disable
- Security headers (X-Frame-Options, HSTS, Referrer-Policy, Permissions-Policy)
- User enumeration block (`/?author=` and REST API `/wp/v2/users`)
- WordPress version hiding (meta tags, feeds, asset query strings)
- wp-config.php constant patching (DISALLOW_FILE_EDIT, FORCE_SSL_ADMIN, WP_DEBUG)
- .htaccess hardening (PHP block in uploads, directory listing, sensitive file protection)
- wp-config.php permission fix (chmod 440)
- readme.html deletion

---

## Installation

### Method A — WordPress admin (recommended)

1. Download `hostnasi-security.zip` from your [Hostnasi client area](https://hostnasi.com/client)
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Choose `hostnasi-security.zip` and click **Install Now**
4. Click **Activate Plugin**
5. Navigate to **HN Security** in the left sidebar — the scan runs immediately

### Method B — FTP / cPanel File Manager

1. Extract `hostnasi-security.zip` on your computer
2. Upload the `hostnasi-security/` folder to `/wp-content/plugins/` on your server
3. Activate via **Plugins → Installed Plugins**

### Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.0 |
| PHP | 8.0 |
| User role | Administrator |
| Web server | Apache or LiteSpeed (Nginx: partial — see [Compatibility](#compatibility)) |

---

## Using the Dashboard

Navigate to **HN Security** in your WordPress admin sidebar after activation.

### Security score

```
  ╭──────╮
  │  73% │  ← Needs work (amber)
  ╰──────╯

  ✓ Good       80% and above
  ⚠ Needs work  50–79%
  ✗ At risk    Below 50%
```

The score updates live in the browser after each auto-fix — no page reload required.

### Fix buttons

Each failing check shows one of two things:

- **Green "Fix" button** — click once; the fix applies via AJAX and the check turns green
- **"Manual fix required"** tag — the fix must be done outside the plugin (see [Manual Steps Guide](#manual-steps-guide))

### Hidden login URL info box

After enabling the hidden login URL, a green info box at the bottom of the dashboard shows your new login address:

```
Your hidden login URL: https://yoursite.com/secure-xk3p9mz2/
```

> ⚠️ **Bookmark this immediately.** Direct access to `/wp-login.php` will be blocked for bots — and for you if you forget the slug.

---

## Security Checks Reference

### 🔐 Authentication & Login

#### Remove default "admin" username
**Severity:** Critical | **Auto-fix:** No (manual)

Every brute-force bot on the internet tries `admin` as the first username. A user account with this username is a permanent open invitation. Rename or delete it.

**Detection:** `username_exists('admin')` — fails if any account uses this username.

---

#### Limit login attempts
**Severity:** Critical | **Auto-fix:** Yes

Blocks IP addresses that repeatedly fail login — stops credential-stuffing and dictionary attacks without requiring a third-party plugin.

**What the fix does:**
- Enables a `wp_login_failed` hook that tracks failure counts per IP in WordPress transients
- After **5 failures** within 10 minutes, the IP is blocked at the application layer for **30 minutes**
- Correctly reads `CF-Connecting-IP` for sites behind Cloudflare

---

#### Hide wp-login.php from bots
**Severity:** Critical | **Auto-fix:** Yes

Moves the login page to a secret URL slug. Bots cannot attack a login page they cannot find.

**What the fix does:**
- Generates a random 8-character slug: `secure-[random]`
- Registers a WordPress rewrite rule pointing the slug to the login logic
- Requests to `/wp-login.php` from bots are silently redirected to the homepage
- The slug is displayed on the dashboard — bookmark it before enabling

---

#### Disable XML-RPC
**Severity:** Critical | **Auto-fix:** Yes

XML-RPC allows attackers to make thousands of login attempts in a single HTTP request (amplification attacks) and is frequently used for DDoS. Disable it unless your mobile app or Jetpack specifically requires it.

**What the fix does:**
- Hooks `xmlrpc_enabled` → `__return_false`
- Hooks `xmlrpc_methods` → `__return_empty_array`

---

### ⚙️ wp-config.php Hardening

#### Disable theme/plugin file editor
**Severity:** Critical | **Auto-fix:** Yes

The built-in editor in **Appearance → Theme File Editor** and **Plugins → Plugin File Editor** lets anyone with admin access run arbitrary PHP code. Disabling it removes this escalation path — so a compromised admin password cannot become a full server compromise.

**What the fix does:** Inserts `define('DISALLOW_FILE_EDIT', true);` into `wp-config.php`

---

#### Force HTTPS for wp-admin
**Severity:** Critical | **Auto-fix:** Yes (requires active SSL)

Ensures all admin panel traffic is encrypted. Without this, session cookies can be intercepted on shared or public networks.

**What the fix does:** Inserts `define('FORCE_SSL_ADMIN', true);` into `wp-config.php`

> Requires a valid SSL certificate. The fix button will return an error if the site is not currently served over HTTPS.

---

#### Non-default database table prefix
**Severity:** Critical | **Auto-fix:** No (manual — ideally set at install time)

The default `wp_` prefix makes SQL injection attacks easier — an attacker who finds an injection vulnerability immediately knows your table names. 

**Detection:** Checks `$wpdb->prefix !== 'wp_'`

> This is safest to change during WordPress installation. Changing it on a live site requires a full database backup and careful find-and-replace across all table names and serialized data.

---

#### WP_DEBUG disabled on production
**Severity:** High | **Auto-fix:** Yes

Debug mode leaks file paths, database errors, and stack traces directly to visitors — invaluable information for an attacker mapping your installation.

**What the fix does:** Inserts or updates `define('WP_DEBUG', false);` in `wp-config.php`

---

#### Security keys & salts are set
**Severity:** High | **Auto-fix:** No (manual)

WordPress security keys and salts are used to encrypt session cookies and authentication tokens. Default or missing keys make stolen cookies reusable. The plugin checks that all four primary keys (`AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`) are defined and at least 40 characters long.

**Generate fresh keys:** https://api.wordpress.org/secret-key/1.1/salt/

---

### 📁 File & Directory Permissions

#### wp-config.php permissions (440/400)
**Severity:** Critical | **Auto-fix:** Yes

On shared hosting, other users on the same server can read world-readable files. `wp-config.php` contains your database credentials — it should never be readable by anyone except the server process.

**What the fix does:** `chmod(wp-config.php, 0440)` — owner and group can read, no write, no world access

**Detection:** Checks for permissions `400`, `440`, `600`, or `640`

---

#### PHP execution blocked in /uploads/
**Severity:** Critical | **Auto-fix:** Yes

Attackers upload malicious `.php` files disguised as images through vulnerable plugins, then rename or directly access them. Blocking PHP execution in the uploads directory breaks this entire attack chain.

**What the fix does:** Writes to `wp-content/uploads/.htaccess`:

```apache
# HNS_BLOCK_PHP_UPLOADS
<Files *.php>
deny from all
</Files>
php_flag engine off
```

---

#### Directory listing disabled
**Severity:** High | **Auto-fix:** Yes

When a directory has no `index.php`, Apache shows a file browser by default. This exposes your directory structure, plugin list, uploaded file names, and backup files.

**What the fix does:** Appends `Options -Indexes` to the root `.htaccess`

---

#### Protect .htaccess and sensitive files
**Severity:** High | **Auto-fix:** Yes

Prevents direct HTTP access to configuration and log files that should never be publicly accessible.

**What the fix does:** Appends to root `.htaccess`:

```apache
# HNS_PROTECT_DOTFILES
<FilesMatch "^(\.htaccess|\.htpasswd|wp-config\.php|debug\.log|readme\.html|license\.txt)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

---

### 🔄 Updates & Plugins

#### WordPress core is up to date
**Severity:** Critical | **Auto-fix:** No (action required in Dashboard → Updates)

Outdated WordPress core has publicly disclosed CVEs that automated scanners actively exploit. This check fires whenever a new core version is available.

**Detection:** `get_core_updates()` — fails if any update with response `!= 'latest'` is available

---

#### No plugins with pending updates
**Severity:** Critical | **Auto-fix:** No (action required in Dashboard → Updates)

Vulnerable plugins are the **#1 attack vector** on WordPress. A single unpatched plugin (e.g. an old file manager, form builder, or page builder) can give an attacker shell access regardless of every other security measure in place.

**Detection:** `get_site_transient('update_plugins')->response` — fails if non-empty

---

#### No inactive plugins installed
**Severity:** Critical | **Auto-fix:** No (manual deletion required)

Inactive plugins are still on the filesystem and still exploitable — deactivating is not enough. Delete plugins you are not using.

**Detection:** Compares `get_plugins()` count against `active_plugins` option

---

#### Minor core auto-updates enabled
**Severity:** High | **Auto-fix:** Yes

Minor WordPress updates (e.g. 6.4.1 → 6.4.2) are almost always security patches. Enabling auto-updates ensures they are applied without waiting for manual action.

**What the fix does:** `update_option('auto_update_core_minor', true)`

---

### 🛡️ HTTP Security Headers

All three header checks are controlled by a single option (`hns_security_headers_enabled`). Enabling any one of them enables all five headers simultaneously.

**What the fix does:** Hooks `send_headers` to output:

```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Strict-Transport-Security: max-age=31536000; includeSubDomains  (HTTPS sites only)
```

#### X-Frame-Options
**Severity:** High | **Auto-fix:** Yes

Prevents your site from being embedded in `<iframe>` elements on other domains — the mechanism behind clickjacking attacks.

#### X-Content-Type-Options
**Severity:** High | **Auto-fix:** Yes

Prevents browsers from guessing (sniffing) the MIME type of responses. Without this, an attacker can upload a file that the browser interprets as executable HTML or JavaScript.

#### Referrer-Policy
**Severity:** High | **Auto-fix:** Yes

Controls what URL information is sent to third-party sites when a visitor clicks a link. `strict-origin-when-cross-origin` shares only the domain, not the full path, protecting admin URLs from leaking in referrer headers.

---

### 🔍 Information Leakage

#### Block user enumeration
**Severity:** High | **Auto-fix:** Yes

Attackers enumerate usernames before launching targeted credential attacks. WordPress leaks usernames in two places by default.

**What the fix does:**
- Redirects `/?author=N` requests to the homepage for unauthenticated visitors
- Removes `/wp/v2/users` and `/wp/v2/users/{id}` from the REST API for unauthenticated requests

---

#### WordPress version hidden
**Severity:** High | **Auto-fix:** Yes

The WordPress version appears in the `<meta name="generator">` tag, RSS feed headers, and appended to CSS/JS file URLs as `?ver=X.X.X`. Knowing the exact version helps attackers target known CVEs.

**What the fix does:**
- Removes `wp_generator` action from `wp_head`
- Filters `the_generator` to return empty string
- Strips `?ver=` parameter from all enqueued style and script URLs

---

#### readme.html deleted from root
**Severity:** High | **Auto-fix:** Yes

WordPress ships with a `readme.html` in the web root that publicly displays the exact WordPress version number. This file serves no purpose on a live site.

**What the fix does:** `unlink(ABSPATH . 'readme.html')` — checks for both `readme.html` and `README.html`

---

## Manual Steps Guide

Seven checks cannot be fixed automatically. Here is what to do for each one.

### Remove the "admin" username

1. Go to **Users → Add New** in your admin panel
2. Create a new Administrator account with a unique username (not `admin`)
3. Log out, then log back in as the new account
4. Go to **Users**, find the old `admin` account, click **Delete**
5. When prompted, choose to **reassign all content** to your new account

### Change the database table prefix

**Best done at install time.** During the WordPress installation wizard, change `wp_` to something unique (e.g. `hn7x_`).

For existing live sites:
1. Create a full database backup via cPanel → phpMyAdmin → Export
2. Use a plugin such as **Brozzme DB Prefix & Tools Addons**
3. Or contact [Hostnasi support](https://hostnasi.com/support) — we can do it for you

### Set strong security keys and salts

1. Visit https://api.wordpress.org/secret-key/1.1/salt/ to generate a fresh set
2. Open `wp-config.php` via cPanel File Manager or FTP
3. Find the block starting with `define('AUTH_KEY',` and replace the entire block with the generated keys
4. Save — all current sessions will be invalidated immediately (all users must log in again)

### Update WordPress core

Go to **Dashboard → Updates** and click **Update Now** when a core update is available. Alternatively, enable auto-updates for major versions from the same screen.

### Update all plugins

Go to **Dashboard → Updates**, select all plugins with pending updates, and click **Update Plugins**. Do this before applying other security fixes — a patched plugin is always the priority.

### Delete inactive plugins

1. Go to **Plugins → Installed Plugins**
2. Use the **Inactive** filter link at the top
3. Select all inactive plugins → **Bulk Actions → Delete → Apply**

---

## How Auto-Fixes Work

All fixes are applied via a secure AJAX request from the dashboard:

- Requests are verified with a WordPress **nonce** (`hns_fix`)
- Only **administrator** users can trigger fixes
- The fix callback is checked against a **whitelist** of 15 allowed method names before execution — no arbitrary code execution is possible

### wp-config.php patching

The plugin reads `wp-config.php` into memory, then either:
- **Updates** an existing `define('CONSTANT', ...)` using a regex replace
- **Inserts** a new `define()` before the `/* That's all, stop editing! */` anchor

The file is written back with `LOCK_EX` to prevent race conditions. If the file is not writable (e.g. `chmod 440`), the fix returns a manual instruction instead of silently failing.

### .htaccess patching

Each `.htaccess` fix uses a named marker comment (e.g. `# HNS_BLOCK_PHP_UPLOADS`) to check for prior application. All `.htaccess` changes are **idempotent** — running the same fix twice will not create duplicate rules.

### Login lockout

Uses WordPress transients (no additional database tables):
- `hns_fail_{md5(ip)}` — incremented on each failed login, expires after 10 minutes
- `hns_lock_{md5(ip)}` — set when threshold is reached, expires after 30 minutes

### Hidden login URL

Uses WordPress rewrite rules — no `.htaccess` modification required:

```php
add_rewrite_rule('^secure-[slug]/?$', 'index.php?hns_login=1', 'top');
```

The slug is stored in `wp_options` as `hns_login_slug`. To recover it if locked out:

```sql
SELECT option_value FROM wp_options WHERE option_name = 'hns_login_slug';
```

---

## Compatibility

| Environment | Status |
|---|---|
| WordPress 6.0–6.7 | ✅ Fully supported |
| PHP 8.0, 8.1, 8.2, 8.3 | ✅ Fully supported |
| Apache | ✅ All features |
| LiteSpeed | ✅ All features |
| Nginx | ⚠️ Headers and lockout work; `.htaccess` fixes have no effect — apply equivalent `location` blocks manually |
| Cloudflare proxy | ✅ Lockout reads `CF-Connecting-IP` |
| WordPress Multisite | ❌ Not tested — single-site installs only |
| Wordfence / Sucuri | ✅ Fully compatible — complementary, no conflicts |
| WP Super Cache / W3TC | ✅ Compatible |
| LiteSpeed Cache | ✅ Compatible |

### Nginx equivalents for .htaccess fixes

If your server runs Nginx, add these to your site's `server {}` block:

```nginx
# Block PHP in uploads
location ~* /wp-content/uploads/.*\.php$ {
    deny all;
}

# Disable directory listing
autoindex off;

# Protect sensitive files
location ~* ^/(\.htaccess|wp-config\.php|debug\.log|readme\.html|license\.txt)$ {
    deny all;
}
```

---

## Troubleshooting

### Locked out after enabling the hidden login URL

The login slug is stored in the database. Retrieve it via:

**cPanel → phpMyAdmin:**
```sql
SELECT option_value FROM wp_options WHERE option_name = 'hns_login_slug';
```

**WP-CLI:**
```bash
wp option get hns_login_slug
```

Navigate to `https://yoursite.com/{slug}/` to log in.

To disable the feature entirely via WP-CLI:
```bash
wp option update hns_hide_login_enabled 0
wp rewrite flush
```

---

### wp-config.php fix says "not writable"

After applying the `chmod 440` fix, `wp-config.php` becomes non-writable by the web process. To apply further wp-config patches:

**Via SSH:**
```bash
chmod 640 wp-config.php
# Apply fix via dashboard
chmod 440 wp-config.php
```

**Or add the define() manually** in cPanel File Manager using the Text Editor (which bypasses file permissions at the OS level).

---

### .htaccess fix applied but check still fails

1. Clear your caching plugin's file cache
2. On Nginx servers, `.htaccess` has no effect — use the [Nginx equivalents](#nginx-equivalents-for-htaccess-fixes) above
3. Verify `.htaccess` is not overridden by a parent directory's `AllowOverride None` directive

---

### FORCE_SSL_ADMIN fix returns an error

The plugin checks `is_ssl()` before applying this constant. If your SSL terminates at a load balancer or Cloudflare, WordPress may not detect HTTPS automatically. Add this to `wp-config.php` first:

```php
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
```

Then retry the fix.

---

### Score not updating after a fix

Scores recalculate live via JavaScript after each AJAX fix. If a check remains red:

1. Hard refresh the page (`Ctrl+Shift+R` / `Cmd+Shift+R`)
2. The fix may have been applied but WordPress needs a rewrite flush — go to **Settings → Permalinks** and click **Save Changes**
3. Some checks (e.g. `DISALLOW_FILE_EDIT`) require the constant to be loaded — it may not be detected until the next page load after `wp-config.php` is patched

---

## File Structure

```
hostnasi-security/
├── hostnasi-security.php          # Plugin bootstrap, constants, activation hook
├── readme.txt                     # WordPress.org plugin readme
├── README.md                      # This file
├── includes/
│   ├── class-hns-checks.php       # 22 check definitions + detection callbacks
│   ├── class-hns-actions.php      # Auto-fix implementations
│   └── class-hns-admin.php        # Dashboard UI, AJAX handler, runtime hooks
└── assets/
    ├── admin.css                  # Dashboard styles (Hostnasi brand)
    └── admin.js                   # AJAX fix button handler + live score update
```

### Database options used

| Option key | Purpose |
|---|---|
| `hns_login_lockout_enabled` | Lockout feature toggle |
| `hns_lockout_attempts` | Failure threshold (default: 5) |
| `hns_lockout_duration` | Block duration in minutes (default: 30) |
| `hns_login_slug` | Hidden login URL slug |
| `hns_hide_login_enabled` | Hidden login feature toggle |
| `hns_xmlrpc_disabled` | XML-RPC disable toggle |
| `hns_security_headers_enabled` | Security headers toggle |
| `hns_user_enum_blocked` | User enumeration block toggle |
| `hns_version_hidden` | Version hiding toggle |
| `auto_update_core_minor` | WordPress native auto-update option |

All options are cleaned up on plugin deletion (add `uninstall.php` for production hardening).

---

## Changelog

### 1.0.0 — June 2025

- Initial release
- 22 security checks across 6 categories
- 15 one-click auto-fixes
- Built-in login lockout (no external plugin required)
- Hidden login URL with random slug generation
- wp-config.php patching (DISALLOW_FILE_EDIT, FORCE_SSL_ADMIN, WP_DEBUG)
- .htaccess hardening (PHP block in uploads, Options -Indexes, sensitive file protection)
- HTTP security headers (X-Frame-Options, HSTS, Referrer-Policy, Permissions-Policy)
- User enumeration blocking (author query + REST API)
- WordPress version hiding (meta, feeds, asset URLs)
- readme.html deletion
- Live security score with colour-coded grade
- Admin notice when score drops below 50%
- Cloudflare IP header support

---

## Support

Built and maintained by **Hostnasi Technologies** — your WordPress hosting provider in Tanzania.

| Channel | Details |
|---|---|
| Support portal | https://hostnasi.com/support |
| Email | support@hostnasi.com |
| Client area | https://hostnasi.com/client |
| Website | https://hostnasi.com |

If you find a security issue in this plugin itself, please disclose it responsibly via email rather than opening a public issue.

---

*Hostnasi Technologies · Dar es Salaam, Tanzania · [hostnasi.com](https://hostnasi.com)*
