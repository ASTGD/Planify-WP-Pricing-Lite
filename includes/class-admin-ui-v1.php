<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin_UI_V1 {
    public function init() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes_v1' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function add_meta_boxes_v1() {
        add_meta_box(
            'pwpl_table_editor_v1',
            __( 'Table Editor — V1 (Preview)', 'planify-wp-pricing-lite' ),
            [ $this, 'render_editor_v1' ],
            'pwpl_table',
            'normal',
            'high'
        );
    }

    public function enqueue_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'pwpl_table' ) {
            return;
        }

        // Styles for the shell layout
        $css_path = PWPL_DIR . 'assets/admin/css/admin-v1.css';
        if ( file_exists( $css_path ) ) {
            wp_enqueue_style( 'pwpl-admin-v1', PWPL_URL . 'assets/admin/css/admin-v1.css', [], filemtime( $css_path ) );
        }

        // React app using WordPress components
        $js_path = PWPL_DIR . 'assets/admin/js/table-editor-v1.js';
        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'pwpl-admin-v1',
                PWPL_URL . 'assets/admin/js/table-editor-v1.js',
                [ 'wp-element', 'wp-components', 'wp-i18n' ],
                filemtime( $js_path ),
                true
            );

            // Hydration data
            $post_id = get_the_ID();
            $meta     = new PWPL_Meta();

            $layout_widths_raw = get_post_meta( $post_id, PWPL_Meta::LAYOUT_WIDTHS, true );
            $layout_columns_raw= get_post_meta( $post_id, PWPL_Meta::LAYOUT_COLUMNS, true );
            $card_meta_raw     = get_post_meta( $post_id, PWPL_Meta::CARD_CONFIG, true );

            $layout_widths = $meta->sanitize_layout_widths( is_array( $layout_widths_raw ) ? $layout_widths_raw : [] );
            $layout_columns= $meta->sanitize_layout_cards( is_array( $layout_columns_raw ) ? $layout_columns_raw : [] );
            $card_config   = is_array( $card_meta_raw ) ? $meta->sanitize_card_config( $card_meta_raw ) : [];

            wp_localize_script( 'pwpl-admin-v1', 'PWPL_AdminV1', [
                'postId' => (int) $post_id,
                'layout' => [
                    'widths'  => $layout_widths,
                    'columns' => $layout_columns,
                ],
                'card' => $card_config,
                'i18n' => [
                    'sidebar' => [
                        'tableLayout' => __( 'Table Layout', 'planify-wp-pricing-lite' ),
                        'planCard'    => __( 'Plan Card', 'planify-wp-pricing-lite' ),
                    ],
                    'tabs' => [
                        'widths'     => __( 'Widths & Columns', 'planify-wp-pricing-lite' ),
                        'breakpoints'=> __( 'Breakpoints', 'planify-wp-pricing-lite' ),
                        'layout'     => __( 'Layout', 'planify-wp-pricing-lite' ),
                        'border'     => __( 'Border', 'planify-wp-pricing-lite' ),
                    ],
                ],
            ] );
        }
    }

    public function render_editor_v1( $post ) {
        echo '<div id="pwpl-admin-v1-root"></div>';
        // Hidden inputs will be rendered by the React app to ensure values submit with the post.
    }
}

