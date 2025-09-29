<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FireVPS_Shortcode {
    public function init(){
        add_shortcode( 'firevps_table', [ $this, 'render' ] );
    }

    public function render( $atts = [] ){
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $table_id = (int) $atts['id'];
        if ( ! $table_id || get_post_type( $table_id ) !== 'firevps_table' ) {
            return '<div class="firevps-table">FireVPS: table not found.</div>';
        }

        // Very basic safe output while we develop
        $title = esc_html( get_the_title( $table_id ) );
        ob_start();
        ?>
        <div class="firevps-table">
            <h3><?php echo $title; ?></h3>
            <p><?php echo esc_html__( 'Pricing table output will render here.', 'firevps' ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
