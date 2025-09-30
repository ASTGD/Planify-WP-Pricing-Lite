<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Plugin {
    public function init() {
        ( new PWPL_CPT() )->init();
        ( new PWPL_Meta() )->init();
        ( new PWPL_Shortcode() )->init();
        if ( is_admin() ) {
            ( new PWPL_Admin() )->init();
            ( new PWPL_Admin_Meta() )->init();
            ( new PWPL_Settings() )->init();
        }
    }
}
