<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HNS_Checks {

    /**
     * Returns all hardening steps organised by category.
     * Each step: id, label, note, severity, auto (bool), check_callback (optional).
     */
    public static function get_steps(): array {
        return [

            /* ── AUTHENTICATION ── */
            'auth' => [
                'title'    => 'Authentication & login',
                'severity' => 'critical',
                'steps'    => [
                    [
                        'id'       => 'no_admin_user',
                        'label'    => 'Remove default "admin" username',
                        'note'     => 'A user named "admin" is targeted by every brute-force bot. Rename or delete it.',
                        'auto'     => false,
                        'check'    => [ __CLASS__, 'check_no_admin_user' ],
                        'fix_label'=> null,
                    ],
                    [
                        'id'       => 'login_attempts',
                        'label'    => 'Limit login attempts',
                        'note'     => 'Automatically blocks IPs that repeatedly fail login — stops credential-stuffing attacks.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_login_attempts_active' ],
                        'fix_label'=> 'Enable login lockout',
                        'fix'      => 'HNS_Actions::enable_login_lockout',
                    ],
                    [
                        'id'       => 'hide_login',
                        'label'    => 'Hide wp-login.php from bots',
                        'note'     => 'Adds a secret login slug so bots cannot find the login page at all.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_hide_login' ],
                        'fix_label'=> 'Enable hidden login',
                        'fix'      => 'HNS_Actions::enable_hide_login',
                    ],
                    [
                        'id'       => 'disable_xmlrpc',
                        'label'    => 'Disable XML-RPC',
                        'note'     => 'XML-RPC is exploited for brute-force amplification and DDoS. Disable unless a mobile app needs it.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_xmlrpc_disabled' ],
                        'fix_label'=> 'Disable XML-RPC',
                        'fix'      => 'HNS_Actions::disable_xmlrpc',
                    ],
                ],
            ],

            /* ── WP-CONFIG ── */
            'wpconfig' => [
                'title'    => 'wp-config.php hardening',
                'severity' => 'critical',
                'steps'    => [
                    [
                        'id'       => 'disallow_file_edit',
                        'label'    => 'Disable theme/plugin file editor',
                        'note'     => 'Prevents an attacker who gets admin access from executing code via the built-in editor.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_disallow_file_edit' ],
                        'fix_label'=> 'Disable file editor',
                        'fix'      => 'HNS_Actions::disable_file_edit',
                    ],
                    [
                        'id'       => 'force_ssl_admin',
                        'label'    => 'Force HTTPS for wp-admin',
                        'note'     => 'Ensures all admin traffic is encrypted — prevents session hijacking on shared networks.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_force_ssl_admin' ],
                        'fix_label'=> 'Enable FORCE_SSL_ADMIN',
                        'fix'      => 'HNS_Actions::enable_force_ssl_admin',
                    ],
                    [
                        'id'       => 'db_prefix',
                        'label'    => 'Non-default database table prefix',
                        'note'     => 'Default "wp_" prefix makes SQL injection easier. Change it at install time.',
                        'auto'     => false,
                        'check'    => [ __CLASS__, 'check_db_prefix' ],
                        'fix_label'=> null,
                    ],
                    [
                        'id'       => 'debug_off',
                        'label'    => 'WP_DEBUG disabled on production',
                        'note'     => 'Debug mode leaks file paths and database errors to visitors.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_debug_off' ],
                        'fix_label'=> 'Disable WP_DEBUG',
                        'fix'      => 'HNS_Actions::disable_debug',
                    ],
                    [
                        'id'       => 'strong_salts',
                        'label'    => 'Security keys & salts are set',
                        'note'     => 'Unique salts make stolen cookies useless. Generate fresh keys at wordpress.org/secret-key.',
                        'auto'     => false,
                        'check'    => [ __CLASS__, 'check_salts_set' ],
                        'fix_label'=> null,
                    ],
                ],
            ],

            /* ── FILE PERMISSIONS ── */
            'files' => [
                'title'    => 'File & directory permissions',
                'severity' => 'critical',
                'steps'    => [
                    [
                        'id'       => 'wpconfig_perms',
                        'label'    => 'wp-config.php is not world-readable (440/400)',
                        'note'     => 'chmod 440 wp-config.php prevents other server users from reading DB credentials.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_wpconfig_perms' ],
                        'fix_label'=> 'Fix wp-config.php permissions',
                        'fix'      => 'HNS_Actions::fix_wpconfig_perms',
                    ],
                    [
                        'id'       => 'directory_listing',
                        'label'    => 'Directory listing disabled',
                        'note'     => 'Prevents attackers from browsing folder contents when index.php is absent.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_directory_listing' ],
                        'fix_label'=> 'Disable directory listing',
                        'fix'      => 'HNS_Actions::disable_directory_listing',
                    ],
                    [
                        'id'       => 'protect_dotfiles',
                        'label'    => 'Protect .htaccess and sensitive files',
                        'note'     => 'Blocks direct HTTP access to .htaccess, wp-config.php, and debug.log.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_dotfiles_protected' ],
                        'fix_label'=> 'Protect sensitive files',
                        'fix'      => 'HNS_Actions::protect_dotfiles',
                    ],
                ],
            ],

            /* ── UPDATES ── */
            'updates' => [
                'title'    => 'Updates & plugins',
                'severity' => 'critical',
                'steps'    => [
                    [
                        'id'       => 'core_updated',
                        'label'    => 'WordPress core is up to date',
                        'note'     => 'Outdated core has known CVEs actively exploited in the wild.',
                        'auto'     => false,
                        'check'    => [ __CLASS__, 'check_core_updated' ],
                        'fix_label'=> null,
                    ],
                    [
                        'id'       => 'plugin_updates',
                        'label'    => 'No plugins with pending updates',
                        'note'     => 'Vulnerable plugins are the #1 attack vector on WordPress. Keep them patched.',
                        'auto'     => false,
                        'check'    => [ __CLASS__, 'check_plugin_updates' ],
                        'fix_label'=> null,
                    ],
                    [
                        'id'       => 'inactive_plugins',
                        'label'    => 'No inactive plugins installed',
                        'note'     => 'Inactive plugins are still exploitable — delete, not just deactivate.',
                        'auto'     => false,
                        'check'    => [ __CLASS__, 'check_inactive_plugins' ],
                        'fix_label'=> null,
                    ],
                    [
                        'id'       => 'auto_updates',
                        'label'    => 'Automatic minor core updates enabled',
                        'note'     => 'Ensures security patches are applied without manual intervention.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_auto_updates' ],
                        'fix_label'=> 'Enable auto-updates',
                        'fix'      => 'HNS_Actions::enable_auto_updates',
                    ],
                ],
            ],

            /* ── HTTP HEADERS ── */
            'headers' => [
                'title'    => 'HTTP security headers',
                'severity' => 'high',
                'steps'    => [
                    [
                        'id'       => 'x_frame_options',
                        'label'    => 'X-Frame-Options header set',
                        'note'     => 'Prevents your site from being embedded in iframes for clickjacking attacks.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_header_x_frame' ],
                        'fix_label'=> 'Add security headers',
                        'fix'      => 'HNS_Actions::add_security_headers',
                    ],
                    [
                        'id'       => 'x_content_type',
                        'label'    => 'X-Content-Type-Options header set',
                        'note'     => 'Prevents browsers from MIME-sniffing responses — stops content injection.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_header_xcto' ],
                        'fix_label'=> 'Add security headers',
                        'fix'      => 'HNS_Actions::add_security_headers',
                    ],
                    [
                        'id'       => 'referrer_policy',
                        'label'    => 'Referrer-Policy header set',
                        'note'     => 'Controls what referrer information is shared with other sites.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_header_referrer' ],
                        'fix_label'=> 'Add security headers',
                        'fix'      => 'HNS_Actions::add_security_headers',
                    ],
                ],
            ],

            /* ── USER ENUMERATION ── */
            'enum' => [
                'title'    => 'Information leakage',
                'severity' => 'high',
                'steps'    => [
                    [
                        'id'       => 'disable_user_enum',
                        'label'    => 'Block user enumeration via REST API',
                        'note'     => 'Prevents attackers from harvesting usernames via /?author=1 and /wp-json/wp/v2/users.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_user_enum_blocked' ],
                        'fix_label'=> 'Block user enumeration',
                        'fix'      => 'HNS_Actions::block_user_enumeration',
                    ],
                    [
                        'id'       => 'hide_wp_version',
                        'label'    => 'WordPress version hidden',
                        'note'     => 'Version disclosure helps attackers target known CVEs for your exact WP version.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_version_hidden' ],
                        'fix_label'=> 'Hide WP version',
                        'fix'      => 'HNS_Actions::hide_wp_version',
                    ],
                    [
                        'id'       => 'readme_deleted',
                        'label'    => 'readme.html deleted from root',
                        'note'     => 'readme.html exposes your WP version number publicly.',
                        'auto'     => true,
                        'check'    => [ __CLASS__, 'check_readme_deleted' ],
                        'fix_label'=> 'Delete readme.html',
                        'fix'      => 'HNS_Actions::delete_readme',
                    ],
                ],
            ],
        ];
    }

    /* ════════════════════════════════════════
       CHECK CALLBACKS  — return true = pass
       ════════════════════════════════════════ */

    public static function check_no_admin_user(): bool {
        return ! username_exists( 'admin' );
    }

    public static function check_login_attempts_active(): bool {
        return (bool) get_option( 'hns_login_lockout_enabled' );
    }

    public static function check_hide_login(): bool {
        return (bool) get_option( 'hns_hide_login_enabled' );
    }

    public static function check_xmlrpc_disabled(): bool {
        return (bool) get_option( 'hns_xmlrpc_disabled' );
    }

    public static function check_disallow_file_edit(): bool {
        return defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
    }

    public static function check_force_ssl_admin(): bool {
        return defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN;
    }

    public static function check_db_prefix(): bool {
        global $wpdb;
        return $wpdb->prefix !== 'wp_';
    }

    public static function check_debug_off(): bool {
        return ! ( defined( 'WP_DEBUG' ) && WP_DEBUG );
    }

    public static function check_salts_set(): bool {
        $keys = [ 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ];
        foreach ( $keys as $k ) {
            if ( ! defined( $k ) || strlen( constant( $k ) ) < 40 ) return false;
        }
        return true;
    }

    public static function check_wpconfig_perms(): bool {
        $f = ABSPATH . 'wp-config.php';
        if ( ! file_exists( $f ) ) return false;
        $perms = substr( sprintf( '%o', fileperms( $f ) ), -3 );
        return in_array( $perms, [ '400', '440', '600', '640' ], true );
    }

    public static function check_uploads_php_blocked(): bool {
        $ht = wp_upload_dir()['basedir'] . '/.htaccess';
        if ( ! file_exists( $ht ) ) return false;
        return str_contains( file_get_contents( $ht ), 'php_flag engine off' )
            || str_contains( file_get_contents( $ht ), 'deny from all' );
    }

    public static function check_directory_listing(): bool {
        $ht = ABSPATH . '.htaccess';
        if ( ! file_exists( $ht ) ) return false;
        return str_contains( file_get_contents( $ht ), 'Options -Indexes' );
    }

    public static function check_dotfiles_protected(): bool {
        $ht = ABSPATH . '.htaccess';
        if ( ! file_exists( $ht ) ) return false;
        return str_contains( file_get_contents( $ht ), 'HNS_PROTECT_DOTFILES' );
    }

    public static function check_core_updated(): bool {
        if ( ! function_exists( 'get_core_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        $updates = get_core_updates();
        return empty( $updates ) || $updates[0]->response === 'latest';
    }

    public static function check_plugin_updates(): bool {
        $updates = get_site_transient( 'update_plugins' );
        return empty( $updates->response );
    }

    public static function check_inactive_plugins(): bool {
        $all    = get_plugins();
        $active = get_option( 'active_plugins', [] );
        return count( $all ) === count( $active );
    }

    public static function check_auto_updates(): bool {
        return get_option( 'auto_update_core_minor' ) !== false
            || ( defined( 'WP_AUTO_UPDATE_CORE' ) && WP_AUTO_UPDATE_CORE );
    }

    public static function check_header_x_frame(): bool {
        return (bool) get_option( 'hns_security_headers_enabled' );
    }

    public static function check_header_xcto(): bool {
        return (bool) get_option( 'hns_security_headers_enabled' );
    }

    public static function check_header_referrer(): bool {
        return (bool) get_option( 'hns_security_headers_enabled' );
    }

    public static function check_user_enum_blocked(): bool {
        return (bool) get_option( 'hns_user_enum_blocked' );
    }

    public static function check_version_hidden(): bool {
        return (bool) get_option( 'hns_version_hidden' );
    }

    public static function check_readme_deleted(): bool {
        return ! file_exists( ABSPATH . 'readme.html' )
            && ! file_exists( ABSPATH . 'README.html' );
    }

    /* ── aggregate score ── */
    public static function get_score(): array {
        $total  = 0;
        $passed = 0;
        foreach ( self::get_steps() as $cat ) {
            foreach ( $cat['steps'] as $step ) {
                $total++;
                if ( ! empty( $step['check'] ) && call_user_func( $step['check'] ) ) {
                    $passed++;
                }
            }
        }
        return [ 'passed' => $passed, 'total' => $total ];
    }
}
