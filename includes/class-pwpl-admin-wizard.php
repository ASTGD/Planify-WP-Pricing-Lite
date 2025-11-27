<?php
/**
 * Admin shell for the New Table Wizard (page + enqueue only).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin_Wizard {

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function register_menu() {
        add_submenu_page(
            'pwpl-tables-dashboard',
            __( 'New Pricing Table wizard', 'planify-wp-pricing-lite' ),
            __( 'New table (wizard)', 'planify-wp-pricing-lite' ),
            'edit_posts',
            'pwpl-table-wizard',
            [ $this, 'render_page' ],
            20
        );
    }

    public function render_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'planify-wp-pricing-lite' ) );
        }
        ?>
        <div class="wrap pwpl-table-wizard-wrap">
            <h1><?php esc_html_e( 'New Pricing Table wizard', 'planify-wp-pricing-lite' ); ?></h1>
            <div id="pwpl-table-wizard-root"></div>
        </div>
        <?php
    }

    public function enqueue( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
        $is_wizard = $screen_id && false !== strpos( $screen_id, 'pwpl-table-wizard' );
        if ( ! $is_wizard ) {
            return;
        }

        $js = PWPL_DIR . 'assets/admin/js/table-wizard.js';
        if ( file_exists( $js ) ) {
            wp_enqueue_script(
                'pwpl-table-wizard',
                PWPL_URL . 'assets/admin/js/table-wizard.js',
                [ 'wp-element', 'wp-components', 'wp-api-fetch' ],
                filemtime( $js ),
                true
            );
        }

        $templates = PWPL_Table_Templates::get_templates();

        wp_localize_script(
            'pwpl-table-wizard',
            'PWPL_TableWizard',
            [
                'templates' => $templates,
                'rest'      => [
                    'root'       => esc_url_raw( rest_url() ),
                    'nonce'      => wp_create_nonce( 'wp_rest' ),
                    'previewUrl' => esc_url_raw( rest_url( 'pwpl/v1/preview-table' ) ),
                    'createUrl'  => esc_url_raw( rest_url( 'pwpl/v1/create-table-from-wizard' ) ), // placeholder for next step
                ],
                'previewFrame' => [
                    'url'   => esc_url_raw( add_query_arg(
                        [
                            'action' => 'pwpl_wizard_preview_frame',
                            '_wpnonce' => wp_create_nonce( 'pwpl_wizard_preview' ),
                        ],
                        admin_url( 'admin-ajax.php' )
                    ) ),
                ],
                'i18n' => [
                    'title'          => __( 'New Pricing Table wizard', 'planify-wp-pricing-lite' ),
                    'selectTemplate' => __( 'Select a template to start', 'planify-wp-pricing-lite' ),
                ],
            ]
        );
    }
}
