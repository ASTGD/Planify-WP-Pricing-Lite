<?php
/**
 * FireVPS layout: comparison matrix.
 *
 * Dedicated layout for side-by-side spec comparison.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
printf( '<!-- PWPL FireVPS source: %s -->', esc_html( __FILE__ ) );

$table      = is_array( $table ?? null ) ? $table : [];
$plans      = is_array( $plans ?? null ) ? array_filter( $plans ) : [];
$manifest   = is_array( $table['manifest'] ?? null ) ? $table['manifest'] : [];
$theme_slug = sanitize_key( $table['theme'] ?? 'classic' );
$dom_id_raw = $table['dom_id'] ?? '';
$dom_id     = $dom_id_raw ? sanitize_html_class( $dom_id_raw ) : '';
$layout_type = isset( $table['layout_type'] ) ? sanitize_key( (string) $table['layout_type'] ) : 'grid';
$preset      = isset( $table['preset'] ) ? sanitize_key( (string) $table['preset'] ) : '';
$style_block = '';
if ( ! empty( $table['style_block'] ) && is_string( $table['style_block'] ) ) {
	$style_block = $table['style_block'];
}

$classes = [ 'pwpl-table', 'pwpl-table--theme-' . $theme_slug ];
if ( ! empty( $manifest['containerClass'] ) ) {
	$classes[] = sanitize_html_class( $manifest['containerClass'] );
}
$extra_classes_raw = isset( $table['extra_classes'] ) ? (string) $table['extra_classes'] : '';
if ( $extra_classes_raw ) {
	$extra_list = preg_split( '/\\s+/', $extra_classes_raw );
	foreach ( $extra_list as $extra_class ) {
		$extra_class = trim( (string) $extra_class );
		if ( $extra_class ) {
			$classes[] = sanitize_html_class( $extra_class );
		}
	}
}
$tabs_glass_enabled  = ! empty( $table['tabs_glass'] );
$cards_glass_enabled = ! empty( $table['cards_glass'] );
$specs_style         = isset( $table['specs_style'] ) ? sanitize_key( (string) $table['specs_style'] ) : '';
$specs_anim          = is_array( $table['specs_anim'] ?? null ) ? $table['specs_anim'] : [];
$sticky_cta_mobile   = ! empty( $table['sticky_cta_mobile'] );

if ( $tabs_glass_enabled ) {
	$glass_tint      = (string) ( $table['tabs_glass_tint'] ?? '' );
	$glass_intensity = isset( $table['tabs_glass_intensity'] ) ? (int) $table['tabs_glass_intensity'] : 60;
	$glass_intensity = max( 10, min( 100, $glass_intensity ) );
	$glass_frost     = isset( $table['tabs_glass_frost'] ) ? (int) $table['tabs_glass_frost'] : 6;
	$glass_frost     = max( 0, min( 24, $glass_frost ) );
}
if ( $tabs_glass_enabled ) {
	$classes[] = 'pwpl-tabs-glass';
}
if ( $cards_glass_enabled ) {
	$classes[] = 'pwpl-cards-glass';
}
$anim_flags = array_map( 'sanitize_key', (array) ( $specs_anim['flags'] ?? [] ) );
foreach ( [ 'row', 'icon', 'divider', 'chip', 'stagger' ] as $flag ) {
	if ( in_array( $flag, $anim_flags, true ) ) {
		$classes[] = 'fvps-anim--' . $flag;
	}
}
$classes = array_filter( array_unique( $classes ) );

$active      = is_array( $table['active'] ?? null ) ? $table['active'] : [];
$allowed     = is_array( $table['allowed'] ?? null ) ? $table['allowed'] : [];
$tabs_source = is_array( $table['tabs'] ?? null ) ? $table['tabs'] : ( $table['dimensions'] ?? [] );

$badges_json       = wp_json_encode( $table['badges'] ?? [] );
$labels_json       = wp_json_encode( $table['dimension_labels'] ?? [] );
$availability_json = wp_json_encode( $table['availability'] ?? [] );

$wrapper_attrs = [];
if ( $dom_id ) {
	$wrapper_attrs['id'] = $dom_id;
}
$wrapper_attrs['data-table-id']         = (int) ( $table['id'] ?? 0 );
$wrapper_attrs['data-table-theme']      = $theme_slug;
$wrapper_attrs['data-badges']           = $badges_json ?: '{}';
$wrapper_attrs['data-dimension-labels'] = $labels_json ?: '{}';

if ( $specs_style && 'default' !== $specs_style ) {
	$wrapper_attrs['data-fvps-specs-style'] = $specs_style;
}
// Animation intensity.
$anim_intensity              = isset( $specs_anim['intensity'] ) ? max( 0, min( 100, (int) $specs_anim['intensity'] ) ) : 45;
$anim_strength               = max( 0.1, min( 1, $anim_intensity / 100 ) );
$wrapper_attrs['data-anim-touch'] = ! empty( $specs_anim['mobile'] ) ? 'on' : 'off';
$wrapper_attrs['data-sticky-cta'] = $sticky_cta_mobile ? 'on' : 'off';

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
$table_style    = trim( (string) ( $table['style'] ?? '' ) );

$style_combined = '';
if ( $table_style ) {
	$style_combined .= trim( (string) $table_style );
}
// Inject animation CSS variables.
$style_combined .= ( $style_combined ? ';' : '' ) . '--fvps-anim-strength:' . esc_attr( $anim_strength );
if ( $tabs_glass_enabled ) {
	$style_vars = '';
	if ( $glass_tint ) {
		$style_vars .= '--glass-tint:' . $glass_tint . ';';
	}
	$style_vars .= '--glass-intensity:' . ( $glass_intensity / 100 ) . ';';
	$style_vars .= '--glass-frost:' . $glass_frost . ';';
	if ( $style_combined ) {
		$style_combined .= ';';
	}
	$style_combined .= $style_vars;
}

$plan_count = count( $plans );

if ( $style_block ) {
	echo $style_block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php
foreach ( $wrapper_attrs as $attr => $value ) {
	printf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
}
if ( $style_combined ) {
	echo ' style="' . esc_attr( $style_combined ) . '"';
}
?>>
	<?php
	static $fvps_sprite_inlined = false;
	if ( ! $fvps_sprite_inlined ) {
		$sprite_path = plugin_dir_path( __FILE__ ) . '../icons.svg';
		if ( is_readable( $sprite_path ) ) {
			echo file_get_contents( $sprite_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$fvps_sprite_inlined = true;
		}
	}
	?>

	<?php if ( $table_title || $table_subtitle ) : ?>
		<header class="fvps-table-header fvps-table-header--comparison">
			<?php if ( $table_title ) : ?>
				<h2 class="fvps-table-title"><?php echo esc_html( $table_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $table_subtitle ) : ?>
				<p class="fvps-table-subtitle"><?php echo esc_html( $table_subtitle ); ?></p>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<?php
	$plan_specs = [];
	$spec_rows  = [];

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

	foreach ( $plans as $plan_index => $plan ) {
		$plan_id    = (int) ( $plan['id'] ?? 0 );
		$plan_theme = sanitize_key( $plan['theme'] ?? $theme_slug );
		$title      = $plan['title'] ?? sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan_id ?: ( $plan_index + 1 ) );
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

		$raw_specs = array_filter(
			array_map(
				function ( $spec ) {
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
				},
				(array) ( $plan['specs'] ?? [] )
			)
		);

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

		$plan_specs[ $plan_index ] = [
			'id'         => $plan_id,
			'theme'      => $plan_theme,
			'title'      => $title,
			'lead'       => $lead,
			'price_html' => $price_html,
			'billing'    => $billing,
			'cta'        => $cta,
			'cta_label'  => $cta_label,
			'cta_url'    => $cta_url,
			'cta_hidden' => $cta_hidden,
			'badge_data' => $badge_data,
			'badge_attr' => $badge_attr,
			'platforms'  => $platforms,
			'periods'    => $periods,
			'locations'  => $locations,
			'variants'   => $variants,
			'specs'      => $ordered_specs,
		];

		// Build union of specs across plans, respecting first plan order.
		foreach ( $ordered_specs as $idx => $spec ) {
			$slug = $spec['slug'] ?: sanitize_title( $spec['label'] ) ?: 'spec-' . $idx;
			if ( ! $slug ) {
				$slug = 'spec-' . $plan_index . '-' . $idx;
			}
			if ( ! isset( $spec_rows[ $slug ] ) ) {
				$spec_rows[ $slug ] = [
					'label' => $spec['label'] ?: sprintf( __( 'Spec %d', 'planify-wp-pricing-lite' ), $idx + 1 ),
					'plans' => [],
				];
			}
			$spec_rows[ $slug ]['plans'][ $plan_index ] = $spec['value'];
		}
	}
	?>

	<div class="fvps-comparison-scroll" data-fvps-comparison-scroll>
		<button type="button" class="fvps-comparison-nav fvps-comparison-nav--prev" aria-label="<?php esc_attr_e( 'Scroll previous', 'planify-wp-pricing-lite' ); ?>" hidden>&#10094;</button>
		<div class="fvps-comparison-viewport">
			<div class="fvps-comparison-table" role="table" style="--fvps-comparison-cols: <?php echo esc_attr( $plan_count ); ?>;">
				<div class="fvps-comparison-row fvps-comparison-row--header" role="row">
					<div class="fvps-comparison-cell fvps-comparison-cell--stub" role="columnheader"></div>
					<?php foreach ( $plan_specs as $plan_index => $plan_meta ) :
						$plan_classes = [
							'pwpl-plan',
							'fvps-comparison-plan',
							'pwpl-theme--' . $plan_meta['theme'],
						];
						if ( $preset ) {
							$plan_classes[] = 'fvps-card--preset-' . $preset;
						}
						?>
						<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $plan_classes ) ) ); ?>"
							role="columnheader"
							data-plan-id="<?php echo esc_attr( $plan_meta['id'] ); ?>"
							data-plan-theme="<?php echo esc_attr( $plan_meta['theme'] ); ?>"
							data-platforms="<?php echo esc_attr( $plan_meta['platforms'] ? implode( ',', $plan_meta['platforms'] ) : '*' ); ?>"
							data-periods="<?php echo esc_attr( $plan_meta['periods'] ? implode( ',', $plan_meta['periods'] ) : '*' ); ?>"
							data-locations="<?php echo esc_attr( $plan_meta['locations'] ? implode( ',', $plan_meta['locations'] ) : '*' ); ?>"
							data-variants="<?php echo esc_attr( $plan_meta['variants'] ?: '[]' ); ?>"
							data-badge="<?php echo esc_attr( $plan_meta['badge_attr'] ?: '{}' ); ?>">
							<div class="fvps-comparison-plan__head">
								<div class="fvps-comparison-plan__title">
									<h3 class="pwpl-plan__title"><?php echo esc_html( $plan_meta['title'] ); ?></h3>
									<?php if ( $plan_meta['lead'] ) : ?>
										<p class="fvps-plan-lead"><?php echo esc_html( $plan_meta['lead'] ); ?></p>
									<?php endif; ?>
								</div>
								<div class="fvps-comparison-plan__pricing">
									<div class="fvps-card__price" data-pwpl-price><?php echo wp_kses_post( $plan_meta['price_html'] ); ?></div>
									<?php if ( $plan_meta['billing'] ) : ?>
										<p class="pwpl-plan__billing" data-pwpl-billing><?php echo esc_html( $plan_meta['billing'] ); ?></p>
									<?php endif; ?>
								</div>
								<div class="fvps-comparison-plan__cta">
									<div class="pwpl-plan__cta fvps-card__cta fvps-card__cta--comparison" data-pwpl-cta>
										<a class="pwpl-plan__cta-button fvps-button"
											data-pwpl-cta-button
											href="<?php echo esc_url( $plan_meta['cta_url'] ); ?>"<?php
												if ( ! empty( $plan_meta['cta']['blank'] ) ) {
													echo ' target="_blank" rel="noopener noreferrer"';
												}
												echo $plan_meta['cta_hidden'] ? ' hidden' : '';
											?>>
											<span data-pwpl-cta-label><?php echo esc_html( $plan_meta['cta_label'] ); ?></span>
										</a>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php foreach ( $spec_rows as $spec_key => $row ) : ?>
					<div class="fvps-comparison-row" role="row">
						<div class="fvps-comparison-cell fvps-comparison-cell--stub fvps-comparison-cell--label" role="rowheader">
							<?php echo esc_html( $row['label'] ); ?>
						</div>
						<?php foreach ( $plan_specs as $plan_index => $plan_meta ) :
							$value      = isset( $row['plans'][ $plan_index ] ) ? (string) $row['plans'][ $plan_index ] : '';
							$value_trim = trim( wp_strip_all_tags( $value ) );
							$has_value  = '' !== $value_trim;
							?>
							<div class="fvps-comparison-cell fvps-comparison-cell--value" role="cell">
								<?php if ( $has_value ) : ?>
									<span class="fvps-comparison-tick" aria-hidden="true"></span>
									<?php if ( '' !== $value_trim ) : ?>
										<span class="fvps-comparison-text">
											<?php echo wp_kses_post( $value ); ?>
										</span>
									<?php endif; ?>
								<?php else : ?>
									<span class="fvps-comparison-tick fvps-comparison-tick--off" aria-hidden="true"></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<button type="button" class="fvps-comparison-nav fvps-comparison-nav--next" aria-label="<?php esc_attr_e( 'Scroll next', 'planify-wp-pricing-lite' ); ?>" hidden>&#10095;</button>
	</div>
</div>
