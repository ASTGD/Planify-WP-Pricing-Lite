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
            'show_in_menu' => 'edit.php?post_type=pwpl_table',
            'supports' => [ 'title' ],
            'hierarchical' => false,
            'has_archive' => false,
        ] );
    }

    public function ensure_submenus() {
        // Reorder and de-duplicate the auto-generated submenus under Pricing Tables.
        $parent = 'edit.php?post_type=pwpl_table';
        global $submenu;
        if ( ! isset( $submenu[ $parent ] ) ) {
            return;
        }

        // Ensure the Add New Plan entry exists before we re-order.
        $slugs = wp_list_pluck( (array) $submenu[ $parent ], 2 );
        if ( ! in_array( 'post-new.php?post_type=pwpl_plan', $slugs, true ) ) {
            add_submenu_page(
                $parent,
                __( 'Add New Plan', 'planify-wp-pricing-lite' ),
                __( 'Add New Plan', 'planify-wp-pricing-lite' ),
                'edit_posts',
                'post-new.php?post_type=pwpl_plan',
                ''
            );
        }

        // Collapse duplicates by slug (WP may add entries automatically; we avoid duplicates).
        $seen = [];
        $filtered = [];
        foreach ( $submenu[ $parent ] as $item ) {
            $slug = isset( $item[2] ) ? $item[2] : '';
            if ( $slug && ! isset( $seen[ $slug ] ) ) {
                $seen[ $slug ] = true;
                $filtered[] = $item;
            }
        }

        // Map by slug to rebuild order.
        $by_slug = [];
        foreach ( $filtered as $item ) {
            $by_slug[ $item[2] ] = $item;
        }

        $desired = [
            'edit.php?post_type=pwpl_table',      // All Pricing Tables
            'post-new.php?post_type=pwpl_table',  // Add New Pricing Table
            'edit.php?post_type=pwpl_plan',       // All Plans
            'post-new.php?post_type=pwpl_plan',   // Add New Plan
            'pwpl-settings',                      // Settings
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
