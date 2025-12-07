<?php
ini_set( 'display_errors', '1' );
error_reporting( E_ALL );

if ( php_sapi_name() !== 'cli' ) {
    fwrite( STDERR, "This script must be run via CLI.\n" );
    exit( 1 );
}

require_once __DIR__ . '/wp-stubs.php';
require_once PWPL_DIR . 'includes/class-pwpl-meta.php';
require_once PWPL_DIR . 'includes/class-pwpl-settings.php';
require_once PWPL_DIR . 'includes/functions-theme.php';
require_once PWPL_DIR . 'includes/class-pwpl-theme-loader.php';
require_once PWPL_DIR . 'includes/class-pwpl-table-templates.php';
require_once PWPL_DIR . 'includes/class-pwpl-table-wizard.php';
require_once PWPL_DIR . 'includes/class-pwpl-table-renderer.php';

$command = $argv[1] ?? '';
if ( 'list' === $command ) {
    $templates = PWPL_Table_Templates::get_templates();
    $visible   = [];
    foreach ( $templates as $template ) {
        if ( ! empty( $template['wizard_hidden'] ) ) {
            continue;
        }
        $visible[] = [
            'id'        => $template['id'] ?? '',
            'theme'     => $template['theme'] ?? 'firevps',
            'thumbnail' => $template['thumbnail'] ?? '',
        ];
    }
    echo wp_json_encode( $visible, JSON_PRETTY_PRINT );
    exit( 0 );
}

$template_id = $argv[1] ?? '';
$output_path = $argv[2] ?? '';
if ( '' === $template_id || '' === $output_path ) {
    fwrite( STDERR, "Usage: php render-template-previews.php <template_id> <output_html>\n" );
    exit( 1 );
}

$config = PWPL_Table_Wizard::build_preview_config( $template_id );
if ( ! $config ) {
    fwrite( STDERR, "Unable to build preview config for {$template_id}\n" );
    exit( 1 );
}

$html = PWPL_Table_Renderer::render_from_config( $config );

$css_core   = file_exists( PWPL_DIR . 'assets/css/frontend.css' ) ? file_get_contents( PWPL_DIR . 'assets/css/frontend.css' ) : '';
$css_theme  = file_exists( PWPL_DIR . 'themes/firevps/theme.css' ) ? file_get_contents( PWPL_DIR . 'themes/firevps/theme.css' ) : '';
$css_themes = file_exists( PWPL_DIR . 'assets/css/themes.css' ) ? file_get_contents( PWPL_DIR . 'assets/css/themes.css' ) : '';

$doc = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { margin:0; background:#f8fafc; padding:40px; }
        {$css_core}
        {$css_themes}
        {$css_theme}
    </style>
</head>
<body>
{$html}
</body>
</html>
HTML;

if ( false === file_put_contents( $output_path, $doc ) ) {
    fwrite( STDERR, "Failed to write {$output_path}\n" );
    exit( 1 );
}
exit( 0 );
