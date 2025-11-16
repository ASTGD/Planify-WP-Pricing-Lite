<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin_MUI {
    const HANDLE = 'pwpl-admin-mui-app';

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=pwpl_table',
            __( 'Planify UI V2 (MUI)', 'planify-wp-pricing-lite' ),
            __( 'UI V2 (MUI)', 'planify-wp-pricing-lite' ),
            'manage_options',
            'pwpl-ui-v2',
            [ $this, 'render_page' ]
        );
    }

    public function enqueue_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'pwpl_table' ) {
            return;
        }
        if ( $hook !== 'pwpl_table_page_pwpl-ui-v2' ) {
            return;
        }

        $entry = PWPL_DIR . 'assets/admin/mui/build/app.js';
        if ( file_exists( $entry ) ) {
            wp_enqueue_script( self::HANDLE, PWPL_URL . 'assets/admin/mui/build/app.js', [], filemtime( $entry ), true );
        } else {
            // Developer hint if the bundle is missing
            wp_add_inline_script( 'jquery', 'console.warn("PWPL MUI bundle missing. Run npm run build:mui in the plugin root.");' );
        }
        // Minimal base container styles for the shell
        $base_css = '#pwpl-mui-root{min-height:calc(100vh - 100px);} .pwpl-mui-container{isolation:isolate;}';
        wp_add_inline_style( 'wp-components', $base_css );
    }

    public function render_page() {
        echo '<div class="wrap pwpl-mui-container">';
        echo '<h1 style="display:none">' . esc_html__( 'Planify UI V2', 'planify-wp-pricing-lite' ) . '</h1>';
        echo '<div id="pwpl-mui-root"></div>';
        echo '</div>';
    }
}

