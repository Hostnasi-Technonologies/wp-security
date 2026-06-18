# Hostnasi Security Hardening Wiki

Version: 1.0.2

This page is a quick reference for operators and support teams managing sites with Hostnasi Security Hardening.

## Quick Start

1. Install and activate the plugin.
2. Open HN Security in WordPress admin.
3. Apply one-click fixes for failing checks.
4. Re-check score and keep it above 80%.

## Current Scope (v1.0.2)

- 21 security checks across 6 categories
- 14 one-click automatic fixes
- Runtime protections for lockout, hidden login, XML-RPC disable, headers, and user enumeration controls

## Release Notes

- 1.0.2: Added in-dashboard version label and metadata updates.
- 1.0.1: Removed uploads PHP execution block check/fix due to compatibility concerns.
- 1.0.0: Initial release.

## Operational Notes

- Nginx does not use .htaccess rules; apply equivalent Nginx directives manually.
- If wp-config.php becomes non-writable after permission hardening, temporarily relax permissions to apply further constant updates.
- Store the hidden login slug securely after enabling it.

## References

- Product docs: README.md
- WordPress plugin readme: readme.txt
- Detailed release history: CHANGELOG.md
