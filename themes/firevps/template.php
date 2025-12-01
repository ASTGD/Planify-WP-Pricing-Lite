<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
printf( '<!-- PWPL FireVPS source: %s -->', esc_html( __FILE__ ) );

// Respect incoming table/plans context from renderer.
$layout_type = '';
if ( isset( $table['layout_type'] ) ) {
    $layout_type = sanitize_key( (string) $table['layout_type'] );
}
$layout_type = $layout_type ?: 'grid';

$layout_file = __DIR__ . '/layouts/grid.php';
if ( 'columns' === $layout_type ) {
    $layout_file = __DIR__ . '/layouts/columns.php';
} elseif ( 'comparison' === $layout_type ) {
    $layout_file = __DIR__ . '/layouts/comparison.php';
}

if ( file_exists( $layout_file ) ) {
    include $layout_file;
    return;
}

include __DIR__ . '/layouts/grid.php';
