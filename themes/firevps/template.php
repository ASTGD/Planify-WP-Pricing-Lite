<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$table    = is_array( $table ?? null ) ? $table : [];
$plans    = is_array( $plans ?? null ) ? array_filter( $plans ) : [];
$manifest = is_array( $table['manifest'] ?? null ) ? $table['manifest'] : [];
$theme    = sanitize_key( $table['theme'] ?? 'classic' );

$classes = [ 'pwpl-table', 'pwpl-table--theme-' . $theme ];
if ( ! empty( $manifest['containerClass'] ) ) {
    $classes[] = sanitize_html_class( $manifest['containerClass'] );
}
$classes = array_filter( array_unique( $classes ) );

$active      = is_array( $table['active'] ?? null ) ? $table['active'] : [];
$allowed     = is_array( $table['allowed'] ?? null ) ? $table['allowed'] : [];
$tabs_source = is_array( $table['tabs'] ?? null ) ? $table['tabs'] : ( $table['dimensions'] ?? [] );
$badges      = wp_json_encode( $table['badges'] ?? [] );
$labels      = wp_json_encode( $table['dimension_labels'] ?? [] );
$availability = wp_json_encode( $table['availability'] ?? [] );

$wrapper_attrs = [
    'data-table-id'         => (int) ( $table['id'] ?? 0 ),
    'data-table-theme'      => $theme,
    'data-badges'           => $badges ?: '{}',
    'data-dimension-labels' => $labels ?: '{}',
];

foreach ( [ 'platform', 'period', 'location' ] as $dimension ) {
    $current = sanitize_title( $active[ $dimension ] ?? '' );
    if ( $current ) {
        $wrapper_attrs[ 'data-active-' . $dimension ] = $current;
    }
    $allowed_slugs = array_filter( array_map( 'sanitize_title', (array) ( $allowed[ $dimension ] ?? [] ) ) );
    if ( $allowed_slugs ) {
        $wrapper_attrs['data-allowed-' . $dimension . 's'] = implode( ',', $allowed_slugs );
    }
}

if ( $availability ) {
    $wrapper_attrs['data-availability'] = $availability;
}

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php
foreach ( $wrapper_attrs as $attr => $value ) {
    printf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
}
?>>
    <?php foreach ( [ 'platform', 'period', 'location' ] as $dimension ) :
        $items = (array) ( $tabs_source[ $dimension ]['values'] ?? [] );
        if ( ! $items ) {
            continue;
        }
        ?>
        <div class="pwpl-dimension-nav" data-dimension="<?php echo esc_attr( $dimension ); ?>">
            <?php foreach ( $items as $item ) :
                $slug = sanitize_title( $item['slug'] ?? '' );
                if ( ! $slug ) {
                    continue;
                }
                $label   = $item['label'] ?? $slug;
                $current = $wrapper_attrs[ 'data-active-' . $dimension ] ?? '';
                ?>
                <button type="button" class="pwpl-tab<?php echo $current === $slug ? ' is-active' : ''; ?>" data-value="<?php echo esc_attr( $slug ); ?>">
                    <span class="pwpl-tab__label"><?php echo esc_html( $label ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="pwpl-plans-rail">
        <?php foreach ( $plans as $plan ) :
            $plan_id   = (int) ( $plan['id'] ?? 0 );
            $plan_slug = sanitize_key( $plan['theme'] ?? $theme );
            $title     = $plan['title'] ?? sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan_id );
            $price     = $plan['price_html'] ?? '';
            $cta       = is_array( $plan['cta'] ?? null ) ? $plan['cta'] : [];
            $cta_label = $cta['label'] ?? __( 'Select Plan', 'planify-wp-pricing-lite' );
            $cta_url   = $cta['url'] ?? '#';
            $datasets  = is_array( $plan['datasets'] ?? null ) ? $plan['datasets'] : [];
            $platforms = array_filter( array_map( 'sanitize_title', (array) ( $datasets['platforms'] ?? $plan['platforms'] ?? [] ) ) );
            $periods   = array_filter( array_map( 'sanitize_title', (array) ( $datasets['periods'] ?? $plan['periods'] ?? [] ) ) );
            $locations = array_filter( array_map( 'sanitize_title', (array) ( $datasets['locations'] ?? $plan['locations'] ?? [] ) ) );
            $variants  = wp_json_encode( $plan['variants'] ?? [] );
            $badge     = wp_json_encode( $plan['badge'] ?? [] );
            ?>
            <article class="pwpl-plan fvps-card pwpl-theme--<?php echo esc_attr( $plan_slug ); ?>"
                data-plan-id="<?php echo esc_attr( $plan_id ); ?>"
                data-plan-theme="<?php echo esc_attr( $plan_slug ); ?>"
                data-platforms="<?php echo esc_attr( $platforms ? implode( ',', $platforms ) : '*' ); ?>"
                data-periods="<?php echo esc_attr( $periods ? implode( ',', $periods ) : '*' ); ?>"
                data-locations="<?php echo esc_attr( $locations ? implode( ',', $locations ) : '*' ); ?>"
                data-variants="<?php echo esc_attr( $variants ?: '[]' ); ?>"
                data-badge="<?php echo esc_attr( $badge ?: '{}' ); ?>">
                <header class="pwpl-plan__header">
                    <h3 class="pwpl-plan__title"><?php echo esc_html( $title ); ?></h3>
                </header>
                <div class="pwpl-plan__pricing" data-pwpl-price><?php echo wp_kses_post( $price ); ?></div>
                <div class="pwpl-plan__cta" data-pwpl-cta>
                    <a class="pwpl-plan__cta-button" data-pwpl-cta-button href="<?php echo esc_url( $cta_url ); ?>"<?php echo ! empty( $cta['blank'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        <span data-pwpl-cta-label><?php echo esc_html( $cta_label ); ?></span>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
