<?php
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

$wrapper_attrs = [];
if ( $dom_id ) {
    $wrapper_attrs['id'] = $dom_id;
}
$wrapper_attrs['data-table-id']         = (int) ( $table['id'] ?? 0 );
$wrapper_attrs['data-table-theme']      = $theme_slug;
$wrapper_attrs['data-badges']           = $badges_json ?: '{}';
$wrapper_attrs['data-dimension-labels'] = $labels_json ?: '{}';

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
	// Additional common slugs used by templates.
	'websites'        => 'websites',
	'ssd-storage'     => 'ssd-storage',
	'email'           => 'email',
	'premium-support' => 'support-agent',
	'free-ssl'        => 'ssl',
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
<?php if ( $style_block ) { echo $style_block; } ?>
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

	<?php if ( $table_title ) : ?>
		<?php
        $header_class = 'fvps-table-header';
        if ( 'image-hero' === $preset ) {
            $header_class .= ' fvps-table-header--hero';
        }
        if ( 'minimal-focus' === $preset ) {
            $header_class .= ' fvps-table-header--minimal';
        }
        ?>
		<header class="<?php echo esc_attr( $header_class ); ?>">
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
        if ( 'period' === $dimension && 'saas-grid-v2' === $preset ) {
            echo '<div class="fvps-period-badge">' . esc_html__( 'Save 15%', 'planify-wp-pricing-lite' ) . '</div>';
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
			<?php foreach ( $plans as $plan_index => $plan ) :
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
					$slug = '';
					if ( ! empty( $spec['slug'] ) && is_string( $spec['slug'] ) ) {
						$slug = sanitize_key( $spec['slug'] );
					} else {
						$slug = sanitize_title( $label );
					}
					// Canonicalize common slugs so icons / colors line up.
					if ( false !== strpos( $slug, 'website' ) ) {
						$slug = 'websites';
					} elseif ( false !== strpos( $slug, 'ssd' ) || false !== strpos( $slug, 'storage' ) ) {
						$slug = 'ssd-storage';
					} elseif ( false !== strpos( $slug, 'email' ) ) {
						$slug = 'email';
					} elseif ( false !== strpos( $slug, 'bandwidth' ) || false !== strpos( $slug, 'traffic' ) ) {
						$slug = 'bandwidth';
					} elseif ( false !== strpos( $slug, 'ssl' ) ) {
						$slug = 'free-ssl';
					}
					$icon = '';
					if ( ! empty( $spec['icon'] ) && is_string( $spec['icon'] ) ) {
						$icon = sanitize_key( $spec['icon'] );
					}
					return [
						'label' => $label,
						'value' => $value,
						'slug'  => $slug,
						'icon'  => $icon,
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

				$badge_label    = trim( (string) ( $badge_data['label'] ?? '' ) );
				$badge_color    = $badge_data['color'] ?? '';
				$badge_text     = $badge_data['text_color'] ?? '';
				$is_featured    = ! empty( $plan['featured'] );
				$deal_label     = $plan['deal_label'] ?? '';
				$hero_image_id  = isset( $plan['hero_image_id'] ) ? (int) $plan['hero_image_id'] : 0;
                $plan_classes = [
                    'pwpl-plan',
                    'fvps-card',
                    'pwpl-theme--' . $plan_theme,
                ];
                if ( $preset ) {
                    $plan_classes[] = 'fvps-card--preset-' . $preset;
                }
                if ( $is_featured ) {
                    $plan_classes[] = 'is-featured';
                }
                $hero_class = '';
                if ( 'saas-grid-v2' === $preset ) {
                    $hero_class = 'fvps-hero-illustration';
                    if ( 0 === $plan_index ) {
                        $hero_class .= ' fvps-hero-illustration--basic';
                    } elseif ( 1 === $plan_index ) {
                        $hero_class .= ' fvps-hero-illustration--standard';
                    } else {
                        $hero_class .= ' fvps-hero-illustration--premium';
                    }
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
                    <?php
                    if ( 'saas-grid-v2' === $preset && $is_featured ) {
                        echo '<span class="fvps-ribbon fvps-ribbon--popular">' . esc_html__( 'Most Popular', 'planify-wp-pricing-lite' ) . '</span>';
                    }
                    ?>
					<?php if ( 'saas-3-col' === $preset ) : ?>
						<div class="fvps-plan-hero">
							<?php if ( $hero_image_id ) : ?>
								<?php
								echo wp_get_attachment_image(
									$hero_image_id,
									'medium',
									false,
									[
										'class'   => 'fvps-plan-hero__img',
										'loading' => 'lazy',
									]
								);
								?>
							<?php else : ?>
								<svg class="fvps-plan-hero__svg" viewBox="0 0 120 60" role="presentation" aria-hidden="true">
									<?php if ( 0 === $plan_index ) : ?>
										<!-- Starter: simple outline panel -->
										<rect x="16" y="24" width="72" height="18" rx="9"></rect>
										<circle cx="32" cy="46" r="6"></circle>
										<circle cx="72" cy="46" r="6"></circle>
									<?php elseif ( 1 === $plan_index ) : ?>
										<!-- Growth: slightly larger panel -->
										<rect x="14" y="22" width="80" height="20" rx="10"></rect>
										<circle cx="30" cy="46" r="6"></circle>
										<circle cx="78" cy="46" r="6"></circle>
									<?php else : ?>
										<!-- Scale: full-width premium panel -->
										<rect x="10" y="20" width="90" height="22" rx="11"></rect>
										<circle cx="28" cy="46" r="6"></circle>
										<circle cx="82" cy="46" r="6"></circle>
									<?php endif; ?>
								</svg>
							<?php endif; ?>
						</div>
					<?php endif; ?>
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
                            <?php if ( $hero_class ) : ?>
                                <div class="<?php echo esc_attr( $hero_class ); ?>" aria-hidden="true">
                                    <svg class="fvps-hero-illustration__svg" viewBox="0 0 120 60" role="presentation" focusable="false">
                                        <?php if ( strpos( $hero_class, 'fvps-hero-illustration--basic' ) !== false ) : ?>
                                            <!-- Basic: simple scooter -->
                                            <rect x="20" y="26" width="52" height="16" rx="8" ry="8"></rect>
                                            <circle cx="32" cy="46" r="7"></circle>
                                            <circle cx="74" cy="46" r="7"></circle>
                                            <line x1="48" y1="26" x2="52" y2="16"></line>
                                        <?php elseif ( strpos( $hero_class, 'fvps-hero-illustration--standard' ) !== false ) : ?>
                                            <!-- Standard: scooter with seat -->
                                            <rect x="18" y="24" width="60" height="18" rx="9" ry="9"></rect>
                                            <rect x="40" y="18" width="20" height="8" rx="4" ry="4"></rect>
                                            <circle cx="32" cy="46" r="7"></circle>
                                            <circle cx="78" cy="46" r="7"></circle>
                                        <?php else : ?>
                                            <!-- Premium: sportier bike -->
                                            <path d="M26 46c0-6 4-10 10-10h30l6-10" />
                                            <circle cx="32" cy="46" r="7"></circle>
                                            <circle cx="78" cy="46" r="7"></circle>
                                            <line x1="40" y1="26" x2="54" y2="18"></line>
                                        <?php endif; ?>
                                    </svg>
                                </div>
                            <?php endif; ?>
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
							<?php if ( $billing ) : ?>
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
								$icon_id    = '';
								if ( ! empty( $spec['icon'] ) && is_string( $spec['icon'] ) ) {
									$icon_id = sanitize_key( (string) $spec['icon'] );
								}
								// Fall back to the slug-based map only when no explicit icon has been provided.
								if ( '' === $icon_id ) {
									$lookup_slug = $slug;
									if ( ! isset( $icon_map[ $lookup_slug ] ) ) {
										if ( false !== strpos( $lookup_slug, 'website' ) ) {
											$lookup_slug = 'websites';
										} elseif ( false !== strpos( $lookup_slug, 'ssd' ) || false !== strpos( $lookup_slug, 'storage' ) ) {
											$lookup_slug = 'ssd-storage';
										} elseif ( false !== strpos( $lookup_slug, 'email' ) ) {
											$lookup_slug = 'email';
										} elseif ( false !== strpos( $lookup_slug, 'bandwidth' ) || false !== strpos( $lookup_slug, 'traffic' ) ) {
											$lookup_slug = 'bandwidth';
										} elseif ( false !== strpos( $lookup_slug, 'ssl' ) ) {
											$lookup_slug = 'free-ssl';
										}
									}
									$icon_id = $icon_map[ $lookup_slug ] ?? 'generic';
								}
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
						<?php
						if ( 'saas-3-col' === $preset && ! $cta_hidden ) {
							$cta_note = apply_filters(
								'pwpl_firevps_saas3_cta_note',
								__( 'No credit card required', 'planify-wp-pricing-lite' ),
								$plan_id,
								$plan
							);
							if ( $cta_note ) :
								?>
								<p class="fvps-card__cta-note"><?php echo esc_html( $cta_note ); ?></p>
								<?php
							endif;
						}
						?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</div>
