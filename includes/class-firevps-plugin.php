<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FireVPS_Plugin {
    public function init() {
        ( new FireVPS_CPT() )->init();
        ( new FireVPS_Shortcode() )->init();
        if ( is_admin() ) {
            // Admin hooks can go here later
        }
    }
}
