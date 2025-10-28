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
    const LAYOUT_WIDTHS           = '_pwpl_layout_widths';
    const LAYOUT_COLUMNS          = '_pwpl_layout_columns';
    const LAYOUT_CARD_WIDTHS      = '_pwpl_layout_card_widths';
    const TABS_GLASS              = '_pwpl_tabs_glass';
    const TABS_GLASS_TINT         = '_pwpl_tabs_glass_tint';
    const TABS_GLASS_INTENSITY    = '_pwpl_tabs_glass_intensity';
    const TABS_GLASS_FROST        = '_pwpl_tabs_glass_frost';
    const CARDS_GLASS             = '_pwpl_cards_glass';
    const SPECS_STYLE             = '_pwpl_specs_style';
    // Specs interactions
    const SPECS_ANIM_PRESET       = '_pwpl_specs_anim_preset';
    const SPECS_ANIM_FLAGS        = '_pwpl_specs_anim_flags';
    const SPECS_ANIM_INTENSITY    = '_pwpl_specs_anim_intensity';
    const SPECS_ANIM_MOBILE       = '_pwpl_specs_anim_mobile';
    // CTA/trust + sticky bar
    const TRUST_TRIO_ENABLED      = '_pwpl_trust_trio_enabled';
    const STICKY_CTA_MOBILE       = '_pwpl_sticky_cta_mobile';
    const TRUST_ITEMS             = '_pwpl_trust_items';
    const CTA_CONFIG              = '_pwpl_cta';

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

        // CTA configuration (table-level)
        register_post_meta( 'pwpl_table', self::CTA_CONFIG, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $v = is_array( $value ) ? $value : [];
                $out = [];
                $out['width']  = in_array( $v['width'] ?? 'full', [ 'auto','full' ], true ) ? $v['width'] : 'full';
                $out['height'] = max( 36, min( 64, (int) ( $v['height'] ?? 48 ) ) );
                $out['pad_x']  = max( 10, min( 32, (int) ( $v['pad_x'] ?? 22 ) ) );
                $out['radius'] = max( 0, min( 999, (int) ( $v['radius'] ?? 12 ) ) );
                $bw = isset( $v['border_width'] ) ? (float) $v['border_width'] : 1.5;
                $out['border_width'] = max( 0, min( 4, $bw ) );
                $out['weight'] = max( 500, min( 900, (int) ( $v['weight'] ?? 700 ) ) );
                $out['lift']   = max( 0, min( 3, (int) ( $v['lift'] ?? 1 ) ) );
                $out['focus']  = (string) ( $v['focus'] ?? '' );
                $out['min_w']  = max( 0, min( 4000, (int) ( $v['min_w'] ?? 0 ) ) );
                $out['max_w']  = max( 0, min( 4000, (int) ( $v['max_w'] ?? 0 ) ) );
                $out['normal'] = [
                    'bg'     => (string) ( $v['normal']['bg'] ?? '' ),
                    'color'  => (string) ( $v['normal']['color'] ?? '' ),
                    'border' => (string) ( $v['normal']['border'] ?? '' ),
                ];
                $out['hover'] = [
                    'bg'     => (string) ( $v['hover']['bg'] ?? '' ),
                    'color'  => (string) ( $v['hover']['color'] ?? '' ),
                    'border' => (string) ( $v['hover']['border'] ?? '' ),
                ];
                // Normalize tracking: accept numeric and append em
                $tracking_raw = isset( $v['font']['tracking'] ) ? trim( (string) $v['font']['tracking'] ) : '';
                if ( $tracking_raw !== '' && preg_match( '/^[-+]?[0-9]*\.?[0-9]+$/', $tracking_raw ) ) {
                    $tracking_raw .= 'em';
                }
                $out['font'] = [
                    'family'    => (string) ( $v['font']['family'] ?? '' ),
                    'size'      => max( 10, min( 28, (int) ( $v['font']['size'] ?? 0 ) ) ),
                    'transform' => in_array( $v['font']['transform'] ?? 'none', [ 'none', 'uppercase' ], true ) ? $v['font']['transform'] : 'none',
                    'tracking'  => $tracking_raw,
                ];
                return $out;
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::LAYOUT_WIDTHS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_layout_widths' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::LAYOUT_COLUMNS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_layout_cards' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::LAYOUT_CARD_WIDTHS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_layout_card_widths' ],
            'show_in_rest'      => false,
        ] );

        // UI toggles
        register_post_meta( 'pwpl_table', self::TABS_GLASS, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { return ! empty( $value ) ? 1 : 0; },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABS_GLASS_TINT, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $value = is_string( $value ) ? trim( $value ) : '';
                $hex   = sanitize_hex_color( $value );
                return $hex ?: '';
            },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABS_GLASS_INTENSITY, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $n = (int) $value; if ( $n < 0 ) $n = 0; if ( $n > 100 ) $n = 100; return $n; },
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABS_GLASS_FROST, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $n = (int) $value; if ( $n < 0 ) $n = 0; if ( $n > 24 ) $n = 24; return $n; },
            'show_in_rest'      => false,
        ] );

        // Enable glass treatment for plan cards container
        register_post_meta( 'pwpl_table', self::CARDS_GLASS, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { return ! empty( $value ) ? 1 : 0; },
            'show_in_rest'      => false,
        ] );

        // Specs list style selector (flat | segmented | chips | default)
        register_post_meta( 'pwpl_table', self::SPECS_STYLE, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $v = is_string( $value ) ? sanitize_key( $value ) : '';
                $allowed = [ 'default', 'flat', 'segmented', 'chips' ];
                return in_array( $v, $allowed, true ) ? $v : 'default';
            },
            'show_in_rest'      => false,
        ] );

        // Specs interactions preset
        register_post_meta( 'pwpl_table', self::SPECS_ANIM_PRESET, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $v = is_string( $value ) ? sanitize_key( $value ) : '';
                $allowed = [ 'off', 'minimal', 'segmented', 'chips', 'all' ];
                return in_array( $v, $allowed, true ) ? $v : 'minimal';
            },
            'show_in_rest'      => false,
        ] );

        // Specs interactions flags (row, icon, divider, chip, stagger)
        register_post_meta( 'pwpl_table', self::SPECS_ANIM_FLAGS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $allowed = [ 'row', 'icon', 'divider', 'chip', 'stagger' ];
                if ( ! is_array( $value ) ) { return []; }
                $list = array_map( 'sanitize_key', $value );
                $list = array_values( array_intersect( $list, $allowed ) );
                return $list;
            },
            'show_in_rest'      => false,
        ] );

        // Intensity: 0-100
        register_post_meta( 'pwpl_table', self::SPECS_ANIM_INTENSITY, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { $n = (int) $value; if ( $n < 0 ) $n = 0; if ( $n > 100 ) $n = 100; return $n; },
            'show_in_rest'      => false,
        ] );

        // Enable on touch devices
        register_post_meta( 'pwpl_table', self::SPECS_ANIM_MOBILE, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { return ! empty( $value ) ? 1 : 0; },
            'show_in_rest'      => false,
        ] );

        // Trust trio under CTA (money-back, uptime, support)
        register_post_meta( 'pwpl_table', self::TRUST_TRIO_ENABLED, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { return ! empty( $value ) ? 1 : 0; },
            'show_in_rest'      => false,
        ] );

        // Sticky mobile summary bar
        register_post_meta( 'pwpl_table', self::STICKY_CTA_MOBILE, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { return ! empty( $value ) ? 1 : 0; },
            'show_in_rest'      => false,
        ] );

        // Trust row custom items (array of non-empty strings)
        register_post_meta( 'pwpl_table', self::TRUST_ITEMS, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $items = [];
                if ( is_array( $value ) ) {
                    foreach ( $value as $v ) {
                        $label = trim( wp_strip_all_tags( (string) $v ) );
                        if ( $label !== '' ) { $items[] = $label; }
                    }
                }
                return array_values( $items );
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

        if ( class_exists( 'PWPL_Theme_Loader' ) ) {
            $loader = new PWPL_Theme_Loader();
            foreach ( $loader->get_available_themes() as $theme ) {
                if ( ! empty( $theme['slug'] ) ) {
                    $allowed[] = $theme['slug'];
                }
            }
        }

        $allowed = array_unique( $allowed );
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
            $base = max( 640, min( $base, 4000 ) );
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

    public function sanitize_layout_widths( $value ) {
        $keys = [ 'global', 'sm', 'md', 'lg', 'xl', 'xxl' ];
        $clean = [];

        if ( ! is_array( $value ) ) {
            $value = [];
        }

        foreach ( $keys as $key ) {
            $raw = isset( $value[ $key ] ) ? (int) $value[ $key ] : 0;
            if ( $raw > 0 ) {
                $raw = max( 640, min( $raw, 4000 ) );
            } else {
                $raw = 0;
            }
            $clean[ $key ] = $raw;
        }

        return $clean;
    }

    public function sanitize_layout_cards( $value ) {
        $keys = [ 'global', 'sm', 'md', 'lg', 'xl', 'xxl' ];
        $clean = [];

        if ( ! is_array( $value ) ) {
            $value = [];
        }

        foreach ( $keys as $key ) {
            $raw = isset( $value[ $key ] ) ? (int) $value[ $key ] : 0;
            if ( $raw > 0 ) {
                $raw = max( 1, min( $raw, 20 ) );
            } else {
                $raw = 0;
            }
            $clean[ $key ] = $raw;
        }

        return $clean;
    }

    public function sanitize_layout_card_widths( $value ) {
        $keys = [ 'global', 'sm', 'md', 'lg', 'xl', 'xxl' ];
        $clean = [];

        if ( ! is_array( $value ) ) {
            $value = [];
        }

        foreach ( $keys as $key ) {
            $raw = isset( $value[ $key ] ) ? (int) $value[ $key ] : 0;
            if ( $raw > 0 ) {
                $raw = max( 1, min( $raw, 4000 ) );
            } else {
                $raw = 0;
            }
            $clean[ $key ] = $raw;
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
                'unavailable'=> ! empty( $item['unavailable'] ) ? 1 : 0,
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
