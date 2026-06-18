<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HNS_Actions {

    /* ── Login lockout (built-in, no external plugin needed) ── */
    public static function enable_login_lockout(): array {
        update_option( 'hns_login_lockout_enabled', 1 );
        update_option( 'hns_lockout_attempts',  5  );
        update_option( 'hns_lockout_duration',  30 ); // minutes

        // hook fires on every load; registered in HNS_Admin::init
        return [ 'ok' => true, 'msg' => 'Login lockout enabled (5 attempts → 30-minute block).' ];
    }

    /* ── Hide login URL ── */
    public static function enable_hide_login(): array {
        $slug = get_option( 'hns_login_slug' );
        if ( ! $slug ) {
            $slug = 'secure-' . wp_generate_password( 8, false );
            update_option( 'hns_login_slug', $slug );
        }
        update_option( 'hns_hide_login_enabled', 1 );
        flush_rewrite_rules();
        return [ 'ok' => true, 'msg' => "Login page moved to /{$slug}/. Save this URL — bookmarks to wp-login.php will stop working." ];
    }

    /* ── Disable XML-RPC ── */
    public static function disable_xmlrpc(): array {
        update_option( 'hns_xmlrpc_disabled', 1 );
        return [ 'ok' => true, 'msg' => 'XML-RPC disabled. Re-enable if your mobile app or Jetpack requires it.' ];
    }

    /* ── DISALLOW_FILE_EDIT ── */
    public static function disable_file_edit(): array {
        return self::patch_wpconfig(
            'DISALLOW_FILE_EDIT',
            'true',
            'DISALLOW_FILE_EDIT disabled — the theme/plugin editor is now locked.'
        );
    }

    /* ── FORCE_SSL_ADMIN ── */
    public static function enable_force_ssl_admin(): array {
        if ( ! is_ssl() ) {
            return [ 'ok' => false, 'msg' => 'Your site is not currently served over HTTPS. Enable SSL first, then apply this setting.' ];
        }
        return self::patch_wpconfig(
            'FORCE_SSL_ADMIN',
            'true',
            'FORCE_SSL_ADMIN enabled — wp-admin now requires HTTPS.'
        );
    }

    /* ── WP_DEBUG off ── */
    public static function disable_debug(): array {
        return self::patch_wpconfig(
            'WP_DEBUG',
            'false',
            'WP_DEBUG disabled — error messages will no longer be shown to visitors.'
        );
    }

    /* ── auto-update minor core ── */
    public static function enable_auto_updates(): array {
        update_option( 'auto_update_core_minor', true );
        return [ 'ok' => true, 'msg' => 'Minor WordPress core auto-updates enabled.' ];
    }

    /* ── Security headers ── */
    public static function add_security_headers(): array {
        update_option( 'hns_security_headers_enabled', 1 );
        return [ 'ok' => true, 'msg' => 'Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy) will now be sent on every response.' ];
    }

    /* ── wp-config.php permissions ── */
    public static function fix_wpconfig_perms(): array {
        $f = ABSPATH . 'wp-config.php';
        if ( ! file_exists( $f ) ) {
            return [ 'ok' => false, 'msg' => 'wp-config.php not found at expected path.' ];
        }
        if ( chmod( $f, 0440 ) ) {
            return [ 'ok' => true, 'msg' => 'wp-config.php permissions set to 440.' ];
        }
        return [ 'ok' => false, 'msg' => 'Could not change permissions — please run: chmod 440 wp-config.php via SSH.' ];
    }

    /* ── Block PHP in uploads ── */
    public static function block_uploads_php(): array {
        $dir = wp_upload_dir()['basedir'];
        $ht  = $dir . '/.htaccess';
        $rule = "\n# HNS_BLOCK_PHP_UPLOADS\n<Files *.php>\ndeny from all\n</Files>\nphp_flag engine off\n";
        if ( file_exists( $ht ) && str_contains( file_get_contents( $ht ), 'HNS_BLOCK_PHP_UPLOADS' ) ) {
            return [ 'ok' => true, 'msg' => 'Already applied.' ];
        }
        if ( file_put_contents( $ht, $rule, FILE_APPEND | LOCK_EX ) !== false ) {
            return [ 'ok' => true, 'msg' => 'PHP execution blocked in the uploads directory.' ];
        }
        return [ 'ok' => false, 'msg' => 'Could not write to uploads/.htaccess — check directory permissions.' ];
    }

    /* ── Disable directory listing ── */
    public static function disable_directory_listing(): array {
        return self::patch_htaccess(
            'HNS_NO_LISTING',
            "# HNS_NO_LISTING\nOptions -Indexes\n",
            'Directory listing disabled.'
        );
    }

    /* ── Protect sensitive files ── */
    public static function protect_dotfiles(): array {
        $rule = <<<'HT'

# HNS_PROTECT_DOTFILES
<FilesMatch "^(\.htaccess|\.htpasswd|wp-config\.php|debug\.log|readme\.html|license\.txt)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
HT;
        return self::patch_htaccess( 'HNS_PROTECT_DOTFILES', $rule, 'Sensitive files are now protected from direct HTTP access.' );
    }

    /* ── Block user enumeration ── */
    public static function block_user_enumeration(): array {
        update_option( 'hns_user_enum_blocked', 1 );
        return [ 'ok' => true, 'msg' => 'User enumeration via /?author= and REST API blocked.' ];
    }

    /* ── Hide WP version ── */
    public static function hide_wp_version(): array {
        update_option( 'hns_version_hidden', 1 );
        return [ 'ok' => true, 'msg' => 'WordPress version removed from meta tags, feeds, and scripts.' ];
    }

    /* ── Delete readme.html ── */
    public static function delete_readme(): array {
        $deleted = false;
        foreach ( [ ABSPATH . 'readme.html', ABSPATH . 'README.html' ] as $f ) {
            if ( file_exists( $f ) ) {
                unlink( $f );
                $deleted = true;
            }
        }
        return $deleted
            ? [ 'ok' => true,  'msg' => 'readme.html deleted from web root.' ]
            : [ 'ok' => true,  'msg' => 'readme.html was already absent.' ];
    }

    /* ════════════════════════════════════
       HELPERS
       ════════════════════════════════════ */

    private static function patch_wpconfig( string $const, string $value, string $success_msg ): array {
        $f = ABSPATH . 'wp-config.php';
        if ( ! is_writable( $f ) ) {
            return [ 'ok' => false, 'msg' => "wp-config.php is not writable. Add manually: define('{$const}', {$value});" ];
        }
        $contents = file_get_contents( $f );
        $define   = "define( '{$const}', {$value} );";

        // already present?
        if ( str_contains( $contents, "'{$const}'" ) ) {
            // update existing
            $contents = preg_replace(
                "/define\s*\(\s*'{$const}'\s*,\s*[^)]+\)\s*;/",
                $define,
                $contents
            );
        } else {
            // insert before "That's all, stop editing!"
            $anchor = "/* That's all, stop editing!";
            if ( str_contains( $contents, $anchor ) ) {
                $contents = str_replace( $anchor, "{$define}\n\n{$anchor}", $contents );
            } else {
                $contents .= "\n{$define}\n";
            }
        }

        if ( file_put_contents( $f, $contents, LOCK_EX ) !== false ) {
            return [ 'ok' => true, 'msg' => $success_msg ];
        }
        return [ 'ok' => false, 'msg' => "Could not write wp-config.php. Add manually: {$define}" ];
    }

    private static function patch_htaccess( string $marker, string $rule, string $success_msg ): array {
        $ht = ABSPATH . '.htaccess';
        if ( file_exists( $ht ) && str_contains( file_get_contents( $ht ), $marker ) ) {
            return [ 'ok' => true, 'msg' => 'Already applied.' ];
        }
        if ( file_put_contents( $ht, $rule, FILE_APPEND | LOCK_EX ) !== false ) {
            return [ 'ok' => true, 'msg' => $success_msg ];
        }
        return [ 'ok' => false, 'msg' => 'Could not write to .htaccess — please apply manually.' ];
    }
}
