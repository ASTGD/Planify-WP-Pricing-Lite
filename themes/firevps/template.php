<?php
/**
 * FireVPS theme template scaffold.
 *
 * Context:
 * - $table : array with id, theme, manifest, dimensions, active, allowed, badges, dimension_labels, availability.
 * - $plans : array of plan data (id, title, variants, specs, cta, featured, theme, badge, platforms).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$table    = isset( $table ) && is_array( $table ) ? $table : [];
$plans    = isset( $plans ) && is_array( $plans ) ? $plans : [];
$manifest = isset( $table['manifest'] ) && is_array( $table['manifest'] ) ? $table['manifest'] : [];

$theme_slug = isset( $table['theme'] ) ? sanitize_key( $table['theme'] ) : 'classic';

$classes = [ 'pwpl-table', 'pwpl-table--theme-' . $theme_slug ];
if ( ! empty( $manifest['containerClass'] ) ) {
    $classes[] = sanitize_html_class( $manifest['containerClass'] );
}
$classes = array_filter( array_unique( $classes ) );

$active = isset( $table['active'] ) && is_array( $table['active'] ) ? $table['active'] : [];
$allowed = isset( $table['allowed'] ) && is_array( $table['allowed'] ) ? $table['allowed'] : [];

$badges_json = isset( $table['badges'] ) ? wp_json_encode( $table['badges'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '{}';
$labels_json = isset( $table['dimension_labels'] ) ? wp_json_encode( $table['dimension_labels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '{}';
$availability_json = isset( $table['availability'] ) ? wp_json_encode( $table['availability'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';

$allowed_platforms = isset( $allowed['platform'] ) && is_array( $allowed['platform'] )
    ? array_filter( array_map( 'sanitize_title', $allowed['platform'] ) )
    : [];

$wrapper_attrs = [
    'data-table-id'         => isset( $table['id'] ) ? (int) $table['id'] : 0,
    'data-table-theme'      => $theme_slug,
    'data-badges'           => $badges_json ?: '{}',
    'data-dimension-labels' => $labels_json ?: '{}',
];

foreach ( [ 'platform', 'period', 'location' ] as $dimension ) {
    if ( ! empty( $active[ $dimension ] ) ) {
        $wrapper_attrs[ 'data-active-' . $dimension ] = sanitize_title( $active[ $dimension ] );
    }
}

if ( $allowed_platforms ) {
    $wrapper_attrs['data-allowed-platforms'] = implode( ',', $allowed_platforms );
}

if ( $availability_json ) {
    $wrapper_attrs['data-availability'] = $availability_json;
}

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php
    foreach ( $wrapper_attrs as $attr => $value ) {
        printf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
    }
?>>
    <?php foreach ( [ 'platform', 'period', 'location' ] as $dimension ) :
        $dimension_config = isset( $table['dimensions'][ $dimension ] ) && is_array( $table['dimensions'][ $dimension ] )
            ? $table['dimensions'][ $dimension ]
            : [];
        $values = isset( $dimension_config['values'] ) && is_array( $dimension_config['values'] ) ? $dimension_config['values'] : [];

        if ( empty( $values ) ) {
            continue;
        }

        $active_value = isset( $active[ $dimension ] ) ? sanitize_title( $active[ $dimension ] ) : '';
        ?>
        <div class="pwpl-dimension-nav" data-dimension="<?php echo esc_attr( $dimension ); ?>">
            <?php foreach ( $values as $value ) :
                $slug = sanitize_title( $value['slug'] ?? '' );
                if ( ! $slug ) {
                    continue;
                }
                $label     = $value['label'] ?? $slug;
                $is_active = $active_value && $active_value === $slug;
                ?>
                <button type="button" class="pwpl-tab<?php echo $is_active ? ' is-active' : ''; ?>" data-value="<?php echo esc_attr( $slug ); ?>">
                    <span class="pwpl-tab__label"><?php echo esc_html( $label ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="pwpl-plans-rail">
        <?php foreach ( $plans as $plan ) :
            $plan_id     = isset( $plan['id'] ) ? (int) $plan['id'] : 0;
            $plan_title  = isset( $plan['title'] ) ? $plan['title'] : sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan_id );
            $plan_theme  = isset( $plan['theme'] ) ? sanitize_key( $plan['theme'] ) : $theme_slug;
            $plan_badge  = isset( $plan['badge'] ) ? wp_json_encode( $plan['badge'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '{}';
            $plan_specs  = isset( $plan['specs'] ) && is_array( $plan['specs'] ) ? $plan['specs'] : [];
            $plan_cta    = isset( $plan['cta'] ) && is_array( $plan['cta'] ) ? $plan['cta'] : [];
            $variants    = isset( $plan['variants'] ) ? wp_json_encode( $plan['variants'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '[]';
            $platforms   = isset( $plan['platforms'] ) && is_array( $plan['platforms'] )
                ? implode( ',', array_map( 'sanitize_title', $plan['platforms'] ) )
                : '';
            $is_featured = ! empty( $plan['featured'] );
            ?>
            <article class="pwpl-plan fvps-card pwpl-theme--<?php echo esc_attr( $plan_theme ); ?>"
                data-plan-id="<?php echo esc_attr( $plan_id ); ?>"
                data-plan-theme="<?php echo esc_attr( $plan_theme ); ?>"
                data-platforms="<?php echo esc_attr( $platforms ?: '*' ); ?>"
                data-variants="<?php echo esc_attr( $variants ); ?>"
                data-badge="<?php echo esc_attr( $plan_badge ); ?>">
                <header class="pwpl-plan__header">
                    <h3 class="pwpl-plan__title"><?php echo esc_html( $plan_title ); ?></h3>
                    <?php if ( $is_featured ) : ?>
                        <span class="pwpl-plan__featured-tag" data-pwpl-featured-label><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
                    <?php endif; ?>
                </header>
                <div class="pwpl-plan__pricing">
                    <span class="pwpl-plan__price" data-bind="price" data-pwpl-price></span>
                </div>
                <?php if ( $plan_specs ) : ?>
                    <ul class="pwpl-plan__specs">
                        <?php foreach ( $plan_specs as $spec ) :
                            if ( empty( $spec['label'] ) && empty( $spec['value'] ) ) {
                                continue;
                            }
                            ?>
                            <li>
                                <?php if ( ! empty( $spec['label'] ) ) : ?>
                                    <span class="pwpl-plan__spec-label"><?php echo esc_html( $spec['label'] ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $spec['value'] ) ) : ?>
                                    <span class="pwpl-plan__spec-value"><?php echo esc_html( $spec['value'] ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="pwpl-plan__cta">
                    <a class="pwpl-plan__cta-button"
                        data-pwpl-cta-button
                        href="<?php echo esc_url( $plan_cta['url'] ?? '#' ); ?>"<?php echo ! empty( $plan_cta['blank'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        <span data-pwpl-cta-label><?php echo esc_html( $plan_cta['label'] ?? __( 'Select Plan', 'planify-wp-pricing-lite' ) ); ?></span>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
