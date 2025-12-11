<?php
/**
 * FireVPS layout: columns router + shared setup.
 *
 * This file prepares context, then delegates to preset-specific partials.
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

// Route to preset-specific partials or fallback base renderer.
if ( 'hospitality-cards' === $preset ) {
	include __DIR__ . '/columns-hospitality.php';
	return;
}

if ( 'service-plans' === $preset ) {
	include __DIR__ . '/columns-service-plans.php';
	return;
}

include __DIR__ . '/columns-base.php';
