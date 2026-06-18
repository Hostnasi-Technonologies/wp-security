<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HNS_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'wp_ajax_hns_run_fix',   [ __CLASS__, 'ajax_run_fix' ] );
        add_action( 'admin_notices',         [ __CLASS__, 'show_notice' ] );

        /* ── Runtime hooks (active regardless of admin page) ── */

        // Login lockout
        if ( get_option( 'hns_login_lockout_enabled' ) ) {
            add_action( 'wp_login_failed',   [ __CLASS__, 'record_failed_login' ] );
            add_filter( 'authenticate',      [ __CLASS__, 'check_lockout' ], 30, 3 );
        }

        // XML-RPC disable
        if ( get_option( 'hns_xmlrpc_disabled' ) ) {
            add_filter( 'xmlrpc_enabled', '__return_false' );
            add_filter( 'xmlrpc_methods', '__return_empty_array' );
        }

        // Security headers
        if ( get_option( 'hns_security_headers_enabled' ) ) {
            add_action( 'send_headers', [ __CLASS__, 'send_security_headers' ] );
        }

        // Hide WP version
        if ( get_option( 'hns_version_hidden' ) ) {
            remove_action( 'wp_head', 'wp_generator' );
            add_filter( 'the_generator',       '__return_empty_string' );
            add_filter( 'style_loader_src',    [ __CLASS__, 'remove_version_param' ], 9999 );
            add_filter( 'script_loader_src',   [ __CLASS__, 'remove_version_param' ], 9999 );
        }

        // Block user enumeration
        if ( get_option( 'hns_user_enum_blocked' ) ) {
            add_action( 'template_redirect',                    [ __CLASS__, 'block_author_enum' ] );
            add_filter( 'rest_endpoints',                       [ __CLASS__, 'block_rest_users' ] );
        }

        // Hide login
        if ( get_option( 'hns_hide_login_enabled' ) ) {
            add_action( 'init',           [ __CLASS__, 'register_login_rewrite' ] );
            add_filter( 'login_url',      [ __CLASS__, 'custom_login_url' ], 10, 3 );
            add_action( 'template_redirect', [ __CLASS__, 'maybe_block_wp_login' ] );
        }
    }

    /* ════════════════════════════════════
       MENU & PAGE
       ════════════════════════════════════ */

    public static function add_menu(): void {
        add_menu_page(
            'Hostnasi Security',
            'HN Security',
            'manage_options',
            'hostnasi-security',
            [ __CLASS__, 'render_page' ],
            'dashicons-shield',
            80
        );
    }

    public static function enqueue( string $hook ): void {
        if ( $hook !== 'toplevel_page_hostnasi-security' ) return;
        wp_enqueue_style(  'hns-admin', HNS_PLUGIN_URL . 'assets/admin.css', [], HNS_VERSION );
        wp_enqueue_script( 'hns-admin', HNS_PLUGIN_URL . 'assets/admin.js',  [ 'jquery' ], HNS_VERSION, true );
        wp_localize_script( 'hns-admin', 'HNS', [
            'ajax' => admin_url( 'admin-ajax.php' ),
            'nonce'=> wp_create_nonce( 'hns_fix' ),
        ] );
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $score = HNS_Checks::get_score();
        $pct   = $score['total'] ? round( $score['passed'] / $score['total'] * 100 ) : 0;
        $cats  = HNS_Checks::get_steps();

        $grade_color = $pct >= 80 ? '#1D9E75' : ( $pct >= 50 ? '#BA7517' : '#E24B4A' );
        $grade_label = $pct >= 80 ? 'Good' : ( $pct >= 50 ? 'Needs work' : 'At risk' );

        echo '<div class="hns-wrap">';

        /* header */
        echo '<div class="hns-header">
            <div class="hns-logo">
                <span class="hns-logo-icon dashicons dashicons-shield"></span>
                <div>
                    <div class="hns-logo-name">Hostnasi Security Hardening <small style="opacity:.75;font-weight:600;">v' . esc_html( HNS_VERSION ) . '</small></div>
                    <div class="hns-logo-sub">Step-by-step WordPress protection by <a href="https://hostnasi.com" target="_blank">Hostnasi Technologies</a></div>
                </div>
            </div>
            <div class="hns-score-card">
                <div class="hns-score-ring" style="--pct:' . $pct . ';--clr:' . $grade_color . '">
                    <span class="hns-score-num">' . $pct . '<small>%</small></span>
                </div>
                <div>
                    <div class="hns-score-label" style="color:' . $grade_color . '">' . $grade_label . '</div>
                    <div class="hns-score-detail">' . $score['passed'] . ' / ' . $score['total'] . ' checks passing</div>
                </div>
            </div>
        </div>';

        /* categories */
        foreach ( $cats as $cat_id => $cat ) {
            $cat_pass  = 0;
            $cat_total = count( $cat['steps'] );
            foreach ( $cat['steps'] as $step ) {
                if ( ! empty( $step['check'] ) && call_user_func( $step['check'] ) ) $cat_pass++;
            }
            $sev_class = 'sev-' . esc_attr( $cat['severity'] );

            echo '<div class="hns-category" id="cat-' . esc_attr( $cat_id ) . '">';
            echo '<div class="hns-cat-header">'
               . '<span class="hns-cat-title">' . esc_html( $cat['title'] ) . '</span>'
               . '<span class="hns-badge ' . $sev_class . '">' . esc_html( $cat['severity'] ) . '</span>'
               . '<span class="hns-cat-count">' . $cat_pass . '/' . $cat_total . '</span>'
               . '</div>';

            echo '<div class="hns-steps">';
            foreach ( $cat['steps'] as $step ) {
                $pass = ! empty( $step['check'] ) ? call_user_func( $step['check'] ) : false;
                $icon = $pass ? '✓' : '✗';
                $row_class = $pass ? 'hns-step pass' : 'hns-step fail';

                echo '<div class="' . $row_class . '" data-step="' . esc_attr( $step['id'] ) . '">';
                echo '<span class="hns-step-icon">' . $icon . '</span>';
                echo '<div class="hns-step-body">';
                echo '<div class="hns-step-label">' . esc_html( $step['label'] ) . '</div>';
                echo '<div class="hns-step-note">' . esc_html( $step['note'] ) . '</div>';
                echo '<div class="hns-step-msg" id="msg-' . esc_attr( $step['id'] ) . '"></div>';
                echo '</div>';

                if ( ! $pass && ! empty( $step['fix'] ) && ! empty( $step['fix_label'] ) ) {
                    echo '<button class="hns-fix-btn button button-primary" '
                       . 'data-fix="' . esc_attr( $step['fix'] ) . '" '
                       . 'data-step="' . esc_attr( $step['id'] ) . '">'
                       . esc_html( $step['fix_label'] ) . '</button>';
                } elseif ( ! $pass ) {
                    echo '<span class="hns-manual-tag">Manual fix required</span>';
                }

                echo '</div>'; // .hns-step
            }
            echo '</div>'; // .hns-steps
            echo '</div>'; // .hns-category
        }

        /* login slug info box */
        $slug = get_option( 'hns_login_slug' );
        if ( $slug ) {
            echo '<div class="hns-info-box">'
               . '<strong>Your hidden login URL:</strong> '
               . '<code>' . esc_url( home_url( '/' . $slug . '/' ) ) . '</code> '
               . '— bookmark this and share it only with authorised users.'
               . '</div>';
        }

        echo '</div>'; // .hns-wrap
    }

    /* ════════════════════════════════════
       AJAX
       ════════════════════════════════════ */

    public static function ajax_run_fix(): void {
        check_ajax_referer( 'hns_fix', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden', 403 );

        $fix = sanitize_text_field( wp_unslash( $_POST['fix'] ?? '' ) );

        // whitelist of allowed callbacks
        $allowed = [
            'HNS_Actions::enable_login_lockout',
            'HNS_Actions::enable_hide_login',
            'HNS_Actions::disable_xmlrpc',
            'HNS_Actions::disable_file_edit',
            'HNS_Actions::enable_force_ssl_admin',
            'HNS_Actions::disable_debug',
            'HNS_Actions::enable_auto_updates',
            'HNS_Actions::add_security_headers',
            'HNS_Actions::fix_wpconfig_perms',
            'HNS_Actions::disable_directory_listing',
            'HNS_Actions::protect_dotfiles',
            'HNS_Actions::block_user_enumeration',
            'HNS_Actions::hide_wp_version',
            'HNS_Actions::delete_readme',
        ];

        if ( ! in_array( $fix, $allowed, true ) ) {
            wp_send_json_error( [ 'msg' => 'Unknown action.' ] );
        }

        [ $class, $method ] = explode( '::', $fix );
        $result = call_user_func( [ $class, $method ] );

        if ( $result['ok'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /* ════════════════════════════════════
       RUNTIME HOOKS
       ════════════════════════════════════ */

    public static function record_failed_login( string $username ): void {
        $ip      = self::get_ip();
        $key     = 'hns_fail_' . md5( $ip );
        $fails   = (int) get_transient( $key );
        set_transient( $key, $fails + 1, 10 * MINUTE_IN_SECONDS );
    }

    public static function check_lockout( $user, string $username, string $password ) {
        $ip    = self::get_ip();
        $key   = 'hns_fail_' . md5( $ip );
        $limit = (int) get_option( 'hns_lockout_attempts', 5 );
        $dur   = (int) get_option( 'hns_lockout_duration', 30 );

        if ( (int) get_transient( $key ) >= $limit ) {
            $lock_key = 'hns_lock_' . md5( $ip );
            set_transient( $lock_key, 1, $dur * MINUTE_IN_SECONDS );
            return new WP_Error(
                'hns_locked',
                sprintf( 'Too many failed attempts. Your IP is blocked for %d minutes.', $dur )
            );
        }

        $lock_key = 'hns_lock_' . md5( $ip );
        if ( get_transient( $lock_key ) ) {
            return new WP_Error( 'hns_locked', 'Your IP is temporarily blocked due to too many failed login attempts.' );
        }

        return $user;
    }

    public static function send_security_headers(): void {
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
        if ( is_ssl() ) {
            header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
        }
    }

    public static function remove_version_param( string $src ): string {
        return $src ? esc_url( remove_query_arg( 'ver', $src ) ) : $src;
    }

    public static function block_author_enum(): void {
        if ( ! is_admin() && isset( $_GET['author'] ) ) {
            wp_redirect( home_url( '/' ), 301 );
            exit;
        }
    }

    public static function block_rest_users( array $endpoints ): array {
        if ( ! is_user_logged_in() ) {
            unset( $endpoints['/wp/v2/users'] );
            unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
        }
        return $endpoints;
    }

    public static function register_login_rewrite(): void {
        $slug = get_option( 'hns_login_slug' );
        if ( $slug ) {
            add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?hns_login=1', 'top' );
            add_filter( 'query_vars', function( $v ) { $v[] = 'hns_login'; return $v; } );
        }
    }

    public static function custom_login_url( string $url, string $redirect, bool $force_reauth ): string {
        $slug = get_option( 'hns_login_slug' );
        return $slug ? home_url( '/' . $slug . '/' ) : $url;
    }

    public static function maybe_block_wp_login(): void {
        if ( ! get_option( 'hns_hide_login_enabled' ) ) return;
        global $wp_query;
        if ( get_query_var( 'hns_login' ) ) {
            // serve wp-login.php
            require_once ABSPATH . 'wp-login.php';
            exit;
        }
        // block direct access to wp-login.php
        if ( $GLOBALS['pagenow'] === 'wp-login.php'
             && ! isset( $_GET['action'] )
             && ! isset( $_POST['log'] ) ) {
            wp_redirect( home_url( '/' ), 302 );
            exit;
        }
    }

    /* ── Admin notice if score is low ── */
    public static function show_notice(): void {
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'toplevel_page_hostnasi-security' ) return;
        $score = HNS_Checks::get_score();
        $pct   = $score['total'] ? round( $score['passed'] / $score['total'] * 100 ) : 0;
        if ( $pct < 50 ) {
            echo '<div class="notice notice-error"><p>'
               . '<strong>Hostnasi Security:</strong> Your site security score is <strong>' . $pct . '%</strong>. '
               . '<a href="' . admin_url( 'admin.php?page=hostnasi-security' ) . '">Fix issues now &rarr;</a>'
               . '</p></div>';
        }
    }

    private static function get_ip(): string {
        foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                return sanitize_text_field( explode( ',', $_SERVER[ $key ] )[0] );
            }
        }
        return '0.0.0.0';
    }
}
