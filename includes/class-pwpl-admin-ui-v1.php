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

        // Ensure WordPress Components styles are present so TabPanel/Card look correct
        wp_enqueue_style( 'wp-components' );

        // Styles for the shell layout (load after wp-components so we can override specifics)
        $css_path = PWPL_DIR . 'assets/admin/css/admin-v1.css';
        if ( file_exists( $css_path ) ) {
            wp_enqueue_style( 'pwpl-admin-v1', PWPL_URL . 'assets/admin/css/admin-v1.css', [ 'wp-components' ], filemtime( $css_path ) );
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
            $layout_cardw_raw  = get_post_meta( $post_id, PWPL_Meta::LAYOUT_CARD_WIDTHS, true );
            $layout_gapx_val   = (int) get_post_meta( $post_id, PWPL_Meta::LAYOUT_GAP_X, true );
            $layout_height_val = (int) get_post_meta( $post_id, PWPL_Meta::TABLE_HEIGHT, true );
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
                    'gap_x'      => $layout_gapx_val,
                    'height'     => $layout_height_val,
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

        // Onboarding (generic coachmarks) — load after core editor assets.
        $onboarding = new PWPL_Onboarding();
        $tour_status = $onboarding->get_tour_status( PWPL_Onboarding::TOUR_TABLE_EDITOR );
        $wizard_landing_status = $onboarding->get_tour_status( PWPL_Onboarding::TOUR_TABLE_WIZARD_LANDING );
        $from_wizard = ! empty( $_GET['pwpl_wizard'] );
        $wizard_tpl    = isset( $_GET['pwpl_template_id'] ) ? sanitize_key( $_GET['pwpl_template_id'] ) : '';
        $wizard_layout = isset( $_GET['pwpl_layout_id'] ) ? sanitize_key( $_GET['pwpl_layout_id'] ) : '';
        $wizard_style  = isset( $_GET['pwpl_card_style'] ) ? sanitize_key( $_GET['pwpl_card_style'] ) : '';

        $onboarding_css = PWPL_DIR . 'assets/admin/css/onboarding.css';
        if ( file_exists( $onboarding_css ) ) {
            wp_enqueue_style( 'pwpl-onboarding', PWPL_URL . 'assets/admin/css/onboarding.css', [ 'pwpl-admin-v1' ], filemtime( $onboarding_css ) );
        }

        $onboarding_js = PWPL_DIR . 'assets/admin/js/onboarding.js';
        if ( file_exists( $onboarding_js ) ) {
            wp_enqueue_script(
                'pwpl-onboarding',
                PWPL_URL . 'assets/admin/js/onboarding.js',
                [ 'pwpl-admin-v1' ],
                filemtime( $onboarding_js ),
                true
            );

            $tour_steps = [
                [
                    'id'     => 'welcome',
                    'target' => '#titlewrap',
                    'title'  => __( 'Welcome to the Table Editor', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'This quick tour will walk you through how to name your table, navigate sections, and publish it. You can skip anytime.', 'planify-wp-pricing-lite' ),
                ],
                [
                    'id'     => 'title',
                    'target' => '#titlewrap',
                    'title'  => __( 'Name your table', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Give this pricing table a clear name, e.g. “VPS Hosting”.', 'planify-wp-pricing-lite' ),
                ],
                [
                    'id'     => 'nav',
                    'target' => '[data-pwpl-tour="table-nav"]',
                    'title'  => __( 'Sections', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Use these tabs to switch between layout, typography, colors, animation, badges, advanced, and filters.', 'planify-wp-pricing-lite' ),
                ],
                [
                    'id'     => 'layout',
                    'target' => '[data-pwpl-tour="tab-layout"]',
                    'title'  => __( 'Layout & Spacing', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Control table width, number of columns, and spacing between plan cards.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'layout',
                ],
                [
                    'id'     => 'typography',
                    'target' => '[data-pwpl-tour="tab-typography"]',
                    'title'  => __( 'Typography', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Adjust headings, body text, and pricing typography for the table.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'typography',
                ],
                [
                    'id'     => 'colors',
                    'target' => '[data-pwpl-tour="tab-colors"]',
                    'title'  => __( 'Theme & colors', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Pick a theme and primary colors to define the overall visual language.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'colors',
                ],
                [
                    'id'     => 'animation',
                    'target' => '[data-pwpl-tour="tab-animation"]',
                    'title'  => __( 'Animation', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Control how specs and interactions animate for more “alive” tables.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'animation',
                ],
                [
                    'id'     => 'badges',
                    'target' => '[data-pwpl-tour="tab-badges"]',
                    'title'  => __( 'Badges & Promotions', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Configure table-level promotions and badges such as “Save 40%”.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'badges',
                ],
                [
                    'id'     => 'advanced',
                    'target' => '[data-pwpl-tour="tab-advanced"]',
                    'title'  => __( 'Advanced', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Control extras like trust row and sticky mobile CTA.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'advanced',
                ],
                [
                    'id'     => 'filters',
                    'target' => '[data-pwpl-tour="tab-filters"]',
                    'title'  => __( 'Filters & dimensions', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Choose which Platforms, Periods, and Locations to expose as filters for this table.', 'planify-wp-pricing-lite' ),
                    'setTab' => 'filters',
                ],
                [
                    'id'     => 'shortcode-area',
                    'target' => '[data-pwpl-tour="table-shortcode-area"]',
                    'title'  => __( 'Publish & shortcode', 'planify-wp-pricing-lite' ),
                    'body'   => __( 'Copy the shortcode to embed this table once you publish or update it.', 'planify-wp-pricing-lite' ),
                ],
            ];

            wp_localize_script( 'pwpl-onboarding', 'PWPL_Tours', [
                'activeTour' => ( 'not_started' === $tour_status ) ? PWPL_Onboarding::TOUR_TABLE_EDITOR : null,
                'tours'      => [
                    PWPL_Onboarding::TOUR_TABLE_EDITOR => $tour_steps,
                ],
                'state'      => [
                    PWPL_Onboarding::TOUR_TABLE_EDITOR => [
                        'status' => $tour_status,
                    ],
                    PWPL_Onboarding::TOUR_TABLE_WIZARD_LANDING => [
                        'status' => $wizard_landing_status,
                    ],
                ],
                'nonce'    => wp_create_nonce( 'pwpl_tour_state' ),
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'labels'   => [
                    'next'   => __( 'Next', 'planify-wp-pricing-lite' ),
                    'finish' => __( 'Finish', 'planify-wp-pricing-lite' ),
                ],
            ] );
        }
    }

    public function render_editor_v1( $post ) {
        wp_nonce_field( 'pwpl_save_table_' . $post->ID, 'pwpl_table_nonce' );
        $onboarding = new PWPL_Onboarding();
        $tour_status = $onboarding->get_tour_status( PWPL_Onboarding::TOUR_TABLE_EDITOR );
        $wizard_landing_status = $onboarding->get_tour_status( PWPL_Onboarding::TOUR_TABLE_WIZARD_LANDING );
        $from_wizard   = ! empty( $_GET['pwpl_wizard'] );
        $wizard_tpl    = isset( $_GET['pwpl_template_id'] ) ? sanitize_key( $_GET['pwpl_template_id'] ) : '';
        $wizard_layout = isset( $_GET['pwpl_layout_id'] ) ? sanitize_key( $_GET['pwpl_layout_id'] ) : '';
        $wizard_style  = isset( $_GET['pwpl_card_style'] ) ? sanitize_key( $_GET['pwpl_card_style'] ) : '';

        $callout_html = '';
        if ( $from_wizard && 'not_started' === $wizard_landing_status ) {
            $template_label = $wizard_tpl;
            $layout_label   = $wizard_layout;
            $style_label    = $wizard_style;
            if ( class_exists( 'PWPL_Table_Templates' ) ) {
                $tpl_obj = PWPL_Table_Templates::get_template( $wizard_tpl );
                if ( $tpl_obj ) {
                    $template_label = $tpl_obj['label'] ?? $wizard_tpl;
                    if ( ! empty( $wizard_layout ) && isset( $tpl_obj['layouts'][ $wizard_layout ]['label'] ) ) {
                        $layout_label = $tpl_obj['layouts'][ $wizard_layout ]['label'];
                    }
                    if ( ! empty( $wizard_style ) && isset( $tpl_obj['card_styles'][ $wizard_style ]['label'] ) ) {
                        $style_label = $tpl_obj['card_styles'][ $wizard_style ]['label'];
                    }
                }
            }
            $plan_count = (int) get_posts( [
                'post_type'      => 'pwpl_plan',
                'post_status'    => [ 'publish', 'draft' ],
                'meta_key'       => PWPL_Meta::PLAN_TABLE_ID,
                'meta_value'     => (int) $post->ID,
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
            ] );
            $plans_url = add_query_arg(
                [
                    'page'       => 'pwpl-plans-dashboard',
                    'pwpl_table' => (int) $post->ID,
                ],
                admin_url( 'admin.php' )
            );
            ob_start();
            ?>
            <div class="pwpl-wizard-callout" data-pwpl-tour="wizard-landing">
                <div class="pwpl-wizard-callout__body">
                    <h2 class="pwpl-wizard-callout__title">
                        <?php echo esc_html( sprintf( __( 'Table created from “%s”', 'planify-wp-pricing-lite' ), $template_label ) ); ?>
                    </h2>
                    <p class="pwpl-wizard-callout__desc">
                        <?php
                        printf(
                            /* translators: 1: layout, 2: card style, 3: plan count */
                            esc_html__( 'We pre-configured layout (%1$s), card style (%2$s), and added %3$d demo plans. You can adjust details here or jump straight to the Plans Dashboard.', 'planify-wp-pricing-lite' ),
                            esc_html( $layout_label ?: __( 'Default', 'planify-wp-pricing-lite' ) ),
                            esc_html( $style_label ?: __( 'Default', 'planify-wp-pricing-lite' ) ),
                            (int) $plan_count
                        );
                        ?>
                    </p>
                    <div class="pwpl-wizard-callout__actions">
                        <a class="button button-primary" href="<?php echo esc_url( $plans_url ); ?>">
                            <?php esc_html_e( 'Open Plans Dashboard', 'planify-wp-pricing-lite' ); ?>
                        </a>
                        <button type="button" class="button button-link pwpl-wizard-callout__tour" data-pwpl-tour-start="<?php echo esc_attr( PWPL_Onboarding::TOUR_TABLE_EDITOR ); ?>">
                            <?php esc_html_e( 'Take a quick tour', 'planify-wp-pricing-lite' ); ?>
                        </button>
                    </div>
                </div>
                <button type="button" class="pwpl-wizard-callout__dismiss" data-pwpl-tour-dismiss="<?php echo esc_attr( PWPL_Onboarding::TOUR_TABLE_WIZARD_LANDING ); ?>" aria-label="<?php esc_attr_e( 'Dismiss', 'planify-wp-pricing-lite' ); ?>">×</button>
            </div>
            <?php
            $callout_html = ob_get_clean();
        }
        echo '<div class="pwpl-tour-replay"><a href="#" data-pwpl-tour-start="' . esc_attr( PWPL_Onboarding::TOUR_TABLE_EDITOR ) . '">' . esc_html__( 'Getting started tour', 'planify-wp-pricing-lite' ) . '</a></div>';
        if ( $callout_html ) {
            echo $callout_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '<div id="pwpl-admin-v1-root"></div>';
        // Hidden inputs will be rendered by the React app to ensure values submit with the post.
    }
}
