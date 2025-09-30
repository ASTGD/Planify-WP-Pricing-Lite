<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Meta {
    const DIMENSION_META      = '_pwpl_dimensions';
    const ALLOWED_PLATFORMS   = '_pwpl_allowed_platforms';
    const ALLOWED_PERIODS     = '_pwpl_allowed_periods';
    const ALLOWED_LOCATIONS   = '_pwpl_allowed_locations';

    const PLAN_TABLE_ID       = '_pwpl_table_id';
    const PLAN_THEME          = '_pwpl_theme';
    const PLAN_SPECS          = '_pwpl_specs';
    const PLAN_VARIANTS       = '_pwpl_variants';

    public function init() {
        add_action( 'init', [ $this, 'register_meta' ] );
    }

    public function register_meta() {
        register_post_meta( 'pwpl_table', self::DIMENSION_META, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_dimensions' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::ALLOWED_PLATFORMS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $values ) {
                return $this->sanitize_allowed_list( $values, 'platforms' );
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::ALLOWED_PERIODS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $values ) {
                return $this->sanitize_allowed_list( $values, 'periods' );
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::ALLOWED_LOCATIONS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $values ) {
                return $this->sanitize_allowed_list( $values, 'locations' );
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_TABLE_ID, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                return max( 0, (int) $value );
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_THEME, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_theme' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_SPECS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_specs' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_VARIANTS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_variants' ],
            'show_in_rest'      => false,
        ] );
    }

    public function can_edit() {
        return current_user_can( 'edit_posts' );
    }

    public function sanitize_dimensions( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }
        $allowed = [ 'platform', 'period', 'location' ];
        $value   = array_map( 'sanitize_key', $value );
        return array_values( array_intersect( $value, $allowed ) );
    }

    private function sanitize_allowed_list( $values, $settings_key ) {
        if ( ! is_array( $values ) ) {
            return [];
        }
        $values = array_map( 'sanitize_title', $values );
        $values = array_filter( $values );
        $values = array_values( array_unique( $values ) );

        $settings = new PWPL_Settings();
        $available = wp_list_pluck( (array) $settings->get( $settings_key ), 'slug' );
        if ( $available ) {
            $values = array_values( array_intersect( $values, $available ) );
        }
        return $values;
    }

    public function sanitize_theme( $value ) {
        $value = sanitize_key( $value );
        $allowed = [ 'warm', 'blue', 'classic' ];
        return in_array( $value, $allowed, true ) ? $value : 'classic';
    }

    public function sanitize_specs( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }
        $clean = [];
        foreach ( $value as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $label = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '';
            $val   = isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '';
            if ( $label === '' && $val === '' ) {
                continue;
            }
            $clean[] = [ 'label' => $label, 'value' => $val ];
        }
        return array_values( $clean );
    }

    public function sanitize_variants( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }
        $clean = [];
        foreach ( $value as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $variant = [
                'platform'   => sanitize_title( $item['platform'] ?? '' ),
                'period'     => sanitize_title( $item['period'] ?? '' ),
                'location'   => sanitize_title( $item['location'] ?? '' ),
                'price'      => $this->sanitize_price( $item['price'] ?? '' ),
                'sale_price' => $this->sanitize_price( $item['sale_price'] ?? '' ),
            ];
            if ( $variant['price'] === '' && $variant['sale_price'] === '' ) {
                continue;
            }
            $clean[] = $variant;
        }
        return array_values( $clean );
    }

    private function sanitize_price( $value ) {
        $value = trim( (string) $value );
        if ( $value === '' ) {
            return '';
        }
        $value = str_replace( ' ', '', $value );
        $value = preg_replace( '/[^0-9\.,-]/', '', $value );

        $has_comma = strpos( $value, ',' ) !== false;
        $has_dot   = strpos( $value, '.' ) !== false;

        if ( $has_comma && $has_dot ) {
            // Assume comma is thousands separator, remove it.
            $value = str_replace( ',', '', $value );
        } elseif ( $has_comma && ! $has_dot ) {
            // Treat comma as decimal separator.
            $value = str_replace( ',', '.', $value );
        }

        return $value;
    }
}
