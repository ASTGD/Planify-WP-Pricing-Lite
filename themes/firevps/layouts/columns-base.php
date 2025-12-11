<?php
/**
 * FireVPS columns layout (base/fallback).
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

			$badge_label = trim( (string) ( $badge_data['label'] ?? '' ) );
			$badge_color = $badge_data['color'] ?? '';
			$badge_text  = $badge_data['text_color'] ?? '';
			$is_featured = ! empty( $plan['featured'] );
			$deal_label  = $plan['deal_label'] ?? '';

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
			$card_footer_classes = [ 'fvps-card__footer' ];
			?>
			<article class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $plan_classes ) ) ); ?>"
				data-plan-id="<?php echo esc_attr( $plan_id ); ?>"
				data-plan-theme="<?php echo esc_attr( $plan_theme ); ?>"
				data-platforms="<?php echo esc_attr( $platforms ? implode( ',', $platforms ) : '*' ); ?>"
				data-periods="<?php echo esc_attr( $periods ? implode( ',', $periods ) : '*' ); ?>"
				data-locations="<?php echo esc_attr( $locations ? implode( ',', $locations ) : '*' ); ?>"
				data-variants="<?php echo esc_attr( $variants ?: '[]' ); ?>"
				data-badge="<?php echo esc_attr( $badge_attr ?: '{}' ); ?>">
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
				</div>

				<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_footer_classes ) ) ); ?>">
					<?php if ( ! $cta_hidden && $cta_label ) : ?>
						<a class="pwpl-plan__cta-button fvps-button" href="<?php echo esc_url( $cta_url ); ?>" <?php echo ! empty( $cta['target_attr'] ) ? $cta['target_attr'] : ''; ?> <?php echo ! empty( $cta['rel_attr'] ) ? $cta['rel_attr'] : ''; ?>>
							<?php echo esc_html( $cta_label ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $trust_trio_enabled && $plan_trust_items ) : ?>
						<ul class="fvps-cta-trust fvps-cta-trust--stack" role="list">
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
