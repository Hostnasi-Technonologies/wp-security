=== Hostnasi Security Hardening ===
Contributors: hostnasi
Tags: security, hardening, firewall, login protection, WordPress security
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.2
License: GPLv2 or later

Step-by-step WordPress security hardening by Hostnasi Technologies.

== Description ==

Hostnasi Security Hardening gives you a clear security dashboard showing exactly which protections are active and which need attention — with one-click auto-fixes for everything that can be fixed automatically.

**What it checks & fixes:**

= Authentication =
* Detects default "admin" username
* Built-in login lockout (no extra plugin needed)
* Hidden login URL to stop bot attacks
* XML-RPC disable

= wp-config.php =
* DISALLOW_FILE_EDIT enforcement
* FORCE_SSL_ADMIN
* WP_DEBUG production check
* Security salts verification
* Database prefix check

= File Security =
* wp-config.php permissions (440)
* Directory listing disabled
* Sensitive file protection (.htaccess, debug.log)

= Updates =
* Core update status
* Plugin update alerts
* Inactive plugin detection
* Auto-update enforcement

= HTTP Headers =
* X-Frame-Options
* X-Content-Type-Options
* Referrer-Policy
* Permissions-Policy
* HSTS (when SSL active)

= Information Leakage =
* User enumeration blocked (/?author= and REST API)
* WordPress version hidden
* readme.html deletion

== Installation ==

1. Upload the `hostnasi-security` folder to `/wp-content/plugins/`
2. Activate through the Plugins menu
3. Navigate to **HN Security** in your admin sidebar
4. Work through each category, clicking "Fix" on any failing checks

== Frequently Asked Questions ==

= Will this break my site? =
All auto-fixes are reversible. XML-RPC can be re-enabled via the database if needed. The hidden login URL is shown prominently on the dashboard — bookmark it before enabling.

= Does it conflict with Wordfence or Sucuri? =
No. It complements them. Hostnasi Security focuses on core hardening; Wordfence/Sucuri add WAF rules and malware scanning.

= Can I use this on client sites? =
Yes — it's designed for hosting providers to deploy across client WordPress installations.

== Changelog ==

= 1.0.2 =
* Added visible plugin version in the admin dashboard header.
* Release metadata bump.

= 1.0.1 =
* Removed the "PHP execution blocked in /uploads/" hardening item from checks and one-click fixes.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.2 =
Adds an in-dashboard version label and updates release metadata.

= 1.0.1 =
Removes the uploads PHP execution block hardening item to avoid compatibility issues.

= 1.0.0 =
Initial release.
