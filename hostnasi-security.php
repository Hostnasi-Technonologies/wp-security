<?php
/**
 * Plugin Name: Hostnasi Security Hardening
 * Plugin URI:  https://hostnasi.com/security
 * Description: Step-by-step WordPress security hardening by Hostnasi Technologies. Enforces best practices and guides you through critical security fixes.
 * Version:     1.0.2
 * Author:      Hostnasi Technologies
 * Author URI:  https://hostnasi.com
 * License:     GPL-2.0+
 * Text Domain: hostnasi-security
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'HNS_VERSION',  '1.0.2' );
define( 'HNS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HNS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HNS_OPTION',   'hns_hardening_state' );

/* ── autoload includes ── */
require_once HNS_PLUGIN_DIR . 'includes/class-hns-checks.php';
require_once HNS_PLUGIN_DIR . 'includes/class-hns-actions.php';
require_once HNS_PLUGIN_DIR . 'includes/class-hns-admin.php';

/* ── boot ── */
add_action( 'plugins_loaded', [ 'HNS_Admin', 'init' ] );

register_activation_hook( __FILE__, function () {
    // seed option so checks run immediately on first visit
    if ( ! get_option( HNS_OPTION ) ) {
        update_option( HNS_OPTION, [] );
    }
} );
