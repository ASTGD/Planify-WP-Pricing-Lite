<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Shortcode {
    public function init(){
        add_shortcode( 'pwpl_table', [ $this, 'render' ] );
    }

    public function render( $atts = [] ){
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $table_id = (int) $atts['id'];
        if ( ! $table_id || get_post_type( $table_id ) !== 'pwpl_table' ) {
            return '<div class="pwpl-table">' . esc_html__( 'Planify: table not found.', 'planify-wp-pricing-lite' ) . '</div>';
        }

        // Ensure frontend assets are loaded when rendering
        $css = PWPL_DIR . 'assets/css/frontend.css';
        $js  = PWPL_DIR . 'assets/js/frontend.js';
        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'pwpl-frontend', PWPL_URL . 'assets/css/frontend.css', [], filemtime( $css ) );
        }
        if ( file_exists( $js ) ) {
            wp_enqueue_script( 'pwpl-frontend', PWPL_URL . 'assets/js/frontend.js', [], filemtime( $js ), true );
        }

        $title = esc_html( get_the_title( $table_id ) );
        ob_start();
        ?>
        <div class="pwpl-table">
            <h3><?php echo $title; ?></h3>
            <p><?php echo esc_html__( 'Pricing table output will render here.', 'planify-wp-pricing-lite' ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
