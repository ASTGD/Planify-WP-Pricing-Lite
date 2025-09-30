<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_CPT {
    public function init() {
        add_action( 'init', [ $this, 'register_tables_cpt' ] );
        add_action( 'init', [ $this, 'register_plans_cpt' ] );
    }

    public function register_tables_cpt() {
        register_post_type( 'pwpl_table', [
            'label' => __( 'Pricing Tables', 'planify-wp-pricing-lite' ),
            'public' => false,
            'show_ui' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-index-card',
            'supports' => [ 'title' ],
            'capability_type' => 'page',
            'map_meta_cap' => true,
        ] );
    }

    public function register_plans_cpt() {
        register_post_type( 'pwpl_plan', [
            'label' => __( 'Plans', 'planify-wp-pricing-lite' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=pwpl_table',
            'supports' => [ 'title' ],
            'hierarchical' => true,
        ] );
    }
}

