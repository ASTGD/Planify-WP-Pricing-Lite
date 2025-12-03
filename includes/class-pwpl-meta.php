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
    const PLAN_HERO_IMAGE         = '_pwpl_plan_hero_image';
    const PLAN_FEATURED           = '_pwpl_featured';
    const PLAN_BADGE_SHADOW       = '_pwpl_badge_shadow';
    const TABLE_SIZE              = '_pwpl_table_size';
    const TABLE_BREAKPOINTS       = '_pwpl_table_breakpoints';
    const PLAN_BADGES_OVERRIDE    = '_pwpl_badges_override';
    const TABLE_BADGES            = '_pwpl_badges';
    const TABLE_THEME             = '_pwpl_table_theme';
    const TABLE_LAYOUT_TYPE       = '_pwpl_table_layout_type';
    const TABLE_PRESET            = '_pwpl_table_preset';
    const TABLE_HEIGHT            = '_pwpl_table_height';
    const LAYOUT_WIDTHS           = '_pwpl_layout_widths';
    const LAYOUT_COLUMNS          = '_pwpl_layout_columns';
    const LAYOUT_CARD_WIDTHS      = '_pwpl_layout_card_widths';
    const LAYOUT_GAP_X            = '_pwpl_layout_gap_x';
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
    const CARD_CONFIG             = '_pwpl_card';

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

        register_post_meta( 'pwpl_table', self::TABLE_LAYOUT_TYPE, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_layout_type' ],
            'show_in_rest'      => false,
        ] );

        register_post_meta( 'pwpl_table', self::TABLE_PRESET, [
            'single'            => true,
            'type'              => 'string',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_preset' ],
            'show_in_rest'      => false,
        ] );

        // Column gap (gutter) between plan cards — global scalar in px
        register_post_meta( 'pwpl_table', self::LAYOUT_GAP_X, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) {
                $n = (int) $value; if ( $n < 0 ) $n = 0; if ( $n > 96 ) $n = 96; return $n; },
            'show_in_rest'      => false,
        ] );

        // Table height (global)
        register_post_meta( 'pwpl_table', self::TABLE_HEIGHT, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => function( $value ) { $n = (int) $value; if ( $n < 0 ) $n = 0; if ( $n > 4000 ) $n = 4000; return $n; },
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

        register_post_meta( 'pwpl_table', self::CARD_CONFIG, [
            'single'            => true,
            'type'              => 'array',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => [ $this, 'sanitize_card_config' ],
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

        register_post_meta( 'pwpl_plan', self::PLAN_HERO_IMAGE, [
            'single'            => true,
            'type'              => 'integer',
            'auth_callback'     => [ $this, 'can_edit' ],
            'sanitize_callback' => 'absint',
            'show_in_rest'      => true,
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
        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }
        // Prefer FireVPS as a modern default if bundled/overridden; else fallback to classic
        return in_array( 'firevps', $allowed, true ) ? 'firevps' : 'classic';
    }

    public function sanitize_layout_type( $value ) {
        $value   = is_string( $value ) ? sanitize_key( $value ) : '';
        $allowed = [ 'grid' ];
        return in_array( $value, $allowed, true ) ? $value : 'grid';
    }

    public function sanitize_preset( $value ) {
        return is_string( $value ) ? sanitize_key( $value ) : '';
    }

    public function sanitize_feature_flag( $value ) {
        return (bool) $value;
    }

    public function sanitize_card_config( $value ) {
        $value = is_array( $value ) ? $value : [];
        $out   = [];
        

        $layout_input = isset( $value['layout'] ) && is_array( $value['layout'] ) ? $value['layout'] : [];
        $layout_clean = [];

        if ( array_key_exists( 'radius', $layout_input ) ) {
            $radius_raw = $layout_input['radius'];
            if ( $radius_raw !== '' && null !== $radius_raw ) {
                $radius = (int) $radius_raw;
                $layout_clean['radius'] = max( 0, min( 24, $radius ) );
            }
        }

        // Optional card border width (px)
        if ( array_key_exists( 'border_w', $layout_input ) ) {
            $bw_raw = $layout_input['border_w'];
            if ( $bw_raw !== '' && null !== $bw_raw ) {
                $bw = (float) $bw_raw;
                // clamp to sensible range
                if ( $bw < 0 ) { $bw = 0; }
                if ( $bw > 12 ) { $bw = 12; }
                // Keep one decimal place max
                $layout_clean['border_w'] = round( $bw, 1 );
            }
        }

        foreach ( [ 'pad_t', 'pad_r', 'pad_b', 'pad_l' ] as $pad_key ) {
            if ( ! array_key_exists( $pad_key, $layout_input ) ) {
                continue;
            }
            $pad_raw = $layout_input[ $pad_key ];
            if ( $pad_raw === '' || null === $pad_raw ) {
                continue;
            }
            $pad = (int) $pad_raw;
            $layout_clean[ $pad_key ] = max( 0, min( 32, $pad ) );
        }

        // Optional card height (px)
        if ( array_key_exists( 'height', $layout_input ) ) {
            $h_raw = $layout_input['height'];
            if ( $h_raw !== '' && null !== $h_raw ) {
                $h = (int) $h_raw;
                // clamp to reasonable bounds
                if ( $h < 0 ) { $h = 0; }
                if ( $h > 2000 ) { $h = 2000; }
                $layout_clean['height'] = $h;
            }
        }

        if ( array_key_exists( 'split', $layout_input ) ) {
            $split = sanitize_key( $layout_input['split'] );
            if ( in_array( $split, [ 'two_tone' ], true ) ) {
                $layout_clean['split'] = $split;
            }
        }

        if ( ! empty( $layout_clean ) ) {
            $out['layout'] = $layout_clean;
        }

        $color_input = isset( $value['colors'] ) && is_array( $value['colors'] ) ? $value['colors'] : [];
        $colors_clean = [];
        foreach ( [ 'top_bg', 'header_bg', 'cta_bg', 'specs_bg', 'specs_text', 'border' ] as $color_key ) {
            if ( ! array_key_exists( $color_key, $color_input ) ) {
                continue;
            }
            $color_value = $this->sanitize_color_token( $color_input[ $color_key ] );
            if ( $color_value !== '' ) {
                $colors_clean[ $color_key ] = $color_value;
            }
        }

        // Helper to sanitize gradient object
        $sanitize_grad = function( $grad_input ) {
            $grad_input = is_array( $grad_input ) ? $grad_input : [];
            $gc = [];
            if ( array_key_exists( 'type', $grad_input ) ) {
                $type = sanitize_key( $grad_input['type'] );
                if ( in_array( $type, [ 'linear', 'radial', 'conic' ], true ) ) {
                    $gc['type'] = $type;
                }
            }
            if ( array_key_exists( 'start', $grad_input ) ) {
                $start = $this->sanitize_color_token( $grad_input['start'] );
                if ( $start !== '' ) { $gc['start'] = $start; }
            }
            if ( array_key_exists( 'end', $grad_input ) ) {
                $end = $this->sanitize_color_token( $grad_input['end'] );
                if ( $end !== '' ) { $gc['end'] = $end; }
            }
            if ( array_key_exists( 'start_pos', $grad_input ) ) {
                $sp = (int) $grad_input['start_pos'];
                $gc['start_pos'] = max( 0, min( 100, $sp ) );
            }
            if ( array_key_exists( 'end_pos', $grad_input ) ) {
                $ep = (int) $grad_input['end_pos'];
                $gc['end_pos'] = max( 0, min( 100, $ep ) );
            }
            if ( array_key_exists( 'angle', $grad_input ) ) {
                $angle = (int) $grad_input['angle'];
                $gc['angle'] = max( 0, min( 360, $angle ) );
            }
            if ( empty( $gc['type'] ) || empty( $gc['start'] ) || empty( $gc['end'] ) ) {
                return [];
            }
            return $gc;
        };

        $grad_clean = $sanitize_grad( isset( $color_input['specs_grad'] ) ? $color_input['specs_grad'] : [] );
        if ( ! empty( $grad_clean ) ) { $colors_clean['specs_grad'] = $grad_clean; }

        $cta_grad_clean = $sanitize_grad( isset( $color_input['cta_grad'] ) ? $color_input['cta_grad'] : [] );
        if ( ! empty( $cta_grad_clean ) ) { $colors_clean['cta_grad'] = $cta_grad_clean; }

        $top_grad_clean = $sanitize_grad( isset( $color_input['top_grad'] ) ? $color_input['top_grad'] : [] );
        if ( ! empty( $top_grad_clean ) ) { $colors_clean['top_grad'] = $top_grad_clean; }

        $keyline_input = isset( $color_input['keyline'] ) && is_array( $color_input['keyline'] ) ? $color_input['keyline'] : [];
        $keyline_clean = [];
        if ( array_key_exists( 'color', $keyline_input ) ) {
            $color = $this->sanitize_color_token( $keyline_input['color'] );
            if ( $color !== '' ) {
                $keyline_clean['color'] = $color;
            }
        }
        if ( array_key_exists( 'opacity', $keyline_input ) ) {
            $opacity = is_numeric( $keyline_input['opacity'] ) ? (float) $keyline_input['opacity'] : null;
            if ( null !== $opacity ) {
                $opacity = max( 0, min( 1, $opacity ) );
                $keyline_clean['opacity'] = round( $opacity, 3 );
            }
        }
        if ( ! empty( $keyline_clean ) ) {
            $colors_clean['keyline'] = $keyline_clean;
        }

        if ( ! empty( $colors_clean ) ) {
            $out['colors'] = $colors_clean;
        }

        $typo_input = isset( $value['typo'] ) && is_array( $value['typo'] ) ? $value['typo'] : [];
        $typo_clean = [];
        $typo_ranges = [
            'title'    => [ 'size' => [ 16, 36 ], 'weight' => [ 600, 800 ] ],
            'subtitle' => [ 'size' => [ 12, 18 ], 'weight' => [ 400, 600 ] ],
            'price'    => [ 'size' => [ 24, 44 ], 'weight' => [ 700, 900 ] ],
        ];
        foreach ( $typo_ranges as $slug => $limits ) {
            $entry_input = isset( $typo_input[ $slug ] ) && is_array( $typo_input[ $slug ] ) ? $typo_input[ $slug ] : [];
            $entry_clean = [];

            if ( array_key_exists( 'size', $entry_input ) && $entry_input['size'] !== '' ) {
                $size = (int) $entry_input['size'];
                $entry_clean['size'] = max( $limits['size'][0], min( $limits['size'][1], $size ) );
            }
            if ( array_key_exists( 'weight', $entry_input ) && $entry_input['weight'] !== '' ) {
                $weight = (int) $entry_input['weight'];
                $entry_clean['weight'] = max( $limits['weight'][0], min( $limits['weight'][1], $weight ) );
            }

            // Optional text alignment for supported areas
            if ( array_key_exists( 'align', $entry_input ) ) {
                $align = is_string( $entry_input['align'] ) ? strtolower( trim( $entry_input['align'] ) ) : '';
                $allowed_align = [ 'left', 'center', 'right' ];
                if ( in_array( $align, $allowed_align, true ) ) {
                    $entry_clean['align'] = $align;
                }
            }

            if ( 'title' === $slug ) {
                $entry_clean['shadow_enable'] = ! empty( $entry_input['shadow_enable'] ) ? '1' : '';
                if ( array_key_exists( 'shadow_x', $entry_input ) && $entry_input['shadow_x'] !== '' ) {
                    $x = (int) $entry_input['shadow_x'];
                    $entry_clean['shadow_x'] = max( -50, min( 50, $x ) );
                }
                if ( array_key_exists( 'shadow_y', $entry_input ) && $entry_input['shadow_y'] !== '' ) {
                    $y = (int) $entry_input['shadow_y'];
                    $entry_clean['shadow_y'] = max( -50, min( 50, $y ) );
                }
                if ( array_key_exists( 'shadow_blur', $entry_input ) && $entry_input['shadow_blur'] !== '' ) {
                    $blur = (int) $entry_input['shadow_blur'];
                    $entry_clean['shadow_blur'] = max( 0, min( 100, $blur ) );
                }
                if ( array_key_exists( 'shadow_color', $entry_input ) ) {
                    $color = $this->sanitize_color_token( $entry_input['shadow_color'] );
                    if ( $color !== '' ) {
                        $entry_clean['shadow_color'] = $color;
                    }
                }
                if ( array_key_exists( 'shadow_style', $entry_input ) ) {
                    $style = sanitize_key( $entry_input['shadow_style'] );
                    $allowed = [ 'custom', 'none', 'soft', 'medium', 'deep', 'glow', 'long' ];
                    if ( in_array( $style, $allowed, true ) ) {
                        $entry_clean['shadow_style'] = $style;
                    }
                }
            }

            if ( ! empty( $entry_clean ) ) {
                $typo_clean[ $slug ] = $entry_clean;
            }
        }
        if ( ! empty( $typo_clean ) ) {
            $out['typo'] = $typo_clean;
        }

        // Text styles (Top and Specs)
        $text_input = isset( $value['text'] ) && is_array( $value['text'] ) ? $value['text'] : [];
        $text_clean = [];
        foreach ( [ 'top', 'specs' ] as $area ) {
            $entry = isset( $text_input[ $area ] ) && is_array( $text_input[ $area ] ) ? $text_input[ $area ] : [];
            $clean = [];
            if ( array_key_exists( 'color', $entry ) ) {
                $col = $this->sanitize_color_token( $entry['color'] );
                if ( $col !== '' ) { $clean['color'] = $col; }
            }
            if ( array_key_exists( 'family', $entry ) ) {
                $family = sanitize_text_field( trim( (string) $entry['family'] ) );
                if ( $family !== '' ) {
                    $clean['family'] = substr( $family, 0, 200 );
                }
            }
            if ( array_key_exists( 'size', $entry ) && $entry['size'] !== '' ) {
                $size = (int) $entry['size'];
                $clean['size'] = max( 10, min( 28, $size ) );
            }
            if ( array_key_exists( 'weight', $entry ) && $entry['weight'] !== '' ) {
                $weight = (int) $entry['weight'];
                $clean['weight'] = max( 300, min( 900, $weight ) );
            }
            if ( ! empty( $clean ) ) {
                $text_clean[ $area ] = $clean;
            }
        }
        if ( ! empty( $text_clean ) ) {
            $out['text'] = $text_clean;
        }

        if ( array_key_exists( 'preset', $value ) ) {
            $preset = sanitize_key( $value['preset'] );
            if ( in_array( $preset, [ 'classic', 'warm', 'minimal' ], true ) ) {
                $out['preset'] = $preset;
            }
        }

        return $out;
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
            $icon = '';
            if ( ! empty( $item['icon'] ) && is_string( $item['icon'] ) ) {
                $icon = sanitize_key( $item['icon'] );
            }
            $slug = '';
            if ( ! empty( $item['slug'] ) && is_string( $item['slug'] ) ) {
                $slug = sanitize_key( $item['slug'] );
            }
            $clean[] = [
                'label' => $label,
                'value' => $val,
                'icon'  => $icon,
                'slug'  => $slug,
            ];
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

    private function sanitize_color_token( $value ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        if ( $value === '' ) {
            return '';
        }

        $hex = sanitize_hex_color( $value );
        if ( $hex ) {
            return $hex;
        }

        $sanitized = sanitize_text_field( $value );
        if ( $sanitized === '' ) {
            return '';
        }

        // Allow reasonably long gradient strings and CSS color tokens
        return substr( $sanitized, 0, 512 );
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
