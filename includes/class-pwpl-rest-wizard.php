<?php
/**
 * REST endpoints for the New Table Wizard previews.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Rest_Wizard {

    public function init() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        add_action( 'wp_ajax_pwpl_wizard_preview_frame', [ $this, 'render_preview_frame' ] );
    }

    public function register_routes() {
        register_rest_route(
            'pwpl/v1',
            '/preview-table',
            [
                'methods'             => [ 'GET', 'POST' ],
                'callback'            => [ $this, 'handle_preview' ],
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'args'                => [
                    'template_id' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'layout_id' => [
                        'required' => false,
                        'type'     => 'string',
                    ],
                    'card_style_id' => [
                        'required' => false,
                        'type'     => 'string',
                    ],
                    'plan_count' => [
                        'required' => false,
                        'type'     => 'integer',
                    ],
                    'plans_override' => [
                        'required' => false,
                        'type'     => 'array',
                    ],
                ],
            ]
        );

        register_rest_route(
            'pwpl/v1',
            '/create-table-from-wizard',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_create_table' ],
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'args'                => [
                    'template_id'   => [ 'required' => true,  'type' => 'string' ],
                    'layout_id'     => [ 'required' => false, 'type' => 'string' ],
                    'card_style_id' => [ 'required' => false, 'type' => 'string' ],
                    'title'         => [ 'required' => false, 'type' => 'string' ],
                    'theme'         => [ 'required' => false, 'type' => 'string' ],
                    'dimensions'    => [ 'required' => false, 'type' => 'object' ],
                    'plan_count'    => [ 'required' => false, 'type' => 'integer' ],
                    'plans_override'=> [ 'required' => false, 'type' => 'array' ],
                ],
            ]
        );
    }

    public function handle_preview( WP_REST_Request $request ) {
        $template_id  = (string) $request->get_param( 'template_id' );
        $layout_id    = $request->get_param( 'layout_id' );
        $card_style_id= $request->get_param( 'card_style_id' );
        $plan_count   = $request->get_param( 'plan_count' );
        $plan_count   = $plan_count ? max( 1, min( 12, (int) $plan_count ) ) : null;
        $plans_override = $request->get_param( 'plans_override' );
        $plans_override = $this->sanitize_plans_override( $plans_override );

        $start = microtime( true );

        $template = PWPL_Table_Templates::get_template( $template_id );
        if ( ! $template ) {
            $this->debug_log(
                'preview_template_missing',
                [
                    'template_id'   => $template_id,
                'layout_id'     => $layout_id ? (string) $layout_id : '',
                'card_style_id' => $card_style_id ? (string) $card_style_id : '',
                'plan_count'    => $plan_count,
                'duration_ms'   => (int) ( ( microtime( true ) - $start ) * 1000 ),
            ]
        );

            return new WP_Error(
                'pwpl_template_not_found',
                __( 'This template is no longer available. Please reload the wizard and pick another template.', 'planify-wp-pricing-lite' ),
                [ 'status' => 404 ]
            );
        }

        $config = PWPL_Table_Wizard::build_preview_config(
            $template_id,
            $layout_id ? (string) $layout_id : null,
            $card_style_id ? (string) $card_style_id : null,
            $plan_count,
            $plans_override
        );
        if ( ! $config ) {
            $this->debug_log(
                'preview_config_missing',
                [
                    'template_id'   => $template_id,
                    'layout_id'     => $layout_id ? (string) $layout_id : '',
                    'card_style_id' => $card_style_id ? (string) $card_style_id : '',
                    'duration_ms'   => (int) ( ( microtime( true ) - $start ) * 1000 ),
                ]
            );

            return new WP_Error(
                'pwpl_preview_not_found',
                __( 'Preview not available for this selection.', 'planify-wp-pricing-lite' ),
                [ 'status' => 404 ]
            );
        }

        $html = PWPL_Table_Renderer::render_from_config( $config );

        $this->debug_log(
            'preview_success',
            [
                'template_id'   => $template_id,
                'layout_id'     => $config['layout_id'],
                'card_style_id' => $config['card_style_id'],
                'plan_count'    => $plans_override ? count( $plans_override ) : $plan_count,
                'duration_ms'   => (int) ( ( microtime( true ) - $start ) * 1000 ),
            ]
        );

        return rest_ensure_response( [
            'html' => $html,
        ] );
    }

    public function handle_create_table( WP_REST_Request $request ) {
        $template_id   = (string) $request['template_id'];
        $layout_id     = $request['layout_id'] ? (string) $request['layout_id'] : null;
        $card_style_id = $request['card_style_id'] ? (string) $request['card_style_id'] : null;
        $title         = $request['title'] ? sanitize_text_field( $request['title'] ) : '';
        $theme         = 'firevps';
        $dimensions    = $request['dimensions'];
        $dimensions    = is_array( $dimensions ) ? $dimensions : [];
        $plan_count    = $request['plan_count'] ? max( 1, min( 12, (int) $request['plan_count'] ) ) : null;
        $plans_override = $this->sanitize_plans_override( $request->get_param( 'plans_override' ) );

        $start = microtime( true );

        $template = PWPL_Table_Templates::get_template( $template_id );
        if ( ! $template ) {
            $this->debug_log(
                'create_template_missing',
                [
                'template_id'   => $template_id,
                'layout_id'     => $layout_id ?: '',
                'card_style_id' => $card_style_id ?: '',
                'plan_count'    => $plan_count,
                'duration_ms'   => (int) ( ( microtime( true ) - $start ) * 1000 ),
            ]
        );

            return new WP_Error(
                'pwpl_template_not_found',
                __( 'This template is no longer available. Please reload the wizard and pick another template.', 'planify-wp-pricing-lite' ),
                [ 'status' => 400 ]
            );
        }

        $table_id = PWPL_Table_Wizard::create_table_from_selection(
            $template_id,
            $layout_id,
            $card_style_id,
            [
                'post_title'  => $title,
                'post_status' => 'publish',
                'theme'       => $theme,
                'dimensions'  => $dimensions,
            ],
            $plan_count,
            $plans_override
        );

        if ( is_wp_error( $table_id ) || null === $table_id ) {
            $this->debug_log(
                'create_failed',
                [
                'template_id'   => $template_id,
                'layout_id'     => $layout_id ?: '',
                'card_style_id' => $card_style_id ?: '',
                'plan_count'    => $plans_override ? count( $plans_override ) : $plan_count,
                'duration_ms'   => (int) ( ( microtime( true ) - $start ) * 1000 ),
            ]
        );

            return new WP_Error(
                'pwpl_create_failed',
                __( 'Unable to create the table from this selection.', 'planify-wp-pricing-lite' ),
                [ 'status' => 400 ]
            );
        }

        $edit_url = add_query_arg(
            [
                'pwpl_wizard'      => 1,
                'pwpl_template_id' => $template_id,
                'pwpl_layout_id'   => $layout_id,
                'pwpl_card_style'  => $card_style_id,
            ],
            get_edit_post_link( $table_id, 'raw' )
        );

        $this->debug_log(
            'create_success',
            [
                'template_id'   => $template_id,
                'layout_id'     => $layout_id ?: '',
                'card_style_id' => $card_style_id ?: '',
                'table_id'      => (int) $table_id,
                'plan_count'    => $plans_override ? count( $plans_override ) : $plan_count,
                'duration_ms'   => (int) ( ( microtime( true ) - $start ) * 1000 ),
            ]
        );

        return rest_ensure_response( [
            'table_id' => (int) $table_id,
            'edit_url' => esc_url_raw( $edit_url ),
        ] );
    }

    /**
     * Minimal HTML frame for iframe preview (admin only).
     */
    public function render_preview_frame() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this preview.', 'planify-wp-pricing-lite' ) );
        }

        $nonce = isset( $_REQUEST['_wpnonce'] ) ? (string) $_REQUEST['_wpnonce'] : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'pwpl_wizard_preview' ) ) {
            wp_die( esc_html__( 'Invalid preview nonce.', 'planify-wp-pricing-lite' ) );
        }

        $template_id   = isset( $_REQUEST['template_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template_id'] ) ) : '';
        $layout_id     = isset( $_REQUEST['layout_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['layout_id'] ) ) : '';
        $card_style_id = isset( $_REQUEST['card_style_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['card_style_id'] ) ) : '';
        $plan_count    = isset( $_REQUEST['plan_count'] ) ? max( 1, min( 12, (int) $_REQUEST['plan_count'] ) ) : null;
        $plans_override_raw = isset( $_REQUEST['plans_override'] ) ? wp_unslash( $_REQUEST['plans_override'] ) : '';
        $plans_override = $plans_override_raw ? json_decode( $plans_override_raw, true ) : null;
        $plans_override = $this->sanitize_plans_override( $plans_override );

        $config = PWPL_Table_Wizard::build_preview_config( $template_id, $layout_id ?: null, $card_style_id ?: null, $plan_count, $plans_override );
        if ( ! $config ) {
            wp_die( esc_html__( 'Preview not available for this selection.', 'planify-wp-pricing-lite' ) );
        }

        $html = PWPL_Table_Renderer::render_from_config( $config );

        status_header( 200 );
        nocache_headers();
        ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <?php wp_head(); ?>
</head>
<body class="pwpl-preview-frame">
    <?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php wp_footer(); ?>
</body>
</html>
        <?php
        exit;
    }

    /**
     * Check whether wizard debug logging is enabled.
     *
     * Controlled via PWPL_TABLE_WIZARD_DEBUG constant or the
     * 'pwpl_table_wizard_debug' filter.
     *
     * @param string $event
     * @param array  $data
     * @return bool
     */
    private function is_debug_enabled( string $event = '', array $data = [] ): bool {
        $enabled = defined( 'PWPL_TABLE_WIZARD_DEBUG' ) && PWPL_TABLE_WIZARD_DEBUG;

        /**
         * Filter whether wizard debug logging is enabled.
         *
         * @since 1.8.9
         *
         * @param bool   $enabled Whether debug logging is enabled.
         * @param string $event   Event name.
         * @param array  $data    Event payload.
         */
        return (bool) apply_filters( 'pwpl_table_wizard_debug', $enabled, $event, $data );
    }

    /**
     * Log a wizard debug event to the PHP error log when enabled.
     *
     * @param string $event
     * @param array  $data
     * @return void
     */
    private function debug_log( string $event, array $data ): void {
        if ( ! $this->is_debug_enabled( $event, $data ) ) {
            return;
        }

        if ( ! function_exists( 'wp_json_encode' ) ) {
            return;
        }

        $payload = [
            'event'     => $event,
            'timestamp' => time(),
            'user_id'   => get_current_user_id(),
            'data'      => $data,
        ];

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log( '[PWPL Table Wizard] ' . wp_json_encode( $payload ) );
    }

    /**
     * Sanitize plan overrides coming from the wizard.
     *
     * @param mixed $plans
     * @return array
     */
    private function sanitize_plans_override( $plans ): array {
        if ( ! is_array( $plans ) ) {
            return [];
        }

        $sanitized = [];

        foreach ( $plans as $plan ) {
            if ( ! is_array( $plan ) ) {
                continue;
            }
            if ( isset( $plan['hidden'] ) && $plan['hidden'] ) {
                continue;
            }
            $title   = isset( $plan['post_title'] ) ? sanitize_text_field( $plan['post_title'] ) : '';
            $excerpt = isset( $plan['post_excerpt'] ) ? sanitize_text_field( $plan['post_excerpt'] ) : '';

            $meta = isset( $plan['meta'] ) && is_array( $plan['meta'] ) ? $plan['meta'] : [];

            $featured = isset( $meta[ PWPL_Meta::PLAN_FEATURED ] ) ? (bool) $meta[ PWPL_Meta::PLAN_FEATURED ] : false;

            $specs = [];
            if ( isset( $meta[ PWPL_Meta::PLAN_SPECS ] ) && is_array( $meta[ PWPL_Meta::PLAN_SPECS ] ) ) {
                foreach ( $meta[ PWPL_Meta::PLAN_SPECS ] as $spec ) {
                    if ( ! is_array( $spec ) ) {
                        continue;
                    }
                    $label = sanitize_text_field( $spec['label'] ?? '' );
                    $value = sanitize_text_field( $spec['value'] ?? '' );
                    $icon  = '';
                    if ( ! empty( $spec['icon'] ) && is_string( $spec['icon'] ) ) {
                        $icon = sanitize_key( $spec['icon'] );
                    }
                    $specs[] = [
                        'label' => $label,
                        'value' => $value,
                        'icon'  => $icon,
                    ];
                }
            }

            $variants = [];
            if ( isset( $meta[ PWPL_Meta::PLAN_VARIANTS ] ) && is_array( $meta[ PWPL_Meta::PLAN_VARIANTS ] ) ) {
                foreach ( $meta[ PWPL_Meta::PLAN_VARIANTS ] as $variant ) {
                    if ( ! is_array( $variant ) ) {
                        continue;
                    }
                    $variants[] = [
                        'period'     => sanitize_text_field( $variant['period'] ?? '' ),
                        'price'      => sanitize_text_field( $variant['price'] ?? '' ),
                        'sale_price' => sanitize_text_field( $variant['sale_price'] ?? '' ),
                        'cta_label'  => sanitize_text_field( $variant['cta_label'] ?? '' ),
                        'cta_url'    => esc_url_raw( $variant['cta_url'] ?? '' ),
                        'target'     => sanitize_text_field( $variant['target'] ?? '' ),
                    ];
                }
            }

            $badges = [];
            if ( isset( $meta[ PWPL_Meta::PLAN_BADGES_OVERRIDE ] ) && is_array( $meta[ PWPL_Meta::PLAN_BADGES_OVERRIDE ] ) ) {
                foreach ( $meta[ PWPL_Meta::PLAN_BADGES_OVERRIDE ] as $badge ) {
                    if ( ! is_array( $badge ) ) {
                        continue;
                    }
                    $badges[] = [
                        'label' => sanitize_text_field( $badge['label'] ?? '' ),
                        'slug'  => sanitize_title( $badge['slug'] ?? '' ),
                        'color' => sanitize_hex_color( $badge['color'] ?? '' ) ?: sanitize_text_field( $badge['color'] ?? '' ),
                    ];
                }
            }
            $badge_shadow = isset( $meta[ PWPL_Meta::PLAN_BADGE_SHADOW ] ) ? (int) $meta[ PWPL_Meta::PLAN_BADGE_SHADOW ] : 0;

            $sanitized[] = [
                'post_title'   => $title,
                'post_excerpt' => $excerpt,
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED        => $featured,
                    PWPL_Meta::PLAN_SPECS           => $specs,
                    PWPL_Meta::PLAN_VARIANTS        => $variants,
                    PWPL_Meta::PLAN_BADGES_OVERRIDE => $badges,
                    PWPL_Meta::PLAN_BADGE_SHADOW    => $badge_shadow,
                ],
            ];
        }

        return $sanitized;
    }
}
