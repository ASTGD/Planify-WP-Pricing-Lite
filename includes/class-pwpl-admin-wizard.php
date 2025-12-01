<?php
/**
 * Admin shell for the New Table Wizard (page + enqueue only).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin_Wizard {

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_divi_shim' ], 5 );
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

        $css = PWPL_DIR . 'assets/admin/css/table-wizard.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style(
                'pwpl-table-wizard',
                PWPL_URL . 'assets/admin/css/table-wizard.css',
                [ 'wp-components' ],
                filemtime( $css )
            );
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
                    'createUrl'  => esc_url_raw( rest_url( 'pwpl/v1/create-table-from-wizard' ) ),
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
                    'title'            => __( 'New Pricing Table wizard', 'planify-wp-pricing-lite' ),
                    'selectTemplate'   => __( 'Select a template to start', 'planify-wp-pricing-lite' ),
                    'previewError'     => __( 'Unable to load preview. Please try again.', 'planify-wp-pricing-lite' ),
                    'createError'      => __( 'Unable to create table. Please try again.', 'planify-wp-pricing-lite' ),
                    'recommended'      => __( 'Recommended', 'planify-wp-pricing-lite' ),
                    'proLabel'         => __( 'Pro', 'planify-wp-pricing-lite' ),
                    'continueTemplate' => __( 'Continue with this template', 'planify-wp-pricing-lite' ),
                    'continueLayout'   => __( 'Continue', 'planify-wp-pricing-lite' ),
                    'back'             => __( 'Back', 'planify-wp-pricing-lite' ),
                    'columns'          => __( 'Plan columns', 'planify-wp-pricing-lite' ),
                    'addColumn'        => __( 'Add column', 'planify-wp-pricing-lite' ),
                    'planLabel'        => __( 'Plan %d', 'planify-wp-pricing-lite' ),
                    'summaryTitle'     => __( 'Summary', 'planify-wp-pricing-lite' ),
                    'summaryTemplate'  => __( 'Template', 'planify-wp-pricing-lite' ),
                    'summaryLayout'    => __( 'Layout', 'planify-wp-pricing-lite' ),
                    'summaryColumns'   => __( 'Plan columns', 'planify-wp-pricing-lite' ),
                    'summaryDimensions'=> __( 'Dimensions', 'planify-wp-pricing-lite' ),
                    'noneLabel'        => __( 'None', 'planify-wp-pricing-lite' ),
                    'createCopyLabel'  => __( 'Create and copy shortcode', 'planify-wp-pricing-lite' ),
                    'shortcodeCopied'  => __( 'Table created. Shortcode copied to clipboard.', 'planify-wp-pricing-lite' ),
                    'edit'             => __( 'Edit', 'planify-wp-pricing-lite' ),
                    'duplicate'        => __( 'Duplicate', 'planify-wp-pricing-lite' ),
                    'hide'             => __( 'Hide', 'planify-wp-pricing-lite' ),
                    'unhide'           => __( 'Unhide', 'planify-wp-pricing-lite' ),
                    'deleteLabel'      => __( 'Delete', 'planify-wp-pricing-lite' ),
                    'editColumn'       => __( 'Edit Column', 'planify-wp-pricing-lite' ),
                    'basics'           => __( 'Basics', 'planify-wp-pricing-lite' ),
                    'planTitle'        => __( 'Title', 'planify-wp-pricing-lite' ),
                    'planSubtitle'     => __( 'Caption', 'planify-wp-pricing-lite' ),
                    'highlightLabel'   => __( 'Highlight label', 'planify-wp-pricing-lite' ),
                    'featured'         => __( 'Featured', 'planify-wp-pricing-lite' ),
                    'features'         => __( 'Features', 'planify-wp-pricing-lite' ),
                    'addFeature'       => __( 'Add Feature', 'planify-wp-pricing-lite' ),
                    'featurePlaceholder'=> __( 'Feature', 'planify-wp-pricing-lite' ),
                    'featureValuePlaceholder'=> __( 'Value', 'planify-wp-pricing-lite' ),
                    'moveUp'          => __( 'Move up', 'planify-wp-pricing-lite' ),
                    'moveDown'        => __( 'Move down', 'planify-wp-pricing-lite' ),
                    'price'            => __( 'Price', 'planify-wp-pricing-lite' ),
                    'priceLabel'       => __( 'Price', 'planify-wp-pricing-lite' ),
                    'salePriceLabel'   => __( 'Old Price / Sale Price', 'planify-wp-pricing-lite' ),
                    'button'           => __( 'Button', 'planify-wp-pricing-lite' ),
                    'buttonText'       => __( 'Text', 'planify-wp-pricing-lite' ),
                    'buttonUrl'        => __( 'Link', 'planify-wp-pricing-lite' ),
                    'editFeature'      => __( 'Edit Feature', 'planify-wp-pricing-lite' ),
                    'featureLabel'     => __( 'Text', 'planify-wp-pricing-lite' ),
                    'featureValue'     => __( 'Value', 'planify-wp-pricing-lite' ),
                    'saveFeature'     => __( 'Save feature', 'planify-wp-pricing-lite' ),
                ],
            ]
        );
    }

    /**
     * Define a safe shim for Divi's `et_pb_custom` on the wizard screen to avoid
     * ReferenceError noise from Divi scripts that may load in wp-admin.
     *
     * This does not change any Planify behavior; it only ensures the global
     * object exists before third-party scripts access it.
     *
     * @param string $hook
     */
    public function enqueue_divi_shim( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
        $is_wizard = $screen_id && false !== strpos( $screen_id, 'pwpl-table-wizard' );
        if ( ! $is_wizard ) {
            return;
        }

        if ( ! wp_script_is( 'pwpl-wizard-divi-shim', 'registered' ) ) {
            wp_register_script(
                'pwpl-wizard-divi-shim',
                '',
                [],
                '1.0.0',
                false
            );
        }

        wp_enqueue_script( 'pwpl-wizard-divi-shim' );

        $shim = 'window.et_pb_custom = window.et_pb_custom || { page_id: 0 };';
        wp_add_inline_script( 'pwpl-wizard-divi-shim', $shim, 'before' );
    }
}
