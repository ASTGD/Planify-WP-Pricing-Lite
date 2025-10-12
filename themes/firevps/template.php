<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
printf( '<!-- PWPL FireVPS source: %s -->', esc_html( __FILE__ ) );

$table      = is_array( $table ?? null ) ? $table : [];
$plans      = is_array( $plans ?? null ) ? array_filter( $plans ) : [];
$manifest   = is_array( $table['manifest'] ?? null ) ? $table['manifest'] : [];
$theme_slug = sanitize_key( $table['theme'] ?? 'classic' );

$classes = [ 'pwpl-table', 'pwpl-table--theme-' . $theme_slug ];
if ( ! empty( $manifest['containerClass'] ) ) {
	$classes[] = sanitize_html_class( $manifest['containerClass'] );
}
$classes = array_filter( array_unique( $classes ) );

$active      = is_array( $table['active'] ?? null ) ? $table['active'] : [];
$allowed     = is_array( $table['allowed'] ?? null ) ? $table['allowed'] : [];
$tabs_source = is_array( $table['tabs'] ?? null ) ? $table['tabs'] : ( $table['dimensions'] ?? [] );

$badges_json       = wp_json_encode( $table['badges'] ?? [] );
$labels_json       = wp_json_encode( $table['dimension_labels'] ?? [] );
$availability_json = wp_json_encode( $table['availability'] ?? [] );

$wrapper_attrs = [
	'data-table-id'         => (int) ( $table['id'] ?? 0 ),
	'data-table-theme'      => $theme_slug,
	'data-badges'           => $badges_json ?: '{}',
	'data-dimension-labels' => $labels_json ?: '{}',
];

foreach ( [ 'platform', 'period', 'location' ] as $dimension ) {
	$current = sanitize_title( $active[ $dimension ] ?? '' );
	if ( $current ) {
		$wrapper_attrs[ 'data-active-' . $dimension ] = $current;
	}
	$allowed_slugs = array_filter( array_map( 'sanitize_title', (array) ( $allowed[ $dimension ] ?? [] ) ) );
	if ( $allowed_slugs ) {
		$wrapper_attrs[ 'data-allowed-' . $dimension . 's' ] = implode( ',', $allowed_slugs );
	}
}

if ( $availability_json ) {
	$wrapper_attrs['data-availability'] = $availability_json;
}

$table_title    = $table['title'] ?? '';
$table_subtitle = $table['subtitle'] ?? '';

$icon_map = [
	'ram'          => 'ram',
	'memory'       => 'ram',
	'cpu'          => 'cpu',
	'processor'    => 'cpu',
	'disk'         => 'disk',
	'storage'      => 'disk',
	'ssd'          => 'disk',
	'nvme'         => 'disk',
	'bandwidth'    => 'bandwidth',
	'traffic'      => 'bandwidth',
	'network'      => 'network',
	'ip'           => 'ip',
	'ip-address'   => 'ip',
	'ip-addresses' => 'ip',
];

