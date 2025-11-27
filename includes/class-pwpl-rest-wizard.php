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
                    'dimensions'    => [ 'required' => false, 'type' => 'array' ],
                ],
            ]
        );
    }

    public function handle_preview( WP_REST_Request $request ) {
        $template_id  = (string) $request->get_param( 'template_id' );
        $layout_id    = $request->get_param( 'layout_id' );
        $card_style_id= $request->get_param( 'card_style_id' );

        $config = PWPL_Table_Wizard::build_preview_config( $template_id, $layout_id ? (string) $layout_id : null, $card_style_id ? (string) $card_style_id : null );
        if ( ! $config ) {
            return new WP_Error( 'pwpl_preview_not_found', __( 'Preview not available for this selection.', 'planify-wp-pricing-lite' ), [ 'status' => 404 ] );
        }

        $html = PWPL_Table_Renderer::render_from_config( $config );
        return rest_ensure_response( [
            'html' => $html,
        ] );
    }

    public function handle_create_table( WP_REST_Request $request ) {
        $template_id   = (string) $request['template_id'];
        $layout_id     = $request['layout_id'] ? (string) $request['layout_id'] : null;
        $card_style_id = $request['card_style_id'] ? (string) $request['card_style_id'] : null;
        $title         = $request['title'] ? sanitize_text_field( $request['title'] ) : '';
        $theme         = $request['theme'] ? sanitize_key( $request['theme'] ) : '';
        $dimensions    = $request['dimensions'];
        $dimensions    = is_array( $dimensions ) ? $dimensions : [];

        $table_id = PWPL_Table_Wizard::create_table_from_selection(
            $template_id,
            $layout_id,
            $card_style_id,
            [
                'post_title'  => $title,
                'post_status' => 'publish',
                'theme'       => $theme,
                'dimensions'  => $dimensions,
            ]
        );

        if ( is_wp_error( $table_id ) || null === $table_id ) {
            return new WP_Error(
                'pwpl_create_failed',
                __( 'Unable to create the table from this selection.', 'planify-wp-pricing-lite' ),
                [ 'status' => 400 ]
            );
        }

        $edit_url = get_edit_post_link( $table_id, 'raw' );

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

        $config = PWPL_Table_Wizard::build_preview_config( $template_id, $layout_id ?: null, $card_style_id ?: null );
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
}
