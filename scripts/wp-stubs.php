<?php

if ( ! defined( 'PWPL_DIR' ) ) {
    define( 'PWPL_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'PWPL_URL' ) ) {
    define( 'PWPL_URL', 'http://example.com/wp-content/plugins/planify-wp-pricing-lite/' );
}
if ( ! defined( 'PWPL_VERSION' ) ) {
    define( 'PWPL_VERSION', '1.0.0' );
}
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

function __($text) { return $text; }
function _e($text) { echo $text; }
function esc_html_e($text) { echo esc_html($text); }
function esc_attr_e($text) { echo esc_attr($text); }
function esc_html__($text) { return esc_html($text); }
function esc_attr__($text) { return esc_attr($text); }

function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_url($text) { return $text; }
function esc_url_raw($text) { return $text; }
function esc_textarea($text) { return esc_html($text); }

function sanitize_text_field( $str ) {
    $filtered = strip_tags( $str );
    $filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
    return trim( $filtered );
}

function sanitize_key( $key ) {
    $key = strtolower( $key );
    return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}

function sanitize_title( $title ) {
    $title = strtolower( preg_replace( '/[^a-z0-9]+/', '-', $title ) );
    return trim( $title, '-' );
}

function sanitize_html_class( $class ) {
    return preg_replace( '/[^A-Za-z0-9_-]/', '', $class );
}

function sanitize_hex_color( $color ) {
    if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
        return $color;
    }
    return '';
}

function wp_kses_post( $text ) {
    return $text;
}

function wp_strip_all_tags( $text ) {
    return strip_tags( $text );
}

function wp_json_encode( $data, $options = 0 ) {
    return json_encode( $data, $options );
}

function wp_parse_args( $args, $defaults = [] ) {
    return array_merge( $defaults, $args );
}

function trailingslashit( $string ) {
    return rtrim( $string, '/\\' ) . '/';
}
function untrailingslashit( $string ) {
    return rtrim( $string, '/\\' );
}

function number_format_i18n( $number, $decimals = 0 ) {
    return number_format( $number, $decimals );
}

function absint( $maybeint ) {
    return abs( (int) $maybeint );
}

function apply_filters( $tag, $value ) {
    return $value;
}

function do_action( $tag ) {
    return;
}

function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_localize_script() {}
function wp_add_inline_style() {}
function wp_style_is() { return false; }
function wp_script_is() { return false; }

function wp_upload_dir() {
    return [
        'basedir' => sys_get_temp_dir(),
        'baseurl' => 'http://example.com/wp-content/uploads',
    ];
}
function wp_get_upload_dir() {
    return wp_upload_dir();
}

function get_stylesheet_directory() {
    return PWPL_DIR . 'themes/firevps';
}
function get_stylesheet_directory_uri() {
    return PWPL_URL . 'themes/firevps';
}
function get_template_directory() {
    return get_stylesheet_directory();
}
function get_template_directory_uri() {
    return get_stylesheet_directory_uri();
}
function plugin_dir_path( $file ) {
    return trailingslashit( dirname( $file ) );
}
function plugin_dir_url( $file ) {
    return PWPL_URL;
}

function get_option( $name, $default = [] ) {
    if ( 'pwpl_settings' === $name ) {
        return [
            'currency_symbol'  => '$',
            'currency_position'=> 'left',
            'thousand_sep'     => ',',
            'decimal_sep'      => '.',
            'price_decimals'   => 2,
        ];
    }
    return $default;
}

function wp_die( $message ) {
    throw new RuntimeException( $message );
}

function wp_remote_get() { return []; }
