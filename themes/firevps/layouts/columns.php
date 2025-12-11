<?php
/**
 * FireVPS layout: columns.
 *
 * Dedicated layout for service-style columns (no tabs/rails).
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
	$extra_list = preg_split( '/\s+/', $extra_classes_raw );
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
$trust_trio_enabled  = ! empty( $table['trust_trio'] );
$sticky_cta_mobile   = ! empty( $table['sticky_cta_mobile'] );
$trust_items         = is_array( $table['trust_items'] ?? null ) ? array_filter( $table['trust_items'] ) : [];
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
if ( 'hospitality-cards' === $preset ) {
	$wrapper_attrs['data-unit-default']   = '/night';
	$wrapper_attrs['data-billing-static'] = 'true';
}

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
$table_badges   = is_array( $table['badges'] ?? null ) ? $table['badges'] : [];
$badge_shadow   = (int) ( $table['badge_shadow'] ?? 0 );

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
		<header class="fvps-table-header fvps-table-header--columns">
			<?php if ( $table_title ) : ?>
				<h2 class="fvps-table-title"><?php echo esc_html( $table_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $table_subtitle ) : ?>
				<p class="fvps-table-subtitle"><?php echo esc_html( $table_subtitle ); ?></p>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<div class="fvps-columns-grid">
		<?php foreach ( $plans as $plan_index => $plan ) :
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

			$badge_label = trim( (string) ( $badge_data['label'] ?? '' ) );
			$badge_color = $badge_data['color'] ?? '';
			$badge_text  = $badge_data['text_color'] ?? '';
			$is_featured = ! empty( $plan['featured'] );
			$deal_label  = $plan['deal_label'] ?? '';
			$hero_image_id = isset( $plan['hero_image_id'] ) ? (int) $plan['hero_image_id'] : 0;
			$hero_image_url = '';
			if ( ! empty( $plan['hero_image_url'] ) ) {
				$hero_image_url = esc_url( (string) $plan['hero_image_url'] );
			}
			$is_hospitality = ( 'hospitality-cards' === $preset );
			if ( $is_hospitality && ! $hero_image_id && ! $hero_image_url && defined( 'PWPL_URL' ) ) {
				// Fallback demo images for Hospitality preset (wizard preview + empty hero meta).
				$fallback_file = '';
				$title_slug = strtolower( sanitize_title( (string) $title ) );
				if ( false !== strpos( $title_slug, 'standard' ) ) {
					$fallback_file = 'hospitality-standard.png';
				} elseif ( false !== strpos( $title_slug, 'deluxe' ) ) {
					$fallback_file = 'hospitality-deluxe.png';
				} elseif ( false !== strpos( $title_slug, 'penthouse' ) || false !== strpos( $title_slug, 'loft' ) ) {
					$fallback_file = 'hospitality-penthouse.png';
				}
				if ( $fallback_file ) {
					$hero_image_url = esc_url( trailingslashit( PWPL_URL ) . 'assets/admin/img/template-demo/' . $fallback_file );
				}
			}
			$plan_classes = [
				'pwpl-plan',
				'fvps-card',
				'fvps-columns-card',
				'pwpl-theme--' . $plan_theme,
			];
			if ( $is_featured ) {
				$plan_classes[] = 'fvps-plan--featured';
			}
			if ( $preset ) {
				$plan_classes[] = 'fvps-card--preset-' . $preset;
			}
			$plan_trust_items = $trust_items;
			if ( ! empty( $plan['trust_items_override'] ) && is_array( $plan['trust_items_override'] ) ) {
				$plan_trust_items = array_values( array_filter( array_map( 'sanitize_text_field', (array) $plan['trust_items_override'] ) ) );
			}
			$card_body_classes = [ 'fvps-card__body' ];
			if ( $is_hospitality ) {
				$card_body_classes[] = 'fvps-card__body--hospitality';
			}
			$card_footer_classes = [ 'fvps-card__footer' ];
			if ( $is_hospitality ) {
				$card_footer_classes[] = 'fvps-card__footer--hospitality';
			}
			?>
			<article class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $plan_classes ) ) ); ?>"
				data-plan-id="<?php echo esc_attr( $plan_id ); ?>"
				data-plan-theme="<?php echo esc_attr( $plan_theme ); ?>"
				data-platforms="<?php echo esc_attr( $platforms ? implode( ',', $platforms ) : '*' ); ?>"
				data-periods="<?php echo esc_attr( $periods ? implode( ',', $periods ) : '*' ); ?>"
				data-locations="<?php echo esc_attr( $locations ? implode( ',', $locations ) : '*' ); ?>"
				data-variants="<?php echo esc_attr( $variants ?: '[]' ); ?>"
				data-badge="<?php echo esc_attr( $badge_attr ?: '{}' ); ?>">
				<?php if ( $is_hospitality ) : ?>
					<div class="fvps-hospitality-hero">
						<?php if ( $hero_image_id ) : ?>
							<?php
							echo wp_get_attachment_image(
								$hero_image_id,
								'large',
								false,
								[
									'class'   => 'fvps-hospitality-hero__img',
									'loading' => 'lazy',
								]
							);
							?>
						<?php elseif ( $hero_image_url ) : ?>
							<img class="fvps-hospitality-hero__img" src="<?php echo esc_url( $hero_image_url ); ?>" alt="" loading="lazy" decoding="async" />
						<?php else : ?>
							<div class="fvps-hospitality-hero__fallback" aria-hidden="true"></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_body_classes ) ) ); ?>">
					<div class="fvps-card__badges">
						<span class="fvps-plan-badge" data-pwpl-badge style="<?php
							if ( $badge_color ) {
								printf( '--pwpl-badge-bg:%s;--pwpl-badge-shadow-color:%s;', esc_attr( $badge_color ), esc_attr( $badge_color ) );
							}
							if ( $badge_text ) {
								printf( '--pwpl-badge-color:%s;', esc_attr( $badge_text ) );
							}
						?>" <?php echo $badge_label ? '' : 'hidden'; ?>>
							<span class="fvps-plan-badge__icon" data-pwpl-badge-icon aria-hidden="true"></span>
							<span class="fvps-plan-badge__label" data-pwpl-badge-label><?php echo esc_html( $badge_label ); ?></span>
						</span>
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

					<?php if ( $ordered_specs ) : ?>
						<ul class="pwpl-plan__specs fvps-card__specs fvps-card__specs--columns">
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
				</div>

				<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_footer_classes ) ) ); ?>">
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
					<?php if ( $trust_trio_enabled && $plan_trust_items ) : ?>
						<ul class="fvps-cta-trust<?php echo count( $plan_trust_items ) > 3 ? ' fvps-cta-trust--stack' : ''; ?>" role="list">
							<?php foreach ( $plan_trust_items as $item ) : ?>
								<li role="listitem"><?php echo esc_html( (string) $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>
