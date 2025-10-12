<?php
/**
 * Plugin Name: Planify WP Pricing Lite
 * Description: Lightweight pricing tables for WordPress. Fresh plugin skeleton.
 * Version: 0.1.0
 * Author: Planify
 * Text Domain: planify-wp-pricing-lite
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'PWPL_VERSION', '0.1.0' );
define( 'PWPL_FILE', __FILE__ );
define( 'PWPL_DIR', plugin_dir_path( __FILE__ ) );
define( 'PWPL_URL', plugin_dir_url( __FILE__ ) );

require_once PWPL_DIR . 'includes/functions-theme.php';

// Simple class autoloader for PWPL_ classes following includes/class-pwpl-*.php
spl_autoload_register( function( $class ){
    if ( strpos( $class, 'PWPL_' ) !== 0 ) return;
    // Keep the `pwpl-` prefix in the filename (e.g., PWPL_Plugin -> class-pwpl-plugin.php)
    $path = str_replace( [ 'PWPL_', '_' ], [ '', '-' ], strtolower( $class ) );
    $file = PWPL_DIR . 'includes/class-' . $path . '.php';
    if ( file_exists( $file ) ) require_once $file;
} );

add_action( 'plugins_loaded', function(){
    ( new PWPL_Plugin() )->init();
} );

register_activation_hook( __FILE__, function(){
    ( new PWPL_CPT() )->init();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function(){
    flush_rewrite_rules();
} );
