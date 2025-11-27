<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Onboarding {
    const TOUR_TABLE_EDITOR = 'tableEditorV1';
    const META_TABLE_EDITOR = 'pwpl_table_editor_tour_status';
    const TOUR_PLAN_EDITOR  = 'planEditorV2';
    const META_PLAN_EDITOR  = 'pwpl_plan_editor_tour_status';
    const TOUR_TABLE_WIZARD_LANDING = 'tableWizardLanding';
    const META_TABLE_WIZARD_LANDING = 'pwpl_table_wizard_landing_status';

    public function init() {
        add_action( 'wp_ajax_pwpl_save_tour_state', [ $this, 'ajax_save_tour_state' ] );
    }

    /**
     * Return the current user's tour status for a given tour id.
     */
    public function get_tour_status( $tour_id ) {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return 'not_started';
        }
        if ( self::TOUR_TABLE_EDITOR === $tour_id ) {
            $status = get_user_meta( $user_id, self::META_TABLE_EDITOR, true );
            return $status ?: 'not_started';
        }
        if ( self::TOUR_PLAN_EDITOR === $tour_id ) {
            $status = get_user_meta( $user_id, self::META_PLAN_EDITOR, true );
            return $status ?: 'not_started';
        }
        if ( self::TOUR_TABLE_WIZARD_LANDING === $tour_id ) {
            $status = get_user_meta( $user_id, self::META_TABLE_WIZARD_LANDING, true );
            return $status ?: 'not_started';
        }
        return 'not_started';
    }

    /**
     * Persist tour state for the current user.
     */
    public function set_tour_status( $tour_id, $status ) {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return;
        }
        $status = in_array( $status, [ 'not_started', 'in_progress', 'completed', 'dismissed' ], true ) ? $status : 'not_started';
        if ( self::TOUR_TABLE_EDITOR === $tour_id ) {
            update_user_meta( $user_id, self::META_TABLE_EDITOR, $status );
        }
        if ( self::TOUR_PLAN_EDITOR === $tour_id ) {
            update_user_meta( $user_id, self::META_PLAN_EDITOR, $status );
        }
        if ( self::TOUR_TABLE_WIZARD_LANDING === $tour_id ) {
            update_user_meta( $user_id, self::META_TABLE_WIZARD_LANDING, $status );
        }
    }

    public function ajax_save_tour_state() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'planify-wp-pricing-lite' ) ], 403 );
        }
        check_ajax_referer( 'pwpl_tour_state', 'nonce' );

        $tour_id = isset( $_POST['tour_id'] ) ? sanitize_key( wp_unslash( $_POST['tour_id'] ) ) : '';
        $status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'not_started';
        if ( ! $tour_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid tour.', 'planify-wp-pricing-lite' ) ], 400 );
        }

        $this->set_tour_status( $tour_id, $status );
        wp_send_json_success( [ 'status' => $status ] );
    }
}
