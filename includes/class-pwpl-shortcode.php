<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Shortcode {
    private $settings;

    public function __construct() {
        $this->settings = new PWPL_Settings();
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

        $badge_shadow = isset( $table_badges['shadow'] ) ? (int) $table_badges['shadow'] : 0;
        $badge_shadow = max( 0, min( $badge_shadow, 60 ) );
        $table_style_attr = '';
        if ( $badge_shadow > 0 ) {
            $table_style_attr = ' style="--pwpl-badge-shadow-strength:' . esc_attr( $badge_shadow ) . ';"';
        }

        ob_start();
        ?>
        <div class="pwpl-table pwpl-table--theme-<?php echo esc_attr( $table_theme ); ?>" data-table-id="<?php echo esc_attr( $table_id ); ?>" data-table-theme="<?php echo esc_attr( $table_theme ); ?>" data-badges="<?php echo esc_attr( $table_badges_json ); ?>" data-dimension-labels="<?php echo esc_attr( $dimension_labels_json ); ?>"<?php foreach ( $active_values as $dim => $value ) { echo ' data-active-' . esc_attr( $dim ) . '="' . esc_attr( $value ) . '"'; } echo $table_style_attr; ?>>
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
                    $specs = get_post_meta( $plan->ID, PWPL_Meta::PLAN_SPECS, true );
                    if ( ! is_array( $specs ) ) {
                        $specs = [];
                    }
                    $variants = get_post_meta( $plan->ID, PWPL_Meta::PLAN_VARIANTS, true );
                    if ( ! is_array( $variants ) ) {
                        $variants = [];
                    }

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
                    <article class="pwpl-plan pwpl-theme--<?php echo esc_attr( $theme ); ?><?php echo $is_featured ? ' pwpl-plan--featured' : ''; ?>" data-plan-id="<?php echo esc_attr( $plan->ID ); ?>" data-variants="<?php echo esc_attr( $variants_json ); ?>" data-badges-override="<?php echo esc_attr( $override_json ); ?>">
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

        if ( $formatted_sale && $formatted_price ) {
            return '<span class="pwpl-plan__price-sale">' . esc_html( $formatted_sale ) . '</span><span class="pwpl-plan__price-original">' . esc_html( $formatted_price ) . '</span>';
        }

        $display = $formatted_price ?: $formatted_sale;
        return '<span class="pwpl-plan__price">' . esc_html( $display ) . '</span>';
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
