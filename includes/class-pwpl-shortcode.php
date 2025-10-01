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
        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'pwpl-frontend', PWPL_URL . 'assets/css/frontend.css', [], filemtime( $css ) );
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

        static $track_instance = 0;
        $track_instance++;
        $track_id = 'pwpl-plan-track-' . $table_id . '-' . $track_instance;
        $is_rtl = is_rtl();
        $prev_icon = $is_rtl ? '&#10095;' : '&#10094;';
        $next_icon = $is_rtl ? '&#10094;' : '&#10095;';

        ob_start();
        ?>
        <div class="pwpl-table" data-table-id="<?php echo esc_attr( $table_id ); ?>"<?php foreach ( $active_values as $dim => $value ) { echo ' data-active-' . esc_attr( $dim ) . '="' . esc_attr( $value ) . '"'; } ?>>
            <div class="pwpl-table__header">
                <h3 class="pwpl-table__title"><?php echo $table_title; ?></h3>
            </div>

            <?php foreach ( $dimension_values as $dimension => $values ) : ?>
                <div class="pwpl-dimension-nav" data-dimension="<?php echo esc_attr( $dimension ); ?>">
                    <?php foreach ( $values as $index => $item ) :
                        $is_active = $index === 0 ? ' is-active' : '';
                        ?>
                        <button type="button" class="pwpl-tab<?php echo $is_active; ?>" data-value="<?php echo esc_attr( $item['slug'] ); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                            <?php echo esc_html( $item['label'] ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="pwpl-plan-rail">
                <button type="button" class="pwpl-plan-nav pwpl-plan-nav--prev" data-direction="prev" aria-controls="<?php echo esc_attr( $track_id ); ?>" aria-disabled="true">
                    <span aria-hidden="true"><?php echo $prev_icon; ?></span>
                </button>
                <div id="<?php echo esc_attr( $track_id ); ?>" class="pwpl-plan-grid" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Pricing plans', 'planify-wp-pricing-lite' ); ?>">
                <?php foreach ( $plans as $plan ) :
                    $plan_title = $plan->post_title ? esc_html( $plan->post_title ) : sprintf( esc_html__( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan->ID );
                    $theme = get_post_meta( $plan->ID, PWPL_Meta::PLAN_THEME, true ) ?: 'classic';
                    $specs = get_post_meta( $plan->ID, PWPL_Meta::PLAN_SPECS, true );
                    if ( ! is_array( $specs ) ) {
                        $specs = [];
                    }
                    $variants = get_post_meta( $plan->ID, PWPL_Meta::PLAN_VARIANTS, true );
                    if ( ! is_array( $variants ) ) {
                        $variants = [];
                    }

                    $variant = $this->resolve_variant( $variants, $active_values );
                    $price_html = $this->build_price_html( $variant, $settings );
                    ?>
                    <article class="pwpl-plan pwpl-theme--<?php echo esc_attr( $theme ); ?>" data-plan-id="<?php echo esc_attr( $plan->ID ); ?>" data-variants="<?php echo esc_attr( wp_json_encode( $variants ) ); ?>">
                        <header class="pwpl-plan__header">
                            <h4 class="pwpl-plan__title"><?php echo $plan_title; ?></h4>
                            <div class="pwpl-plan__pricing" data-pwpl-price>
                                <?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </header>
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
                <button type="button" class="pwpl-plan-nav pwpl-plan-nav--next" data-direction="next" aria-controls="<?php echo esc_attr( $track_id ); ?>" aria-disabled="true">
                    <span aria-hidden="true"><?php echo $next_icon; ?></span>
                </button>
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
