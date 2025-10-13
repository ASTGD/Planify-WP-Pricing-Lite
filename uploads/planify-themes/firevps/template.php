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

$spec_icon_map = [
	'ram'          => [ 'slug' => 'memory', 'symbol' => 'ram' ],
	'memory'       => [ 'slug' => 'memory', 'symbol' => 'ram' ],
	'cpu'          => [ 'slug' => 'processor', 'symbol' => 'cpu' ],
	'processor'    => [ 'slug' => 'processor', 'symbol' => 'cpu' ],
	'disk'         => [ 'slug' => 'storage', 'symbol' => 'disk' ],
	'storage'      => [ 'slug' => 'storage', 'symbol' => 'disk' ],
	'ssd'          => [ 'slug' => 'storage', 'symbol' => 'disk' ],
	'nvme'         => [ 'slug' => 'storage', 'symbol' => 'disk' ],
	'bandwidth'    => [ 'slug' => 'bandwidth', 'symbol' => 'bandwidth' ],
	'traffic'      => [ 'slug' => 'bandwidth', 'symbol' => 'bandwidth' ],
	'network'      => [ 'slug' => 'network', 'symbol' => 'network' ],
	'ip'           => [ 'slug' => 'ip', 'symbol' => 'ip' ],
	'ip-address'   => [ 'slug' => 'ip', 'symbol' => 'ip' ],
	'ip-addresses' => [ 'slug' => 'ip', 'symbol' => 'ip' ],
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

if ( ! function_exists( 'pwpl_fvps_parse_badge_date' ) ) {
	/**
	 * Parse badge date string into timestamp.
	 *
	 * @param string $date Date string (Y-m-d).
	 * @param bool   $end_of_day Whether to set time to end of day.
	 * @return int|null
	 */
	function pwpl_fvps_parse_badge_date( $date, $end_of_day = false ) {
		$date = trim( (string) $date );
		if ( '' === $date ) {
			return null;
		}
		try {
			$time = $end_of_day ? '23:59:59' : '00:00:00';
			$zone = wp_timezone();
			$dt   = new \DateTimeImmutable( $date . ' ' . $time, $zone );
			return $dt->getTimestamp();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			return null;
		}
	}

	/**
	 * Determine if a badge is currently active.
	 *
	 * @param array $badge Badge settings.
	 * @return bool
	 */
	function pwpl_fvps_badge_active( array $badge ) {
		$now   = current_time( 'timestamp' );
		$start = pwpl_fvps_parse_badge_date( $badge['start'] ?? '', false );
		if ( null !== $start && $start > $now ) {
			return false;
		}
		$end = pwpl_fvps_parse_badge_date( $badge['end'] ?? '', true );
		if ( null !== $end && $end < $now ) {
			return false;
		}
		return true;
	}

	/**
	 * Locate a badge configuration for a given slug.
	 *
	 * @param string $slug       Badge slug.
	 * @param array  $collection Badge collection.
	 * @return array|null
	 */
	function pwpl_fvps_match_badge_for_slug( $slug, array $collection ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}
		foreach ( $collection as $badge ) {
			if ( ! is_array( $badge ) ) {
				continue;
			}
			if ( empty( $badge['slug'] ) || sanitize_title( $badge['slug'] ) !== $slug ) {
				continue;
			}
			if ( ! pwpl_fvps_badge_active( $badge ) ) {
				continue;
			}
			return $badge;
		}
		return null;
	}

	/**
	 * Convert hex color to rgba.
	 *
	 * @param string $hex   Hex code.
	 * @param float  $alpha Alpha value.
	 * @return string
	 */
	function pwpl_fvps_hex_to_rgba( $hex, $alpha = 0.35 ) {
		$hex = trim( (string) $hex );
		if ( '' === $hex ) {
			return '';
		}
		if ( strpos( $hex, '#' ) === 0 ) {
			$hex = substr( $hex, 1 );
		}
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '';
		}
		$int = hexdec( $hex );
		$r   = ( $int >> 16 ) & 255;
		$g   = ( $int >> 8 ) & 255;
		$b   = $int & 255;
		$alpha = max( 0, min( 1, (float) $alpha ) );
		return sprintf( 'rgba(%d, %d, %d, %.3f)', $r, $g, $b, $alpha );
	}

	/**
	 * Normalise badge data for display.
	 *
	 * @param array $badge  Raw badge data.
	 * @param int   $shadow Shadow strength.
	 * @return array
	 */
	function pwpl_fvps_format_badge_for_output( array $badge, $shadow = 0 ) {
		$label = isset( $badge['label'] ) ? trim( (string) $badge['label'] ) : '';
		if ( '' === $label ) {
			return [
				'label'      => '',
				'color'      => '',
				'text_color' => '',
				'icon'       => '',
				'tone'       => '',
				'style'      => '',
			];
		}

		$color      = isset( $badge['color'] ) ? (string) $badge['color'] : '';
		$text_color = isset( $badge['text_color'] ) ? (string) $badge['text_color'] : '';
		$tone       = sanitize_html_class( $badge['tone'] ?? '' );

		$style = '';
		if ( $color ) {
			$style .= '--pwpl-badge-bg:' . $color . ';';
			$rgba = pwpl_fvps_hex_to_rgba( $color, 0.35 );
			if ( $rgba ) {
				$style .= '--pwpl-badge-shadow-color:' . $rgba . ';';
			}
		}
		if ( $text_color ) {
			$style .= '--pwpl-badge-color:' . $text_color . ';';
		}
		if ( $shadow > 0 ) {
			$style .= '--pwpl-badge-shadow-strength:' . (int) $shadow . ';';
		}

		return [
			'label'      => $label,
			'color'      => $color,
			'text_color' => $text_color,
			'icon'       => isset( $badge['icon'] ) ? (string) $badge['icon'] : '',
			'tone'       => $tone,
			'style'      => $style,
		];
	}
}

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
		$sprite = __DIR__ . '/icons.svg';
		if ( is_readable( $sprite ) ) {
			echo file_get_contents( $sprite ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		$dimension_badges = (array) ( $table['badges'][ $dimension ] ?? [] );
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
				$tab_badge_view = null;
				if ( $dimension_badges ) {
					$matched_badge = pwpl_fvps_match_badge_for_slug( $slug, $dimension_badges );
					if ( $matched_badge ) {
						$tab_badge_view = pwpl_fvps_format_badge_for_output( $matched_badge, (int) ( $table['badge_shadow'] ?? 0 ) );
					}
				}
				$tab_badge_label = isset( $tab_badge_view['label'] ) ? trim( (string) $tab_badge_view['label'] ) : '';
				$tab_badge_color = isset( $tab_badge_view['color'] ) ? sanitize_text_field( (string) $tab_badge_view['color'] ) : '';
				$tab_badge_text  = isset( $tab_badge_view['text_color'] ) ? sanitize_text_field( (string) $tab_badge_view['text_color'] ) : '';
				$tab_badge_icon  = isset( $tab_badge_view['icon'] ) ? sanitize_text_field( (string) $tab_badge_view['icon'] ) : '';
				$tab_badge_styles = [];
				if ( $tab_badge_color ) {
					$tab_badge_styles[] = '--fvps-tab-badge-bg:' . $tab_badge_color;
					$tab_badge_styles[] = '--pwpl-tab-badge-bg:' . $tab_badge_color;
				}
				if ( $tab_badge_text ) {
					$tab_badge_styles[] = '--fvps-tab-badge-color:' . $tab_badge_text;
					$tab_badge_styles[] = '--pwpl-tab-badge-color:' . $tab_badge_text;
				}
				$tab_badge_style_attr = $tab_badge_styles ? ' style="' . esc_attr( implode( ';', $tab_badge_styles ) ) . '"' : '';
				$tab_badge_classes = [ 'pwpl-tab__badge', 'fvps-tab__badge' ];
				if ( $tab_badge_view && ! $tab_badge_color && ! $tab_badge_text && ! empty( $tab_badge_view['tone'] ) ) {
					$tab_badge_classes[] = 'fvps-tab__badge--tone-' . sanitize_html_class( $tab_badge_view['tone'] );
				}
				?>
				<button type="button"
					class="pwpl-tab fvps-tab<?php echo $is_active ? ' is-active' : ''; ?>"
					data-value="<?php echo esc_attr( $slug ); ?>"
					aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
					<span class="pwpl-tab__label"><?php echo esc_html( $label ); ?></span>
					<?php if ( '' !== $tab_badge_label ) : ?>
						<span class="<?php echo esc_attr( implode( ' ', $tab_badge_classes ) ); ?>"<?php echo $tab_badge_style_attr; ?>>
							<?php if ( '' !== $tab_badge_icon ) : ?>
								<span class="pwpl-tab__badge-icon" aria-hidden="true"><?php echo esc_html( $tab_badge_icon ); ?></span>
							<?php endif; ?>
							<span class="pwpl-tab__badge-label"><?php echo esc_html( $tab_badge_label ); ?></span>
						</span>
					<?php endif; ?>
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

			<div class="pwpl-plan-grid fvps-plan-rail" tabindex="0">
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

	$badge_label     = trim( (string) ( $badge_data['label'] ?? '' ) );
	$badge_color_raw = isset( $badge_data['color'] ) ? sanitize_text_field( (string) $badge_data['color'] ) : '';
	$badge_text_raw  = isset( $badge_data['text_color'] ) ? sanitize_text_field( (string) $badge_data['text_color'] ) : '';
	$badge_tone      = isset( $badge_data['tone'] ) ? sanitize_key( (string) $badge_data['tone'] ) : '';
	$is_featured     = ! empty( $plan['featured'] );
	$deal_label      = $plan['deal_label'] ?? '';

	$badge_styles = [];
	if ( $badge_color_raw ) {
		$badge_styles[] = '--fvps-badge-bg:' . $badge_color_raw;
		$badge_styles[] = '--pwpl-badge-bg:' . $badge_color_raw;
	}
	if ( $badge_text_raw ) {
		$badge_styles[] = '--fvps-badge-color:' . $badge_text_raw;
		$badge_styles[] = '--pwpl-badge-color:' . $badge_text_raw;
	}
	$badge_style_attr = $badge_styles ? ' style="' . esc_attr( implode( ';', $badge_styles ) ) . '"' : '';
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
				<?php if ( $is_featured ) : ?>
					<span class="fvps-plan-featured" data-pwpl-featured-label><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
				<?php endif; ?>
				<span class="fvps-plan-badge"
					data-pwpl-badge
					data-badge-color="<?php echo esc_attr( $badge_color_raw ); ?>"
					data-badge-text="<?php echo esc_attr( $badge_text_raw ); ?>"
					data-badge-tone="<?php echo esc_attr( $badge_tone ); ?>"<?php
						echo $badge_style_attr;
						echo $badge_label === '' ? ' hidden' : '';
					?>>
					<span class="label" data-pwpl-badge-label><?php echo esc_html( $badge_label ); ?></span>
				</span>
			</div>

	<div class="fvps-card__heading">
		<h4 class="pwpl-plan__title"><?php echo esc_html( $title ); ?></h4>
		<?php if ( $lead ) : ?>
			<p class="fvps-plan-lead"><?php echo esc_html( $lead ); ?></p>
		<?php endif; ?>
		<?php if ( $deal_label ) : ?>
			<span class="fvps-plan-deal"><?php echo esc_html( $deal_label ); ?></span>
		<?php endif; ?>
	</div>

	<div class="fvps-card__price">
		<div data-pwpl-price><?php echo wp_kses_post( $price_html ); ?></div>
		<?php if ( $billing ) : ?>
			<p class="pwpl-plan__billing" data-pwpl-billing><?php echo esc_html( $billing ); ?></p>
		<?php endif; ?>
	</div>

				<?php if ( $ordered_specs ) : ?>
								<ul class="pwpl-plan__specs fvps-card__specs">
									<?php foreach ( $ordered_specs as $spec ) :
										$raw_slug      = sanitize_title( $spec['slug'] ?? ( $spec['label'] ?? '' ) );
										$icon_settings = $spec_icon_map[ $raw_slug ] ?? null;
										$spec_slug     = sanitize_title( $icon_settings['slug'] ?? $raw_slug );
										if ( '' === $spec_slug ) {
											$spec_slug = 'generic';
										}
										$symbol_id = sanitize_title( $icon_settings['symbol'] ?? 'generic' );
										if ( '' === $symbol_id ) {
											$symbol_id = 'generic';
										}
										?>
										<li class="fvps-spec fvps-spec--<?php echo esc_attr( $spec_slug ); ?>">
											<svg class="fvps-icon fvps-icon-<?php echo esc_attr( $spec_slug ); ?>" aria-hidden="true" focusable="false">
												<use href="#<?php echo esc_attr( $symbol_id ); ?>" xlink:href="#<?php echo esc_attr( $symbol_id ); ?>"></use>
											</svg>
											<div class="fvps-spec__text">
												<span class="fvps-spec__label"><?php echo esc_html( $spec['label'] ); ?></span>
												<span class="fvps-spec__value" data-pwpl-spec-value><?php echo esc_html( $spec['value'] ); ?></span>
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