$spec_priority = [
	'ram',
	'memory',
	'cpu',
	'processor',
	'disk',
	'storage',
	'ssd',
	'nvme',
	'bandwidth',
	'traffic',
	'network',
	'ip',
	'ip-address',
	'ip-addresses',
];

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
<?php
foreach ( $wrapper_attrs as $attr => $value ) {
	printf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
}
?>
>
	<?php
	static $fvps_sprite_inlined = false;
	if ( ! $fvps_sprite_inlined ) {
		$sprite_path = plugin_dir_path( __FILE__ ) . 'icons.svg';
		if ( is_readable( $sprite_path ) ) {
			echo file_get_contents( $sprite_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$fvps_sprite_inlined = true;
		}
	}
	?>

	<?php if ( $table_title ) : ?>
		<header class="fvps-table-header">
			<h2 class="fvps-table-title"><?php echo esc_html( $table_title ); ?></h2>
			<?php if ( $table_subtitle ) : ?>
				<p class="fvps-table-subtitle"><?php echo esc_html( $table_subtitle ); ?></p>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<?php foreach ( [ 'platform', 'period', 'location' ] as $dimension ) :
		$items = (array) ( $tabs_source[ $dimension ]['values'] ?? [] );
		if ( empty( $items ) ) {
			continue;
		}
		$current_value = $wrapper_attrs[ 'data-active-' . $dimension ] ?? '';
		?>
		<div class="pwpl-dimension-nav fvps-dimension-nav" data-dimension="<?php echo esc_attr( $dimension ); ?>">
			<?php foreach ( $items as $item ) :
				$slug = sanitize_title( $item['slug'] ?? '' );
				if ( ! $slug ) {
					continue;
				}
				$label = $item['label'] ?? $slug;
				$is_active = $current_value === $slug;
				?>
				<button type="button"
					class="pwpl-tab fvps-tab<?php echo $is_active ? ' is-active' : ''; ?>"
					data-value="<?php echo esc_attr( $slug ); ?>"
					aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
					<span class="pwpl-tab__label"><?php echo esc_html( $label ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<div class="pwpl-plan-rail-wrapper fvps-plan-rail-wrapper">
		<div class="pwpl-plan-nav pwpl-plan-nav--prev" hidden>
			<button type="button" class="pwpl-plan-nav__btn" data-direction="prev" aria-label="<?php esc_attr_e( 'Scroll previous plans', 'planify-wp-pricing-lite' ); ?>">&#10094;</button>
		</div>
		<div class="pwpl-plan-nav pwpl-plan-nav--next" hidden>
			<button type="button" class="pwpl-plan-nav__btn" data-direction="next" aria-label="<?php esc_attr_e( 'Scroll next plans', 'planify-wp-pricing-lite' ); ?>">&#10095;</button>
		</div>

		<div class="pwpl-plans-rail fvps-plan-rail" tabindex="0">
			<?php foreach ( $plans as $plan ) :
				$plan_id    = (int) ( $plan['id'] ?? 0 );
				$plan_theme = sanitize_key( $plan['theme'] ?? $theme_slug );
				$title      = $plan['title'] ?? sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan_id );
				$lead       = $plan['subtitle'] ?? '';
				$price_html = $plan['price_html'] ?? '';
				$billing    = isset( $plan['billing'] ) ? (string) $plan['billing'] : '';

				$cta        = is_array( $plan['cta'] ?? null ) ? $plan['cta'] : [];
				$cta_label  = $cta['label'] ?? __( 'Select Plan', 'planify-wp-pricing-lite' );
				$cta_url    = $cta['url'] ?? '#';
				$cta_hidden = ! empty( $cta['hidden'] );

				$badge_data = is_array( $plan['badge'] ?? null ) ? $plan['badge'] : [];
				$badge_attr = wp_json_encode( $badge_data );

				$datasets  = is_array( $plan['datasets'] ?? null ) ? $plan['datasets'] : [];
				$platforms = array_filter( array_map( 'sanitize_title', (array) ( $datasets['platforms'] ?? ( $plan['platforms'] ?? [] ) ) ) );
				$periods   = array_filter( array_map( 'sanitize_title', (array) ( $datasets['periods'] ?? ( $plan['periods'] ?? [] ) ) ) );
				$locations = array_filter( array_map( 'sanitize_title', (array) ( $datasets['locations'] ?? ( $plan['locations'] ?? [] ) ) ) );
				$variants  = wp_json_encode( $plan['variants'] ?? [] );

				$raw_specs = array_filter( array_map( function ( $spec ) {
					if ( ! is_array( $spec ) ) {
						return null;
					}
					$label = trim( (string) ( $spec['label'] ?? '' ) );
					$value = trim( (string) ( $spec['value'] ?? '' ) );
					if ( '' === $label && '' === $value ) {
						return null;
					}
					return [
						'label' => $label,
						'value' => $value,
						'slug'  => sanitize_title( $label ),
					];
				}, (array) ( $plan['specs'] ?? [] ) ) );

				$ordered_specs = [];
				if ( $raw_specs ) {
					$mapped_specs = [];
					foreach ( $raw_specs as $spec_entry ) {
						if ( empty( $spec_entry['slug'] ) ) {
							$mapped_specs[] = $spec_entry;
							continue;
						}
						$mapped_specs[ $spec_entry['slug'] ] = $spec_entry;
					}
					foreach ( $spec_priority as $priority_slug ) {
						if ( isset( $mapped_specs[ $priority_slug ] ) ) {
							$ordered_specs[] = $mapped_specs[ $priority_slug ];
							unset( $mapped_specs[ $priority_slug ] );
						}
					}
					foreach ( $mapped_specs as $remaining ) {
						if ( ! empty( $remaining ) ) {
							$ordered_specs[] = $remaining;
						}
					}
				}

				$badge_label = trim( (string) ( $badge_data['label'] ?? '' ) );
				$badge_color = $badge_data['color'] ?? '';
				$badge_text  = $badge_data['text_color'] ?? '';
				$is_featured = ! empty( $plan['featured'] );
				$deal_label  = $plan['deal_label'] ?? '';
				?>
				<article class="pwpl-plan fvps-card pwpl-theme--<?php echo esc_attr( $plan_theme ); ?>"
					data-plan-id="<?php echo esc_attr( $plan_id ); ?>"
					data-plan-theme="<?php echo esc_attr( $plan_theme ); ?>"
					data-platforms="<?php echo esc_attr( $platforms ? implode( ',', $platforms ) : '*' ); ?>"
					data-periods="<?php echo esc_attr( $periods ? implode( ',', $periods ) : '*' ); ?>"
					data-locations="<?php echo esc_attr( $locations ? implode( ',', $locations ) : '*' ); ?>"
					data-variants="<?php echo esc_attr( $variants ?: '[]' ); ?>"
					data-badge="<?php echo esc_attr( $badge_attr ?: '{}' ); ?>">
					<div class="fvps-card__top">
						<div class="fvps-card__badges">
							<?php if ( $badge_label ) : ?>
								<span class="fvps-plan-badge" style="<?php
									if ( $badge_color ) {
										printf( '--fvps-badge-bg:%s;', esc_attr( $badge_color ) );
									}
									if ( $badge_text ) {
										printf( '--fvps-badge-color:%s;', esc_attr( $badge_text ) );
									}
								?>">
									<?php echo esc_html( $badge_label ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $is_featured ) : ?>
								<span class="fvps-plan-featured" data-pwpl-featured-label><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
							<?php endif; ?>
						</div>

						<div class="fvps-card__heading">
							<h3 class="pwpl-plan__title"><?php echo esc_html( $title ); ?></h3>
							<?php if ( $lead ) : ?>
								<p class="fvps-plan-lead"><?php echo esc_html( $lead ); ?></p>
							<?php endif; ?>
							<?php if ( $deal_label ) : ?>
								<span class="fvps-plan-deal"><?php echo esc_html( $deal_label ); ?></span>
							<?php endif; ?>
						</div>

						<div class="fvps-card__price" data-pwpl-price><?php echo wp_kses_post( $price_html ); ?></div>
						<?php if ( $billing ) : ?>
							<p class="pwpl-plan__billing" data-pwpl-billing><?php echo esc_html( $billing ); ?></p>
						<?php endif; ?>
					</div>

					<?php if ( $ordered_specs ) : ?>
						<ul class="pwpl-plan__specs fvps-card__specs">
							<?php foreach ( $ordered_specs as $spec ) :
								$slug       = $spec['slug'] ?? '';
								$icon_id    = $icon_map[ $slug ] ?? 'generic';
								$icon_class = $slug ? 'fvps-icon-' . $slug : 'fvps-icon-generic';
								?>
								<li class="fvps-spec fvps-spec--<?php echo esc_attr( $slug ?: 'generic' ); ?>">
									<svg class="fvps-icon <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true" focusable="false">
										<use href="#<?php echo esc_attr( $icon_id ); ?>" xlink:href="#<?php echo esc_attr( $icon_id ); ?>"></use>
									</svg>
									<div class="fvps-spec__text">
										<span class="fvps-spec__label"><?php echo esc_html( $spec['label'] ); ?></span>
										<span class="fvps-spec__value"><?php echo esc_html( $spec['value'] ); ?></span>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<div class="pwpl-plan__cta fvps-card__cta" data-pwpl-cta>
						<a class="pwpl-plan__cta-button fvps-button"
							data-pwpl-cta-button
							href="<?php echo esc_url( $cta_url ); ?>"<?php
								if ( ! empty( $cta['blank'] ) ) {
									echo ' target="_blank" rel="noopener noreferrer"';
								}
								echo $cta_hidden ? ' hidden' : '';
							?>>
							<span data-pwpl-cta-label><?php echo esc_html( $cta_label ); ?></span>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</div>
