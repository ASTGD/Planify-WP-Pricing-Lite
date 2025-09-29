<?php
/**
 * Plugin Name: FireVPS Pricing Tables
 * Description: Minimal safe bootstrap for FireVPS Pricing Tables (dev skeleton). Registers CPTs and a basic shortcode.
 * Version: 0.1.1
 * Author: FireVPS
 * Text Domain: firevps
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'FIREVPS_VER', '0.1.1' );
define( 'FIREVPS_FILE', __FILE__ );
define( 'FIREVPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'FIREVPS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function( $class ){
    if ( strpos( $class, 'FireVPS_' ) !== 0 ) return;
    $path = str_replace( [ 'FireVPS_', '_' ], [ '', '-' ], strtolower( $class ) );
    $file = FIREVPS_DIR . 'includes/class-' . $path . '.php';
    if ( file_exists( $file ) ) require_once $file;
});

add_action( 'plugins_loaded', function(){
    ( new FireVPS_Plugin() )->init();
});

register_activation_hook( __FILE__, function(){
    // Ensure CPTs are registered before flushing rewrite rules
    ( new FireVPS_CPT() )->init();
    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function(){
    flush_rewrite_rules();
});
