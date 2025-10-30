<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin_UI_V1 {
    public function init() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes_v1' ] );
        // Remove legacy meta boxes that V1 replaces (server-side so they don't flash)
        add_action( 'add_meta_boxes', [ $this, 'remove_legacy_meta_boxes' ], 100 );
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

    public function remove_legacy_meta_boxes() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'pwpl_table' ) {
            return;
        }
        // Hide legacy boxes that are covered by V1 blocks
        remove_meta_box( 'pwpl_table_layout', 'pwpl_table', 'normal' ); // Layout & Size
        remove_meta_box( 'pwpl_table_badges', 'pwpl_table', 'side' );   // Badges & Promotions (legacy)
        // Remove Dimensions & Variants now that Filters block ships
        remove_meta_box( 'pwpl_table_dimensions', 'pwpl_table', 'normal' );
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

        // Ensure WordPress Components styles are present so TabPanel/Card look correct
        wp_enqueue_style( 'wp-components' );

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
            $layout_cardw_raw  = get_post_meta( $post_id, PWPL_Meta::LAYOUT_CARD_WIDTHS, true );
            $dims_enabled      = get_post_meta( $post_id, PWPL_Meta::DIMENSION_META, true );
            $allowed_platforms = get_post_meta( $post_id, PWPL_Meta::ALLOWED_PLATFORMS, true );
            $allowed_periods   = get_post_meta( $post_id, PWPL_Meta::ALLOWED_PERIODS, true );
            $allowed_locations = get_post_meta( $post_id, PWPL_Meta::ALLOWED_LOCATIONS, true );
            $card_meta_raw     = get_post_meta( $post_id, PWPL_Meta::CARD_CONFIG, true );
            $badges_raw        = get_post_meta( $post_id, PWPL_Meta::TABLE_BADGES, true );
            $cta_raw           = get_post_meta( $post_id, PWPL_Meta::CTA_CONFIG, true );
            $specs_style       = get_post_meta( $post_id, PWPL_Meta::SPECS_STYLE, true );
            $anim_flags        = get_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_FLAGS, true );
            $anim_intensity    = (int) get_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_INTENSITY, true );
            $anim_mobile       = (int) get_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_MOBILE, true );
            $trust_trio        = (int) get_post_meta( $post_id, PWPL_Meta::TRUST_TRIO_ENABLED, true );
            $sticky_cta        = (int) get_post_meta( $post_id, PWPL_Meta::STICKY_CTA_MOBILE, true );
            $trust_items       = get_post_meta( $post_id, PWPL_Meta::TRUST_ITEMS, true );

            $layout_widths = $meta->sanitize_layout_widths( is_array( $layout_widths_raw ) ? $layout_widths_raw : [] );
            $layout_columns= $meta->sanitize_layout_cards( is_array( $layout_columns_raw ) ? $layout_columns_raw : [] );
            $layout_cardw  = $meta->sanitize_layout_card_widths( is_array( $layout_cardw_raw ) ? $layout_cardw_raw : [] );
            $dims_enabled  = is_array( $dims_enabled ) ? array_values( array_intersect( $dims_enabled, [ 'platform', 'period', 'location' ] ) ) : [];
            $allowed_platforms = is_array( $allowed_platforms ) ? array_values( $allowed_platforms ) : [];
            $allowed_periods   = is_array( $allowed_periods ) ? array_values( $allowed_periods ) : [];
            $allowed_locations = is_array( $allowed_locations ) ? array_values( $allowed_locations ) : [];

            // Catalog from settings
            $settings = new PWPL_Settings();
            $catalog_platforms = (array) $settings->get( 'platforms' );
            $catalog_periods   = (array) $settings->get( 'periods' );
            $catalog_locations = (array) $settings->get( 'locations' );
            $card_config   = is_array( $card_meta_raw ) ? $meta->sanitize_card_config( $card_meta_raw ) : [];
            $badges_config = is_array( $badges_raw ) ? $meta->sanitize_badges( $badges_raw ) : [];
            $cta_config    = is_array( $cta_raw ) ? $cta_raw : [];
            $anim_flags    = is_array( $anim_flags ) ? array_values( array_intersect( array_map( 'sanitize_key', $anim_flags ), [ 'row', 'icon', 'divider', 'chip', 'stagger' ] ) ) : [];
            $specs_style   = in_array( $specs_style, [ 'default','flat','segmented','chips' ], true ) ? $specs_style : 'default';
            $anim_intensity= $anim_intensity > 0 ? $anim_intensity : 45;
            $anim_mobile   = $anim_mobile ? 1 : 0;

            wp_localize_script( 'pwpl-admin-v1', 'PWPL_AdminV1', [
                'postId' => (int) $post_id,
                'layout' => [
                    'widths'     => $layout_widths,
                    'columns'    => $layout_columns,
                    'cardWidths' => $layout_cardw,
                ],
                'card' => $card_config,
                'filters' => [
                    'enabled'  => $dims_enabled,
                    'allowed'  => [
                        'platform' => $allowed_platforms,
                        'period'   => $allowed_periods,
                        'location' => $allowed_locations,
                    ],
                    'catalog'  => [
                        'platform' => $catalog_platforms,
                        'period'   => $catalog_periods,
                        'location' => $catalog_locations,
                    ],
                ],
                'ui'   => [
                    'cta'   => $cta_config,
                    'specs' => [
                        'style' => $specs_style,
                        'anim'  => [
                            'flags'     => $anim_flags,
                            'intensity' => $anim_intensity,
                            'mobile'    => $anim_mobile,
                        ],
                    ],
                    'advanced' => [
                        'trust_trio' => $trust_trio ? 1 : 0,
                        'sticky_cta' => $sticky_cta ? 1 : 0,
                        'trust_items'=> is_array( $trust_items ) ? array_values( $trust_items ) : [],
                    ],
                ],
                'badges' => $badges_config,
                'i18n' => [
                    'sidebar' => [
                        'tableLayout' => __( 'Table Layout', 'planify-wp-pricing-lite' ),
                        'planCard'    => __( 'Plan Card', 'planify-wp-pricing-lite' ),
                        'typography'  => __( 'Typography', 'planify-wp-pricing-lite' ),
                        'colors'      => __( 'Colors & Surfaces', 'planify-wp-pricing-lite' ),
                        'cta'         => __( 'CTA', 'planify-wp-pricing-lite' ),
                        'specs'       => __( 'Specs', 'planify-wp-pricing-lite' ),
                        'badges'      => __( 'Badges & Promotions', 'planify-wp-pricing-lite' ),
                        'advanced'    => __( 'Advanced', 'planify-wp-pricing-lite' ),
                        'filters'     => __( 'Filters', 'planify-wp-pricing-lite' ),
                    ],
                    'tabs' => [
                        'widths'     => __( 'Widths & Columns', 'planify-wp-pricing-lite' ),
                        'breakpoints'=> __( 'Breakpoints', 'planify-wp-pricing-lite' ),
                        'layout'     => __( 'Layout', 'planify-wp-pricing-lite' ),
                        'border'     => __( 'Border', 'planify-wp-pricing-lite' ),
                        'topText'    => __( 'Top Text', 'planify-wp-pricing-lite' ),
                        'sizes'      => __( 'Sizes', 'planify-wp-pricing-lite' ),
                        'topBg'      => __( 'Top Background', 'planify-wp-pricing-lite' ),
                        'specsBg'    => __( 'Specs Background', 'planify-wp-pricing-lite' ),
                        'keyline'    => __( 'Keyline', 'planify-wp-pricing-lite' ),
                        'sizeLayout' => __( 'Size & Layout', 'planify-wp-pricing-lite' ),
                        'style'      => __( 'Style', 'planify-wp-pricing-lite' ),
                        'interact'   => __( 'Interactions', 'planify-wp-pricing-lite' ),
                        'period'     => __( 'Period', 'planify-wp-pricing-lite' ),
                        'location'   => __( 'Location', 'planify-wp-pricing-lite' ),
                        'platform'   => __( 'Platform', 'planify-wp-pricing-lite' ),
                        'priority'   => __( 'Priority', 'planify-wp-pricing-lite' ),
                        'advanced'   => __( 'Advanced', 'planify-wp-pricing-lite' ),
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
