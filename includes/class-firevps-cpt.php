<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FireVPS_CPT {
    public function init() {
        add_action( 'init', [ $this, 'register_tables_cpt' ] );
        add_action( 'init', [ $this, 'register_plans_cpt' ] );
    }

    public function register_tables_cpt() {
        register_post_type( 'firevps_table', [
            'label' => __( 'Pricing Tables', 'firevps' ),
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
        register_post_type( 'firevps_plan', [
            'label' => __( 'Plans', 'firevps' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=firevps_table',
            'supports' => [ 'title' ],
            'hierarchical' => true,
        ] );
    }
}
