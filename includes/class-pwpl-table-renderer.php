<?php
/**
 * Render a pricing table from an in-memory config (wizard preview).
 *
 * This mirrors the shortcode rendering pipeline but uses the provided config
 * instead of fetching posts/meta from the database.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Table_Renderer {

    /**
     * Render HTML from a wizard config.
     *
     * @param array $config Output of PWPL_Table_Wizard::build_preview_config().
     * @return string
     */
    public static function render_from_config( array $config ): string {
        if ( empty( $config['table']['meta'] ) || empty( $config['plans'] ) ) {
            return '';
        }

        $meta_helper   = new PWPL_Meta();
        $settings      = ( new PWPL_Settings() )->get();
        $theme_loader  = new PWPL_Theme_Loader();
        $table_meta    = is_array( $config['table']['meta'] ) ? $config['table']['meta'] : [];
        $table_theme   = $meta_helper->sanitize_theme( $table_meta[ PWPL_Meta::TABLE_THEME ] ?? '' );
        $layout_type   = isset( $table_meta[ PWPL_Meta::TABLE_LAYOUT_TYPE ] ) ? (string) $table_meta[ PWPL_Meta::TABLE_LAYOUT_TYPE ] : '';
        $preset        = isset( $table_meta[ PWPL_Meta::TABLE_PRESET ] ) ? (string) $table_meta[ PWPL_Meta::TABLE_PRESET ] : '';
        $dom_id        = 'pwpl-preview-' . sanitize_key( $config['template_id'] ?? uniqid() );
        $table_id      = 0;

        if ( '' === $layout_type ) {
            $layout_type = ! empty( $config['layout_type'] ) ? sanitize_key( (string) $config['layout_type'] ) : 'grid';
        }
        if ( '' === $preset ) {
            $preset = ! empty( $config['preset'] ) ? sanitize_key( (string) $config['preset'] ) : ( $config['template_id'] ?? '' );
        }

        self::enqueue_frontend_assets();

        $theme_package = $theme_loader->get_theme( $table_theme );
        $template_rel  = 'template.php';
        if ( ! empty( $theme_package['manifest']['template'] ) ) {
            $candidate = ltrim( (string) $theme_package['manifest']['template'], '/' );
            if ( $candidate ) {
                $template_rel = $candidate;
            }
        }
        $template_path = function_exists( 'pwpl_locate_theme_file' ) ? pwpl_locate_theme_file( $table_theme, $template_rel ) : false;
        if ( ! $template_path ) {
            return '';
        }

        $theme_assets = $theme_loader->get_assets( [
            'slug'     => $table_theme,
            'dir'      => $theme_package['dir'] ?? '',
            'url'      => $theme_package['url'] ?? '',
            'manifest' => $theme_package['manifest'] ?? [],
        ] );
        self::enqueue_theme_assets( $theme_assets );

        $dimensions_enabled = array_values( array_intersect( (array) ( $table_meta[ PWPL_Meta::DIMENSION_META ] ?? [] ), [ 'platform', 'period', 'location' ] ) );
        $allowed = [
            'platform' => (array) ( $table_meta[ PWPL_Meta::ALLOWED_PLATFORMS ] ?? [] ),
            'period'   => (array) ( $table_meta[ PWPL_Meta::ALLOWED_PERIODS ] ?? [] ),
            'location' => (array) ( $table_meta[ PWPL_Meta::ALLOWED_LOCATIONS ] ?? [] ),
        ];
        $dimension_labels = self::dimension_labels_from_settings( $settings );
        $active_values = [];
        foreach ( [ 'platform', 'period', 'location' ] as $dimension ) {
            if ( ! in_array( $dimension, $dimensions_enabled, true ) ) {
                continue;
            }
            $list = array_filter( (array) $allowed[ $dimension ] );
            if ( empty( $list ) ) {
                $list = array_keys( $dimension_labels[ $dimension ] ?? [] );
            }
            if ( $list ) {
                $active_values[ $dimension ] = $list[0];
            }
        }

        $table_badges  = is_array( $table_meta[ PWPL_Meta::TABLE_BADGES ] ?? null ) ? $table_meta[ PWPL_Meta::TABLE_BADGES ] : [];
        $badge_shadow  = (int) ( $table_meta['_pwpl_badge_shadow_global'] ?? 10 );

        $plans_context = self::build_plans_context(
            $config['plans'],
            $table_theme,
            $active_values,
            $dimension_labels,
            $settings,
            $badge_shadow
        );

        $tabs_context = self::build_tabs_context( $dimensions_enabled, $allowed, $dimension_labels );
        $extra_classes = [];
        if ( $layout_type ) {
            $extra_classes[] = 'pwpl-table--layout-' . sanitize_html_class( $layout_type );
        }
        if ( $preset ) {
            $extra_classes[] = 'pwpl-table--preset-' . sanitize_html_class( $preset );
        }

        $table_context = [
            'id'               => $table_id,
            'dom_id'           => $dom_id,
            'theme'            => $table_theme,
            'layout_type'      => $layout_type,
            'preset'           => $preset,
            'template_id'      => $config['template_id'] ?? '',
            'extra_classes'    => implode( ' ', array_filter( $extra_classes ) ),
            'title'            => $config['table']['post_title'] ?? __( 'Preview Table', 'planify-wp-pricing-lite' ),
            'subtitle'         => $config['table']['post_excerpt'] ?? '',
            'manifest'         => $theme_package['manifest'] ?? [],
            'badges'           => $table_badges,
            'badge_shadow'     => $badge_shadow,
            'dimension_labels' => $dimension_labels,
            'active'           => $active_values,
            'allowed'          => $allowed,
            'tabs'             => $tabs_context,
            'dimensions'       => $tabs_context,
            'availability'     => [],
            'style'            => '',
            'style_block'      => '',
            'tabs_glass'       => ! empty( $table_meta[ PWPL_Meta::TABS_GLASS ] ),
            'tabs_glass_tint'  => (string) ( $table_meta[ PWPL_Meta::TABS_GLASS_TINT ] ?? '' ),
            'tabs_glass_intensity' => (int) ( $table_meta[ PWPL_Meta::TABS_GLASS_INTENSITY ] ?? 60 ),
            'tabs_glass_frost'     => (int) ( $table_meta[ PWPL_Meta::TABS_GLASS_FROST ] ?? 6 ),
            'cards_glass'      => ! empty( $table_meta[ PWPL_Meta::CARDS_GLASS ] ),
            'specs_style'      => (string) ( $table_meta[ PWPL_Meta::SPECS_STYLE ] ?? 'default' ),
            'specs_anim'       => [
                'preset'    => get_option( 'pwpl_specs_anim_preset_default', 'minimal' ),
                'flags'     => [ 'row', 'icon' ],
                'intensity' => 45,
                'mobile'    => false,
            ],
            'trust_trio'        => ! empty( $table_meta[ PWPL_Meta::TRUST_TRIO_ENABLED ] ),
            'sticky_cta_mobile' => ! empty( $table_meta[ PWPL_Meta::STICKY_CTA_MOBILE ] ),
            'trust_items'       => is_array( $table_meta[ PWPL_Meta::TRUST_ITEMS ] ?? null ) ? $table_meta[ PWPL_Meta::TRUST_ITEMS ] : [],
        ];

        ob_start();
        $table = $table_context; // for template scope
        $plans = $plans_context;
        include $template_path;
        return (string) ob_get_clean();
    }

    /**
     * Enqueue core frontend assets (same as shortcode).
     */
    private static function enqueue_frontend_assets(): void {
        $css = PWPL_DIR . 'assets/css/frontend.css';
        $css_themes = PWPL_DIR . 'assets/css/themes.css';
        $js  = PWPL_DIR . 'assets/js/frontend.js';

        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'pwpl-frontend', PWPL_URL . 'assets/css/frontend.css', [], filemtime( $css ) );
        }
        if ( file_exists( $css_themes ) ) {
            wp_enqueue_style( 'pwpl-frontend-themes', PWPL_URL . 'assets/css/themes.css', [ 'pwpl-frontend' ], filemtime( $css_themes ) );
        }
        if ( file_exists( $js ) ) {
            wp_enqueue_script( 'pwpl-frontend', PWPL_URL . 'assets/js/frontend.js', [], filemtime( $js ), true );
        }

        $settings = ( new PWPL_Settings() )->get();
        wp_localize_script( 'pwpl-frontend', 'PWPL_Frontend', [
            'currency' => [
                'symbol'        => $settings['currency_symbol'],
                'position'      => $settings['currency_position'],
                'thousand_sep'  => $settings['thousand_sep'],
                'decimal_sep'   => $settings['decimal_sep'],
                'price_decimals'=> (int) $settings['price_decimals'],
            ],
        ] );
    }

    /**
     * Enqueue theme assets for preview.
     *
     * @param array $assets
     */
    private static function enqueue_theme_assets( array $assets ): void {
        if ( ! empty( $assets['css'] ) ) {
            foreach ( $assets['css'] as $style ) {
                if ( empty( $style['handle'] ) || empty( $style['url'] ) ) {
                    continue;
                }
                wp_enqueue_style( $style['handle'], $style['url'], [ 'pwpl-frontend' ], file_exists( $style['path'] ?? '' ) ? filemtime( $style['path'] ) : false );
            }
        }
        if ( ! empty( $assets['js'] ) ) {
            foreach ( $assets['js'] as $script ) {
                if ( empty( $script['handle'] ) || empty( $script['url'] ) ) {
                    continue;
                }
                wp_enqueue_script( $script['handle'], $script['url'], [ 'pwpl-frontend' ], file_exists( $script['path'] ?? '' ) ? filemtime( $script['path'] ) : false, true );
            }
        }
    }

    /**
     * Build labels for dimensions from settings.
     */
    private static function dimension_labels_from_settings( array $settings ): array {
        $index = function( array $items ) {
            $out = [];
            foreach ( $items as $item ) {
                if ( empty( $item['slug'] ) ) {
                    continue;
                }
                $out[ $item['slug'] ] = $item['label'] ?? $item['slug'];
            }
            return $out;
        };

        return [
            'platform' => $index( (array) ( $settings['platforms'] ?? [] ) ),
            'period'   => $index( (array) ( $settings['periods'] ?? [] ) ),
            'location' => $index( (array) ( $settings['locations'] ?? [] ) ),
        ];
    }

    /**
     * Build tabs context for template.
     */
    private static function build_tabs_context( array $dimensions, array $allowed, array $labels ): array {
        $tabs = [];
        foreach ( $dimensions as $dimension ) {
            $values = [];
            $allowed_values = array_filter( (array) ( $allowed[ $dimension ] ?? [] ) );
            if ( empty( $allowed_values ) ) {
                $allowed_values = array_keys( $labels[ $dimension ] ?? [] );
            }
            foreach ( $allowed_values as $slug ) {
                $values[] = [
                    'slug'  => $slug,
                    'label' => $labels[ $dimension ][ $slug ] ?? $slug,
                ];
            }
            if ( $values ) {
                $tabs[ $dimension ] = [ 'values' => $values ];
            }
        }
        return $tabs;
    }

    /**
     * Build plan context for template consumption.
     */
    private static function build_plans_context( array $plans, string $theme_slug, array $active_values, array $dimension_labels, array $settings, int $badge_shadow ): array {
        $ctx = [];
        $id_counter = 1;
        foreach ( $plans as $plan ) {
            $meta          = is_array( $plan['meta'] ?? null ) ? $plan['meta'] : [];
            $variants      = is_array( $meta[ PWPL_Meta::PLAN_VARIANTS ] ?? null ) ? array_values( $meta[ PWPL_Meta::PLAN_VARIANTS ] ) : [];
            $specs         = is_array( $meta[ PWPL_Meta::PLAN_SPECS ] ?? null ) ? $meta[ PWPL_Meta::PLAN_SPECS ] : [];
            $hero_image_id = isset( $meta[ PWPL_Meta::PLAN_HERO_IMAGE ] ) ? (int) $meta[ PWPL_Meta::PLAN_HERO_IMAGE ] : 0;
            $hero_image_url = '';
            if ( ! empty( $meta[ PWPL_Meta::PLAN_HERO_IMAGE_URL ] ) ) {
                $hero_image_url = esc_url_raw( (string) $meta[ PWPL_Meta::PLAN_HERO_IMAGE_URL ] );
            } elseif ( ! empty( $meta['hero_image'] ) ) {
                // Legacy key coming from sample sets prior to dedicated meta.
                $hero_image_url = esc_url_raw( (string) $meta['hero_image'] );
            }
            $featured  = ! empty( $meta[ PWPL_Meta::PLAN_FEATURED ] );
            $plan_theme= $meta[ PWPL_Meta::PLAN_THEME ] ?? $theme_slug;
            $trust_override_source = null;
            if ( isset( $meta[ PWPL_Meta::PLAN_TRUST_ITEMS_OVERRIDE ] ) ) {
                $trust_override_source = $meta[ PWPL_Meta::PLAN_TRUST_ITEMS_OVERRIDE ];
            } elseif ( isset( $meta['trust_items_override'] ) ) {
                $trust_override_source = $meta['trust_items_override'];
            }
            $trust_override = [];
            if ( is_array( $trust_override_source ) ) {
                foreach ( $trust_override_source as $item ) {
                    $label = sanitize_text_field( (string) $item );
                    if ( '' !== $label ) {
                        $trust_override[] = $label;
                    }
                }
            }

            $best_variant = self::resolve_variant( $variants, $active_values );
            $price_html   = self::build_price_html( $best_variant, $settings );
            $billing      = self::get_billing_copy( $active_values, $dimension_labels );

            $datasets = [
                'platforms' => [],
                'periods'   => [],
                'locations' => [],
            ];
            foreach ( $variants as $variant ) {
                foreach ( [ 'platform', 'period', 'location' ] as $dim ) {
                    if ( ! empty( $variant[ $dim ] ) ) {
                        $datasets[ $dim . 's' ][] = sanitize_title( $variant[ $dim ] );
                    }
                }
            }
            foreach ( $datasets as $key => $vals ) {
                $datasets[ $key ] = array_values( array_unique( array_filter( $vals ) ) );
            }

            $ctx[] = [
                'id'         => $id_counter++,
                'theme'      => sanitize_key( $plan_theme ),
                'title'      => $plan['post_title'] ?? '',
                'subtitle'   => $plan['post_excerpt'] ?? '',
                'price_html' => $price_html,
                'billing'    => $billing,
                'cta'        => self::prepare_cta( $best_variant ),
                'badge'      => [], // table/plan badge resolution can be added later
                'featured'   => $featured,
                'variants'   => $variants,
                'datasets'   => $datasets,
                'specs'      => $specs,
                'deal_label' => '',
                'hero_image_id' => $hero_image_id,
                'hero_image_url' => $hero_image_url,
                'trust_items_override' => array_slice( $trust_override, 0, 3 ),
            ];
        }
        return $ctx;
    }

    /**
     * Variant resolver (copied from shortcode logic).
     */
    private static function resolve_variant( array $variants, array $selection ) {
        if ( empty( $variants ) ) {
            return null;
        }
        $best = null;
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

    private static function build_price_html( $variant, $settings ) {
        if ( ! $variant ) {
            return '<span class="pwpl-plan__price--empty">' . esc_html__( 'Contact us', 'planify-wp-pricing-lite' ) . '</span>';
        }

        $price = $variant['price'] ?? '';
        $sale  = $variant['sale_price'] ?? '';
        if ( $price === '' && $sale === '' ) {
            return '<span class="pwpl-plan__price--empty">' . esc_html__( 'Contact us', 'planify-wp-pricing-lite' ) . '</span>';
        }

        $price_num = is_numeric( $price ) ? (float) $price : null;
        $sale_num  = is_numeric( $sale ) ? (float) $sale : null;
        $formatted_price = ( null !== $price_num ) ? self::format_price( $price_num, $settings ) : '';
        $formatted_sale  = ( null !== $sale_num )  ? self::format_price( $sale_num,  $settings ) : '';

        $has_discount = null !== $price_num && null !== $sale_num && $price_num > 0 && $sale_num >= 0 && $sale_num < $price_num;

        $unit_label = trim( (string) ( $variant['unit'] ?? '' ) );
        if ( '' === $unit_label ) {
            $unit_label = __( '/mo', 'planify-wp-pricing-lite' );
        }

        if ( $has_discount && $formatted_sale && $formatted_price ) {
            $pct = (int) round( ( ( $price_num - $sale_num ) / $price_num ) * 100 );
            $badge_html = '';
            if ( $pct > 0 ) {
                $badge_html = '<span class="fvps-price-badge" aria-label="' . esc_attr( sprintf( __( '%d%% off', 'planify-wp-pricing-lite' ), $pct ) ) . '">' . esc_html( sprintf( __( '%d%% OFF', 'planify-wp-pricing-lite' ), $pct ) ) . '</span>';
            }
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
                . '<span class="pwpl-price-unit">' . esc_html( $unit_label ) . '</span>'
                . '</span>';
            return '<span class="pwpl-plan__price-original">' . esc_html( $formatted_price ) . '</span>' . $badge_html . $sale_html;
        }

        $display = $formatted_sale ?: $formatted_price;
        $pfx = ''; $val = $display; $sfx = '';
        if ( preg_match( '/^([^\d\-]*)([0-9][0-9\.,]*)\s*([^\d]*)$/u', (string) $display, $m ) ) {
            $pfx = trim( (string) ( $m[1] ?? '' ) );
            $val = (string) ( $m[2] ?? $display );
            $sfx = trim( (string) ( $m[3] ?? '' ) );
        }
        $single_html = ( $pfx !== '' ? '<span class="pwpl-price-currency pwpl-currency--prefix">' . esc_html( $pfx ) . '</span>' : '' )
            . '<span class="pwpl-price-value">' . esc_html( $val ) . '</span>'
            . ( $sfx !== '' ? '<span class="pwpl-price-currency pwpl-currency--suffix">' . esc_html( $sfx ) . '</span>' : '' )
            . '<span class="pwpl-price-unit">' . esc_html( $unit_label ) . '</span>';
        return '<span class="pwpl-plan__price">' . $single_html . '</span>';
    }

    private static function prepare_cta( $variant ): array {
        $label = $variant['cta_label'] ?? __( 'Select Plan', 'planify-wp-pricing-lite' );
        $url   = $variant['cta_url'] ?? '';
        $hidden = empty( $url );
        $target_attr = '';
        $rel_attr    = '';
        if ( ! empty( $variant['target'] ) ) {
            $target_attr = ' target="' . esc_attr( $variant['target'] ) . '"';
        }
        if ( ! empty( $variant['rel'] ) ) {
            $rel_attr = ' rel="' . esc_attr( $variant['rel'] ) . '"';
        }
        return [
            'label'       => $label,
            'url'         => $url ?: '#',
            'hidden'      => $hidden,
            'target_attr' => $target_attr,
            'rel_attr'    => $rel_attr,
            'blank'       => ( isset( $variant['target'] ) && '_blank' === $variant['target'] ),
        ];
    }

    private static function get_billing_copy( array $selection, array $dimension_labels ) {
        $period_slug = $selection['period'] ?? '';
        if ( '' === $period_slug ) {
            return '';
        }
        $raw = $dimension_labels['period'][ $period_slug ] ?? '';
        if ( '' === $raw ) {
            return '';
        }
        $t = strtolower( trim( (string) $raw ) );
        if ( false !== strpos( $t, 'month' ) ) {
            return esc_html__( 'Billed monthly', 'planify-wp-pricing-lite' );
        }
        if ( false !== strpos( $t, 'annual' ) || false !== strpos( $t, 'year' ) ) {
            return esc_html__( 'Billed annually*', 'planify-wp-pricing-lite' );
        }
        if ( false !== strpos( $t, 'quarter' ) ) {
            return esc_html__( 'Billed quarterly', 'planify-wp-pricing-lite' );
        }
        if ( false !== strpos( $t, 'semi' ) ) {
            return esc_html__( 'Billed semi‑annually', 'planify-wp-pricing-lite' );
        }
        return sprintf( esc_html__( 'Billed %s', 'planify-wp-pricing-lite' ), strtolower( $raw ) );
    }

    private static function format_price( $amount, $settings ) {
        $amount = (float) str_replace( [ ',', ' ' ], '', $amount );
        $decimals = isset( $settings['price_decimals'] ) ? (int) $settings['price_decimals'] : 2;
        $thousand = $settings['thousand_sep'] ?? ',';
        $decimal  = $settings['decimal_sep'] ?? '.';

        $formatted = number_format_i18n( $amount, $decimals );
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
