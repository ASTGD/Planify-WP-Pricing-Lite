<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Settings {
    const OPTION = 'pwpl_settings';

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register' ] );
    }

    public function defaults() {
        return [
            'currency_symbol'   => '$',
            'currency_position' => 'left', // left, right, left_space, right_space
            'thousand_sep'      => ',',
            'decimal_sep'       => '.',
            'price_decimals'    => 2,
            'primary_color'     => '#2563eb',
        ];
    }

    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=pwpl_table',
            __( 'Planify Settings', 'planify-wp-pricing-lite' ),
            __( 'Settings', 'planify-wp-pricing-lite' ),
            'manage_options',
            'pwpl-settings',
            [ $this, 'render_page' ]
        );
    }

    public function register() {
        register_setting( 'pwpl_settings', self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize' ],
            'default'           => $this->defaults(),
        ] );

        add_settings_section( 'pwpl_general', __( 'General', 'planify-wp-pricing-lite' ), '__return_false', 'pwpl-settings' );

        add_settings_field( 'currency_symbol', __( 'Currency symbol', 'planify-wp-pricing-lite' ), [ $this, 'field_currency_symbol' ], 'pwpl-settings', 'pwpl_general' );
        add_settings_field( 'currency_position', __( 'Currency position', 'planify-wp-pricing-lite' ), [ $this, 'field_currency_position' ], 'pwpl-settings', 'pwpl_general' );
        add_settings_field( 'thousand_sep', __( 'Thousand separator', 'planify-wp-pricing-lite' ), [ $this, 'field_thousand_sep' ], 'pwpl-settings', 'pwpl_general' );
        add_settings_field( 'decimal_sep', __( 'Decimal separator', 'planify-wp-pricing-lite' ), [ $this, 'field_decimal_sep' ], 'pwpl-settings', 'pwpl_general' );
        add_settings_field( 'price_decimals', __( 'Price decimals', 'planify-wp-pricing-lite' ), [ $this, 'field_price_decimals' ], 'pwpl-settings', 'pwpl_general' );
        add_settings_field( 'primary_color', __( 'Primary color', 'planify-wp-pricing-lite' ), [ $this, 'field_primary_color' ], 'pwpl-settings', 'pwpl_general' );
    }

    public function get( $key = null ) {
        $opts = wp_parse_args( get_option( self::OPTION, [] ), $this->defaults() );
        return $key ? ( $opts[ $key ] ?? null ) : $opts;
    }

    public function sanitize( $input ) {
        $defaults = $this->defaults();
        $out = [];
        $out['currency_symbol'] = isset( $input['currency_symbol'] ) ? sanitize_text_field( $input['currency_symbol'] ) : $defaults['currency_symbol'];
        $allowed_positions = [ 'left', 'right', 'left_space', 'right_space' ];
        $pos = isset( $input['currency_position'] ) ? sanitize_text_field( $input['currency_position'] ) : $defaults['currency_position'];
        $out['currency_position'] = in_array( $pos, $allowed_positions, true ) ? $pos : $defaults['currency_position'];

        $th = isset( $input['thousand_sep'] ) ? substr( sanitize_text_field( $input['thousand_sep'] ), 0, 1 ) : $defaults['thousand_sep'];
        $de = isset( $input['decimal_sep'] ) ? substr( sanitize_text_field( $input['decimal_sep'] ), 0, 1 ) : $defaults['decimal_sep'];
        $out['thousand_sep'] = $th;
        $out['decimal_sep']  = $de;

        $dec = isset( $input['price_decimals'] ) ? (int) $input['price_decimals'] : $defaults['price_decimals'];
        $out['price_decimals'] = max( 0, min( 4, $dec ) );

        $color = isset( $input['primary_color'] ) ? trim( (string) $input['primary_color'] ) : $defaults['primary_color'];
        $out['primary_color'] = preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ? $color : $defaults['primary_color'];

        return $out;
    }

    // Fields
    public function field_currency_symbol() {
        $v = esc_attr( $this->get( 'currency_symbol' ) );
        echo '<input type="text" name="' . esc_attr( self::OPTION ) . '[currency_symbol]" value="' . $v . '" class="regular-text" maxlength="3" />';
    }

    public function field_currency_position() {
        $v = esc_attr( $this->get( 'currency_position' ) );
        $options = [
            'left'        => __( 'Left (e.g. $100)', 'planify-wp-pricing-lite' ),
            'right'       => __( 'Right (e.g. 100$)', 'planify-wp-pricing-lite' ),
            'left_space'  => __( 'Left with space (e.g. $ 100)', 'planify-wp-pricing-lite' ),
            'right_space' => __( 'Right with space (e.g. 100 $)', 'planify-wp-pricing-lite' ),
        ];
        echo '<select name="' . esc_attr( self::OPTION ) . '[currency_position]">';
        foreach ( $options as $key => $label ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $v, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
    }

    public function field_thousand_sep() {
        $v = esc_attr( $this->get( 'thousand_sep' ) );
        echo '<input type="text" name="' . esc_attr( self::OPTION ) . '[thousand_sep]" value="' . $v . '" class="small-text" maxlength="1" />';
    }

    public function field_decimal_sep() {
        $v = esc_attr( $this->get( 'decimal_sep' ) );
        echo '<input type="text" name="' . esc_attr( self::OPTION ) . '[decimal_sep]" value="' . $v . '" class="small-text" maxlength="1" />';
    }

    public function field_price_decimals() {
        $v = (int) $this->get( 'price_decimals' );
        echo '<input type="number" min="0" max="4" step="1" name="' . esc_attr( self::OPTION ) . '[price_decimals]" value="' . esc_attr( $v ) . '" class="small-text" />';
    }

    public function field_primary_color() {
        $v = esc_attr( $this->get( 'primary_color' ) );
        echo '<input type="color" name="' . esc_attr( self::OPTION ) . '[primary_color]" value="' . $v . '" />';
    }

    public function render_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Planify Settings', 'planify-wp-pricing-lite' ) . '</h1>';
        echo '<form method="post" action="options.php" class="pwpl-admin-meta">';
        settings_fields( 'pwpl_settings' );
        do_settings_sections( 'pwpl-settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

