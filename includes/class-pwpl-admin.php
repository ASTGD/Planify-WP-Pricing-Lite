<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin {
    public function init() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $post_type = $screen && isset( $screen->post_type ) ? $screen->post_type : '';
        $screen_id = $screen && isset( $screen->id ) ? $screen->id : '';
        $is_plugin_screen = in_array( $post_type, [ 'pwpl_table', 'pwpl_plan' ], true ) || ( $screen_id && false !== strpos( $screen_id, 'pwpl' ) );
        if ( ! $is_plugin_screen ) {
            return; // Only load on our plugin screens
        }

        $css = PWPL_DIR . 'assets/admin/css/admin.css';
        $js  = PWPL_DIR . 'assets/admin/js/admin.js';

        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'pwpl-admin', PWPL_URL . 'assets/admin/css/admin.css', [], filemtime( $css ) );
        }
        if ( file_exists( $js ) ) {
            wp_enqueue_script( 'pwpl-admin', PWPL_URL . 'assets/admin/js/admin.js', [ 'jquery', 'wp-util' ], filemtime( $js ), true );
        }
    }
}
