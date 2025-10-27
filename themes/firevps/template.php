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
$tabs_glass_enabled = ! empty( $table['tabs_glass'] );
$cards_glass_enabled = ! empty( $table['cards_glass'] );
$specs_style = isset( $table['specs_style'] ) ? sanitize_key( (string) $table['specs_style'] ) : '';
$specs_anim  = is_array( $table['specs_anim'] ?? null ) ? $table['specs_anim'] : [];
$trust_trio_enabled = ! empty( $table['trust_trio'] );
$sticky_cta_mobile  = ! empty( $table['sticky_cta_mobile'] );
$trust_items        = is_array( $table['trust_items'] ?? null ) ? array_filter( $table['trust_items'] ) : [];
if ( $tabs_glass_enabled ) {
    $glass_tint = (string) ( $table['tabs_glass_tint'] ?? '' );
    $glass_intensity = isset( $table['tabs_glass_intensity'] ) ? (int) $table['tabs_glass_intensity'] : 60;
    $glass_intensity = max( 10, min( 100, $glass_intensity ) );
    $glass_frost = isset( $table['tabs_glass_frost'] ) ? (int) $table['tabs_glass_frost'] : 6;
    $glass_frost = max( 0, min( 24, $glass_frost ) );
}
if ( $tabs_glass_enabled ) {
    $classes[] = 'pwpl-tabs-glass';
}
if ( $cards_glass_enabled ) {
    $classes[] = 'pwpl-cards-glass';
}
$anim_flags = array_map( 'sanitize_key', (array) ( $specs_anim['flags'] ?? [] ) );
foreach ( [ 'row','icon','divider','chip','stagger' ] as $flag ) {
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

$wrapper_attrs = [
	'data-table-id'         => (int) ( $table['id'] ?? 0 ),
	'data-table-theme'      => $theme_slug,
	'data-badges'           => $badges_json ?: '{}',
	'data-dimension-labels' => $labels_json ?: '{}',
];

if ( $specs_style && $specs_style !== 'default' ) {
    $wrapper_attrs['data-fvps-specs-style'] = $specs_style;
}
// Animation intensity
$anim_intensity = isset( $specs_anim['intensity'] ) ? max( 0, min( 100, (int) $specs_anim['intensity'] ) ) : 45;
$anim_strength = max( 0.1, min( 1, $anim_intensity / 100 ) );
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

?>
<?php
$style_combined = '';
if ( $table_style ) { $style_combined .= trim( (string) $table_style ); }
// Inject animation CSS variables
$style_combined .= ($style_combined ? ';' : '') . '--fvps-anim-strength:' . esc_attr( $anim_strength );
if ( $tabs_glass_enabled ) {
    $style_vars = '';
    if ( $glass_tint ) { $style_vars .= '--glass-tint:' . $glass_tint . ';'; }
    $style_vars .= '--glass-intensity:' . ( $glass_intensity / 100 ) . ';';
    $style_vars .= '--glass-frost:' . $glass_frost . ';';
    if ( $style_combined ) { $style_combined .= ';'; }
    $style_combined .= $style_vars;
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
            <div class="fvps-dimension-nav__viewport" data-fvps-tab-viewport>
                <div class="fvps-tablist" data-fvps-tablist>
            <?php foreach ( $items as $item ) :
                $slug = sanitize_title( $item['slug'] ?? '' );
                if ( ! $slug ) {
                    continue;
                }
                $label = $item['label'] ?? $slug;
                $is_active = $current_value === $slug;
                // Resolve a promo badge for this tab (if configured)
                $tab_badge_raw = null;
                $tab_badge_list = is_array( $table_badges[ $dimension ] ?? null ) ? $table_badges[ $dimension ] : [];
                foreach ( $tab_badge_list as $candidate ) {
                    $cslug = sanitize_title( $candidate['slug'] ?? '' );
                    if ( $cslug && $cslug === $slug ) { $tab_badge_raw = $candidate; break; }
                }
                $tab_badge_hidden = ! empty( $tab_badge_raw['hidden'] );
                $tab_badge_label  = trim( (string) ( $tab_badge_raw['label'] ?? '' ) );
                $tab_badge_icon   = (string) ( $tab_badge_raw['icon'] ?? '' );
                $tab_badge_style  = '';
                if ( ! empty( $tab_badge_raw['color'] ) ) {
                    $tab_badge_style .= '--pwpl-tab-badge-bg:' . $tab_badge_raw['color'] . ';';
                }
                if ( ! empty( $tab_badge_raw['text_color'] ) ) {
                    $tab_badge_style .= '--pwpl-tab-badge-color:' . $tab_badge_raw['text_color'] . ';';
                }
                if ( $badge_shadow > 0 ) {
                    $tab_badge_style .= '--pwpl-badge-shadow-strength:' . (int) $badge_shadow . ';';
                }
                ?>
                <button type="button"
                    class="pwpl-tab fvps-tab<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-value="<?php echo esc_attr( $slug ); ?>"
                    aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                    <span class="pwpl-tab__label"><?php echo esc_html( $label ); ?></span>
                    <?php if ( $tab_badge_label && ! $tab_badge_hidden ) : ?>
                        <span class="pwpl-tab__badge" style="<?php echo esc_attr( $tab_badge_style ); ?>">
                            <?php if ( $tab_badge_icon ) : ?><span class="pwpl-tab__badge-icon" aria-hidden="true"><?php echo esc_html( $tab_badge_icon ); ?></span><?php endif; ?>
                            <span class="pwpl-tab__badge-label"><?php echo esc_html( $tab_badge_label ); ?></span>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
                </div>
            </div>
        </div>
	<?php endforeach; ?>

	<div class="pwpl-plan-rail-wrapper fvps-plan-rail-wrapper">
		<div class="pwpl-plan-nav pwpl-plan-nav--prev" hidden>
			<button type="button" class="pwpl-plan-nav__btn" data-direction="prev" aria-label="<?php esc_attr_e( 'Scroll previous plans', 'planify-wp-pricing-lite' ); ?>">&#10094;</button>
		</div>
		<div class="pwpl-plan-nav pwpl-plan-nav--next" hidden>
			<button type="button" class="pwpl-plan-nav__btn" data-direction="next" aria-label="<?php esc_attr_e( 'Scroll next plans', 'planify-wp-pricing-lite' ); ?>">&#10095;</button>
		</div>

		<div class="pwpl-plan-grid pwpl-plan-rail fvps-plan-rail" tabindex="0">
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

                        <span class="fvps-plan-location" data-pwpl-location hidden></span>
							<?php if ( $is_featured ) : ?>
								<span class="fvps-plan-featured" data-pwpl-featured-label><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
							<?php endif; ?>
						</div>

                        <div class="fvps-card__heading">
                            <div class="fvps-plan-heading-icon" aria-hidden="true">
                                <svg class="fvps-icon fvps-icon--plan" focusable="false">
                                    <use href="#ram" xlink:href="#ram"></use>
                                </svg>
                            </div>
                            <h3 class="pwpl-plan__title"><?php echo esc_html( $title ); ?></h3>
							<?php if ( $lead ) : ?>
								<p class="fvps-plan-lead"><?php echo esc_html( $lead ); ?></p>
							<?php endif; ?>
							<?php if ( $deal_label ) : ?>
								<span class="fvps-plan-deal"><?php echo esc_html( $deal_label ); ?></span>
							<?php endif; ?>
						</div>

							<div class="fvps-card__price" data-pwpl-price><?php echo wp_kses_post( $price_html ); ?></div>
							<?php if ( $billing && preg_match( '/annual/i', $billing ) ) : ?>
								<p class="pwpl-plan__billing" data-pwpl-billing><?php echo esc_html( $billing ); ?></p>
							<?php endif; ?>

                        <div class="fvps-card__cta-inline"<?php echo $cta_hidden ? ' hidden' : ''; ?>>
                            <a class="fvps-button fvps-button--inline"
                                href="<?php echo esc_url( $cta_url ); ?>"<?php
                                    if ( ! empty( $cta['blank'] ) ) {
                                        echo ' target="_blank" rel="noopener noreferrer"';
                                    }
                                ?>>
                                <span><?php echo esc_html( $cta_label ); ?></span>
                            </a>
                            <?php if ( $trust_trio_enabled && $trust_items ) : ?>
                            <ul class="fvps-cta-trust<?php echo count( $trust_items ) > 3 ? ' fvps-cta-trust--stack' : ''; ?>" role="list">
                                <?php foreach ( $trust_items as $item ) : ?>
                                    <li role="listitem"><?php echo esc_html( (string) $item ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
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
