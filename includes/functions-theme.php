<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Locate a theme asset, preferring uploads overrides.
 *
 * @param string $slug     Theme slug.
 * @param string $filename Relative file path within the theme.
 * @return string|false Absolute path if readable, otherwise false.
 */
function pwpl_locate_theme_file( $slug, $filename ) {
	$slug     = sanitize_key( $slug );
	$filename = ltrim( (string) $filename, '/' );

	if ( '' === $slug || '' === $filename ) {
		return false;
	}

	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
		$uploads_path = trailingslashit( $uploads['basedir'] ) . 'planify-themes/' . $slug . '/' . $filename;
		if ( is_readable( $uploads_path ) ) {
			return $uploads_path;
		}
	}

	$stylesheet_dir = get_stylesheet_directory();
	if ( $stylesheet_dir ) {
		$theme_override = trailingslashit( $stylesheet_dir ) . 'planify-themes/' . $slug . '/' . $filename;
		if ( is_readable( $theme_override ) ) {
			return $theme_override;
		}
	}

	$bundled_path = trailingslashit( PWPL_DIR ) . 'themes/' . $slug . '/' . $filename;
	if ( is_readable( $bundled_path ) ) {
		return $bundled_path;
	}

	return false;
}
