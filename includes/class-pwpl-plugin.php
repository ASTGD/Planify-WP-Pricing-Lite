<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Plugin {
    public function init() {
        ( new PWPL_CPT() )->init();
        ( new PWPL_Meta() )->init();
        ( new PWPL_Shortcode() )->init();
        ( new PWPL_Rest_Wizard() )->init();
        if ( is_admin() ) {
            ( new PWPL_Onboarding() )->init();
            ( new PWPL_Admin() )->init();
            ( new PWPL_Admin_Meta() )->init();
            ( new PWPL_Settings() )->init();
            // New Table Editor UI (V1) — optional preview
            ( new PWPL_Admin_UI_V1() )->init();
        }
    }
}
