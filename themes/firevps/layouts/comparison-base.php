<?php
/**
 * FireVPS comparison layout (base/fallback).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
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
