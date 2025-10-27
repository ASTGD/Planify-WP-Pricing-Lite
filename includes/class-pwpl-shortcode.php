<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Shortcode {
    private $settings;
    private $theme_loader;
    private $enqueued_themes = [];

    public function __construct() {
        $this->settings      = new PWPL_Settings();
        $this->theme_loader  = new PWPL_Theme_Loader();
    }

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
        $css_themes = PWPL_DIR . 'assets/css/themes.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'pwpl-frontend', PWPL_URL . 'assets/css/frontend.css', [], filemtime( $css ) );
        }
        if ( file_exists( $css_themes ) ) {
            wp_enqueue_style( 'pwpl-frontend-themes', PWPL_URL . 'assets/css/themes.css', [ 'pwpl-frontend' ], filemtime( $css_themes ) );
        }
        if ( file_exists( $js ) ) {
            wp_enqueue_script( 'pwpl-frontend', PWPL_URL . 'assets/js/frontend.js', [], filemtime( $js ), true );
        }

        $settings = $this->settings->get();

        wp_localize_script( 'pwpl-frontend', 'PWPL_Frontend', [
            'currency' => [
                'symbol'        => $settings['currency_symbol'],
                'position'      => $settings['currency_position'],
                'thousand_sep'  => $settings['thousand_sep'],
                'decimal_sep'   => $settings['decimal_sep'],
                'price_decimals'=> (int) $settings['price_decimals'],
            ],
        ] );

        $dimensions = get_post_meta( $table_id, PWPL_Meta::DIMENSION_META, true );
        if ( ! is_array( $dimensions ) ) {
            $dimensions = [];
        }
        $dimensions = array_values( array_intersect( $dimensions, [ 'platform', 'period', 'location' ] ) );

        $allowed = [
            'platform' => get_post_meta( $table_id, PWPL_Meta::ALLOWED_PLATFORMS, true ),
            'period'   => get_post_meta( $table_id, PWPL_Meta::ALLOWED_PERIODS, true ),
            'location' => get_post_meta( $table_id, PWPL_Meta::ALLOWED_LOCATIONS, true ),
        ];

        $catalog = [
            'platform' => $this->index_by_slug( (array) $settings['platforms'] ),
            'period'   => $this->index_by_slug( (array) $settings['periods'] ),
            'location' => $this->index_by_slug( (array) $settings['locations'] ),
        ];

        $dimension_values = [];
        $active_values    = [];
        foreach ( [ 'platform', 'period', 'location' ] as $dimension ) {
            if ( ! in_array( $dimension, $dimensions, true ) ) {
                continue;
            }
            $allowed_slugs = array_filter( (array) $allowed[ $dimension ] );
            if ( empty( $allowed_slugs ) ) {
                $allowed_slugs = array_keys( $catalog[ $dimension ] );
            }
            $values = [];
            foreach ( $allowed_slugs as $slug ) {
                if ( isset( $catalog[ $dimension ][ $slug ] ) ) {
                    $values[] = $catalog[ $dimension ][ $slug ];
                }
            }
            if ( empty( $values ) ) {
                continue;
            }
            $dimension_values[ $dimension ] = $values;
            $active_values[ $dimension ]    = $values[0]['slug'];
        }

        $plans = get_posts( [
            'post_type'      => 'pwpl_plan',
            'posts_per_page' => -1,
            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => PWPL_Meta::PLAN_TABLE_ID,
                    'value' => $table_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ],
            ],
        ] );

        if ( empty( $plans ) ) {
            return '<div class="pwpl-table">' . esc_html__( 'No plans found for this table yet.', 'planify-wp-pricing-lite' ) . '</div>';
        }

        $table_title = get_the_title( $table_id );
        $table_title = $table_title ? esc_html( $table_title ) : esc_html__( 'Pricing Table', 'planify-wp-pricing-lite' );

        $dimension_labels = [];
        foreach ( $dimension_values as $dimension => $values ) {
            foreach ( $values as $item ) {
                $dimension_labels[ $dimension ][ $item['slug'] ] = $item['label'];
            }
        }

        $meta_helper  = new PWPL_Meta();
        $table_badges = get_post_meta( $table_id, PWPL_Meta::TABLE_BADGES, true );
        if ( ! is_array( $table_badges ) ) {
            $table_badges = [];
        }

        $table_badges_json = wp_json_encode( $table_badges, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        if ( false === $table_badges_json ) {
            $table_badges_json = '{}';
        }

        $dimension_labels_json = wp_json_encode( $dimension_labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        if ( false === $dimension_labels_json ) {
            $dimension_labels_json = '{}';
        }

        $table_theme = get_post_meta( $table_id, PWPL_Meta::TABLE_THEME, true );
        $table_theme = $meta_helper->sanitize_theme( $table_theme ?: 'classic' );

        $table_theme_package = $this->enqueue_theme_assets( $table_theme );
        $table_manifest      = is_array( $table_theme_package['manifest'] ?? null ) ? $table_theme_package['manifest'] : [];

        $table_classes = [
            'pwpl-table',
            'pwpl-table--theme-' . $table_theme,
        ];

        if ( ! empty( $table_manifest['containerClass'] ) ) {
            $table_classes[] = $table_manifest['containerClass'];
        }

        $table_classes   = array_filter( array_unique( $table_classes ) );
        $table_classes   = array_map( 'sanitize_html_class', $table_classes );
        $table_class_attr = implode( ' ', array_filter( $table_classes ) );

        $platform_allowed_order = [];
        $initial_platform = '';
        $platform_filtering_enabled = isset( $dimension_values['platform'] );
        if ( $platform_filtering_enabled ) {
            foreach ( (array) $dimension_values['platform'] as $platform_item ) {
                if ( empty( $platform_item['slug'] ) ) {
                    continue;
                }
                $platform_allowed_order[] = sanitize_title( $platform_item['slug'] );
            }
            $platform_allowed_order = array_values( array_unique( array_filter( $platform_allowed_order ) ) );
            if ( $platform_allowed_order ) {
                $initial_platform = $platform_allowed_order[0];
            }
        }

        $availability_periods   = [];
        $availability_locations = [];
        $plan_variants_cache    = [];

        foreach ( $plans as $plan_item ) {
            $variants = get_post_meta( $plan_item->ID, PWPL_Meta::PLAN_VARIANTS, true );
            if ( ! is_array( $variants ) ) {
                $variants = [];
            }
            $plan_variants_cache[ $plan_item->ID ] = $variants;

            if ( ! $platform_filtering_enabled ) {
                continue;
            }

            foreach ( (array) $variants as $variant_entry ) {
                if ( ! is_array( $variant_entry ) ) {
                    continue;
                }
                $variant_platform = isset( $variant_entry['platform'] ) ? sanitize_title( $variant_entry['platform'] ) : '';
                $target_platforms = [];
                if ( $variant_platform ) {
                    $target_platforms = [ $variant_platform ];
                } else {
                    $target_platforms = $platform_allowed_order ? $platform_allowed_order : array_keys( $availability_periods );
                }
                if ( empty( $target_platforms ) ) {
                    continue;
                }

                $variant_period   = isset( $variant_entry['period'] ) ? sanitize_title( $variant_entry['period'] ) : '';
                $variant_location = isset( $variant_entry['location'] ) ? sanitize_title( $variant_entry['location'] ) : '';

                foreach ( $target_platforms as $platform_slug ) {
                    if ( ! $platform_slug ) {
                        continue;
                    }
                    if ( ! isset( $availability_periods[ $platform_slug ] ) ) {
                        $availability_periods[ $platform_slug ]   = [];
                        $availability_locations[ $platform_slug ] = [];
                    }
                    if ( $variant_period && ! in_array( $variant_period, $availability_periods[ $platform_slug ], true ) ) {
                        $availability_periods[ $platform_slug ][] = $variant_period;
                    }
                    if ( $variant_location && ! in_array( $variant_location, $availability_locations[ $platform_slug ], true ) ) {
                        $availability_locations[ $platform_slug ][] = $variant_location;
                    }
                }
            }
        }

        $availability_payload = [];
        if ( $platform_filtering_enabled ) {
            $availability_payload = [
                'periodsByPlatform'   => [],
                'locationsByPlatform' => [],
            ];
            $platform_keys_for_payload = $platform_allowed_order ? $platform_allowed_order : array_unique( array_merge( array_keys( $availability_periods ), array_keys( $availability_locations ) ) );
            foreach ( $platform_keys_for_payload as $platform_key ) {
                if ( ! $platform_key ) {
                    continue;
                }
                $availability_payload['periodsByPlatform'][ $platform_key ]   = $availability_periods[ $platform_key ] ?? [];
                $availability_payload['locationsByPlatform'][ $platform_key ] = $availability_locations[ $platform_key ] ?? [];
            }
        }

        $size_meta  = get_post_meta( $table_id, PWPL_Meta::TABLE_SIZE, true );
        $size_values = $meta_helper->sanitize_table_size( is_array( $size_meta ) ? $size_meta : [] );

        $layout_widths_raw = get_post_meta( $table_id, PWPL_Meta::LAYOUT_WIDTHS, true );
        $layout_widths     = $meta_helper->sanitize_layout_widths( is_array( $layout_widths_raw ) ? $layout_widths_raw : [] );

        $layout_columns_raw = get_post_meta( $table_id, PWPL_Meta::LAYOUT_COLUMNS, true );
        $layout_columns     = $meta_helper->sanitize_layout_cards( is_array( $layout_columns_raw ) ? $layout_columns_raw : [] );

        $card_widths_raw = get_post_meta( $table_id, PWPL_Meta::LAYOUT_CARD_WIDTHS, true );
        $card_widths     = $meta_helper->sanitize_layout_card_widths( is_array( $card_widths_raw ) ? $card_widths_raw : [] );

        $breakpoint_meta    = get_post_meta( $table_id, PWPL_Meta::TABLE_BREAKPOINTS, true );
        $breakpoint_values  = $meta_helper->sanitize_table_breakpoints( is_array( $breakpoint_meta ) ? $breakpoint_meta : [] );

        if ( ! array_filter( $layout_widths ) ) {
            if ( ! empty( $size_values['base'] ) ) {
                $layout_widths['global'] = max( 640, min( (int) $size_values['base'], 4000 ) );
            } elseif ( ! empty( $size_values['max'] ) ) {
                $layout_widths['global'] = max( 640, min( (int) $size_values['max'], 4000 ) );
            }

            $legacy_map = [ 'big' => 'xxl', 'desktop' => 'xl', 'laptop' => 'lg', 'tablet' => 'md', 'mobile' => 'sm' ];
            foreach ( $legacy_map as $legacy_key => $target_key ) {
                if ( empty( $breakpoint_values[ $legacy_key ]['table_max'] ) ) {
                    continue;
                }
                $layout_widths[ $target_key ] = max( 640, min( (int) $breakpoint_values[ $legacy_key ]['table_max'], 4000 ) );
            }
        }

        if ( ! array_filter( $card_widths ) && ! empty( $breakpoint_values ) ) {
            foreach ( [ 'mobile' => 'sm', 'tablet' => 'md', 'laptop' => 'lg', 'desktop' => 'xl', 'big' => 'xxl' ] as $legacy_device => $layout_key ) {
                if ( empty( $breakpoint_values[ $legacy_device ]['card_min'] ) ) {
                    continue;
                }
                $card_widths[ $layout_key ] = max( 1, min( (int) $breakpoint_values[ $legacy_device ]['card_min'], 4000 ) );
            }
        }

        $badge_shadow = isset( $table_badges['shadow'] ) ? (int) $table_badges['shadow'] : 0;
        $badge_shadow = max( 0, min( $badge_shadow, 60 ) );

        $style_vars = [
            '--pwpl-table-min'   => '320px',
            '--pwpl-table-active'=> 'var(--pwpl-width-active)',
            '--pwpl-table-max'   => 'var(--pwpl-width-active)',
        ];

        if ( $badge_shadow > 0 ) {
            $style_vars['--pwpl-badge-shadow-strength'] = $badge_shadow;
        }

        if ( ! empty( $layout_widths['global'] ) ) {
            $style_vars['--pwpl-width-global'] = $layout_widths['global'] . 'px';
        }
        if ( ! empty( $layout_columns['global'] ) ) {
            $style_vars['--pwpl-columns-global'] = (int) $layout_columns['global'];
        }
        if ( ! empty( $card_widths['global'] ) ) {
            $style_vars['--pwpl-card-min-global'] = (int) $card_widths['global'] . 'px';
        }

        $device_suffix = [
            'sm'  => 'mobile',
            'md'  => 'tablet',
            'lg'  => 'laptop',
            'xl'  => 'desktop',
            'xxl' => 'big',
        ];

        foreach ( $device_suffix as $var_key => $legacy_key ) {
            $width_value = isset( $layout_widths[ $var_key ] ) ? (int) $layout_widths[ $var_key ] : 0;
            if ( $width_value > 0 ) {
                $style_vars[ '--pwpl-width-' . $var_key ] = $width_value . 'px';
                $style_vars[ '--pwpl-table-max-' . $var_key ] = 'var(--pwpl-width-' . $var_key . ')';
            }

            $column_value = isset( $layout_columns[ $var_key ] ) ? (int) $layout_columns[ $var_key ] : 0;
            if ( $column_value >= 1 ) {
                $style_vars[ '--pwpl-columns-' . $var_key ] = $column_value;
            }

            $card_width_value = isset( $card_widths[ $var_key ] ) ? (int) $card_widths[ $var_key ] : 0;
            if ( $card_width_value > 0 ) {
                $style_vars[ '--pwpl-card-min-' . $var_key ] = $card_width_value . 'px';
            }
        }

        // Legacy card height overrides
        foreach ( [ 'mobile' => 'sm', 'tablet' => 'md', 'laptop' => 'lg', 'desktop' => 'xl', 'big' => 'xxl' ] as $legacy_device => $suffix ) {
            if ( empty( $breakpoint_values[ $legacy_device ] ) ) {
                continue;
            }
            $values = $breakpoint_values[ $legacy_device ];
            if ( ! empty( $values['card_min_h'] ) ) {
                $style_vars[ '--pwpl-card-min-h-' . $suffix ] = (int) $values['card_min_h'] . 'px';
            }
        }

        $style_inline = '';
        $table_attr_extras = '';
        if ( $platform_filtering_enabled && $platform_allowed_order ) {
            $table_attr_extras .= ' data-allowed-platforms="' . esc_attr( implode( ',', $platform_allowed_order ) ) . '"';
            if ( $initial_platform ) {
                $table_attr_extras .= ' data-initial-platform="' . esc_attr( $initial_platform ) . '"';
            }
            if ( ! empty( $availability_payload ) ) {
                $availability_json = wp_json_encode( $availability_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                if ( false !== $availability_json ) {
                    $table_attr_extras .= ' data-availability="' . esc_attr( $availability_json ) . '"';
                }
            }
        }

        foreach ( $style_vars as $var => $value ) {
            if ( '' === $value && '0' !== $value ) {
                continue;
            }
            $style_inline .= $var . ':' . $value . ';';
        }

        $table_style_attr = $style_inline ? ' style="' . esc_attr( $style_inline ) . '"' : '';

        $template_rel = 'template.php';
        if ( ! empty( $table_manifest['template'] ) ) {
            $template_rel_candidate = ltrim( (string) $table_manifest['template'], '/' );
            if ( $template_rel_candidate ) {
                $template_rel = $template_rel_candidate;
            }
        }

        $template_path = function_exists( 'pwpl_locate_theme_file' ) ? pwpl_locate_theme_file( $table_theme, $template_rel ) : false;

        if ( $template_path ) {
            $table_subtitle_meta = get_post_meta( $table_id, '_pwpl_table_subtitle', true );
            $table_subtitle_raw  = is_string( $table_subtitle_meta ) ? trim( wp_strip_all_tags( $table_subtitle_meta ) ) : '';
            if ( '' === $table_subtitle_raw ) {
                $table_excerpt = get_post_field( 'post_excerpt', $table_id );
                if ( $table_excerpt ) {
                    $table_subtitle_raw = trim( wp_strip_all_tags( $table_excerpt ) );
                }
            }

            $allowed_context = [];
            foreach ( [ 'platform', 'period', 'location' ] as $dimension_key ) {
                $allowed_context[ $dimension_key ] = array_values( array_unique( array_filter( array_map(
                    'sanitize_title',
                    (array) ( $allowed[ $dimension_key ] ?? [] )
                ) ) ) );
            }

            $tabs_context = [];
            foreach ( [ 'platform', 'period', 'location' ] as $dimension_key ) {
                if ( empty( $dimension_values[ $dimension_key ] ) ) {
                    continue;
                }
                $tabs_context[ $dimension_key ] = [
                    'values' => array_map( function( $item ) {
                        $slug  = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
                        $label = isset( $item['label'] ) ? (string) $item['label'] : $slug;
                        return [
                            'slug'  => $slug,
                            'label' => $label,
                        ];
                    }, $dimension_values[ $dimension_key ] ),
                ];
            }

            $table_title_raw = get_post_field( 'post_title', $table_id );
            if ( '' === $table_title_raw ) {
                $table_title_raw = __( 'Pricing Table', 'planify-wp-pricing-lite' );
            }

            $table_context = [
                'id'               => $table_id,
                'theme'            => $table_theme,
                'title'            => $table_title_raw,
                'subtitle'         => $table_subtitle_raw,
                'manifest'         => $table_manifest,
                'badges'           => $table_badges,
                'dimension_labels' => $dimension_labels,
                'active'           => $active_values,
                'allowed'          => $allowed_context,
                'tabs'             => $tabs_context,
                'dimensions'       => $tabs_context,
                'availability'     => $availability_payload,
                'style'            => $style_inline,
                'badge_shadow'     => $badge_shadow,
                'tabs_glass'       => (bool) get_post_meta( $table_id, PWPL_Meta::TABS_GLASS, true ),
                'tabs_glass_tint'  => (string) get_post_meta( $table_id, PWPL_Meta::TABS_GLASS_TINT, true ),
                'tabs_glass_intensity' => (int) get_post_meta( $table_id, PWPL_Meta::TABS_GLASS_INTENSITY, true ),
                'tabs_glass_frost'     => (int) get_post_meta( $table_id, PWPL_Meta::TABS_GLASS_FROST, true ),
                'cards_glass'          => (bool) get_post_meta( $table_id, PWPL_Meta::CARDS_GLASS, true ),
                'specs_style'         => (string) get_post_meta( $table_id, PWPL_Meta::SPECS_STYLE, true ),
                'specs_anim'           => (function($table_id){
                    $preset = get_post_meta( $table_id, PWPL_Meta::SPECS_ANIM_PRESET, true );
                    $preset = $preset ? sanitize_key( $preset ) : 'minimal';
                    $flags  = get_post_meta( $table_id, PWPL_Meta::SPECS_ANIM_FLAGS, true );
                    $flags  = is_array( $flags ) ? array_values( array_intersect( array_map( 'sanitize_key', $flags ), [ 'row','icon','divider','chip','stagger' ] ) ) : [];
                    if ( empty( $flags ) ) {
                        switch ( $preset ) {
                            case 'off': $flags = []; break;
                            case 'segmented': $flags = [ 'row', 'divider' ]; break;
                            case 'chips': $flags = [ 'row', 'chip' ]; break;
                            case 'all': $flags = [ 'row', 'icon', 'divider', 'chip', 'stagger' ]; break;
                            case 'minimal':
                            default: $flags = [ 'row', 'icon' ]; break;
                        }
                    }
                    $intensity = (int) get_post_meta( $table_id, PWPL_Meta::SPECS_ANIM_INTENSITY, true );
                    $intensity = $intensity > 0 ? $intensity : 45;
                    $mobile    = (bool) get_post_meta( $table_id, PWPL_Meta::SPECS_ANIM_MOBILE, true );
                    return [ 'preset' => $preset, 'flags' => $flags, 'intensity' => $intensity, 'mobile' => $mobile ];
                })($table_id),
                'trust_trio'          => (bool) get_post_meta( $table_id, PWPL_Meta::TRUST_TRIO_ENABLED, true ),
                'sticky_cta_mobile'   => (bool) get_post_meta( $table_id, PWPL_Meta::STICKY_CTA_MOBILE, true ),
                'trust_items'         => (function($table_id){ $items = get_post_meta( $table_id, PWPL_Meta::TRUST_ITEMS, true ); return is_array( $items ) ? array_values( array_filter( array_map( 'sanitize_text_field', $items ) ) ) : []; })($table_id),
            ];

            $billing_copy = $this->get_billing_copy( $active_values, $dimension_labels );

            $plan_entries = [];
            foreach ( $plans as $plan_post ) {
                $plan_id = (int) $plan_post->ID;

                $plan_theme_meta = get_post_meta( $plan_id, PWPL_Meta::PLAN_THEME, true );
                $plan_theme_meta = $plan_theme_meta ? $meta_helper->sanitize_theme( $plan_theme_meta ) : '';
                $plan_theme_slug = ( 'classic' === $table_theme && $plan_theme_meta ) ? $plan_theme_meta : $table_theme;
                $this->enqueue_theme_assets( $plan_theme_slug );

                $variants = $plan_variants_cache[ $plan_id ] ?? [];
                $best_variant = $this->resolve_variant( $variants, $active_values );
                $price_html   = $this->build_price_html( $best_variant, $settings );

                $override_badges = get_post_meta( $plan_id, PWPL_Meta::PLAN_BADGES_OVERRIDE, true );
                if ( ! is_array( $override_badges ) ) {
                    $override_badges = [];
                }

                $is_featured         = (bool) get_post_meta( $plan_id, PWPL_Meta::PLAN_FEATURED, true );
                $plan_badge_shadow   = (int) get_post_meta( $plan_id, PWPL_Meta::PLAN_BADGE_SHADOW, true );
                $badge               = $this->resolve_badge( $active_values, $override_badges, $table_badges );
                $effective_shadow    = $plan_badge_shadow > 0 ? $plan_badge_shadow : $badge_shadow;
                $badge_view          = $this->format_badge_for_output( $badge, $effective_shadow );
                $cta_view            = $this->prepare_cta( $best_variant );
                $cta_target          = '';
                $cta_rel             = '';

                if ( ! empty( $cta_view['target_attr'] ) && preg_match( '/target=\"([^\"]+)\"/i', $cta_view['target_attr'], $match_target ) ) {
                    $cta_target = $match_target[1];
                }
                if ( ! empty( $cta_view['rel_attr'] ) && preg_match( '/rel=\"([^\"]+)\"/i', $cta_view['rel_attr'], $match_rel ) ) {
                    $cta_rel = $match_rel[1];
                }

                $spec_meta  = get_post_meta( $plan_id, PWPL_Meta::PLAN_SPECS, true );
                $spec_meta  = is_array( $spec_meta ) ? $spec_meta : [];
                $spec_items = [];
                foreach ( $spec_meta as $spec_entry ) {
                    if ( ! is_array( $spec_entry ) ) {
                        continue;
                    }
                    $label = isset( $spec_entry['label'] ) ? trim( (string) $spec_entry['label'] ) : '';
                    $value = isset( $spec_entry['value'] ) ? trim( (string) $spec_entry['value'] ) : '';
                    if ( '' === $label && '' === $value ) {
                        continue;
                    }
                    $spec_items[] = [
                        'label' => $label,
                        'value' => $value,
                        'slug'  => sanitize_title( $label ),
                    ];
                }

                $plan_platforms = [];
                $plan_periods   = [];
                $plan_locations = [];
                $supports_all_platforms = false;

                foreach ( (array) $variants as $variant_entry ) {
                    if ( ! is_array( $variant_entry ) ) {
                        continue;
                    }
                    $variant_platform = isset( $variant_entry['platform'] ) ? sanitize_title( $variant_entry['platform'] ) : '';
                    if ( $variant_platform ) {
                        $plan_platforms[] = $variant_platform;
                    } else {
                        $supports_all_platforms = true;
                    }

                    $variant_period = isset( $variant_entry['period'] ) ? sanitize_title( $variant_entry['period'] ) : '';
                    if ( $variant_period ) {
                        $plan_periods[] = $variant_period;
                    }

                    $variant_location = isset( $variant_entry['location'] ) ? sanitize_title( $variant_entry['location'] ) : '';
                    if ( $variant_location ) {
                        $plan_locations[] = $variant_location;
                    }
                }

                $plan_platforms = array_values( array_unique( array_filter( $plan_platforms ) ) );
                $plan_periods   = array_values( array_unique( array_filter( $plan_periods ) ) );
                $plan_locations = array_values( array_unique( array_filter( $plan_locations ) ) );

                $plan_lead_meta = get_post_meta( $plan_id, '_pwpl_plan_subtitle', true );
                $plan_lead      = is_string( $plan_lead_meta ) ? trim( wp_strip_all_tags( $plan_lead_meta ) ) : '';
                if ( '' === $plan_lead && ! empty( $plan_post->post_excerpt ) ) {
                    $plan_lead = trim( wp_strip_all_tags( $plan_post->post_excerpt ) );
                }

                $deal_label = '';
                if ( is_array( $best_variant ) && ! empty( $best_variant['deal_label'] ) ) {
                    $deal_label = (string) $best_variant['deal_label'];
                }

                $plan_title_raw = get_post_field( 'post_title', $plan_id );
                if ( '' === $plan_title_raw ) {
                    $plan_title_raw = sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan_id );
                }

                $plan_entries[] = [
                    'id'              => $plan_id,
                    'theme'           => $plan_theme_slug,
                    'title'           => $plan_title_raw,
                    'subtitle'        => $plan_lead,
                    'price_html'      => $price_html,
                    'billing'         => $billing_copy,
                    'cta'             => [
                        'label'  => $cta_view['label'],
                        'url'    => $cta_view['url'],
                        'hidden' => $cta_view['hidden'],
                        'target' => $cta_target,
                        'rel'    => $cta_rel,
                        'blank'  => ( '_blank' === $cta_target ),
                    ],
                    'badge'           => $badge_view,
                    'badges_override' => $override_badges,
                    'featured'        => $is_featured,
                    'deal_label'      => $deal_label,
                    'datasets'        => [
                        'platforms' => $plan_platforms,
                        'periods'   => $plan_periods,
                        'locations' => $plan_locations,
                    ],
                    'platforms'       => $supports_all_platforms ? [] : $plan_platforms,
                    'periods'         => $plan_periods,
                    'locations'       => $plan_locations,
                    'variants'        => array_values( $variants ),
                    'specs'           => $spec_items,
                    'badge_shadow'    => $effective_shadow,
                ];
            }

            ob_start();
            echo "\n<!-- PWPL template: " . esc_html( $template_path ) . " -->\n";
            $table = $table_context;
            $plans = $plan_entries;
            include $template_path;
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $table_class_attr ); ?>" data-table-id="<?php echo esc_attr( $table_id ); ?>" data-table-theme="<?php echo esc_attr( $table_theme ); ?>" data-badges="<?php echo esc_attr( $table_badges_json ); ?>" data-dimension-labels="<?php echo esc_attr( $dimension_labels_json ); ?>"<?php echo $table_attr_extras; foreach ( $active_values as $dim => $value ) { echo ' data-active-' . esc_attr( $dim ) . '="' . esc_attr( $value ) . '"'; } echo $table_style_attr; ?>>
            <div class="pwpl-table__header">
                <h3 class="pwpl-table__title"><?php echo $table_title; ?></h3>
            </div>

            <?php foreach ( $dimension_values as $dimension => $values ) : ?>
                <div class="pwpl-dimension-nav" data-dimension="<?php echo esc_attr( $dimension ); ?>">
                    <?php foreach ( $values as $index => $item ) :
                        $is_active = $index === 0 ? ' is-active' : '';
                        $tab_badge_raw = $this->match_badge_for_slug( $item['slug'], $table_badges[ $dimension ] ?? [] );
                        $tab_badge     = $this->format_badge_for_output( $tab_badge_raw, $badge_shadow );
                        ?>
                        <button type="button" class="pwpl-tab<?php echo $is_active; ?>" data-value="<?php echo esc_attr( $item['slug'] ); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                            <span class="pwpl-tab__label"><?php echo esc_html( $item['label'] ); ?></span>
                            <?php if ( ! $tab_badge['hidden'] ) :
                                $tab_badge_style = '';
                                if ( $tab_badge['color'] ) {
                                    $tab_badge_style .= '--pwpl-tab-badge-bg:' . $tab_badge['color'] . ';';
                                }
                                if ( $tab_badge['text_color'] ) {
                                    $tab_badge_style .= '--pwpl-tab-badge-color:' . $tab_badge['text_color'] . ';';
                                }
                                ?>
                                <span class="pwpl-tab__badge" style="<?php echo esc_attr( $tab_badge_style ); ?>">
                                    <?php if ( ! empty( $tab_badge['icon'] ) ) : ?>
                                        <span class="pwpl-tab__badge-icon" aria-hidden="true"><?php echo esc_html( $tab_badge['icon'] ); ?></span>
                                    <?php endif; ?>
                                    <span class="pwpl-tab__badge-label"><?php echo esc_html( $tab_badge['label'] ); ?></span>
                                </span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="pwpl-plan-rail-wrapper">
                <div class="pwpl-plan-nav pwpl-plan-nav--prev" hidden>
                    <button type="button" class="pwpl-plan-nav__btn" data-direction="prev" aria-label="<?php esc_attr_e( 'Scroll previous plans', 'planify-wp-pricing-lite' ); ?>">&#10094;</button>
                </div>
                <div class="pwpl-plan-nav pwpl-plan-nav--next" hidden>
                    <button type="button" class="pwpl-plan-nav__btn" data-direction="next" aria-label="<?php esc_attr_e( 'Scroll next plans', 'planify-wp-pricing-lite' ); ?>">&#10095;</button>
                </div>
                <div class="pwpl-plan-grid" tabindex="0">
                <?php foreach ( $plans as $plan ) :
                    $plan_title = $plan->post_title ? esc_html( $plan->post_title ) : sprintf( esc_html__( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan->ID );
                    $plan_theme_meta = get_post_meta( $plan->ID, PWPL_Meta::PLAN_THEME, true );
                    $plan_theme_meta = $plan_theme_meta ? $meta_helper->sanitize_theme( $plan_theme_meta ) : '';
                    $theme = $table_theme === 'classic' && $plan_theme_meta ? $plan_theme_meta : $table_theme;
                    $this->enqueue_theme_assets( $theme );
                    $specs = get_post_meta( $plan->ID, PWPL_Meta::PLAN_SPECS, true );
                    if ( ! is_array( $specs ) ) {
                        $specs = [];
                    }
                    $variants = $plan_variants_cache[ $plan->ID ] ?? [];

                    $override_badges = get_post_meta( $plan->ID, PWPL_Meta::PLAN_BADGES_OVERRIDE, true );
                    if ( ! is_array( $override_badges ) ) {
                        $override_badges = [];
                    }

                    $is_featured = (bool) get_post_meta( $plan->ID, PWPL_Meta::PLAN_FEATURED, true );

                    $variant = $this->resolve_variant( $variants, $active_values );
                    $price_html = $this->build_price_html( $variant, $settings );
                    $badge        = $this->resolve_badge( $active_values, $override_badges, $table_badges );
                    $plan_badge_shadow = (int) get_post_meta( $plan->ID, PWPL_Meta::PLAN_BADGE_SHADOW, true );
                    $effective_shadow  = $plan_badge_shadow > 0 ? $plan_badge_shadow : $badge_shadow;
                    $badge_view   = $this->format_badge_for_output( $badge, $effective_shadow );
                    $cta          = $this->prepare_cta( $variant );
                    $billing_copy = $this->get_billing_copy( $active_values, $dimension_labels );

                    $location_slug  = $active_values['location'] ?? '';
                    $location_label = $this->get_dimension_label( 'location', $location_slug, $dimension_labels );

                    $override_json = wp_json_encode( $override_badges, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                    if ( false === $override_json ) {
                        $override_json = '{}';
                    }
                    $variants_json = wp_json_encode( $variants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                    if ( false === $variants_json ) {
                        $variants_json = '[]';
                    }
                    ?>
                    <?php
                    $plan_platforms = [];
                    $supports_all_platforms = false;
                    foreach ( (array) $variants as $variant_entry ) {
                        if ( ! is_array( $variant_entry ) ) {
                            continue;
                        }
                        $variant_platform = isset( $variant_entry['platform'] ) ? sanitize_title( $variant_entry['platform'] ) : '';
                        if ( $variant_platform ) {
                            $plan_platforms[] = $variant_platform;
                        } else {
                            $supports_all_platforms = true;
                        }
                    }
                    $plan_platforms = array_values( array_unique( array_filter( $plan_platforms ) ) );
                    $plan_platform_attr = ( $supports_all_platforms || ! $plan_platforms ) ? '*' : implode( ',', $plan_platforms );
                    $plan_classes = [ 'pwpl-plan', 'pwpl-theme--' . $theme ];
                    if ( $is_featured ) {
                        $plan_classes[] = 'pwpl-plan--featured';
                    }
                    if ( $platform_filtering_enabled && $initial_platform && '*' !== $plan_platform_attr && ! in_array( $initial_platform, $plan_platforms, true ) ) {
                        $plan_classes[] = 'pwpl-hidden';
                    }
                    $plan_class_attr = implode( ' ', array_map( 'sanitize_html_class', $plan_classes ) );
                    ?>
                    <article class="<?php echo esc_attr( $plan_class_attr ); ?>" data-plan-id="<?php echo esc_attr( $plan->ID ); ?>" data-platforms="<?php echo esc_attr( $plan_platform_attr ); ?>" data-variants="<?php echo esc_attr( $variants_json ); ?>" data-badges-override="<?php echo esc_attr( $override_json ); ?>">
                        <header class="pwpl-plan__header">
                            <div class="pwpl-plan__header-meta">
                                <span class="pwpl-plan__location" data-pwpl-location <?php echo $location_label ? '' : 'hidden'; ?>><?php echo esc_html( $location_label ); ?></span>
                                <span class="pwpl-plan__badge<?php echo $badge_view['tone_class']; ?>" data-pwpl-badge data-badge-color="<?php echo esc_attr( $badge_view['color'] ); ?>" data-badge-text="<?php echo esc_attr( $badge_view['text_color'] ); ?>" data-badge-tone="<?php echo esc_attr( $badge_view['tone'] ); ?>" style="<?php echo esc_attr( $badge_view['style'] ); ?>" <?php echo $badge_view['hidden'] ? 'hidden' : ''; ?>>
                                    <span class="pwpl-plan__badge-icon" data-pwpl-badge-icon aria-hidden="true"><?php echo esc_html( $badge_view['icon'] ); ?></span>
                                    <span class="pwpl-plan__badge-label" data-pwpl-badge-label><?php echo esc_html( $badge_view['label'] ); ?></span>
                                </span>
                                <?php if ( $is_featured ) : ?>
                                    <span class="pwpl-plan__featured-tag" data-pwpl-featured-label><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <h4 class="pwpl-plan__title"><?php echo $plan_title; ?></h4>
                            <div class="pwpl-plan__pricing" data-pwpl-price>
                                <?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </header>
                        <div class="pwpl-plan__cta" data-pwpl-cta>
                            <p class="pwpl-plan__billing" data-pwpl-billing <?php echo $billing_copy ? '' : 'hidden'; ?>><?php echo esc_html( $billing_copy ); ?></p>
                            <a class="pwpl-plan__cta-button" data-pwpl-cta-button href="<?php echo esc_url( $cta['url'] ); ?>"<?php echo $cta['target_attr']; ?><?php echo $cta['rel_attr']; ?> <?php echo $cta['hidden'] ? 'hidden' : ''; ?>>
                                <span data-pwpl-cta-label><?php echo esc_html( $cta['label'] ); ?></span>
                            </a>
                        </div>
                        <?php if ( ! empty( $specs ) ) : ?>
                            <ul class="pwpl-plan__specs">
                                <?php foreach ( $specs as $spec ) :
                                    if ( empty( $spec['label'] ) && empty( $spec['value'] ) ) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <?php if ( ! empty( $spec['label'] ) ) : ?><span class="pwpl-plan__spec-label"><?php echo esc_html( $spec['label'] ); ?></span><?php endif; ?>
                                        <?php if ( ! empty( $spec['value'] ) ) : ?><span class="pwpl-plan__spec-value"><?php echo esc_html( $spec['value'] ); ?></span><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function enqueue_theme_assets( $slug ) {
        if ( ! $slug || ! $this->theme_loader ) {
            return null;
        }

        if ( array_key_exists( $slug, $this->enqueued_themes ) ) {
            return $this->enqueued_themes[ $slug ];
        }

        $theme = $this->theme_loader->get_theme( $slug );
        if ( ! $theme ) {
            $this->enqueued_themes[ $slug ] = null;
            return null;
        }

        $assets = $this->theme_loader->get_assets( $theme );

        if ( ! empty( $assets['css'] ) ) {
            foreach ( $assets['css'] as $style ) {
                $version = file_exists( $style['path'] ) ? filemtime( $style['path'] ) : PWPL_VERSION;
                wp_enqueue_style( $style['handle'], $style['url'], [], $version );
            }
        }

        if ( ! empty( $assets['js'] ) ) {
            foreach ( $assets['js'] as $script ) {
                $version = file_exists( $script['path'] ) ? filemtime( $script['path'] ) : PWPL_VERSION;
                wp_enqueue_script( $script['handle'], $script['url'], [], $version, true );
            }
        }

        $this->enqueued_themes[ $slug ] = $theme;
        return $theme;
    }

    private function index_by_slug( array $items ) {
        $indexed = [];
        foreach ( $items as $item ) {
            if ( empty( $item['slug'] ) ) {
                continue;
            }
            $indexed[ $item['slug'] ] = [
                'slug'  => $item['slug'],
                'label' => $item['label'] ?? $item['slug'],
            ];
        }
        return $indexed;
    }

    private function resolve_variant( array $variants, array $selection ) {
        if ( empty( $variants ) ) {
            return null;
        }
        $best      = null;
        $bestScore = -1;
        $dimensions = [ 'platform', 'period', 'location' ];

        foreach ( $variants as $variant ) {
            if ( ! is_array( $variant ) ) {
                continue;
            }
            $score = 0;
            $match = true;
            foreach ( $dimensions as $dimension ) {
                $selected = $selection[ $dimension ] ?? '';
                $value    = $variant[ $dimension ] ?? '';
                if ( $selected ) {
                    if ( $value && $value === $selected ) {
                        $score += 2;
                    } elseif ( $value === '' ) {
                        $score += 1; // wildcard
                    } else {
                        $match = false;
                        break;
                    }
                }
            }
            if ( $match && $score > $bestScore ) {
                $bestScore = $score;
                $best = $variant;
            }
        }

        if ( $best === null ) {
            $best = $variants[0];
        }

        return $best;
    }

    private function build_price_html( $variant, $settings ) {
        if ( ! $variant ) {
            return '<span class="pwpl-plan__price--empty">' . esc_html__( 'Contact us', 'planify-wp-pricing-lite' ) . '</span>';
        }

        $price = $variant['price'] ?? '';
        $sale  = $variant['sale_price'] ?? '';
        if ( $price === '' && $sale === '' ) {
            return '<span class="pwpl-plan__price--empty">' . esc_html__( 'Contact us', 'planify-wp-pricing-lite' ) . '</span>';
        }

        $formatted_price = $price !== '' ? $this->format_price( $price, $settings ) : '';
        $formatted_sale  = $sale !== '' ? $this->format_price( $sale, $settings ) : '';

        // Show old price + inline discount badge + sale price only when numeric and sale < base
        $price_num = is_numeric( $price ) ? (float) $price : null;
        $sale_num  = is_numeric( $sale ) ? (float) $sale : null;
        $has_discount = null !== $price_num && null !== $sale_num && $price_num > 0 && $sale_num >= 0 && $sale_num < $price_num;

        if ( $has_discount && $formatted_sale && $formatted_price ) {
            $pct = (int) round( ( ( $price_num - $sale_num ) / $price_num ) * 100 );
            $badge_html = '';
            if ( $pct > 0 ) {
                $badge_html = '<span class="fvps-price-badge" aria-label="' . esc_attr( sprintf( __( '%d%% off', 'planify-wp-pricing-lite' ), $pct ) ) . '">' . esc_html( sprintf( __( '%d%% OFF', 'planify-wp-pricing-lite' ), $pct ) ) . '</span>';
            }
            // Split currency symbol from numeric portion for precise typography
            $sale_prefix = ''; $sale_value = $formatted_sale; $sale_suffix = '';
            if ( preg_match( '/^([^\d\-]*)([0-9][0-9\.,]*)\s*([^\d]*)$/u', $formatted_sale, $m ) ) {
                $sale_prefix = trim( (string) ( $m[1] ?? '' ) );
                $sale_value  = (string) ( $m[2] ?? $formatted_sale );
                $sale_suffix = trim( (string) ( $m[3] ?? '' ) );
            }
            $sale_html = '<span class="pwpl-plan__price-sale">'
                . ( $sale_prefix !== '' ? '<span class="pwpl-price-currency pwpl-currency--prefix">' . esc_html( $sale_prefix ) . '</span>' : '' )
                . '<span class="pwpl-price-value">' . esc_html( $sale_value ) . '</span>'
                . ( $sale_suffix !== '' ? '<span class="pwpl-price-currency pwpl-currency--suffix">' . esc_html( $sale_suffix ) . '</span>' : '' )
                . '</span>';
            // Order: old price + badge (same line), then sale block; unit is added by CSS/JS if needed
            return '<span class="pwpl-plan__price-original">' . esc_html( $formatted_price ) . '</span>' . $badge_html . $sale_html;
        }

        // Otherwise prefer whichever value exists (single price, no badge)
        $display = $formatted_sale ?: $formatted_price;
        // Split single price as well for consistent typography
        $pfx = ''; $val = $display; $sfx = '';
        if ( preg_match( '/^([^\d\-]*)([0-9][0-9\.,]*)\s*([^\d]*)$/u', (string) $display, $m ) ) {
            $pfx = trim( (string) ( $m[1] ?? '' ) );
            $val = (string) ( $m[2] ?? $display );
            $sfx = trim( (string) ( $m[3] ?? '' ) );
        }
        $single_html = ( $pfx !== '' ? '<span class="pwpl-price-currency pwpl-currency--prefix">' . esc_html( $pfx ) . '</span>' : '' )
            . '<span class="pwpl-price-value">' . esc_html( $val ) . '</span>'
            . ( $sfx !== '' ? '<span class="pwpl-price-currency pwpl-currency--suffix">' . esc_html( $sfx ) . '</span>' : '' );
        return '<span class="pwpl-plan__price">' . $single_html . '</span>';
    }

    private function prepare_cta( $variant ) {
        $cta = [
            'label'       => '',
            'url'         => '',
            'target_attr' => '',
            'rel_attr'    => '',
            'hidden'      => true,
        ];

        if ( ! is_array( $variant ) ) {
            return $cta;
        }

        $label = trim( (string) ( $variant['cta_label'] ?? '' ) );
        $url   = trim( (string) ( $variant['cta_url'] ?? '' ) );

        if ( $label === '' || $url === '' ) {
            return $cta;
        }

        $target = $variant['target'] ?? '';
        $rel    = trim( (string) ( $variant['rel'] ?? '' ) );

        $cta['label']  = $label;
        $cta['url']    = $url;
        $cta['hidden'] = false;

        if ( in_array( $target, [ '_blank', '_self' ], true ) ) {
            $cta['target_attr'] = ' target="' . esc_attr( $target ) . '"';
            if ( '_blank' === $target && $rel === '' ) {
                $rel = 'noopener noreferrer';
            }
        }

        if ( $rel !== '' ) {
            $cta['rel_attr'] = ' rel="' . esc_attr( $rel ) . '"';
        }

        return $cta;
    }

    private function resolve_badge( array $selection, array $override, array $table_badges ) {
        $stack = $this->collect_badges( $selection, $override, $table_badges );
        return $stack ? $stack[0] : null;
    }

    /**
     * Compile a prioritized list of badges for the active selection.
     * Option A (single badge): use the first element of the returned array.
     * Option B (stacked badges): future implementation can render the entire stack.
     */
    private function collect_badges( array $selection, array $override, array $table_badges ) {
        $dimensions = [ 'period', 'location', 'platform' ];

        $priority = [];
        if ( isset( $override['priority'] ) && is_array( $override['priority'] ) ) {
            $priority = array_values( array_intersect( $override['priority'], $dimensions ) );
        }

        if ( empty( $priority ) && isset( $table_badges['priority'] ) && is_array( $table_badges['priority'] ) ) {
            $priority = array_values( array_intersect( $table_badges['priority'], $dimensions ) );
        }

        if ( empty( $priority ) ) {
            $priority = $dimensions;
        }

        $priority = array_values( array_unique( array_merge( $priority, $dimensions ) ) );

        $matches = [];

        foreach ( $priority as $dimension ) {
            $slug = $selection[ $dimension ] ?? '';
            if ( $slug === '' ) {
                continue;
            }

            $override_collection = isset( $override[ $dimension ] ) && is_array( $override[ $dimension ] ) ? $override[ $dimension ] : [];
            $match = $this->match_badge_for_slug( $slug, $override_collection );
            if ( $match ) {
                $matches[] = $match;
                continue;
            }

            $table_collection = isset( $table_badges[ $dimension ] ) && is_array( $table_badges[ $dimension ] ) ? $table_badges[ $dimension ] : [];
            $match = $this->match_badge_for_slug( $slug, $table_collection );
            if ( $match ) {
                $matches[] = $match;
            }
        }
        return $matches;
    }

    private function match_badge_for_slug( $slug, array $collection ) {
        foreach ( $collection as $badge ) {
            if ( ! is_array( $badge ) ) {
                continue;
            }
            if ( empty( $badge['slug'] ) || $badge['slug'] !== $slug ) {
                continue;
            }
            if ( ! $this->badge_active( $badge ) ) {
                continue;
            }
            return $badge;
        }

        return null;
    }

    private function badge_active( array $badge ) {
        $now   = current_time( 'timestamp' );
        $start = $this->parse_badge_date( $badge['start'] ?? '', false );
        $end   = $this->parse_badge_date( $badge['end'] ?? '', true );

        if ( null !== $start && $start > $now ) {
            return false;
        }
        if ( null !== $end && $end < $now ) {
            return false;
        }

        return true;
    }

    private function parse_badge_date( $date, $end_of_day = false ) {
        if ( empty( $date ) ) {
            return null;
        }

        try {
            $time    = $end_of_day ? '23:59:59' : '00:00:00';
            $zone    = wp_timezone();
            $dt      = new DateTimeImmutable( $date . ' ' . $time, $zone );
            return $dt->getTimestamp();
        } catch ( \Exception $e ) {
            return null;
        }
    }

    private function format_badge_for_output( $badge, $shadow = 0 ) {
        $defaults = [
            'label'      => '',
            'color'      => '',
            'text_color' => '',
            'icon'       => '',
            'tone'       => '',
            'tone_class' => '',
            'style'      => '',
            'hidden'     => true,
        ];

        if ( ! is_array( $badge ) || empty( $badge['label'] ) ) {
            return $defaults;
        }

        $color      = $badge['color'] ?? '';
        $text_color = $badge['text_color'] ?? '';
        $tone       = sanitize_html_class( $badge['tone'] ?? '' );

        $style = '';
        if ( $color ) {
            $style .= '--pwpl-badge-bg:' . $color . ';';
        }
        if ( $text_color ) {
            $style .= '--pwpl-badge-color:' . $text_color . ';';
        }
        if ( $shadow > 0 ) {
            $style .= '--pwpl-badge-shadow-strength:' . $shadow . ';';
        }
        if ( $color ) {
            $rgba = $this->hex_to_rgba( $color, 0.35 );
            if ( $rgba ) {
                $style .= '--pwpl-badge-shadow-color:' . $rgba . ';';
            }
        }

        $has_custom = ( $color !== '' ) || ( $text_color !== '' );

        return [
            'label'      => $badge['label'] ?? '',
            'color'      => $color,
            'text_color' => $text_color,
            'icon'       => $badge['icon'] ?? '',
            'tone'       => $tone,
            // Only apply tone class when no custom colors are provided
            'tone_class' => ( ! $has_custom && $tone ) ? ' pwpl-plan__badge--tone-' . $tone : '',
            'style'      => $style,
            'hidden'     => false,
        ];
    }

    private function hex_to_rgba( $hex, $alpha = 0.35 ) {
        $hex = trim( (string) $hex );
        if ( '' === $hex ) {
            return '';
        }
        if ( strpos( $hex, '#' ) === 0 ) {
            $hex = substr( $hex, 1 );
        }
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
            return '';
        }
        $int = hexdec( $hex );
        $r   = ( $int >> 16 ) & 255;
        $g   = ( $int >> 8 ) & 255;
        $b   = $int & 255;

        $alpha = max( 0, min( (float) $alpha, 1 ) );
        return sprintf( 'rgba(%d, %d, %d, %.3f)', $r, $g, $b, $alpha );
    }

    private function get_dimension_label( $dimension, $slug, array $labels ) {
        if ( $slug === '' ) {
            return '';
        }
        return $labels[ $dimension ][ $slug ] ?? '';
    }

    private function get_billing_copy( array $selection, array $dimension_labels ) {
        $period_slug = $selection['period'] ?? '';
        if ( '' === $period_slug ) {
            return '';
        }
        $label = $dimension_labels['period'][ $period_slug ] ?? '';
        if ( '' === $label ) {
            return '';
        }
        return sprintf( esc_html__( 'Billed %s', 'planify-wp-pricing-lite' ), $label );
    }

    private function format_price( $amount, $settings ) {
        $amount = (float) str_replace( [ ',', ' ' ], '', $amount );
        $decimals = isset( $settings['price_decimals'] ) ? (int) $settings['price_decimals'] : 2;
        $thousand = $settings['thousand_sep'] ?? ',';
        $decimal  = $settings['decimal_sep'] ?? '.';

        $formatted = number_format_i18n( $amount, $decimals );
        // number_format_i18n already uses locale separators, override if custom set
        if ( $thousand !== ',' || $decimal !== '.' ) {
            $formatted = number_format( $amount, $decimals, $decimal, $thousand );
        }

        $symbol   = $settings['currency_symbol'] ?? '$';
        $position = $settings['currency_position'] ?? 'left';

        switch ( $position ) {
            case 'right':
                return $formatted . $symbol;
            case 'left_space':
                return $symbol . ' ' . $formatted;
            case 'right_space':
                return $formatted . ' ' . $symbol;
            case 'left':
            default:
                return $symbol . $formatted;
        }
    }
}
