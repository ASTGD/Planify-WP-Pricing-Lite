<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin {
    public function init() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'manage_edit-pwpl_plan_columns', [ $this, 'plan_columns' ] );
        add_action( 'manage_pwpl_plan_posts_custom_column', [ $this, 'render_plan_columns' ], 10, 2 );
        add_filter( 'manage_edit-pwpl_plan_sortable_columns', [ $this, 'sortable_plan_columns' ] );
        add_action( 'pre_get_posts', [ $this, 'order_plan_admin_query' ] );
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
            wp_localize_script( 'pwpl-admin', 'PWPL_Admin', [
                'copySuccess' => __( 'Shortcode copied to clipboard.', 'planify-wp-pricing-lite' ),
                'copyError'   => __( 'Unable to copy. Please copy manually.', 'planify-wp-pricing-lite' ),
            ] );
        }
    }

    public function plan_columns( $columns ) {
        $columns['menu_order'] = __( 'Order', 'planify-wp-pricing-lite' );
        return $columns;
    }

    public function render_plan_columns( $column, $post_id ) {
        if ( 'menu_order' === $column ) {
            echo (int) get_post_field( 'menu_order', $post_id );
        }
    }

    public function sortable_plan_columns( $columns ) {
        $columns['menu_order'] = 'menu_order';
        return $columns;
    }

    public function order_plan_admin_query( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }
        $post_type = $query->get( 'post_type' );
        if ( is_array( $post_type ) ) {
            if ( ! in_array( 'pwpl_plan', $post_type, true ) ) {
                return;
            }
        } elseif ( 'pwpl_plan' !== $post_type ) {
            return;
        }

        $orderby = $query->get( 'orderby' );
        if ( ! $orderby ) {
            $query->set( 'orderby', [ 'menu_order' => 'ASC', 'title' => 'ASC' ] );
        }
    }
}
