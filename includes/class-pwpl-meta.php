<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Meta {
    const DIMENSION_META      = '_pwpl_dimensions';
    const ALLOWED_PLATFORMS   = '_pwpl_allowed_platforms';
    const ALLOWED_PERIODS     = '_pwpl_allowed_periods';
    const ALLOWED_LOCATIONS   = '_pwpl_allowed_locations';

    const PLAN_TABLE_ID           = '_pwpl_table_id';
    const PLAN_THEME              = '_pwpl_theme';
    const PLAN_SPECS              = '_pwpl_specs';
    const PLAN_VARIANTS           = '_pwpl_variants';
    const PLAN_FEATURED           = '_pwpl_featured';
    const PLAN_BADGE_SHADOW       = '_pwpl_badge_shadow';
    const TABLE_SIZE              = '_pwpl_table_size';
    const TABLE_BREAKPOINTS       = '_pwpl_table_breakpoints';
    const PLAN_BADGES_OVERRIDE    = '_pwpl_badges_override';
    const TABLE_BADGES            = '_pwpl_badges';
    const TABLE_THEME             = '_pwpl_table_theme';

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

        register_post_meta( 'pwpl_table', self::TABLE_BADGES, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_badges' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABLE_SIZE, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_table_size' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABLE_BREAKPOINTS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_table_breakpoints' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABLE_THEME, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_theme' ],
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

        register_post_meta( 'pwpl_plan', self::PLAN_BADGES_OVERRIDE, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_badges' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_FEATURED, [
            'single'            => true,
            'type'              => 'boolean',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_feature_flag' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_BADGE_SHADOW, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $value = (int) $value;
                $value = max( 0, min( $value, 60 ) );
                return $value;
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_plan', self::PLAN_BADGE_SHADOW, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $v ) {
                $v = (int) $v; return max( 0, min( $v, 60 ) );
            },
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
        $allowed = [ 'warm', 'blue', 'classic', 'modern-discount' ];
        return in_array( $value, $allowed, true ) ? $value : 'classic';
    }

    public function sanitize_feature_flag( $value ) {
        return (bool) $value;
    }

    public function sanitize_table_size( $value ) {
        $defaults = [
            'min' => 320,
            'max' => 1140,
        ];

        if ( ! is_array( $value ) ) {
            return $defaults;
        }

        $min = isset( $value['min'] ) ? (int) $value['min'] : $defaults['min'];
        $max = isset( $value['max'] ) ? (int) $value['max'] : $defaults['max'];
        $base = isset( $value['base'] ) ? (int) $value['base'] : 0;

        $min = max( 0, min( $min, 4000 ) );
        $max = max( 0, min( $max, 4000 ) );

        if ( $min <= 0 ) {
            $min = $defaults['min'];
        }
        if ( $max <= 0 ) {
            $max = $defaults['max'];
        }
        if ( $min > $max ) {
            $min = $max;
        }

        if ( $base > 0 ) {
            $base = max( 640, min( $base, 1440 ) );
        } else {
            $base = 0;
        }

        return [
            'min' => $min,
            'max' => $max,
            'base' => $base,
        ];
    }

    public function sanitize_table_breakpoints( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }

        $devices = [ 'big', 'desktop', 'laptop', 'tablet', 'mobile' ];
        $keys    = [ 'table_max', 'card_min', 'card_min_h' ];
        $clean   = [];

        foreach ( $devices as $device ) {
            if ( empty( $value[ $device ] ) || ! is_array( $value[ $device ] ) ) {
                continue;
            }

            $device_values = [];
            foreach ( $keys as $key ) {
                if ( ! isset( $value[ $device ][ $key ] ) ) {
                    continue;
                }
                $raw = (int) $value[ $device ][ $key ];
                if ( $raw <= 0 ) {
                    continue;
                }
                $device_values[ $key ] = max( 0, min( $raw, 4000 ) );
            }

            if ( ! empty( $device_values ) ) {
                $clean[ $device ] = $device_values;
            }
        }

        return $clean;
    }

    public function sanitize_badges( $value ) {
        $dimensions = [ 'period', 'location', 'platform' ];
        $clean      = [];

        if ( ! is_array( $value ) ) {
            $value = [];
        }

        foreach ( $dimensions as $dimension ) {
            $items = isset( $value[ $dimension ] ) && is_array( $value[ $dimension ] ) ? $value[ $dimension ] : [];
            $clean[ $dimension ] = [];

            foreach ( $items as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }

                $slug  = sanitize_title( $item['slug'] ?? '' );
                $label = sanitize_text_field( $item['label'] ?? '' );
                $color = $this->sanitize_hex( $item['color'] ?? '' );
                $text  = $this->sanitize_hex( $item['text_color'] ?? '' );
                $icon  = sanitize_text_field( $item['icon'] ?? '' );
                $tone  = sanitize_key( $item['tone'] ?? '' );
                $start = $this->sanitize_date( $item['start'] ?? '' );
                $end   = $this->sanitize_date( $item['end'] ?? '' );

                if ( $slug === '' || $label === '' ) {
                    continue;
                }

                $clean[ $dimension ][] = [
                    'slug'       => $slug,
                    'label'      => $label,
                    'color'      => $color,
                    'text_color' => $text,
                    'icon'       => $icon,
                    'tone'       => in_array( $tone, [ 'success', 'info', 'warning', 'danger', 'neutral' ], true ) ? $tone : '',
                    'start'      => $start,
                    'end'        => $end,
                ];
            }
        }

        $shadow = isset( $value['shadow'] ) ? (int) $value['shadow'] : 0;
        $shadow = max( 0, min( $shadow, 60 ) );

        $priority = isset( $value['priority'] ) && is_array( $value['priority'] )
            ? array_values( array_intersect( array_map( 'sanitize_key', $value['priority'] ), $dimensions ) )
            : [];

        if ( empty( $priority ) ) {
            $priority = [ 'period', 'location', 'platform' ];
        }

        // Ensure we always include every dimension in deterministic order.
        $priority = array_values( array_unique( array_merge( $priority, $dimensions ) ) );

        $clean['priority'] = $priority;
        $clean['shadow']   = $shadow;

        return $clean;
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
                'cta_label'  => sanitize_text_field( $item['cta_label'] ?? '' ),
                'cta_url'    => esc_url_raw( $item['cta_url'] ?? '' ),
                'target'     => in_array( $item['target'] ?? '', [ '_blank', '_self' ], true ) ? $item['target'] : '',
                'rel'        => sanitize_text_field( $item['rel'] ?? '' ),
            ];
            if ( $variant['price'] === '' && $variant['sale_price'] === '' && $variant['cta_url'] === '' ) {
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

    private function sanitize_hex( $value ) {
        $value = sanitize_text_field( $value );
        $hex   = sanitize_hex_color( $value );
        return $hex ? $hex : '';
    }

    private function sanitize_date( $value ) {
        $value = sanitize_text_field( $value );
        if ( $value === '' ) {
            return '';
        }
        $timestamp = strtotime( $value );
        if ( ! $timestamp ) {
            return '';
        }
        return gmdate( 'Y-m-d', $timestamp );
    }
}
