<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_CPT {
    public function init() {
        add_action( 'init', [ $this, 'register_tables_cpt' ] );
        add_action( 'init', [ $this, 'register_plans_cpt' ] );
        // Ensure desired submenu layout under Pricing Tables
        add_action( 'admin_menu', [ $this, 'ensure_submenus' ], 20 );
    }

    private function labels( $singular, $plural, $add_new_text = null ) {
        $add_new_text = $add_new_text ?: sprintf( __( 'Add New %s', 'planify-wp-pricing-lite' ), $singular );
        return [
            'name'               => $plural,
            'singular_name'      => $singular,
            'menu_name'          => $plural,
            'name_admin_bar'     => $singular,
            'add_new'            => $add_new_text,
            'add_new_item'       => $add_new_text,
            'new_item'           => sprintf( __( 'New %s', 'planify-wp-pricing-lite' ), $singular ),
            'edit_item'          => sprintf( __( 'Edit %s', 'planify-wp-pricing-lite' ), $singular ),
            'view_item'          => sprintf( __( 'View %s', 'planify-wp-pricing-lite' ), $singular ),
            'all_items'          => sprintf( __( 'All %s', 'planify-wp-pricing-lite' ), $plural ),
            'search_items'       => sprintf( __( 'Search %s', 'planify-wp-pricing-lite' ), $plural ),
            'not_found'          => __( 'No items found.', 'planify-wp-pricing-lite' ),
            'not_found_in_trash' => __( 'No items found in Trash.', 'planify-wp-pricing-lite' ),
        ];
    }

    public function register_tables_cpt() {
        register_post_type( 'pwpl_table', [
            'labels' => $this->labels( __( 'Pricing Table', 'planify-wp-pricing-lite' ), __( 'Pricing Tables', 'planify-wp-pricing-lite' ), __( 'Add New Pricing Table', 'planify-wp-pricing-lite' ) ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_in_menu' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-index-card',
            'supports' => [ 'title' ],
            'capability_type' => 'page',
            'map_meta_cap' => true,
            'has_archive' => false,
        ] );
    }

    public function register_plans_cpt() {
        register_post_type( 'pwpl_plan', [
            'labels' => $this->labels( __( 'Plan', 'planify-wp-pricing-lite' ), __( 'Plans', 'planify-wp-pricing-lite' ), __( 'Add New Plan', 'planify-wp-pricing-lite' ) ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_in_menu' => false,
            'supports' => [ 'title', 'page-attributes' ],
            'hierarchical' => false,
            'has_archive' => false,
        ] );
    }

    public function ensure_submenus() {
        // Keep the plugin's top-level Pricing Tables menu tidy and avoid duplicates.
        $parent = 'pwpl-tables-dashboard';
        global $submenu;
        if ( ! isset( $submenu[ $parent ] ) ) {
            return;
        }

        // Collapse duplicates by slug (WP may add entries automatically; we avoid duplicates).
        $by_slug = [];
        foreach ( (array) $submenu[ $parent ] as $item ) {
            $slug = isset( $item[2] ) ? $item[2] : '';
            if ( ! $slug || isset( $by_slug[ $slug ] ) ) {
                continue;
            }
            if ( $slug === 'pwpl-tables-dashboard' ) {
                $item[0] = __( 'Pricing Tables', 'planify-wp-pricing-lite' );
            }
            $by_slug[ $slug ] = $item;
        }

        $desired = [
            'pwpl-tables-dashboard', // Dashboard view (cards)
            'pwpl-settings',         // Settings
        ];

        $ordered = [];
        foreach ( $desired as $slug ) {
            if ( isset( $by_slug[ $slug ] ) ) {
                $ordered[] = $by_slug[ $slug ];
                unset( $by_slug[ $slug ] );
            }
        }
        // Append anything else that may exist.
        foreach ( $by_slug as $item ) {
            $ordered[] = $item;
        }

        $submenu[ $parent ] = $ordered;
    }
}
