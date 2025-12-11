<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin_Meta {
    public function init() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_pwpl_table', [ $this, 'save_table' ] );
        add_action( 'save_post_pwpl_plan', [ $this, 'save_plan' ] );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'pwpl_table_layout',
            __( 'Layout & Size', 'planify-wp-pricing-lite' ),
            [ $this, 'render_table_layout_meta' ],
            'pwpl_table',
            'normal',
            'high'
        );

        add_meta_box(
            'pwpl_table_dimensions',
            __( 'Dimensions & Variants', 'planify-wp-pricing-lite' ),
            [ $this, 'render_table_meta' ],
            'pwpl_table',
            'normal',
            'default'
        );

        add_meta_box(
            'pwpl_table_shortcode',
            __( 'Shortcode', 'planify-wp-pricing-lite' ),
            [ $this, 'render_table_shortcode_meta' ],
            'pwpl_table',
            'side',
            'default'
        );

        add_meta_box(
            'pwpl_table_badges',
            __( 'Badges & Promotions', 'planify-wp-pricing-lite' ),
            [ $this, 'render_table_badges_meta' ],
            'pwpl_table',
            'side',
            'default'
        );

        add_meta_box(
            'pwpl_plan_details',
            __( 'Plan Details', 'planify-wp-pricing-lite' ),
            [ $this, 'render_plan_meta' ],
            'pwpl_plan',
            'normal',
            'default'
        );

        add_meta_box(
            'pwpl_plan_badges',
            __( 'Promotions (Override)', 'planify-wp-pricing-lite' ),
            [ $this, 'render_plan_badges_meta' ],
            'pwpl_plan',
            'side',
            'default'
        );
    }

    public function render_table_shortcode_meta( $post ) {
        $shortcode = sprintf( '[pwpl_table id="%d"]', $post->ID );
        $input_id  = 'pwpl-shortcode-' . $post->ID;
        ?>
        <p><?php esc_html_e( 'Paste this shortcode into any page, post, or builder module to render the pricing table.', 'planify-wp-pricing-lite' ); ?></p>
        <div class="pwpl-shortcode-field">
            <input type="text" id="<?php echo esc_attr( $input_id ); ?>" class="widefat" readonly value="<?php echo esc_attr( $shortcode ); ?>" />
            <button type="button" class="button pwpl-copy-shortcode" data-target="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Copy', 'planify-wp-pricing-lite' ); ?></button>
        </div>
        <p class="pwpl-copy-feedback" data-pwpl-feedback aria-live="polite"></p>
        <p class="description"><?php esc_html_e( 'Click the field or copy button to use the shortcode.', 'planify-wp-pricing-lite' ); ?></p>
        <?php
    }

    private function settings() {
        return new PWPL_Settings();
    }

    private function layout_has_values( array $values ) {
        foreach ( $values as $value ) {
            if ( is_array( $value ) ) {
                if ( $this->layout_has_values( $value ) ) {
                    return true;
                }
                continue;
            }
            if ( (int) $value > 0 ) {
                return true;
            }
        }
        return false;
    }

    private function convert_legacy_layout_widths( $post_id, PWPL_Meta $meta ) {
        $widths = $meta->sanitize_layout_widths( [] );

        $legacy_size = get_post_meta( $post_id, PWPL_Meta::TABLE_SIZE, true );
        if ( is_array( $legacy_size ) ) {
            $legacy_size = $meta->sanitize_table_size( $legacy_size );
            if ( ! empty( $legacy_size['base'] ) ) {
                $widths['global'] = $legacy_size['base'];
            } elseif ( ! empty( $legacy_size['max'] ) ) {
                $widths['global'] = $legacy_size['max'];
            }
        }

        $legacy_breakpoints = get_post_meta( $post_id, PWPL_Meta::TABLE_BREAKPOINTS, true );
        if ( is_array( $legacy_breakpoints ) ) {
            $map = [ 'big' => 'xxl', 'desktop' => 'xl', 'laptop' => 'lg', 'tablet' => 'md', 'mobile' => 'sm' ];
            foreach ( $legacy_breakpoints as $device => $values ) {
                if ( ! isset( $map[ $device ] ) || ! is_array( $values ) ) {
                    continue;
                }
                $raw = isset( $values['table_max'] ) ? (int) $values['table_max'] : 0;
                if ( $raw <= 0 ) {
                    continue;
                }
                $widths[ $map[ $device ] ] = max( 640, min( $raw, 4000 ) );
            }
        }

        return $widths;
    }

    private function convert_legacy_card_widths( $post_id, PWPL_Meta $meta ) {
        $widths = $meta->sanitize_layout_card_widths( [] );

        $legacy_breakpoints = get_post_meta( $post_id, PWPL_Meta::TABLE_BREAKPOINTS, true );
        if ( is_array( $legacy_breakpoints ) ) {
            $map = [ 'big' => 'xxl', 'desktop' => 'xl', 'laptop' => 'lg', 'tablet' => 'md', 'mobile' => 'sm' ];
            foreach ( $legacy_breakpoints as $device => $values ) {
                if ( ! isset( $map[ $device ] ) || ! is_array( $values ) ) {
                    continue;
                }
                $raw = isset( $values['card_min'] ) ? (int) $values['card_min'] : 0;
                if ( $raw <= 0 ) {
                    continue;
                }
                $widths[ $map[ $device ] ] = max( 1, min( $raw, 4000 ) );
            }
        }

        return $widths;
    }

    private function load_layout_meta( $post_id, PWPL_Meta $meta ) {
        $widths_raw = get_post_meta( $post_id, PWPL_Meta::LAYOUT_WIDTHS, true );
        $widths     = $meta->sanitize_layout_widths( is_array( $widths_raw ) ? $widths_raw : [] );

        if ( ! $this->layout_has_values( $widths ) ) {
            $converted = $this->convert_legacy_layout_widths( $post_id, $meta );
            if ( $this->layout_has_values( $converted ) ) {
                $widths = $meta->sanitize_layout_widths( $converted );
                update_post_meta( $post_id, PWPL_Meta::LAYOUT_WIDTHS, $widths );
            }
        }

        $columns_raw = get_post_meta( $post_id, PWPL_Meta::LAYOUT_COLUMNS, true );
        $columns     = $meta->sanitize_layout_cards( is_array( $columns_raw ) ? $columns_raw : [] );

        $card_widths_raw = get_post_meta( $post_id, PWPL_Meta::LAYOUT_CARD_WIDTHS, true );
        $card_widths     = $meta->sanitize_layout_card_widths( is_array( $card_widths_raw ) ? $card_widths_raw : [] );

        if ( ! $this->layout_has_values( $card_widths ) ) {
            $converted_cards = $this->convert_legacy_card_widths( $post_id, $meta );
            if ( $this->layout_has_values( $converted_cards ) ) {
                $card_widths = $meta->sanitize_layout_card_widths( $converted_cards );
                update_post_meta( $post_id, PWPL_Meta::LAYOUT_CARD_WIDTHS, $card_widths );
            }
        }

        $breakpoints_raw = get_post_meta( $post_id, PWPL_Meta::TABLE_BREAKPOINTS, true );
        $breakpoints     = $meta->sanitize_table_breakpoints( is_array( $breakpoints_raw ) ? $breakpoints_raw : [] );

        return [ $widths, $columns, $card_widths, $breakpoints ];
    }

    public function render_table_layout_meta( $post ) {
        // Ensure card text config is available for the Text styles section in this box
        $meta_helper = new PWPL_Meta();
        $card_raw    = get_post_meta( $post->ID, PWPL_Meta::CARD_CONFIG, true );
        $card_config = is_array( $card_raw ) ? $meta_helper->sanitize_card_config( $card_raw ) : [];
        $card_text   = (array) ( $card_config['text'] ?? [] );
        $meta_helper = new PWPL_Meta();
        list( $layout_widths, $layout_columns, $card_widths, $breakpoint_values ) = $this->load_layout_meta( $post->ID, $meta_helper );

        $width_labels = [
            'global' => __( 'Global default width', 'planify-wp-pricing-lite' ),
            'xxl'    => __( 'Big screens (≥ 1536px)', 'planify-wp-pricing-lite' ),
            'xl'     => __( 'Desktop (1280–1535px)', 'planify-wp-pricing-lite' ),
            'lg'     => __( 'Laptop (1024–1279px)', 'planify-wp-pricing-lite' ),
            'md'     => __( 'Tablet (768–1023px)', 'planify-wp-pricing-lite' ),
            'sm'     => __( 'Mobile (≤ 767px)', 'planify-wp-pricing-lite' ),
        ];

        $device_map = [
            'xxl' => 'big',
            'xl'  => 'desktop',
            'lg'  => 'laptop',
            'md'  => 'tablet',
            'sm'  => 'mobile',
        ];

        $badge_overrides = __( 'Overrides columns', 'planify-wp-pricing-lite' );
        $badge_inherits  = __( 'Inherits columns', 'planify-wp-pricing-lite' );

        $global_width       = isset( $layout_widths['global'] ) ? (int) $layout_widths['global'] : 0;
        $global_card_width  = isset( $card_widths['global'] ) ? (int) $card_widths['global'] : 0;
        $global_columns     = isset( $layout_columns['global'] ) ? (int) $layout_columns['global'] : 0;
        $global_badge_state = $global_card_width > 0 ? 'overrides' : 'inherits';
        $global_badge_text  = $global_card_width > 0 ? $badge_overrides : $badge_inherits;

        ?>
        <div class="pwpl-meta pwpl-meta--layout" data-pwpl-layout>
            <div class="pwpl-layout-v2">
                <p class="description"><?php esc_html_e( 'Set a base width for the table and override it for specific breakpoints. Leave a value at 0 to inherit the global default.', 'planify-wp-pricing-lite' ); ?></p>

                <div class="pwpl-range-control">
                    <label for="pwpl_layout_width_global"><strong><?php echo esc_html( $width_labels['global'] ); ?></strong></label>
                    <div class="pwpl-range-control__inputs">
                        <input type="range" id="pwpl_layout_width_global" name="pwpl_table[layout][widths][global]" min="0" max="4000" step="1" value="<?php echo esc_attr( $global_width ); ?>" data-pwpl-range data-pwpl-range-output="#pwpl_layout_width_global_value" data-pwpl-range-unit="px" data-pwpl-range-empty="<?php esc_attr_e( 'inherit', 'planify-wp-pricing-lite' ); ?>" />
                        <input type="number" id="pwpl_layout_width_global_number" class="pwpl-range-control__number" name="pwpl_table[layout][widths][global]" min="0" max="4000" step="1" value="<?php echo $global_width ? esc_attr( $global_width ) : ''; ?>" data-pwpl-range-input data-pwpl-range-sync="#pwpl_layout_width_global" />
                        <div class="pwpl-range-control__value"><output id="pwpl_layout_width_global_value"><?php echo $global_width ? esc_html( $global_width . 'px' ) : esc_html__( 'inherit', 'planify-wp-pricing-lite' ); ?></output></div>
                    </div>
                    <p class="description"><?php esc_html_e( 'Applies to all devices unless overridden below.', 'planify-wp-pricing-lite' ); ?></p>
                </div>

                <fieldset class="pwpl-device-widths">
                    <legend><?php esc_html_e( 'Device widths (px)', 'planify-wp-pricing-lite' ); ?></legend>
                    <div class="pwpl-device-grid">
                        <?php foreach ( [ 'xxl', 'xl', 'lg', 'md', 'sm' ] as $device_key ) :
                            $value    = isset( $layout_widths[ $device_key ] ) ? (int) $layout_widths[ $device_key ] : 0;
                            $input_id = 'pwpl_layout_width_' . $device_key;
                            $number_id = $input_id . '_number';
                            ?>
                            <div class="pwpl-device-slider">
                                <label for="<?php echo esc_attr( $input_id ); ?>"><span><?php echo esc_html( $width_labels[ $device_key ] ); ?></span></label>
                                <div class="pwpl-range-control__inputs">
                                    <input type="range" id="<?php echo esc_attr( $input_id ); ?>" name="pwpl_table[layout][widths][<?php echo esc_attr( $device_key ); ?>]" min="0" max="4000" step="1" value="<?php echo esc_attr( $value ); ?>" data-pwpl-range data-pwpl-range-output="#<?php echo esc_attr( $input_id ); ?>_value" data-pwpl-range-unit="px" data-pwpl-range-empty="<?php esc_attr_e( 'inherit', 'planify-wp-pricing-lite' ); ?>" />
                                    <input type="number" id="<?php echo esc_attr( $number_id ); ?>" class="pwpl-range-control__number" name="pwpl_table[layout][widths][<?php echo esc_attr( $device_key ); ?>]" min="0" max="4000" step="1" value="<?php echo $value ? esc_attr( $value ) : ''; ?>" data-pwpl-range-input data-pwpl-range-sync="#<?php echo esc_attr( $input_id ); ?>" />
                                    <div class="pwpl-range-control__value"><output id="<?php echo esc_attr( $input_id ); ?>_value"><?php echo $value ? esc_html( $value . 'px' ) : esc_html__( 'inherit', 'planify-wp-pricing-lite' ); ?></output></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="pwpl-card-settings" data-pwpl-card-settings>
                    <h4><?php esc_html_e( 'Cards', 'planify-wp-pricing-lite' ); ?></h4>
                    <p class="description"><?php esc_html_e( 'Card min width overrides preferred columns at the same breakpoint. Clear the value to inherit columns.', 'planify-wp-pricing-lite' ); ?></p>

                    <div class="pwpl-card-device pwpl-card-device--global" data-pwpl-card-row="global">
                        <div class="pwpl-card-device__header">
                            <span class="pwpl-card-device__label"><?php echo esc_html( $width_labels['global'] ); ?></span>
                            <span class="pwpl-card-badge pwpl-card-badge--<?php echo esc_attr( $global_badge_state ); ?>" data-pwpl-card-badge data-overrides-label="<?php echo esc_attr( $badge_overrides ); ?>" data-inherit-label="<?php echo esc_attr( $badge_inherits ); ?>"><?php echo esc_html( $global_badge_text ); ?></span>
                        </div>
                        <div class="pwpl-range-control">
                            <label for="pwpl_card_width_global"><strong><?php esc_html_e( 'Card min width', 'planify-wp-pricing-lite' ); ?></strong></label>
                            <div class="pwpl-range-control__inputs">
                                <input type="range" id="pwpl_card_width_global" name="pwpl_table[layout][card_widths][global]" min="0" max="4000" step="1" value="<?php echo esc_attr( $global_card_width ); ?>" data-pwpl-range data-pwpl-range-output="#pwpl_card_width_global_value" data-pwpl-range-unit="px" data-pwpl-range-empty="<?php esc_attr_e( 'inherit', 'planify-wp-pricing-lite' ); ?>" />
                                <input type="number" id="pwpl_card_width_global_number" class="pwpl-range-control__number" name="pwpl_table[layout][card_widths][global]" min="0" max="4000" step="1" value="<?php echo $global_card_width ? esc_attr( $global_card_width ) : ''; ?>" data-pwpl-range-input data-pwpl-range-sync="#pwpl_card_width_global" />
                                <div class="pwpl-range-control__value"><output id="pwpl_card_width_global_value"><?php echo $global_card_width ? esc_html( $global_card_width . 'px' ) : esc_html__( 'inherit', 'planify-wp-pricing-lite' ); ?></output></div>
                            </div>
                        </div>
                        <label class="pwpl-card-device__columns" for="pwpl_card_columns_global">
                            <strong><?php esc_html_e( 'Preferred columns', 'planify-wp-pricing-lite' ); ?></strong>
                            <input type="number" id="pwpl_card_columns_global" name="pwpl_table[layout][columns][global]" min="0" max="20" step="1" value="<?php echo $global_columns ? esc_attr( $global_columns ) : ''; ?>" placeholder="<?php esc_attr_e( 'auto', 'planify-wp-pricing-lite' ); ?>" />
                        </label>
                    </div>

                    <div class="pwpl-card-grid">
                        <?php foreach ( $device_map as $layout_key => $legacy_key ) :
                            $card_width   = isset( $card_widths[ $layout_key ] ) ? (int) $card_widths[ $layout_key ] : 0;
                            $columns_val  = isset( $layout_columns[ $layout_key ] ) ? (int) $layout_columns[ $layout_key ] : 0;
                            $height_val   = isset( $breakpoint_values[ $legacy_key ]['card_min_h'] ) ? (int) $breakpoint_values[ $legacy_key ]['card_min_h'] : 0;
                            $badge_state  = $card_width > 0 ? 'overrides' : 'inherits';
                            $badge_text   = $card_width > 0 ? $badge_overrides : $badge_inherits;
                            $range_id     = 'pwpl_card_width_' . $layout_key;
                            $number_id    = $range_id . '_number';
                            $column_id    = 'pwpl_card_columns_' . $layout_key;
                            $height_id    = 'pwpl_card_height_' . $layout_key;
                            ?>
                            <div class="pwpl-card-device" data-pwpl-card-row="<?php echo esc_attr( $layout_key ); ?>">
                                <div class="pwpl-card-device__header">
                                    <span class="pwpl-card-device__label"><?php echo esc_html( $width_labels[ $layout_key ] ); ?></span>
                                    <span class="pwpl-card-badge pwpl-card-badge--<?php echo esc_attr( $badge_state ); ?>" data-pwpl-card-badge data-overrides-label="<?php echo esc_attr( $badge_overrides ); ?>" data-inherit-label="<?php echo esc_attr( $badge_inherits ); ?>"><?php echo esc_html( $badge_text ); ?></span>
                                </div>
                                <div class="pwpl-range-control">
                                    <label for="<?php echo esc_attr( $range_id ); ?>"><?php esc_html_e( 'Card min width', 'planify-wp-pricing-lite' ); ?></label>
                                    <div class="pwpl-range-control__inputs">
                                        <input type="range" id="<?php echo esc_attr( $range_id ); ?>" name="pwpl_table[layout][card_widths][<?php echo esc_attr( $layout_key ); ?>]" min="0" max="4000" step="1" value="<?php echo esc_attr( $card_width ); ?>" data-pwpl-range data-pwpl-range-output="#<?php echo esc_attr( $range_id ); ?>_value" data-pwpl-range-unit="px" data-pwpl-range-empty="<?php esc_attr_e( 'inherit', 'planify-wp-pricing-lite' ); ?>" />
                                        <input type="number" id="<?php echo esc_attr( $number_id ); ?>" class="pwpl-range-control__number" name="pwpl_table[layout][card_widths][<?php echo esc_attr( $layout_key ); ?>]" min="0" max="4000" step="1" value="<?php echo $card_width ? esc_attr( $card_width ) : ''; ?>" data-pwpl-range-input data-pwpl-range-sync="#<?php echo esc_attr( $range_id ); ?>" />
                                        <div class="pwpl-range-control__value"><output id="<?php echo esc_attr( $range_id ); ?>_value"><?php echo $card_width ? esc_html( $card_width . 'px' ) : esc_html__( 'inherit', 'planify-wp-pricing-lite' ); ?></output></div>
                                    </div>
                                </div>
                                <label class="pwpl-card-device__columns" for="<?php echo esc_attr( $column_id ); ?>">
                                    <span><?php esc_html_e( 'Preferred columns', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" id="<?php echo esc_attr( $column_id ); ?>" name="pwpl_table[layout][columns][<?php echo esc_attr( $layout_key ); ?>]" min="0" max="20" step="1" value="<?php echo $columns_val ? esc_attr( $columns_val ) : ''; ?>" placeholder="<?php esc_attr_e( 'inherit', 'planify-wp-pricing-lite' ); ?>" />
                                </label>
                                <label class="pwpl-card-device__height" for="<?php echo esc_attr( $height_id ); ?>">
                                    <span><?php esc_html_e( 'Min height (px, optional)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" id="<?php echo esc_attr( $height_id ); ?>" class="pwpl-range-control__number" name="pwpl_table[breakpoints][<?php echo esc_attr( $legacy_key ); ?>][card_min_h]" min="0" max="4000" step="1" value="<?php echo $height_val ? esc_attr( $height_val ) : ''; ?>" placeholder="<?php esc_attr_e( 'auto', 'planify-wp-pricing-lite' ); ?>" />
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="pwpl-field">
                <strong><?php esc_html_e( 'Text styles', 'planify-wp-pricing-lite' ); ?></strong>
                <p class="description"><?php esc_html_e( 'Control text color and typography for the Top section and Specs section. Leave blank to inherit theme defaults.', 'planify-wp-pricing-lite' ); ?></p>
                <?php $card_text = is_array( $card_text ) ? $card_text : []; ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap:16px; margin-top:10px;">
                    <div>
                        <h4><?php esc_html_e( 'Top section (header + price + CTA context)', 'planify-wp-pricing-lite' ); ?></h4>
                        <?php $top_text = isset($card_text['top']) && is_array($card_text['top']) ? $card_text['top'] : []; ?>
                        <label style="display:flex; flex-direction:column; gap:6px;">
                            <span><?php esc_html_e( 'Text color', 'planify-wp-pricing-lite' ); ?></span>
                            <?php $tx = isset($top_text['color']) ? (string)$top_text['color'] : ''; $tx_hex = ($tx && preg_match('/^#/',$tx)) ? $tx : ''; ?>
                            <input type="color" name="pwpl_table[card][text][top][color]" value="<?php echo esc_attr( $tx_hex ); ?>" data-pwpl-card-field="text.top.color" />
                        </label>
                        <label style="display:flex; flex-direction:column; gap:6px; margin-top:8px;">
                            <span><?php esc_html_e( 'Font family', 'planify-wp-pricing-lite' ); ?></span>
                            <?php $fam = isset($top_text['family']) ? (string)$top_text['family'] : ''; ?>
                            <input type="text" name="pwpl_table[card][text][top][family]" value="<?php echo esc_attr( $fam ); ?>" placeholder="system-ui, -apple-system, sans-serif" data-pwpl-card-field="text.top.family" />
                            <span class="description" style="opacity:.75;">
                                <?php esc_html_e( 'Enter a CSS font stack (e.g., "Inter", system-ui, sans-serif). Leave blank to inherit.', 'planify-wp-pricing-lite' ); ?>
                            </span>
                        </label>
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:8px; align-items:flex-end;">
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Size (px)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="10" max="28" step="1" name="pwpl_table[card][text][top][size]" value="<?php echo esc_attr( $top_text['size'] ?? '' ); ?>" data-pwpl-card-field="text.top.size" />
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="300" max="900" step="50" name="pwpl_table[card][text][top][weight]" value="<?php echo esc_attr( $top_text['weight'] ?? '' ); ?>" data-pwpl-card-field="text.top.weight" />
                            </label>
                        </div>
                    </div>
                    <div>
                        <h4><?php esc_html_e( 'Specs section', 'planify-wp-pricing-lite' ); ?></h4>
                        <?php $spec_text = isset($card_text['specs']) && is_array($card_text['specs']) ? $card_text['specs'] : []; ?>
                        <label style="display:flex; flex-direction:column; gap:6px;">
                            <span><?php esc_html_e( 'Text color', 'planify-wp-pricing-lite' ); ?></span>
                            <?php $sx = isset($spec_text['color']) ? (string)$spec_text['color'] : ''; $sx_hex = ($sx && preg_match('/^#/',$sx)) ? $sx : ''; ?>
                            <input type="color" name="pwpl_table[card][text][specs][color]" value="<?php echo esc_attr( $sx_hex ); ?>" data-pwpl-card-field="text.specs.color" />
                        </label>
                        <label style="display:flex; flex-direction:column; gap:6px; margin-top:8px;">
                            <span><?php esc_html_e( 'Font family', 'planify-wp-pricing-lite' ); ?></span>
                            <?php $sfam = isset($spec_text['family']) ? (string)$spec_text['family'] : ''; ?>
                            <input type="text" name="pwpl_table[card][text][specs][family]" value="<?php echo esc_attr( $sfam ); ?>" placeholder="system-ui, sans-serif" data-pwpl-card-field="text.specs.family" />
                            <span class="description" style="opacity:.75;">
                                <?php esc_html_e( 'Enter a CSS font stack (e.g., "Poppins", sans-serif). Leave blank to inherit.', 'planify-wp-pricing-lite' ); ?>
                            </span>
                        </label>
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:8px; align-items:flex-end;">
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Size (px)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="10" max="24" step="1" name="pwpl_table[card][text][specs][size]" value="<?php echo esc_attr( $spec_text['size'] ?? '' ); ?>" data-pwpl-card-field="text.specs.size" />
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="300" max="900" step="50" name="pwpl_table[card][text][specs][weight]" value="<?php echo esc_attr( $spec_text['weight'] ?? '' ); ?>" data-pwpl-card-field="text.specs.weight" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_dimension_catalog() {
        $settings = $this->settings();
        $catalog  = [
            'period'   => (array) $settings->get( 'periods' ),
            'location' => (array) $settings->get( 'locations' ),
            'platform' => (array) $settings->get( 'platforms' ),
        ];

        $indexed = [];
        foreach ( $catalog as $dimension => $items ) {
            $indexed[ $dimension ] = [];
            foreach ( $items as $item ) {
                if ( empty( $item['slug'] ) ) {
                    continue;
                }
                $indexed[ $dimension ][ $item['slug'] ] = [
                    'slug'  => $item['slug'],
                    'label' => $item['label'] ?? $item['slug'],
                ];
            }
        }

        return $indexed;
    }

    private function get_dimension_options( $table_id = 0 ) {
        $catalog   = $this->get_dimension_catalog();
        $dimensions = [ 'period', 'location', 'platform' ];
        $options   = [];

        if ( $table_id ) {
            $allowed_map = [
                'period'   => get_post_meta( $table_id, PWPL_Meta::ALLOWED_PERIODS, true ),
                'location' => get_post_meta( $table_id, PWPL_Meta::ALLOWED_LOCATIONS, true ),
                'platform' => get_post_meta( $table_id, PWPL_Meta::ALLOWED_PLATFORMS, true ),
            ];
        } else {
            $allowed_map = [ 'period' => [], 'location' => [], 'platform' => [] ];
        }

        foreach ( $dimensions as $dimension ) {
            $options[ $dimension ] = [];
            $allowed_slugs = array_filter( (array) ( $allowed_map[ $dimension ] ?? [] ) );

            if ( empty( $allowed_slugs ) ) {
                $allowed_slugs = array_keys( $catalog[ $dimension ] );
            }

            foreach ( $allowed_slugs as $slug ) {
                if ( isset( $catalog[ $dimension ][ $slug ] ) ) {
                    $options[ $dimension ][] = $catalog[ $dimension ][ $slug ];
                }
            }
        }

        return $options;
    }

    private function find_dimension_item_label( array $items, $slug ) {
        if ( ! $slug ) {
            return '';
        }
        foreach ( $items as $item ) {
            if ( ! isset( $item['slug'] ) || $item['slug'] !== $slug ) {
                continue;
            }
            return isset( $item['label'] ) ? (string) $item['label'] : (string) $slug;
        }
        return '';
    }

    private function render_badge_priority_controls( $context, $selected ) {
        $field_prefix = $context === 'table' ? 'pwpl_table_badges' : 'pwpl_plan_badges_override';
        $dimensions   = [ 'period', 'location', 'platform' ];
        $selected     = is_array( $selected ) ? array_values( array_intersect( $selected, $dimensions ) ) : [];
        if ( empty( $selected ) ) {
            $selected = [ 'period', 'location', 'platform' ];
        }
        $selected = array_values( array_unique( array_merge( $selected, $dimensions ) ) );

        ?>
        <div class="pwpl-field pwpl-badge-priority">
            <label><strong><?php esc_html_e( 'Badge priority', 'planify-wp-pricing-lite' ); ?></strong></label>
            <p class="description"><?php esc_html_e( 'When multiple promotions apply, the first dimension in this list wins.', 'planify-wp-pricing-lite' ); ?></p>
            <ol class="pwpl-badge-priority__list">
                <?php foreach ( [0,1,2] as $index ) :
                    $current = $selected[ $index ] ?? $dimensions[ $index ];
                    ?>
                    <li>
                        <label class="screen-reader-text" for="<?php echo esc_attr( $field_prefix . '_priority_' . $index ); ?>"><?php printf( esc_html__( 'Priority %d', 'planify-wp-pricing-lite' ), $index + 1 ); ?></label>
                        <select id="<?php echo esc_attr( $field_prefix . '_priority_' . $index ); ?>" name="<?php echo esc_attr( $field_prefix ); ?>[priority][<?php echo esc_attr( $index ); ?>]" class="widefat">
                            <?php foreach ( $dimensions as $dimension ) : ?>
                                <option value="<?php echo esc_attr( $dimension ); ?>" <?php selected( $current, $dimension ); ?>><?php echo esc_html( ucfirst( $dimension ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
    }

    private function render_badge_section( $context, $dimension, $label, array $rows, array $options ) {
        $field_prefix = $context === 'table' ? 'pwpl_table_badges' : 'pwpl_plan_badges_override';
        $target       = 'badge-' . $context . '-' . $dimension;
        $template     = 'pwpl-badge-row-' . $context . '-' . $dimension;
        $rows         = array_values( $rows );
        $row_count    = count( $rows );

        if ( empty( $options ) ) {
            ?>
            <p class="description"><?php printf( esc_html__( 'No %s values available. Configure them in Dimensions & Variants first.', 'planify-wp-pricing-lite' ), esc_html( strtolower( $label ) ) ); ?></p>
            <?php
            return;
        }

        if ( $row_count === 0 ) {
            $rows      = [ [] ];
            $row_count = 0;
        }

        ?>
        <div class="pwpl-badge-group pwpl-badge-group--<?php echo esc_attr( $dimension ); ?>">
            <h4><?php echo esc_html( $label ); ?></h4>
            <table class="widefat pwpl-repeatable" data-pwpl-repeatable="<?php echo esc_attr( $target ); ?>" data-template="<?php echo esc_attr( $template ); ?>" data-next-index="<?php echo esc_attr( max( $row_count, count( $rows ) ) ); ?>">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Value', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'Badge label', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'Badge color', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'Text color', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'Icon', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'Tone', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'Start', 'planify-wp-pricing-lite' ); ?></th>
                        <th><?php esc_html_e( 'End', 'planify-wp-pricing-lite' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $index => $row ) :
                        $value      = $row['slug'] ?? '';
                        $badge      = $row['label'] ?? '';
                        $color      = $row['color'] ?? '';
                        $text_color = $row['text_color'] ?? '';
                        $icon       = $row['icon'] ?? '';
                        $tone       = $row['tone'] ?? '';
                        $start      = $row['start'] ?? '';
                        $end        = $row['end'] ?? '';
                        ?>
                        <tr>
                            <td>
                                <select name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][slug]" class="widefat">
                                    <option value=""><?php esc_html_e( 'Select', 'planify-wp-pricing-lite' ); ?></option>
                                    <?php foreach ( $options as $option ) : ?>
                                        <option value="<?php echo esc_attr( $option['slug'] ); ?>" <?php selected( $value, $option['slug'] ); ?>><?php echo esc_html( $option['label'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $badge ); ?>" class="widefat" /></td>
                            <td><input type="color" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][color]" value="<?php echo esc_attr( $color ); ?>" /></td>
                            <td><input type="color" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][text_color]" value="<?php echo esc_attr( $text_color ); ?>" /></td>
                            <td><input type="text" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][icon]" value="<?php echo esc_attr( $icon ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Optional', 'planify-wp-pricing-lite' ); ?>" /></td>
                            <td>
                                <select name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][tone]" class="widefat">
                                    <option value=""><?php esc_html_e( 'Auto', 'planify-wp-pricing-lite' ); ?></option>
                                    <?php foreach ( [ 'success', 'info', 'warning', 'danger', 'neutral' ] as $tone_option ) : ?>
                                        <option value="<?php echo esc_attr( $tone_option ); ?>" <?php selected( $tone, $tone_option ); ?>><?php echo esc_html( ucfirst( $tone_option ) ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="date" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][start]" value="<?php echo esc_attr( $start ); ?>" /></td>
                            <td><input type="date" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $index ); ?>][end]" value="<?php echo esc_attr( $end ); ?>" /></td>
                            <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove badge', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button button-secondary pwpl-add-row" data-target="<?php echo esc_attr( $target ); ?>"><?php esc_html_e( 'Add Promotion', 'planify-wp-pricing-lite' ); ?></button></p>
        </div>

        <script type="text/html" id="tmpl-<?php echo esc_attr( $template ); ?>">
            <tr>
                <td>
                    <select name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][slug]" class="widefat">
                        <option value=""><?php esc_html_e( 'Select', 'planify-wp-pricing-lite' ); ?></option>
                        <?php foreach ( $options as $option ) : ?>
                            <option value="<?php echo esc_attr( $option['slug'] ); ?>"><?php echo esc_html( $option['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][label]" class="widefat" /></td>
                <td><input type="color" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][color]" /></td>
                <td><input type="color" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][text_color]" /></td>
                <td><input type="text" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][icon]" class="widefat" placeholder="<?php esc_attr_e( 'Optional', 'planify-wp-pricing-lite' ); ?>" /></td>
                <td>
                    <select name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][tone]" class="widefat">
                        <option value=""><?php esc_html_e( 'Auto', 'planify-wp-pricing-lite' ); ?></option>
                        <?php foreach ( [ 'success', 'info', 'warning', 'danger', 'neutral' ] as $tone_option ) : ?>
                            <option value="<?php echo esc_attr( $tone_option ); ?>"><?php echo esc_html( ucfirst( $tone_option ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="date" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][start]" /></td>
                <td><input type="date" name="<?php echo esc_attr( $field_prefix ); ?>[<?php echo esc_attr( $dimension ); ?>][{{data.index}}][end]" /></td>
                <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove badge', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
            </tr>
        </script>
        <?php
    }

    public function render_table_meta( $post ) {
        wp_nonce_field( 'pwpl_save_table_' . $post->ID, 'pwpl_table_nonce' );

        $dimensions = get_post_meta( $post->ID, PWPL_Meta::DIMENSION_META, true );
        if ( ! is_array( $dimensions ) ) {
            $dimensions = [];
        }

        $allowed_platforms = get_post_meta( $post->ID, PWPL_Meta::ALLOWED_PLATFORMS, true );
        $allowed_periods   = get_post_meta( $post->ID, PWPL_Meta::ALLOWED_PERIODS, true );
        $allowed_locations = get_post_meta( $post->ID, PWPL_Meta::ALLOWED_LOCATIONS, true );

        $settings = $this->settings();
        $platforms = (array) $settings->get( 'platforms' );
        $periods   = (array) $settings->get( 'periods' );
        $locations = (array) $settings->get( 'locations' );

        $meta_helper   = new PWPL_Meta();
        $table_theme   = get_post_meta( $post->ID, PWPL_Meta::TABLE_THEME, true );
        $table_theme   = $meta_helper->sanitize_theme( $table_theme ?: '' );
        if ( ! $table_theme || $table_theme === 'classic' ) {
            $maybe = array_map( function($t){ return sanitize_key( $t['slug'] ?? '' ); }, (array) ( new PWPL_Theme_Loader() )->get_available_themes() );
            if ( in_array( 'firevps', $maybe, true ) ) { $table_theme = 'firevps'; }
            elseif ( ! $table_theme ) { $table_theme = 'classic'; }
        }
        $theme_loader  = new PWPL_Theme_Loader();
        $available_themes = $theme_loader->get_available_themes();
        $themes = [];

        foreach ( $available_themes as $theme ) {
            $label = isset( $theme['name'] ) && $theme['name'] ? (string) $theme['name'] : ucwords( str_replace( '-', ' ', $theme['slug'] ) );
            $themes[ $theme['slug'] ] = $label;
        }

        // Load card (Plan Card) configuration early so all sections can read values.
        $card_raw    = get_post_meta( $post->ID, PWPL_Meta::CARD_CONFIG, true );
        $card_config = is_array( $card_raw ) ? $meta_helper->sanitize_card_config( $card_raw ) : [];
        $card_layout = (array) ( $card_config['layout'] ?? [] );
        $card_colors = (array) ( $card_config['colors'] ?? [] );
        $card_typo   = (array) ( $card_config['typo'] ?? [] );
        $card_text   = (array) ( $card_config['text'] ?? [] );
        if ( empty( $card_text ) && is_array( $card_raw ) && isset( $card_raw['text'] ) && is_array( $card_raw['text'] ) ) {
            foreach ( $card_raw['text'] as $area_key => $area_values ) {
                if ( ! is_array( $area_values ) ) {
                    continue;
                }
                $card_text[ $area_key ] = [];
                foreach ( $area_values as $prop_key => $prop_value ) {
                    if ( in_array( $prop_key, [ 'size', 'weight' ], true ) ) {
                        $card_text[ $area_key ][ $prop_key ] = (int) $prop_value;
                    } elseif ( is_scalar( $prop_value ) ) {
                        $card_text[ $area_key ][ $prop_key ] = sanitize_text_field( (string) $prop_value );
                    }
                }
            }
        }
        $card_preset = (string) ( $card_config['preset'] ?? '' );

        $dimension_map = [
            'platform' => [
                'label'   => __( 'Platform / OS', 'planify-wp-pricing-lite' ),
                'values'  => $platforms,
                'allowed' => (array) $allowed_platforms,
            ],
            'period' => [
                'label'   => __( 'Service Period', 'planify-wp-pricing-lite' ),
                'values'  => $periods,
                'allowed' => (array) $allowed_periods,
            ],
            'location' => [
                'label'   => __( 'Location', 'planify-wp-pricing-lite' ),
                'values'  => $locations,
                'allowed' => (array) $allowed_locations,
            ],
        ];
        $tabs_glass = (int) get_post_meta( $post->ID, PWPL_Meta::TABS_GLASS, true );
        $specs_style = get_post_meta( $post->ID, PWPL_Meta::SPECS_STYLE, true );
        $specs_style = in_array( $specs_style, [ 'default', 'flat', 'segmented', 'chips' ], true ) ? $specs_style : 'default';
        $anim_preset = get_post_meta( $post->ID, PWPL_Meta::SPECS_ANIM_PRESET, true );
        $anim_preset = in_array( $anim_preset, [ 'off', 'minimal', 'segmented', 'chips', 'all' ], true ) ? $anim_preset : 'minimal';
        $anim_flags  = get_post_meta( $post->ID, PWPL_Meta::SPECS_ANIM_FLAGS, true );
        $anim_flags  = is_array( $anim_flags ) ? array_values( array_intersect( array_map( 'sanitize_key', $anim_flags ), [ 'row', 'icon', 'divider', 'chip', 'stagger' ] ) ) : [];
        $anim_intensity = (int) get_post_meta( $post->ID, PWPL_Meta::SPECS_ANIM_INTENSITY, true ); if ( $anim_intensity <= 0 ) $anim_intensity = 45;
        $anim_mobile = (int) get_post_meta( $post->ID, PWPL_Meta::SPECS_ANIM_MOBILE, true );
        $trust_trio  = (int) get_post_meta( $post->ID, PWPL_Meta::TRUST_TRIO_ENABLED, true );
        $sticky_cta  = (int) get_post_meta( $post->ID, PWPL_Meta::STICKY_CTA_MOBILE, true );
        $card_split  = isset( $card_layout['split'] ) ? $card_layout['split'] : 'two_tone';
        if ( ! in_array( $card_split, [ 'two_tone' ], true ) ) {
            $card_split = 'two_tone';
        }
        $card_specs_grad = isset( $card_colors['specs_grad'] ) && is_array( $card_colors['specs_grad'] ) ? $card_colors['specs_grad'] : [];
        $card_keyline    = isset( $card_colors['keyline'] ) && is_array( $card_colors['keyline'] ) ? $card_colors['keyline'] : [];
        $card_typo_title = isset( $card_typo['title'] ) && is_array( $card_typo['title'] ) ? $card_typo['title'] : [];
        $card_typo_sub   = isset( $card_typo['subtitle'] ) && is_array( $card_typo['subtitle'] ) ? $card_typo['subtitle'] : [];
        $card_typo_price = isset( $card_typo['price'] ) && is_array( $card_typo['price'] ) ? $card_typo['price'] : [];
        $card_pad_values = [
            'pad_t' => $card_layout['pad_t'] ?? '',
            'pad_r' => $card_layout['pad_r'] ?? '',
            'pad_b' => $card_layout['pad_b'] ?? '',
            'pad_l' => $card_layout['pad_l'] ?? '',
        ];
        $pad_lock = true;
        $non_empty_pads = array_filter( $card_pad_values, function( $value ) {
            return $value !== '' && $value !== null;
        } );
        if ( count( $non_empty_pads ) > 1 ) {
            $first_pad = (int) reset( $non_empty_pads );
            foreach ( $non_empty_pads as $pad_value ) {
                if ( (int) $pad_value !== $first_pad ) {
                    $pad_lock = false;
                    break;
                }
            }
        }
        $wizard_preset = get_post_meta( $post->ID, PWPL_Meta::TABLE_PRESET, true );
        $is_wizard_table = ! empty( $wizard_preset );
        ?>
        <div class="pwpl-meta pwpl-meta--table" data-pwpl-dimensions>
            <div class="pwpl-field">
                <label for="pwpl_table_theme"><strong><?php esc_html_e( 'Theme / Style', 'planify-wp-pricing-lite' ); ?></strong></label>
                <?php if ( $is_wizard_table ) : ?>
                    <input type="hidden" id="pwpl_table_theme" name="pwpl_table[theme]" value="firevps" />
                    <p class="description"><?php esc_html_e( 'Wizard-created tables use the FireVPS layout automatically based on their preset.', 'planify-wp-pricing-lite' ); ?></p>
                <?php else : ?>
                    <select id="pwpl_table_theme" name="pwpl_table[theme]" class="widefat">
                        <?php foreach ( $themes as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $table_theme, $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Applies to every plan within this table. Customize colors via assets/css/themes.css.', 'planify-wp-pricing-lite' ); ?></p>
                <?php endif; ?>
            </div>

            <div class="pwpl-field">
                <h3 style="margin:8px 0 6px;"><?php esc_html_e( 'CTA Button', 'planify-wp-pricing-lite' ); ?></h3>
                <?php $cta = get_post_meta( $post->ID, PWPL_Meta::CTA_CONFIG, true ); $cta = is_array( $cta ) ? $cta : []; ?>
                <div class="pwpl-field__row" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Width', 'planify-wp-pricing-lite' ); ?></span>
                        <select name="pwpl_table[ui][cta][width]">
                            <option value="auto" <?php selected( ($cta['width'] ?? 'full'), 'auto' ); ?>><?php esc_html_e( 'Auto', 'planify-wp-pricing-lite' ); ?></option>
                            <option value="full" <?php selected( ($cta['width'] ?? 'full'), 'full' ); ?>><?php esc_html_e( 'Full', 'planify-wp-pricing-lite' ); ?></option>
                        </select>
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Height (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="36" max="64" step="1" name="pwpl_table[ui][cta][height]" value="<?php echo esc_attr( $cta['height'] ?? 48 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Padding X (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="10" max="32" step="1" name="pwpl_table[ui][cta][pad_x]" value="<?php echo esc_attr( $cta['pad_x'] ?? 22 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Radius (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="0" max="999" step="1" name="pwpl_table[ui][cta][radius]" value="<?php echo esc_attr( $cta['radius'] ?? 12 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Border width (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="0" max="4" step="0.5" name="pwpl_table[ui][cta][border_width]" value="<?php echo esc_attr( $cta['border_width'] ?? 1.5 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="500" max="900" step="50" name="pwpl_table[ui][cta][weight]" value="<?php echo esc_attr( $cta['weight'] ?? 700 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Hover lift (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="0" max="3" step="1" name="pwpl_table[ui][cta][lift]" value="<?php echo esc_attr( $cta['lift'] ?? 1 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Min width (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="0" max="4000" step="1" name="pwpl_table[ui][cta][min_w]" value="<?php echo esc_attr( $cta['min_w'] ?? 0 ); ?>" />
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span><?php esc_html_e( 'Max width (px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="number" min="0" max="4000" step="1" name="pwpl_table[ui][cta][max_w]" value="<?php echo esc_attr( $cta['max_w'] ?? 0 ); ?>" />
                    </label>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:8px;">
                    <div>
                        <strong><?php esc_html_e( 'Normal', 'planify-wp-pricing-lite' ); ?></strong>
                        <div style="display:flex; gap:10px; margin-top:6px;">
                            <label><?php esc_html_e( 'BG', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][normal][bg]" value="<?php echo esc_attr( $cta['normal']['bg'] ?? '' ); ?>" /></label>
                            <label><?php esc_html_e( 'Text', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][normal][color]" value="<?php echo esc_attr( $cta['normal']['color'] ?? '' ); ?>" /></label>
                            <label><?php esc_html_e( 'Border', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][normal][border]" value="<?php echo esc_attr( $cta['normal']['border'] ?? '' ); ?>" /></label>
                        </div>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Hover', 'planify-wp-pricing-lite' ); ?></strong>
                        <div style="display:flex; gap:10px; margin-top:6px;">
                            <label><?php esc_html_e( 'BG', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][hover][bg]" value="<?php echo esc_attr( $cta['hover']['bg'] ?? '' ); ?>" /></label>
                            <label><?php esc_html_e( 'Text', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][hover][color]" value="<?php echo esc_attr( $cta['hover']['color'] ?? '' ); ?>" /></label>
                            <label><?php esc_html_e( 'Border', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][hover][border]" value="<?php echo esc_attr( $cta['hover']['border'] ?? '' ); ?>" /></label>
                        </div>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Focus', 'planify-wp-pricing-lite' ); ?></strong>
                        <div style="display:flex; gap:10px; margin-top:6px;">
                            <label><?php esc_html_e( 'Outline', 'planify-wp-pricing-lite' ); ?> <input type="color" name="pwpl_table[ui][cta][focus]" value="<?php echo esc_attr( $cta['focus'] ?? '' ); ?>" /></label>
                        </div>
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:8px;">
                    <div>
                        <strong><?php esc_html_e( 'Text', 'planify-wp-pricing-lite' ); ?></strong>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:6px; align-items:flex-end;">
                            <label style="display:flex; flex-direction:column; gap:6px; min-width:220px;">
                                <span><?php esc_html_e( 'Preset font', 'planify-wp-pricing-lite' ); ?></span>
                                <?php $preset = $cta['font']['preset'] ?? ''; ?>
                                <select name="pwpl_table[ui][cta][font][preset]">
                                    <option value="" <?php selected( $preset, '' ); ?>><?php esc_html_e( '— Select a preset —', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="system" <?php selected( $preset, 'system' ); ?>><?php esc_html_e( 'System UI (safe stack)', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="inter" <?php selected( $preset, 'inter' ); ?>>Inter</option>
                                    <option value="poppins" <?php selected( $preset, 'poppins' ); ?>>Poppins</option>
                                    <option value="open_sans" <?php selected( $preset, 'open_sans' ); ?>>Open Sans</option>
                                    <option value="montserrat" <?php selected( $preset, 'montserrat' ); ?>>Montserrat</option>
                                    <option value="lato" <?php selected( $preset, 'lato' ); ?>>Lato</option>
                                    <option value="space_grotesk" <?php selected( $preset, 'space_grotesk' ); ?>>Space Grotesk</option>
                                    <option value="rubik" <?php selected( $preset, 'rubik' ); ?>>Rubik</option>
                                </select>
                                <em class="description" style="opacity:.75;"><?php esc_html_e( 'Pick a preset font. (Fonts must be available/loaded by your theme/site.)', 'planify-wp-pricing-lite' ); ?></em>
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Font size (px)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="10" max="28" step="1" name="pwpl_table[ui][cta][font][size]" value="<?php echo esc_attr( $cta['font']['size'] ?? 0 ); ?>" />
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Transform', 'planify-wp-pricing-lite' ); ?></span>
                                <select name="pwpl_table[ui][cta][font][transform]">
                                    <option value="none" <?php selected( ($cta['font']['transform'] ?? 'none'), 'none' ); ?>><?php esc_html_e( 'None', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="uppercase" <?php selected( ($cta['font']['transform'] ?? 'none'), 'uppercase' ); ?>><?php esc_html_e( 'Uppercase', 'planify-wp-pricing-lite' ); ?></option>
                                </select>
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                                <?php $fw = (int) ( $cta['weight'] ?? 700 ); ?>
                                <select name="pwpl_table[ui][cta][font][weight]">
                                    <option value="400" <?php selected( $fw, 400 ); ?>><?php esc_html_e( 'Regular (400)', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="500" <?php selected( $fw, 500 ); ?>><?php esc_html_e( 'Medium (500)', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="600" <?php selected( $fw, 600 ); ?>><?php esc_html_e( 'Semibold (600)', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="700" <?php selected( $fw, 700 ); ?>><?php esc_html_e( 'Bold (700)', 'planify-wp-pricing-lite' ); ?></option>
                                    <option value="800" <?php selected( $fw, 800 ); ?>><?php esc_html_e( 'ExtraBold (800)', 'planify-wp-pricing-lite' ); ?></option>
                                </select>
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Letter spacing (em)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="text" name="pwpl_table[ui][cta][font][tracking]" value="<?php echo esc_attr( $cta['font']['tracking'] ?? '' ); ?>" placeholder="0.01" />
                                <em class="description" style="opacity:.75;"><?php esc_html_e( 'Enter a number; “em” is added automatically (e.g., 0.01).', 'planify-wp-pricing-lite' ); ?></em>
                            </label>
                        </div>
                    </div>
                </div>
                <p class="description"><?php esc_html_e( 'Leave colors empty to use the theme accent outline (fills on hover).', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <div class="pwpl-field" data-pwpl-card-settings>
                <h3 style="margin:14px 0 8px;"><?php esc_html_e( 'Plan Card', 'planify-wp-pricing-lite' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Fine-tune FireVPS plan cards. Leave any field blank to inherit the theme default.', 'planify-wp-pricing-lite' ); ?></p>
                <div style="display:flex; flex-direction:column; gap:18px; margin-top:12px;">
                    
                    <div>
                        <strong><?php esc_html_e( 'Layout', 'planify-wp-pricing-lite' ); ?></strong>
                        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-top:8px;">
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Card radius (px)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="0" max="24" step="1" name="pwpl_table[card][layout][radius]" value="<?php echo esc_attr( $card_layout['radius'] ?? '' ); ?>" data-pwpl-card-field="layout.radius" />
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px; width:140px;">
                                <span><?php esc_html_e( 'Border width (px)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="0" max="12" step="0.5" name="pwpl_table[card][layout][border_w]" value="<?php echo esc_attr( $card_layout['border_w'] ?? '' ); ?>" data-pwpl-card-field="layout.border_w" />
                            </label>
                            <div style="display:flex; flex-direction:column; gap:6px; min-width:260px;">
                                <span><?php esc_html_e( 'Padding (px)', 'planify-wp-pricing-lite' ); ?></span>
                                <div style="display:flex; flex-wrap:wrap; gap:8px;" data-pwpl-card-pad-group>
                                    <label style="display:flex; flex-direction:column; gap:4px; width:72px;">
                                        <span style="font-size:12px; text-transform:uppercase; letter-spacing:0.02em; opacity:.7;"><?php esc_html_e( 'Top', 'planify-wp-pricing-lite' ); ?></span>
                                        <input type="number" min="0" max="32" step="1" name="pwpl_table[card][layout][pad_t]" value="<?php echo esc_attr( $card_pad_values['pad_t'] ?? '' ); ?>" data-pwpl-card-field="layout.pad_t" data-pwpl-card-pad="pad_t" />
                                    </label>
                                    <label style="display:flex; flex-direction:column; gap:4px; width:72px;">
                                        <span style="font-size:12px; text-transform:uppercase; letter-spacing:0.02em; opacity:.7;"><?php esc_html_e( 'Right', 'planify-wp-pricing-lite' ); ?></span>
                                        <input type="number" min="0" max="32" step="1" name="pwpl_table[card][layout][pad_r]" value="<?php echo esc_attr( $card_pad_values['pad_r'] ?? '' ); ?>" data-pwpl-card-field="layout.pad_r" data-pwpl-card-pad="pad_r" />
                                    </label>
                                    <label style="display:flex; flex-direction:column; gap:4px; width:72px;">
                                        <span style="font-size:12px; text-transform:uppercase; letter-spacing:0.02em; opacity:.7;"><?php esc_html_e( 'Bottom', 'planify-wp-pricing-lite' ); ?></span>
                                        <input type="number" min="0" max="32" step="1" name="pwpl_table[card][layout][pad_b]" value="<?php echo esc_attr( $card_pad_values['pad_b'] ?? '' ); ?>" data-pwpl-card-field="layout.pad_b" data-pwpl-card-pad="pad_b" />
                                    </label>
                                    <label style="display:flex; flex-direction:column; gap:4px; width:72px;">
                                        <span style="font-size:12px; text-transform:uppercase; letter-spacing:0.02em; opacity:.7;"><?php esc_html_e( 'Left', 'planify-wp-pricing-lite' ); ?></span>
                                        <input type="number" min="0" max="32" step="1" name="pwpl_table[card][layout][pad_l]" value="<?php echo esc_attr( $card_pad_values['pad_l'] ?? '' ); ?>" data-pwpl-card-field="layout.pad_l" data-pwpl-card-pad="pad_l" />
                                    </label>
                                </div>
                                <label style="display:flex; align-items:center; gap:6px; margin-top:6px;">
                                    <input type="checkbox" data-pwpl-card-pad-lock <?php checked( $pad_lock ); ?> />
                                    <span><?php esc_html_e( 'Lock padding values together', 'planify-wp-pricing-lite' ); ?></span>
                                </label>
                            </div>
                            <label style="display:flex; flex-direction:column; gap:6px;">
                                <span><?php esc_html_e( 'Split layout', 'planify-wp-pricing-lite' ); ?></span>
                                <select name="pwpl_table[card][layout][split]" data-pwpl-card-field="layout.split">
                                    <option value="two_tone" <?php selected( $card_split, 'two_tone' ); ?>><?php esc_html_e( 'Two-tone (header & CTA vs. specs)', 'planify-wp-pricing-lite' ); ?></option>
                                </select>
                            </label>
                        </div>
                        <p class="description"><?php esc_html_e( 'Radius and padding apply to the entire card. Zero is allowed for flush edges.', 'planify-wp-pricing-lite' ); ?></p>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Colors & surfaces', 'planify-wp-pricing-lite' ); ?></strong>
                        <?php $swatches = ['#ffffff','#f7f7f8','#f5a623','#d9790b','#ffcd30','#1c1a16','#6c655c']; ?>
                        <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:8px; align-items:flex-end;">
                            <label style="display:flex; flex-direction:column; gap:6px; min-width:220px;">
                                <span><?php esc_html_e( 'Top section background (header + CTA + price)', 'planify-wp-pricing-lite' ); ?></span>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <?php $top_val = isset($card_colors['top_bg']) ? (string)$card_colors['top_bg'] : ''; $top_hex = ( $top_val && preg_match('/^#/',$top_val) ) ? $top_val : '#fff6e0'; ?>
                                    <input type="color" name="pwpl_table[card][colors][top_bg]" value="<?php echo esc_attr( $top_hex ); ?>" data-pwpl-card-field="colors.top_bg" />
                                    <button type="button" class="button-link" data-pwpl-clear="colors.top_bg"><?php esc_html_e( 'Reset', 'planify-wp-pricing-lite' ); ?></button>
                                </div>
                            </label>
                            <label style="display:flex; flex-direction:column; gap:6px; min-width:220px;">
                                <span><?php esc_html_e( 'Specs solid fallback', 'planify-wp-pricing-lite' ); ?></span>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <?php $specs_val = isset($card_colors['specs_bg']) ? (string)$card_colors['specs_bg'] : ''; $specs_hex = ( $specs_val && preg_match('/^#/',$specs_val) ) ? $specs_val : '#cf7a1a'; ?>
                                    <input type="color" name="pwpl_table[card][colors][specs_bg]" value="<?php echo esc_attr( $specs_hex ); ?>" data-pwpl-card-field="colors.specs_bg" />
                                    <button type="button" class="button-link" data-pwpl-clear="colors.specs_bg"><?php esc_html_e( 'Reset', 'planify-wp-pricing-lite' ); ?></button>
                                </div>
                                
                            </label>
                            
                        </div>
                        

                        <div style="display:flex; flex-direction:column; gap:6px; margin-top:4px;">
                            <span><?php esc_html_e( 'Specs gradient (optional)', 'planify-wp-pricing-lite' ); ?></span>
                            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;" data-pwpl-gradient>
                                <?php $g_type = isset($card_specs_grad['type']) ? $card_specs_grad['type'] : ''; ?>
                                <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                    <span><?php esc_html_e( 'Type', 'planify-wp-pricing-lite' ); ?></span>
                                    <select name="pwpl_table[card][colors][specs_grad][type]" data-pwpl-card-field="colors.specs_grad.type" data-pwpl-grad-type>
                                        <option value="" <?php selected( $g_type, '' ); ?>><?php esc_html_e( 'None', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="linear" <?php selected( $g_type, 'linear' ); ?>><?php esc_html_e( 'Linear', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="radial" <?php selected( $g_type, 'radial' ); ?>><?php esc_html_e( 'Radial', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="conic"  <?php selected( $g_type, 'conic' ); ?>><?php esc_html_e( 'Conic', 'planify-wp-pricing-lite' ); ?></option>
                                    </select>
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                    <span><?php esc_html_e( 'Start color', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $gstart = isset($card_specs_grad['start']) ? (string)$card_specs_grad['start'] : '#cf7a1a'; ?>
                                    <input type="color" name="pwpl_table[card][colors][specs_grad][start]" value="<?php echo esc_attr( preg_match('/^#/',$gstart) ? $gstart : '#cf7a1a' ); ?>" data-pwpl-card-field="colors.specs_grad.start" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                    <span><?php esc_html_e( 'End color', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $gend = isset($card_specs_grad['end']) ? (string)$card_specs_grad['end'] : '#8a3f00'; ?>
                                    <input type="color" name="pwpl_table[card][colors][specs_grad][end]" value="<?php echo esc_attr( preg_match('/^#/',$gend) ? $gend : '#8a3f00' ); ?>" data-pwpl-card-field="colors.specs_grad.end" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; width:160px;" data-pwpl-grad-angle>
                                    <span><?php esc_html_e( 'Angle (deg)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="0" max="360" step="1" name="pwpl_table[card][colors][specs_grad][angle]" value="<?php echo esc_attr( $card_specs_grad['angle'] ?? 180 ); ?>" data-pwpl-card-field="colors.specs_grad.angle" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; width:180px;">
                                    <span><?php esc_html_e( 'Start position (%)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="range" min="0" max="100" step="1" name="pwpl_table[card][colors][specs_grad][start_pos]" value="<?php echo esc_attr( $card_specs_grad['start_pos'] ?? 0 ); ?>" data-pwpl-card-field="colors.specs_grad.start_pos" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; width:180px;">
                                    <span><?php esc_html_e( 'End position (%)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="range" min="0" max="100" step="1" name="pwpl_table[card][colors][specs_grad][end_pos]" value="<?php echo esc_attr( $card_specs_grad['end_pos'] ?? 100 ); ?>" data-pwpl-card-field="colors.specs_grad.end_pos" />
                                </label>
                                <div style="display:flex; align-items:flex-end; gap:8px;">
                                    <button type="button" class="button-link" data-pwpl-grad-reset="colors.specs_grad."><?php esc_html_e( 'Reset gradient', 'planify-wp-pricing-lite' ); ?></button>
                                </div>
                            </div>
                        </div>

                        <?php $card_top_grad = isset( $card_colors['top_grad'] ) && is_array( $card_colors['top_grad'] ) ? $card_colors['top_grad'] : []; ?>
                        <div style="display:flex; flex-direction:column; gap:6px; margin-top:10px;">
                            <span><?php esc_html_e( 'Top gradient (optional)', 'planify-wp-pricing-lite' ); ?></span>
                            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;" data-pwpl-gradient-top>
                                <?php $g_type_c = isset($card_top_grad['type']) ? $card_top_grad['type'] : ''; ?>
                                <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                    <span><?php esc_html_e( 'Type', 'planify-wp-pricing-lite' ); ?></span>
                                    <select name="pwpl_table[card][colors][top_grad][type]" data-pwpl-card-field="colors.top_grad.type" data-pwpl-grad-type-top>
                                        <option value="" <?php selected( $g_type_c, '' ); ?>><?php esc_html_e( 'None', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="linear" <?php selected( $g_type_c, 'linear' ); ?>><?php esc_html_e( 'Linear', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="radial" <?php selected( $g_type_c, 'radial' ); ?>><?php esc_html_e( 'Radial', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="conic"  <?php selected( $g_type_c, 'conic' ); ?>><?php esc_html_e( 'Conic', 'planify-wp-pricing-lite' ); ?></option>
                                    </select>
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                    <span><?php esc_html_e( 'Start color', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $gstart_c = isset($card_top_grad['start']) ? (string)$card_top_grad['start'] : '#ffe8c4'; ?>
                                    <input type="color" name="pwpl_table[card][colors][top_grad][start]" value="<?php echo esc_attr( preg_match('/^#/',$gstart_c) ? $gstart_c : '#ffe8c4' ); ?>" data-pwpl-card-field="colors.top_grad.start" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                    <span><?php esc_html_e( 'End color', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $gend_c = isset($card_top_grad['end']) ? (string)$card_top_grad['end'] : '#ffd3b1'; ?>
                                    <input type="color" name="pwpl_table[card][colors][top_grad][end]" value="<?php echo esc_attr( preg_match('/^#/',$gend_c) ? $gend_c : '#ffd3b1' ); ?>" data-pwpl-card-field="colors.top_grad.end" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; width:160px;" data-pwpl-grad-angle-top>
                                    <span><?php esc_html_e( 'Angle (deg)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="0" max="360" step="1" name="pwpl_table[card][colors][top_grad][angle]" value="<?php echo esc_attr( $card_top_grad['angle'] ?? 180 ); ?>" data-pwpl-card-field="colors.top_grad.angle" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; width:180px;">
                                    <span><?php esc_html_e( 'Start position (%)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="range" min="0" max="100" step="1" name="pwpl_table[card][colors][top_grad][start_pos]" value="<?php echo esc_attr( $card_top_grad['start_pos'] ?? 0 ); ?>" data-pwpl-card-field="colors.top_grad.start_pos" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px; width:180px;">
                                    <span><?php esc_html_e( 'End position (%)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="range" min="0" max="100" step="1" name="pwpl_table[card][colors][top_grad][end_pos]" value="<?php echo esc_attr( $card_top_grad['end_pos'] ?? 100 ); ?>" data-pwpl-card-field="colors.top_grad.end_pos" />
                                </label>
                                <div style="display:flex; align-items:flex-end; gap:8px;">
                                    <button type="button" class="button-link" data-pwpl-grad-reset="colors.top_grad."><?php esc_html_e( 'Reset gradient', 'planify-wp-pricing-lite' ); ?></button>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;">
                            <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                <span><?php esc_html_e( 'Keyline color', 'planify-wp-pricing-lite' ); ?></span>
                                <?php $kline = isset($card_keyline['color']) ? (string)$card_keyline['color'] : ''; $khex = $kline && preg_match('/^#/',$kline) ? $kline : '#1c1a16'; ?>
                                <input type="color" name="pwpl_table[card][colors][keyline][color]" value="<?php echo esc_attr( $khex ); ?>" data-pwpl-card-field="colors.keyline.color" />
                            </label>
                            <label style="display:flex; flex-direction:column; gap:4px; width:140px;">
                                <span><?php esc_html_e( 'Keyline opacity (0–1)', 'planify-wp-pricing-lite' ); ?></span>
                                <input type="number" min="0" max="1" step="0.01" name="pwpl_table[card][colors][keyline][opacity]" value="<?php echo esc_attr( $card_keyline['opacity'] ?? '' ); ?>" data-pwpl-card-field="colors.keyline.opacity" />
                            </label>
                            <label style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
                                <span><?php esc_html_e( 'Border color', 'planify-wp-pricing-lite' ); ?></span>
                                <?php $bcol = isset($card_colors['border']) ? (string)$card_colors['border'] : ''; $bhex = $bcol && preg_match('/^#/',$bcol) ? $bcol : '#e5e7eb'; ?>
                                <input type="color" name="pwpl_table[card][colors][border]" value="<?php echo esc_attr( $bhex ); ?>" data-pwpl-card-field="colors.border" />
                            </label>
                        </div>
                        <p class="description"><?php esc_html_e( 'Provide a gradient start and end to override the specs background. If either is empty, the solid color fallback is used. Keylines are thin separators; leave blank to disable.', 'planify-wp-pricing-lite' ); ?></p>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Typography', 'planify-wp-pricing-lite' ); ?></strong>
                        <div style="display:flex; flex-direction:column; gap:10px; margin-top:8px;">
                            <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                                <span style="min-width:110px; font-weight:600;"><?php esc_html_e( 'Title', 'planify-wp-pricing-lite' ); ?></span>
                                <label style="display:flex; flex-direction:column; gap:4px;">
                                    <span><?php esc_html_e( 'Size (px)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="16" max="36" step="1" name="pwpl_table[card][typo][title][size]" value="<?php echo esc_attr( $card_typo_title['size'] ?? '' ); ?>" data-pwpl-card-field="typo.title.size" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px;">
                                    <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="600" max="800" step="50" name="pwpl_table[card][typo][title][weight]" value="<?php echo esc_attr( $card_typo_title['weight'] ?? '' ); ?>" data-pwpl-card-field="typo.title.weight" />
                                </label>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                                <span style="min-width:110px; font-weight:600;"><?php esc_html_e( 'Subtitle', 'planify-wp-pricing-lite' ); ?></span>
                                <label style="display:flex; flex-direction:column; gap:4px;">
                                    <span><?php esc_html_e( 'Size (px)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="12" max="18" step="1" name="pwpl_table[card][typo][subtitle][size]" value="<?php echo esc_attr( $card_typo_sub['size'] ?? '' ); ?>" data-pwpl-card-field="typo.subtitle.size" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px;">
                                    <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="400" max="600" step="50" name="pwpl_table[card][typo][subtitle][weight]" value="<?php echo esc_attr( $card_typo_sub['weight'] ?? '' ); ?>" data-pwpl-card-field="typo.subtitle.weight" />
                                </label>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                                <span style="min-width:110px; font-weight:600;"><?php esc_html_e( 'Price', 'planify-wp-pricing-lite' ); ?></span>
                                <label style="display:flex; flex-direction:column; gap:4px;">
                                    <span><?php esc_html_e( 'Size (px)', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="24" max="44" step="1" name="pwpl_table[card][typo][price][size]" value="<?php echo esc_attr( $card_typo_price['size'] ?? '' ); ?>" data-pwpl-card-field="typo.price.size" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:4px;">
                                    <span><?php esc_html_e( 'Weight', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="number" min="700" max="900" step="50" name="pwpl_table[card][typo][price][weight]" value="<?php echo esc_attr( $card_typo_price['weight'] ?? '' ); ?>" data-pwpl-card-field="typo.price.weight" />
                                </label>
                            </div>
                        </div>
                        <p class="description"><?php esc_html_e( 'Size values are pixels. Weights accept 50-point steps (e.g., 700). Leave blank to inherit the theme stack.', 'planify-wp-pricing-lite' ); ?></p>
                    </div>
                </div>
            </div>
            <script>
                (function(){
                    const section = document.querySelector('[data-pwpl-card-settings]');
                    if (!section) { return; }
                    const padInputs = section.querySelectorAll('[data-pwpl-card-pad]');
                    const padLock = section.querySelector('[data-pwpl-card-pad-lock]');
                    const syncOthers = function(value, source){
                        padInputs.forEach(function(input){
                            if (source && input === source) {
                                return;
                            }
                            input.value = value;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    };
                    padInputs.forEach(function(input){
                        input.addEventListener('input', function(){
                            if (!padLock || !padLock.checked) { return; }
                            syncOthers(this.value, this);
                        });
                    });
                    if (padLock) {
                        padLock.addEventListener('change', function(){
                            if (!padLock.checked) { return; }
                            const first = padInputs.length ? padInputs[0].value : '';
                            syncOthers(first, padInputs.length ? padInputs[0] : null);
                        });
                    }
                    const enableField = function(path){
                        const fields = section.querySelectorAll('[data-pwpl-card-field="' + path + '"]');
                        fields.forEach(function(field){ field.disabled = false; field.removeAttribute('data-pwpl-cleared'); });
                    };
                    const disableField = function(path){
                        const fields = section.querySelectorAll('[data-pwpl-card-field="' + path + '"]');
                        fields.forEach(function(field){ field.disabled = true; field.setAttribute('data-pwpl-cleared','1'); });
                    };
                    const setFieldValue = function(path, value){
                        const fields = section.querySelectorAll('[data-pwpl-card-field="' + path + '"]');
                        if (!fields || !fields.length) { return; }
                        const resolved = (value === null || typeof value === 'undefined') ? '' : value;
                        fields.forEach(function(field){
                            field.value = resolved;
                            // Trigger both input and change to cover inputs and selects
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                            field.disabled = false;
                            field.removeAttribute('data-pwpl-cleared');
                        });
                    };
                    const fillValues = function(obj, prefix){
                        Object.keys(obj).forEach(function(key){
                            const next = obj[key];
                            const path = prefix ? prefix + '.' + key : key;
                            if (next && typeof next === 'object' && !Array.isArray(next)) {
                                fillValues(next, path);
                                return;
                            }
                            setFieldValue(path, next);
                        });
                    };
                    // No presets — manual only

                    // Swatch buttons: set on target path
                    section.querySelectorAll('[data-pwpl-swatch]').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            const hex = this.getAttribute('data-pwpl-swatch');
                            const path = this.getAttribute('data-pwpl-target');
                            if (!path) { return; }
                            setFieldValue(path, hex);
                        });
                    });
                    // Clear buttons (reset to empty -> default theme)
                    section.querySelectorAll('[data-pwpl-clear]').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            const path = this.getAttribute('data-pwpl-clear');
                            if (!path) { return; }
                            setFieldValue(path, '');
                            disableField(path);
                        });
                    });
                    // Re-enable a field whenever the user picks a color
                    section.querySelectorAll('input[type="color"][data-pwpl-card-field]').forEach(function(input){
                        input.addEventListener('input', function(){
                            const path = this.getAttribute('data-pwpl-card-field');
                            if (path) { enableField(path); }
                        });
                    });
                    // Gradient UI toggles: show angle only for linear
                    const gradWrap = section.querySelector('[data-pwpl-gradient]');
                    const wireGradSection = function(wrapperSel, typeSelSel, angleWrapSel, prefix){
                        const wrap = section.querySelector(wrapperSel);
                        if (!wrap) return;
                        const typeSel = wrap.querySelector(typeSelSel);
                        const angleWrap = wrap.querySelector(angleWrapSel);
                        const sync = function(){
                            if (!typeSel) return;
                            const t = typeSel.value;
                            if (angleWrap) angleWrap.style.display = (t === 'linear' || t === '') ? '' : 'none';
                            const gradFields = wrap.querySelectorAll('[data-pwpl-card-field^="' + prefix + '"]');
                            gradFields.forEach(function(f){
                                if (f === typeSel) return;
                                if (!t) {
                                    f.disabled = true;
                                    f.setAttribute('data-pwpl-cleared','1');
                                    
                                } else {
                                    f.disabled = false;
                                    f.removeAttribute('data-pwpl-cleared');
                                    
                                }
                            });
                        };
                        if (typeSel) { typeSel.addEventListener('change', sync); sync(); }
                    };
                    wireGradSection('[data-pwpl-gradient]', '[data-pwpl-grad-type]', '[data-pwpl-grad-angle]', 'colors.specs_grad.');
                    wireGradSection('[data-pwpl-gradient-top]', '[data-pwpl-grad-type-top]', '[data-pwpl-grad-angle-top]', 'colors.top_grad.');

                    // Gradient preset apply
                    // Reset gradient helpers (clear fields and switch Type to None)
                    section.querySelectorAll('[data-pwpl-grad-reset]').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            const prefix = this.getAttribute('data-pwpl-grad-reset');
                            if (!prefix) return;
                            setFieldValue(prefix + 'type', '');
                            setFieldValue(prefix + 'start', '');
                            setFieldValue(prefix + 'end', '');
                            setFieldValue(prefix + 'angle', '');
                            setFieldValue(prefix + 'start_pos', '');
                            setFieldValue(prefix + 'end_pos', '');
                            // Trigger UI sync for the right wrapper
                            let wrapSel = '[data-pwpl-gradient]';
                            if (prefix.indexOf('top_grad') !== -1) wrapSel = '[data-pwpl-gradient-top]';
                            const wrap = section.querySelector(wrapSel);
                            const typeSel = wrap ? wrap.querySelector('[data-pwpl-grad-type], [data-pwpl-grad-type-top]') : null;
                            if (typeSel) { typeSel.dispatchEvent(new Event('change', { bubbles: true })); }
                        });
                    });
                })();
            </script>
            <div class="pwpl-field">
                <label style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" name="pwpl_table[ui][trust_trio]" value="1" <?php checked( $trust_trio, 1 ); ?> />
                    <strong><?php esc_html_e( 'Show trust row under CTA (Money‑back, Uptime, Support)', 'planify-wp-pricing-lite' ); ?></strong>
                </label>
                <p class="description"><?php esc_html_e( 'Displays a concise assurance row beneath the inline CTA.', 'planify-wp-pricing-lite' ); ?></p>
                <?php $trust_items = get_post_meta( $post->ID, PWPL_Meta::TRUST_ITEMS, true ); $trust_items = is_array( $trust_items ) ? implode("\n", $trust_items) : ""; ?>
                <label for="pwpl_trust_items" style="display:block; margin-top:8px;"><strong><?php esc_html_e( 'Trust items (one per line)', 'planify-wp-pricing-lite' ); ?></strong></label>
                <textarea id="pwpl_trust_items" name="pwpl_table[ui][trust_items]" class="widefat" rows="3" placeholder="<?php esc_attr_e( "7-day money-back\n99.9% uptime SLA\n24/7 support", 'planify-wp-pricing-lite' ); ?>"><?php echo esc_textarea( $trust_items ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Each line becomes a bullet. Keep 2–3 items for best results.', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <div class="pwpl-field">
                <label style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" name="pwpl_table[ui][sticky_cta]" value="1" <?php checked( $sticky_cta, 1 ); ?> />
                    <strong><?php esc_html_e( 'Enable sticky mobile summary bar', 'planify-wp-pricing-lite' ); ?></strong>
                </label>
                <p class="description"><?php esc_html_e( 'Shows plan title, price, and CTA when a plan CTA is off‑screen (mobile).', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <div class="pwpl-field">
                <label><strong><?php esc_html_e( 'Specifications interactions (hover effects)', 'planify-wp-pricing-lite' ); ?></strong></label>
                <div class="pwpl-field__row" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center; margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="pwpl_table[ui][specs_anim][flags][]" value="row" <?php checked( in_array( 'row', $anim_flags, true ) ); ?> />
                        <span><?php esc_html_e( 'Row highlight', 'planify-wp-pricing-lite' ); ?></span>
                        <span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Adds a soft background tint and thin keyline on row hover; no layout shift.', 'planify-wp-pricing-lite' ); ?>" style="opacity:.6"></span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="pwpl_table[ui][specs_anim][flags][]" value="icon" <?php checked( in_array( 'icon', $anim_flags, true ) ); ?> />
                        <span><?php esc_html_e( 'Icon micro‑motion', 'planify-wp-pricing-lite' ); ?></span>
                        <span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Brightens the icon tile slightly and scales the glyph (~1–3%) on hover.', 'planify-wp-pricing-lite' ); ?>" style="opacity:.6"></span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="pwpl_table[ui][specs_anim][flags][]" value="divider" <?php checked( in_array( 'divider', $anim_flags, true ) ); ?> />
                        <span><?php esc_html_e( 'Divider sweep', 'planify-wp-pricing-lite' ); ?></span>
                        <span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Animates a thin underline from left to right on row hover. Works with any spec style.', 'planify-wp-pricing-lite' ); ?>" style="opacity:.6"></span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="pwpl_table[ui][specs_anim][flags][]" value="chip" <?php checked( in_array( 'chip', $anim_flags, true ) ); ?> />
                        <span><?php esc_html_e( 'Chip emphasis', 'planify-wp-pricing-lite' ); ?></span>
                        <span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Emphasizes the value with a pill-like highlight on hover — even when your spec style is not Chips.', 'planify-wp-pricing-lite' ); ?>" style="opacity:.6"></span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="pwpl_table[ui][specs_anim][flags][]" value="stagger" <?php checked( in_array( 'stagger', $anim_flags, true ) ); ?> />
                        <span><?php esc_html_e( 'Stagger on card hover', 'planify-wp-pricing-lite' ); ?></span>
                        <span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Rows animate in a gentle top‑to‑bottom sequence when hovering a card (desktop, motion‑friendly only).', 'planify-wp-pricing-lite' ); ?>" style="opacity:.6"></span>
                    </label>
                </div>
                <div class="pwpl-field__row" style="display:flex; gap:12px; align-items:center; margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <span><?php esc_html_e( 'Intensity', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="range" min="0" max="100" step="1" name="pwpl_table[ui][specs_anim][intensity]" value="<?php echo esc_attr( $anim_intensity ); ?>" />
                        <output><?php echo esc_html( $anim_intensity ); ?></output>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="pwpl_table[ui][specs_anim][mobile]" value="1" <?php checked( $anim_mobile, 1 ); ?> />
                        <span><?php esc_html_e( 'Enable on touch devices', 'planify-wp-pricing-lite' ); ?></span>
                        <span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'When enabled, hover effects also apply on touch devices (tap highlights). Off by default.', 'planify-wp-pricing-lite' ); ?>" style="opacity:.6"></span>
                    </label>
                </div>
                <p class="description"><?php esc_html_e( 'Choose one or more interactions. Intensity controls subtlety/speed. All effects work with any spec style and respect reduced motion.', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <div class="pwpl-field">
                <label for="pwpl_specs_style"><strong><?php esc_html_e( 'Specifications list style', 'planify-wp-pricing-lite' ); ?></strong></label>
                <select id="pwpl_specs_style" name="pwpl_table[ui][specs_style]" class="widefat">
                    <?php
                    $options = [
                        'default'   => __( 'Default (theme)', 'planify-wp-pricing-lite' ),
                        'flat'      => __( 'Flat rows (clean)', 'planify-wp-pricing-lite' ),
                        'segmented' => __( 'Segmented with dividers', 'planify-wp-pricing-lite' ),
                        'chips'     => __( 'Value chips (compact)', 'planify-wp-pricing-lite' ),
                    ];
                    foreach ( $options as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $specs_style, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Choose how plan specifications are displayed. Changes affect this table only.', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <div class="pwpl-field">
                <label>
                    <input type="checkbox" name="pwpl_table[ui][tabs_glass]" value="1" <?php checked( $tabs_glass, 1 ); ?> />
                    <strong><?php esc_html_e( 'Enable glass tabs (iOS liquid glass)', 'planify-wp-pricing-lite' ); ?></strong>
                </label>
                <p class="description"><?php esc_html_e( 'Adds a translucent, depthy look to tab pills using backdrop blur when available.', 'planify-wp-pricing-lite' ); ?></p>
                <div class="pwpl-field__row" style="display:flex; gap:12px; align-items:center; margin-top:8px; flex-wrap: wrap;">
                    <?php $glass_tint = get_post_meta( $post->ID, PWPL_Meta::TABS_GLASS_TINT, true ); ?>
                    <label style="display:flex; align-items:center; gap:8px;">
                        <span><?php esc_html_e( 'Tint', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="color" name="pwpl_table[ui][tabs_glass_tint]" value="<?php echo esc_attr( $glass_tint ?: '#a6c8ff' ); ?>" />
                    </label>
                    <?php $glass_intensity = (int) get_post_meta( $post->ID, PWPL_Meta::TABS_GLASS_INTENSITY, true ); if ( $glass_intensity <= 0 ) $glass_intensity = 60; ?>
                    <label style="display:flex; align-items:center; gap:8px;">
                        <span><?php esc_html_e( 'Intensity', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="range" min="10" max="100" step="1" name="pwpl_table[ui][tabs_glass_intensity]" value="<?php echo esc_attr( $glass_intensity ); ?>" />
                    </label>
                    <?php $glass_frost = (int) get_post_meta( $post->ID, PWPL_Meta::TABS_GLASS_FROST, true ); if ( $glass_frost < 0 ) $glass_frost = 6; ?>
                    <label style="display:flex; align-items:center; gap:8px;">
                        <span><?php esc_html_e( 'Frost (blur px)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="range" min="0" max="16" step="1" name="pwpl_table[ui][tabs_glass_frost]" value="<?php echo esc_attr( $glass_frost ?: 6 ); ?>" />
                    </label>
                </div>
            </div>

            <?php $cards_glass = (int) get_post_meta( $post->ID, PWPL_Meta::CARDS_GLASS, true ); ?>
            <div class="pwpl-field">
                <label>
                    <input type="checkbox" name="pwpl_table[ui][cards_glass]" value="1" <?php checked( $cards_glass, 1 ); ?> />
                    <strong><?php esc_html_e( 'Enable glass plan cards', 'planify-wp-pricing-lite' ); ?></strong>
                </label>
                <p class="description"><?php esc_html_e( 'Applies a frosted glass treatment to each plan card. Uses the same Tint and Frost values.', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <?php foreach ( $dimension_map as $key => $config ) :
                $enabled = in_array( $key, $dimensions, true );
                ?>
                <div class="pwpl-dimension" data-dimension="<?php echo esc_attr( $key ); ?>">
                    <label>
                        <input type="checkbox" name="pwpl_table[dimensions][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $enabled ); ?> />
                        <strong><?php echo esc_html( $config['label'] ); ?></strong>
                    </label>
                    <div class="pwpl-dimension-options" <?php if ( ! $enabled ) echo 'style="display:none"'; ?>>
                        <?php if ( ! empty( $config['values'] ) ) : ?>
                            <?php foreach ( $config['values'] as $item ) :
                                $slug  = $item['slug'];
                                $label = $item['label'];
                                $name  = sprintf( 'pwpl_table[allowed][%s][]', $key );
                                ?>
                                <label class="pwpl-dimension-option">
                                    <input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $slug ); ?>" data-pwpl-dimension-item-label="<?php echo esc_attr( $label ); ?>" <?php checked( in_array( $slug, $config['allowed'], true ) ); ?> />
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No values defined. Add options in Settings → Dimensions & Variants.', 'planify-wp-pricing-lite' ); ?></p>
                        <?php endif; ?>
                        <?php if ( 'platform' === $key ) :
                            $order_value = implode( ',', array_map( 'sanitize_title', (array) $config['allowed'] ) );
                            $default_platform_slug = $config['allowed'][0] ?? '';
                            $default_platform_label = $this->find_dimension_item_label( $platforms, $default_platform_slug );
                            ?>
                            <div class="pwpl-platform-order" data-pwpl-platform-order data-label-move-up="<?php esc_attr_e( 'Move up', 'planify-wp-pricing-lite' ); ?>" data-label-move-down="<?php esc_attr_e( 'Move down', 'planify-wp-pricing-lite' ); ?>">
                                <input type="hidden" name="pwpl_table[allowed_order][platform]" value="<?php echo esc_attr( $order_value ); ?>" data-pwpl-order-input />
                                <div class="pwpl-platform-order__header">
                                    <strong><?php esc_html_e( 'Tab order', 'planify-wp-pricing-lite' ); ?></strong>
                                    <span class="pwpl-platform-order__default" data-pwpl-order-default data-empty-label="<?php esc_attr_e( 'None selected', 'planify-wp-pricing-lite' ); ?>"><?php echo $default_platform_label ? esc_html( $default_platform_label ) : esc_html__( 'None selected', 'planify-wp-pricing-lite' ); ?></span>
                                </div>
                                <ol class="pwpl-platform-order__list" data-pwpl-order-list>
                                    <?php foreach ( (array) $config['allowed'] as $ordered_slug ) :
                                        $ordered_label = $this->find_dimension_item_label( $platforms, $ordered_slug );
                                        if ( ! $ordered_label ) {
                                            continue;
                                        }
                                        ?>
                                        <li data-value="<?php echo esc_attr( $ordered_slug ); ?>">
                                            <span><?php echo esc_html( $ordered_label ); ?></span>
                                            <div class="pwpl-order-actions">
                                                <button type="button" class="button button-small" data-pwpl-move="up" aria-label="<?php esc_attr_e( 'Move up', 'planify-wp-pricing-lite' ); ?>">&#8593;</button>
                                                <button type="button" class="button button-small" data-pwpl-move="down" aria-label="<?php esc_attr_e( 'Move down', 'planify-wp-pricing-lite' ); ?>">&#8595;</button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                                <p class="description"><?php esc_html_e( 'The first platform determines the initially active tab.', 'planify-wp-pricing-lite' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function render_table_badges_meta( $post ) {
        $options = $this->get_dimension_options( $post->ID );
        $badges  = get_post_meta( $post->ID, PWPL_Meta::TABLE_BADGES, true );
        if ( ! is_array( $badges ) ) {
            $badges = [];
        }

        $shadow = isset( $badges['shadow'] ) ? (int) $badges['shadow'] : 0;
        $shadow = max( 0, min( $shadow, 60 ) );

        $groups = [
            'period'   => __( 'Period promotions', 'planify-wp-pricing-lite' ),
            'location' => __( 'Location promotions', 'planify-wp-pricing-lite' ),
            'platform' => __( 'Platform promotions', 'planify-wp-pricing-lite' ),
        ];

        ?>
        <div class="pwpl-meta pwpl-meta--badges">
            <p class="description"><?php esc_html_e( 'Highlight seasonal or location-based offers. Badges appear on matching plans according to priority.', 'planify-wp-pricing-lite' ); ?></p>
            <div class="pwpl-field pwpl-badge-shadow">
                <label for="pwpl_table_badges_shadow"><strong><?php esc_html_e( 'Badge shadow intensity', 'planify-wp-pricing-lite' ); ?></strong></label>
                <div class="pwpl-badge-shadow__controls">
                    <input type="range" id="pwpl_table_badges_shadow" name="pwpl_table_badges[shadow]" min="0" max="60" step="1" value="<?php echo esc_attr( $shadow ); ?>" data-pwpl-range data-pwpl-range-output="#pwpl_table_badges_shadow_value" data-pwpl-range-unit="" />
                    <output id="pwpl_table_badges_shadow_value"><?php echo esc_html( $shadow ); ?></output>
                </div>
                <p class="description"><?php esc_html_e( '0 disables the glow. Increase the value to add more halo around badges—perfect for darker themes.', 'planify-wp-pricing-lite' ); ?></p>
            </div>
            <?php foreach ( $groups as $dimension => $label ) :
                $rows = isset( $badges[ $dimension ] ) && is_array( $badges[ $dimension ] ) ? $badges[ $dimension ] : [];
                $this->render_badge_section( 'table', $dimension, $label, $rows, $options[ $dimension ] ?? [] );
            endforeach; ?>
            <?php $this->render_badge_priority_controls( 'table', $badges['priority'] ?? [] ); ?>
        </div>
        <?php
        $size_meta   = get_post_meta( $post->ID, PWPL_Meta::TABLE_SIZE, true );
        $meta_helper = new PWPL_Meta();
        $size_values = $meta_helper->sanitize_table_size( is_array( $size_meta ) ? $size_meta : [] );
        $breakpoint_meta = get_post_meta( $post->ID, PWPL_Meta::TABLE_BREAKPOINTS, true );
        $breakpoint_values = $meta_helper->sanitize_table_breakpoints( is_array( $breakpoint_meta ) ? $breakpoint_meta : [] );
        ?>
        <?php
    }

    public function render_plan_meta( $post ) {
        wp_nonce_field( 'pwpl_save_plan_' . $post->ID, 'pwpl_plan_nonce' );

        $table_id = (int) get_post_meta( $post->ID, PWPL_Meta::PLAN_TABLE_ID, true );
        $specs    = get_post_meta( $post->ID, PWPL_Meta::PLAN_SPECS, true );
        $variants = get_post_meta( $post->ID, PWPL_Meta::PLAN_VARIANTS, true );
        $featured = (bool) get_post_meta( $post->ID, PWPL_Meta::PLAN_FEATURED, true );

        if ( ! is_array( $specs ) ) {
            $specs = [];
        }
        if ( ! is_array( $variants ) ) {
            $variants = [];
        }

        $spec_count = count( $specs );
        if ( $spec_count === 0 ) {
            $specs = [ [] ];
        }

        $variant_count = count( $variants );
        if ( $variant_count === 0 ) {
            $variants = [ [] ];
        }

        $tables = get_posts( [
            'post_type'      => 'pwpl_table',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $settings = $this->settings();
        $platforms = (array) $settings->get( 'platforms' );
        $periods   = (array) $settings->get( 'periods' );
        $locations = (array) $settings->get( 'locations' );
        ?>
        <div class="pwpl-meta pwpl-meta--plan">
            <div class="pwpl-field">
                <label for="pwpl_plan_table_id"><strong><?php esc_html_e( 'Assign to Pricing Table', 'planify-wp-pricing-lite' ); ?></strong></label>
                <select id="pwpl_plan_table_id" name="pwpl_plan[table_id]" class="widefat">
                    <option value="0"><?php esc_html_e( '— Select a Pricing Table —', 'planify-wp-pricing-lite' ); ?></option>
                    <?php foreach ( $tables as $table ) : ?>
                        <option value="<?php echo esc_attr( $table->ID ); ?>" <?php selected( $table_id, $table->ID ); ?>><?php echo esc_html( $table->post_title ?: sprintf( __( 'Table #%d', 'planify-wp-pricing-lite' ), $table->ID ) ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ( empty( $tables ) ) : ?>
                    <p class="description"><?php esc_html_e( 'No pricing tables found yet. Create a table first, then assign plans to it.', 'planify-wp-pricing-lite' ); ?></p>
                <?php endif; ?>
            </div>

            <?php $plan_subtitle = get_post_meta( $post->ID, '_pwpl_plan_subtitle', true ); $plan_subtitle = is_string( $plan_subtitle ) ? $plan_subtitle : ''; ?>
            <div class="pwpl-field">
                <label for="pwpl_plan_subtitle"><strong><?php esc_html_e( 'Plan subtitle', 'planify-wp-pricing-lite' ); ?></strong></label>
                <input type="text" id="pwpl_plan_subtitle" name="pwpl_plan[subtitle]" class="widefat" value="<?php echo esc_attr( $plan_subtitle ); ?>" placeholder="<?php esc_attr_e( 'e.g. Basic VPS to start your hosting easily', 'planify-wp-pricing-lite' ); ?>" />
                <p class="description"><?php esc_html_e( 'Appears under the plan title in the CTA section. Falls back to the plan excerpt if empty.', 'planify-wp-pricing-lite' ); ?></p>
            </div>

            <div class="pwpl-field">
                <label>
                    <input type="checkbox" name="pwpl_plan[featured]" value="1" <?php checked( $featured ); ?> />
                    <strong><?php esc_html_e( 'Mark as featured plan', 'planify-wp-pricing-lite' ); ?></strong>
                </label>
                <p class="description"><?php esc_html_e( 'Use this flag in your theme to highlight a primary plan.', 'planify-wp-pricing-lite' ); ?></p>
            </div>

            <div class="pwpl-field">
                <label for="pwpl_plan_badge_shadow"><strong><?php esc_html_e( 'Badge glow (override)', 'planify-wp-pricing-lite' ); ?></strong></label>
                <?php $badge_shadow = (int) get_post_meta( $post->ID, PWPL_Meta::PLAN_BADGE_SHADOW, true ); ?>
                <div>
                    <input type="range" id="pwpl_plan_badge_shadow" name="pwpl_plan[badge_shadow]" min="0" max="60" step="1" value="<?php echo esc_attr( $badge_shadow ); ?>" data-pwpl-range data-pwpl-range-output="#pwpl_plan_badge_shadow_value" data-pwpl-range-unit="" />
                    <output id="pwpl_plan_badge_shadow_value"><?php echo esc_html( $badge_shadow ); ?></output>
                </div>
                <p class="description"><?php esc_html_e( 'Leave 0 to inherit from table. Increase to intensify the badge glow for this plan only.', 'planify-wp-pricing-lite' ); ?></p>
            </div>

            <div class="pwpl-field">
                <label><strong><?php esc_html_e( 'Specifications', 'planify-wp-pricing-lite' ); ?></strong></label>
                <p class="description"><?php esc_html_e( 'Add spec rows like CPU, RAM, Bandwidth. Leave blank rows to remove.', 'planify-wp-pricing-lite' ); ?></p>
                <table class="widefat pwpl-repeatable" data-pwpl-repeatable="specs" data-template="pwpl-row-specs" data-next-index="<?php echo esc_attr( max( $spec_count, count( $specs ) ) ); ?>">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Label', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Value', 'planify-wp-pricing-lite' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $specs as $index => $row ) :
                            $label = $row['label'] ?? '';
                            $value = $row['value'] ?? '';
                            ?>
                            <tr>
                                <td><input type="text" name="pwpl_plan[specs][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="widefat" /></td>
                                <td><input type="text" name="pwpl_plan[specs][<?php echo esc_attr( $index ); ?>][value]" value="<?php echo esc_attr( $value ); ?>" class="widefat" /></td>
                                <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-secondary pwpl-add-row" data-target="specs"><?php esc_html_e( 'Add Specification', 'planify-wp-pricing-lite' ); ?></button></p>
            </div>

            <div class="pwpl-field">
                <label><strong><?php esc_html_e( 'Price Variants', 'planify-wp-pricing-lite' ); ?></strong></label>
                <p class="description"><?php esc_html_e( 'Define price combinations per Platform / Period / Location. Leave optional dimensions blank if not used.', 'planify-wp-pricing-lite' ); ?></p>
                <table class="widefat pwpl-repeatable" data-pwpl-repeatable="variants" data-template="pwpl-row-variants" data-next-index="<?php echo esc_attr( max( $variant_count, count( $variants ) ) ); ?>">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Platform', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Period', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Location', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Sale Price', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'CTA Label', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'CTA URL', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Target', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Rel', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Unavailable', 'planify-wp-pricing-lite' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $variants as $index => $row ) :
                            $platform = $row['platform'] ?? '';
                            $period   = $row['period'] ?? '';
                            $location = $row['location'] ?? '';
                            $price    = $row['price'] ?? '';
                            $sale     = $row['sale_price'] ?? '';
                            $cta_label = $row['cta_label'] ?? '';
                            $cta_url   = $row['cta_url'] ?? '';
                            $target    = $row['target'] ?? '';
                            $rel       = $row['rel'] ?? '';
                            $unavail   = ! empty( $row['unavailable'] );
                            ?>
                            <tr>
                                <td><?php $this->render_select( "pwpl_plan[variants][{$index}][platform]", $platforms, $platform, __( 'Any', 'planify-wp-pricing-lite' ) ); ?></td>
                                <td><?php $this->render_select( "pwpl_plan[variants][{$index}][period]", $periods, $period, __( 'Any', 'planify-wp-pricing-lite' ) ); ?></td>
                                <td><?php $this->render_select( "pwpl_plan[variants][{$index}][location]", $locations, $location, __( 'Any', 'planify-wp-pricing-lite' ) ); ?></td>
                                <td><input type="text" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" class="widefat" /></td>
                                <td><input type="text" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][sale_price]" value="<?php echo esc_attr( $sale ); ?>" class="widefat" /></td>
                                <td><input type="text" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][cta_label]" value="<?php echo esc_attr( $cta_label ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Buy Now', 'planify-wp-pricing-lite' ); ?>" /></td>
                                <td><input type="url" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][cta_url]" value="<?php echo esc_attr( $cta_url ); ?>" class="widefat" placeholder="https://" /></td>
                                <td>
                                    <select name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][target]" class="widefat">
                                        <option value="" <?php selected( $target, '' ); ?>><?php esc_html_e( 'Default', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="_self" <?php selected( $target, '_self' ); ?>><?php esc_html_e( 'Same tab', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="_blank" <?php selected( $target, '_blank' ); ?>><?php esc_html_e( 'New tab', 'planify-wp-pricing-lite' ); ?></option>
                                    </select>
                                </td>
                                <td><input type="text" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][rel]" value="<?php echo esc_attr( $rel ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'nofollow noopener', 'planify-wp-pricing-lite' ); ?>" /></td>
                                <td style="text-align:center"><input type="checkbox" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][unavailable]" value="1" <?php checked( $unavail ); ?> /></td>
                                <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-secondary pwpl-add-row" data-target="variants"><?php esc_html_e( 'Add Variant', 'planify-wp-pricing-lite' ); ?></button></p>
            </div>
        </div>

        <script type="text/html" id="tmpl-pwpl-row-specs">
            <tr>
                <td><input type="text" name="pwpl_plan[specs][{{data.index}}][label]" class="widefat" /></td>
                <td><input type="text" name="pwpl_plan[specs][{{data.index}}][value]" class="widefat" /></td>
                <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
            </tr>
        </script>
        <script type="text/html" id="tmpl-pwpl-row-variants">
            <tr>
                <td><?php $this->render_select( 'pwpl_plan[variants][{{data.index}}][platform]', $platforms, '', __( 'Any', 'planify-wp-pricing-lite' ), true ); ?></td>
                <td><?php $this->render_select( 'pwpl_plan[variants][{{data.index}}][period]', $periods, '', __( 'Any', 'planify-wp-pricing-lite' ), true ); ?></td>
                <td><?php $this->render_select( 'pwpl_plan[variants][{{data.index}}][location]', $locations, '', __( 'Any', 'planify-wp-pricing-lite' ), true ); ?></td>
                <td><input type="text" name="pwpl_plan[variants][{{data.index}}][price]" class="widefat" /></td>
                <td><input type="text" name="pwpl_plan[variants][{{data.index}}][sale_price]" class="widefat" /></td>
                <td><input type="text" name="pwpl_plan[variants][{{data.index}}][cta_label]" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Buy Now', 'planify-wp-pricing-lite' ); ?>" /></td>
                <td><input type="url" name="pwpl_plan[variants][{{data.index}}][cta_url]" class="widefat" placeholder="https://" /></td>
                <td>
                    <select name="pwpl_plan[variants][{{data.index}}][target]" class="widefat">
                        <option value=""><?php esc_html_e( 'Default', 'planify-wp-pricing-lite' ); ?></option>
                        <option value="_self"><?php esc_html_e( 'Same tab', 'planify-wp-pricing-lite' ); ?></option>
                        <option value="_blank"><?php esc_html_e( 'New tab', 'planify-wp-pricing-lite' ); ?></option>
                    </select>
                </td>
                <td><input type="text" name="pwpl_plan[variants][{{data.index}}][rel]" class="widefat" placeholder="<?php esc_attr_e( 'nofollow noopener', 'planify-wp-pricing-lite' ); ?>" /></td>
                <td style="text-align:center"><input type="checkbox" name="pwpl_plan[variants][{{data.index}}][unavailable]" value="1" /></td>
                <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
            </tr>
        </script>
        <?php
    }

    public function render_plan_badges_meta( $post ) {
        $assigned_table = (int) get_post_meta( $post->ID, PWPL_Meta::PLAN_TABLE_ID, true );
        $options        = $assigned_table ? $this->get_dimension_options( $assigned_table ) : $this->get_dimension_options( 0 );

        $override = get_post_meta( $post->ID, PWPL_Meta::PLAN_BADGES_OVERRIDE, true );
        if ( ! is_array( $override ) ) {
            $override = [];
        }
        $enabled = ! empty( array_filter( $override ) );

        $groups = [
            'period'   => __( 'Period promotions', 'planify-wp-pricing-lite' ),
            'location' => __( 'Location promotions', 'planify-wp-pricing-lite' ),
            'platform' => __( 'Platform promotions', 'planify-wp-pricing-lite' ),
        ];

        ?>
        <div class="pwpl-meta pwpl-meta--plan-badges">
            <p>
                <label>
                    <input type="checkbox" id="pwpl_plan_badges_override_enabled" name="pwpl_plan_badges_override[enabled]" value="1" <?php checked( $enabled ); ?> />
                    <?php esc_html_e( 'Override table promotions for this plan', 'planify-wp-pricing-lite' ); ?>
                </label>
            </p>
            <div class="pwpl-plan-badges-fields" data-pwpl-plan-badge-fields <?php if ( ! $enabled ) echo 'style="display:none"'; ?>>
                <p class="description"><?php esc_html_e( 'Define plan-specific badges. Leave blank to inherit table promotions.', 'planify-wp-pricing-lite' ); ?></p>
                <?php foreach ( $groups as $dimension => $label ) :
                    $rows = isset( $override[ $dimension ] ) && is_array( $override[ $dimension ] ) ? $override[ $dimension ] : [];
                    $this->render_badge_section( 'plan', $dimension, $label, $rows, $options[ $dimension ] ?? [] );
                endforeach; ?>
                <?php $this->render_badge_priority_controls( 'plan', $override['priority'] ?? [] ); ?>
            </div>
        </div>
        <?php
    }

    private function render_select( $name, $options, $current, $placeholder, $template = false ) {
        $attr_name = $template ? $name : esc_attr( $name );
        echo '<select name="' . $attr_name . '" class="widefat">';
        echo '<option value="">' . esc_html( $placeholder ) . '</option>';
        foreach ( (array) $options as $item ) {
            $slug  = esc_attr( $item['slug'] );
            $label = esc_html( $item['label'] );
            $selected = $template ? '' : selected( $current, $item['slug'], false );
            echo '<option value="' . $slug . '" ' . $selected . '>' . $label . '</option>';
        }
        echo '</select>';
    }

    public function save_table( $post_id ) {
        $plugin_nonce_ok = isset( $_POST['pwpl_table_nonce'] ) && wp_verify_nonce( $_POST['pwpl_table_nonce'], 'pwpl_save_table_' . $post_id );
        // Allow save when Gutenberg posts via core post update nonce as part of the editor flow
        $core_nonce_ok   = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $post_id );
        if ( ! $plugin_nonce_ok && ! $core_nonce_ok ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $input = $_POST['pwpl_table'] ?? [];
        $meta  = new PWPL_Meta();
        $settings = $this->settings();

        if ( isset( $_POST['pwpl_table_badges'] ) ) {
            $badges_input = (array) $_POST['pwpl_table_badges'];
            $badges       = $meta->sanitize_badges( $badges_input );
            update_post_meta( $post_id, PWPL_Meta::TABLE_BADGES, $badges );
        }

        if ( array_key_exists( 'theme', $input ) ) {
            $theme_input = (string) $input['theme'];
            $theme       = $meta->sanitize_theme( $theme_input );
            update_post_meta( $post_id, PWPL_Meta::TABLE_THEME, $theme );
        }

        $layout_input = isset( $input['layout'] ) ? (array) $input['layout'] : [];
        $ui_input     = isset( $input['ui'] ) ? (array) $input['ui'] : [];

        // Only update widths if the sub-array is present; otherwise preserve existing meta
        if ( array_key_exists( 'widths', $layout_input ) ) {
            $layout_widths_input = (array) $layout_input['widths'];
            $layout_widths       = $meta->sanitize_layout_widths( $layout_widths_input );
            if ( $this->layout_has_values( $layout_widths ) ) {
                update_post_meta( $post_id, PWPL_Meta::LAYOUT_WIDTHS, $layout_widths );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::LAYOUT_WIDTHS );
            }
        }

        // Only update columns if provided
        if ( array_key_exists( 'columns', $layout_input ) ) {
            $layout_columns_input = (array) $layout_input['columns'];
            $layout_columns       = $meta->sanitize_layout_cards( $layout_columns_input );
            if ( $this->layout_has_values( $layout_columns ) ) {
                update_post_meta( $post_id, PWPL_Meta::LAYOUT_COLUMNS, $layout_columns );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::LAYOUT_COLUMNS );
            }
        }

        // Only update card widths if provided
        if ( array_key_exists( 'card_widths', $layout_input ) ) {
            $layout_card_widths_input = (array) $layout_input['card_widths'];
            $layout_card_widths       = $meta->sanitize_layout_card_widths( $layout_card_widths_input );
            if ( $this->layout_has_values( $layout_card_widths ) ) {
                update_post_meta( $post_id, PWPL_Meta::LAYOUT_CARD_WIDTHS, $layout_card_widths );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::LAYOUT_CARD_WIDTHS );
            }
        }

        // Column gap (gutter) — global scalar
        if ( array_key_exists( 'gap_x', $layout_input ) ) {
            $gap_val = (int) $layout_input['gap_x'];
            $gap_val = max( 0, min( 96, $gap_val ) );
            if ( $gap_val ) {
                update_post_meta( $post_id, PWPL_Meta::LAYOUT_GAP_X, $gap_val );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::LAYOUT_GAP_X );
            }
        }

        // Table height (single scalar)
        if ( array_key_exists( 'height', $layout_input ) ) {
            $height_val = (int) $layout_input['height'];
            $height_val = max( 0, min( 4000, $height_val ) );
            if ( $height_val ) {
                update_post_meta( $post_id, PWPL_Meta::TABLE_HEIGHT, $height_val );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::TABLE_HEIGHT );
            }
        }

        // UI toggles
        if ( array_key_exists( 'tabs_glass', $ui_input ) ) {
            $tabs_glass = ! empty( $ui_input['tabs_glass'] ) ? 1 : 0;
            if ( $tabs_glass ) {
                update_post_meta( $post_id, PWPL_Meta::TABS_GLASS, 1 );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::TABS_GLASS );
            }
        }

        // Tint & intensity
        if ( array_key_exists( 'tabs_glass_tint', $ui_input ) ) {
            $tabs_glass_tint = sanitize_hex_color( $ui_input['tabs_glass_tint'] );
            if ( $tabs_glass_tint ) {
                update_post_meta( $post_id, PWPL_Meta::TABS_GLASS_TINT, $tabs_glass_tint );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::TABS_GLASS_TINT );
            }
        }
        if ( array_key_exists( 'tabs_glass_intensity', $ui_input ) ) {
            $tabs_glass_intensity = (int) $ui_input['tabs_glass_intensity'];
            $tabs_glass_intensity = max( 0, min( 100, $tabs_glass_intensity ) );
            if ( $tabs_glass_intensity ) {
                update_post_meta( $post_id, PWPL_Meta::TABS_GLASS_INTENSITY, $tabs_glass_intensity );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::TABS_GLASS_INTENSITY );
            }
        }
        if ( array_key_exists( 'tabs_glass_frost', $ui_input ) ) {
            $tabs_glass_frost = (int) $ui_input['tabs_glass_frost'];
            $tabs_glass_frost = max( 0, min( 24, $tabs_glass_frost ) );
            if ( $tabs_glass_frost ) {
                update_post_meta( $post_id, PWPL_Meta::TABS_GLASS_FROST, $tabs_glass_frost );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::TABS_GLASS_FROST );
            }
        }

        if ( array_key_exists( 'cards_glass', $ui_input ) ) {
            $cards_glass = ! empty( $ui_input['cards_glass'] ) ? 1 : 0;
            if ( $cards_glass ) {
                update_post_meta( $post_id, PWPL_Meta::CARDS_GLASS, 1 );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::CARDS_GLASS );
            }
        }

        // Specs style selector
        if ( array_key_exists( 'specs_style', $ui_input ) ) {
            $specs_style = sanitize_key( $ui_input['specs_style'] );
            if ( ! in_array( $specs_style, [ 'default', 'flat', 'segmented', 'chips' ], true ) ) {
                $specs_style = 'default';
            }
            update_post_meta( $post_id, PWPL_Meta::SPECS_STYLE, $specs_style );
        }

        // Specs interactions
        if ( array_key_exists( 'specs_anim', $ui_input ) ) {
            $anim_input = (array) $ui_input['specs_anim'];
            // No preset control in UI anymore. Persist 'off' and rely on flags.
            update_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_PRESET, 'off' );

            $flags = isset( $anim_input['flags'] ) && is_array( $anim_input['flags'] ) ? array_map( 'sanitize_key', $anim_input['flags'] ) : [];
            $flags = array_values( array_intersect( $flags, [ 'row', 'icon', 'divider', 'chip', 'stagger' ] ) );
            update_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_FLAGS, $flags );

            $intensity = isset( $anim_input['intensity'] ) ? (int) $anim_input['intensity'] : 45;
            $intensity = max( 0, min( 100, $intensity ) );
            update_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_INTENSITY, $intensity );

            $mobile = ! empty( $anim_input['mobile'] ) ? 1 : 0;
            if ( $mobile ) { update_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_MOBILE, 1 ); } else { delete_post_meta( $post_id, PWPL_Meta::SPECS_ANIM_MOBILE ); }
        }

        // Trust trio + sticky cta
        if ( array_key_exists( 'trust_trio', $ui_input ) ) {
            $trust_trio = ! empty( $ui_input['trust_trio'] ) ? 1 : 0;
            if ( $trust_trio ) { update_post_meta( $post_id, PWPL_Meta::TRUST_TRIO_ENABLED, 1 ); } else { delete_post_meta( $post_id, PWPL_Meta::TRUST_TRIO_ENABLED ); }
        }

        if ( array_key_exists( 'sticky_cta', $ui_input ) ) {
            $sticky_cta = ! empty( $ui_input['sticky_cta'] ) ? 1 : 0;
            if ( $sticky_cta ) { update_post_meta( $post_id, PWPL_Meta::STICKY_CTA_MOBILE, 1 ); } else { delete_post_meta( $post_id, PWPL_Meta::STICKY_CTA_MOBILE ); }
        }

        // CTA config
        if ( array_key_exists( 'cta', $ui_input ) ) {
            $cta_input = (array) $ui_input['cta'];
            // Call the sanitizer inline (duplicated logic as in PWPL_Meta::register_meta anonymous sanitizer)
            $v = $cta_input;
            $out = [];
            $out['width']  = in_array( $v['width'] ?? 'full', [ 'auto','full' ], true ) ? $v['width'] : 'full';
            $out['height'] = max( 36, min( 64, (int) ( $v['height'] ?? 48 ) ) );
            $out['pad_x']  = max( 10, min( 32, (int) ( $v['pad_x'] ?? 22 ) ) );
            $out['radius'] = max( 0, min( 999, (int) ( $v['radius'] ?? 12 ) ) );
            $bw = isset( $v['border_width'] ) ? (float) $v['border_width'] : 1.5;
            $out['border_width'] = max( 0, min( 4, $bw ) );
            $font_weight = isset( $v['font']['weight'] ) ? (int) $v['font']['weight'] : 0;
            if ( $font_weight ) { $out['weight'] = max( 400, min( 900, $font_weight ) ); }
            else { $out['weight'] = max( 500, min( 900, (int) ( $v['weight'] ?? 700 ) ) ); }
            $out['lift']   = max( 0, min( 3, (int) ( $v['lift'] ?? 1 ) ) );
            $out['min_w']  = max( 0, min( 4000, (int) ( $v['min_w'] ?? 0 ) ) );
            $out['max_w']  = max( 0, min( 4000, (int) ( $v['max_w'] ?? 0 ) ) );
            $out['focus']  = (string) ( $v['focus'] ?? '' );
            $out['normal'] = [
                'bg'     => (string) ( $v['normal']['bg'] ?? '' ),
                'color'  => (string) ( $v['normal']['color'] ?? '' ),
                'border' => (string) ( $v['normal']['border'] ?? '' ),
            ];
            $out['hover'] = [
                'bg'     => (string) ( $v['hover']['bg'] ?? '' ),
                'color'  => (string) ( $v['hover']['color'] ?? '' ),
                'border' => (string) ( $v['hover']['border'] ?? '' ),
            ];
            $tracking_raw = isset( $v['font']['tracking'] ) ? trim( (string) $v['font']['tracking'] ) : '';
            if ( $tracking_raw !== '' && preg_match( '/^[-+]?[0-9]*\.?[0-9]+$/', $tracking_raw ) ) { $tracking_raw .= 'em'; }
            $preset = isset( $v['font']['preset'] ) ? sanitize_key( $v['font']['preset'] ) : '';
            $preset_map = [
                'system'        => 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
                'inter'         => '"Inter", system-ui, -apple-system, sans-serif',
                'poppins'       => '"Poppins", system-ui, -apple-system, sans-serif',
                'open_sans'     => '"Open Sans", system-ui, -apple-system, sans-serif',
                'montserrat'    => '"Montserrat", system-ui, -apple-system, sans-serif',
                'lato'          => '"Lato", system-ui, -apple-system, sans-serif',
                'space_grotesk' => '"Space Grotesk", system-ui, sans-serif',
                'rubik'         => '"Rubik", system-ui, -apple-system, sans-serif',
            ];
            $family = (string) ( $v['font']['family'] ?? '' );
            if ( $family === '' && $preset && isset( $preset_map[ $preset ] ) ) { $family = $preset_map[ $preset ]; }
            $out['font'] = [
                'family'    => $family,
                'size'      => max( 10, min( 28, (int) ( $v['font']['size'] ?? 0 ) ) ),
                'transform' => in_array( $v['font']['transform'] ?? 'none', [ 'none', 'uppercase' ], true ) ? $v['font']['transform'] : 'none',
                'tracking'  => $tracking_raw,
            ];
            update_post_meta( $post_id, PWPL_Meta::CTA_CONFIG, $out );
        }

        $card_input = isset( $input['card'] ) ? (array) $input['card'] : [];
        $card_clean = $meta->sanitize_card_config( $card_input );

        // Merge with existing card config to avoid wiping values from other tabs
        $existing_card_raw = get_post_meta( $post_id, PWPL_Meta::CARD_CONFIG, true );
        $existing_card     = is_array( $existing_card_raw ) ? $meta->sanitize_card_config( $existing_card_raw ) : [];
        $merged_card       = $existing_card;
        if ( is_array( $card_clean ) && $card_clean ) {
            $merged_card = array_replace_recursive( $existing_card, $card_clean );
        }
        update_post_meta( $post_id, PWPL_Meta::CARD_CONFIG, $merged_card );
        // Trust items textarea -> array
        if ( array_key_exists( 'trust_items', $ui_input ) ) {
            $trust_items_input = (string) $ui_input['trust_items'];
            $lines = array_filter( array_map( function( $line ) {
                $t = trim( (string) $line );
                return $t !== '' ? wp_strip_all_tags( $t ) : '';
            }, preg_split( '/\r\n|\r|\n/', $trust_items_input ) ) );
            if ( ! empty( $lines ) ) {
                update_post_meta( $post_id, PWPL_Meta::TRUST_ITEMS, array_values( $lines ) );
            } else {
                delete_post_meta( $post_id, PWPL_Meta::TRUST_ITEMS );
            }
        }

        // Optional plan card size controls (legacy breakpoint container)
        if ( array_key_exists( 'breakpoints', $input ) ) {
            $breakpoints_input  = (array) $input['breakpoints'];
            $breakpoint_values  = $meta->sanitize_table_breakpoints( $breakpoints_input );
            if ( ! empty( $breakpoint_values ) ) {
                foreach ( $breakpoint_values as $device => $values ) {
                    if ( isset( $breakpoint_values[ $device ]['card_min'] ) ) {
                        unset( $breakpoint_values[ $device ]['card_min'] );
                    }
                    if ( empty( $breakpoint_values[ $device ] ) ) {
                        unset( $breakpoint_values[ $device ] );
                    }
                }
            }
            if ( empty( $breakpoint_values ) ) {
                delete_post_meta( $post_id, PWPL_Meta::TABLE_BREAKPOINTS );
            } else {
                update_post_meta( $post_id, PWPL_Meta::TABLE_BREAKPOINTS, $breakpoint_values );
            }
        }

        if ( array_key_exists( 'dimensions', $input ) ) {
            $dimensions = (array) $input['dimensions'];
            $dimensions = $meta->sanitize_dimensions( $dimensions );
            update_post_meta( $post_id, PWPL_Meta::DIMENSION_META, $dimensions );
        }

        $allowed = isset( $input['allowed'] ) ? (array) $input['allowed'] : null;
        $allowed_order_input = isset( $input['allowed_order'] ) && is_array( $input['allowed_order'] ) ? $input['allowed_order'] : [];
        if ( is_array( $allowed ) ) {
            $platforms = isset( $allowed['platform'] ) ? (array) $allowed['platform'] : [];
            $platform_slugs = wp_list_pluck( (array) $settings->get( 'platforms' ), 'slug' );
            $platforms = array_values( array_intersect( array_map( 'sanitize_title', $platforms ), $platform_slugs ) );

            if ( ! empty( $allowed_order_input['platform'] ) ) {
                $order = array_filter( array_map( 'sanitize_title', explode( ',', $allowed_order_input['platform'] ) ) );
                if ( $order ) {
                    $ordered = [];
                    foreach ( $order as $slug ) {
                        if ( in_array( $slug, $platforms, true ) && ! in_array( $slug, $ordered, true ) ) {
                            $ordered[] = $slug;
                        }
                    }
                    foreach ( $platforms as $slug ) {
                        if ( ! in_array( $slug, $ordered, true ) ) {
                            $ordered[] = $slug;
                        }
                    }
                    $platforms = $ordered;
                }
            }
            update_post_meta( $post_id, PWPL_Meta::ALLOWED_PLATFORMS, $platforms );

            $periods = isset( $allowed['period'] ) ? (array) $allowed['period'] : [];
            $period_slugs = wp_list_pluck( (array) $settings->get( 'periods' ), 'slug' );
            $periods = array_values( array_intersect( array_map( 'sanitize_title', $periods ), $period_slugs ) );
            update_post_meta( $post_id, PWPL_Meta::ALLOWED_PERIODS, $periods );

            $locations = isset( $allowed['location'] ) ? (array) $allowed['location'] : [];
            $location_slugs = wp_list_pluck( (array) $settings->get( 'locations' ), 'slug' );
            $locations = array_values( array_intersect( array_map( 'sanitize_title', $locations ), $location_slugs ) );
            update_post_meta( $post_id, PWPL_Meta::ALLOWED_LOCATIONS, $locations );
        }
    }

    public function save_plan( $post_id ) {
        $plugin_nonce_ok = isset( $_POST['pwpl_plan_nonce'] ) && wp_verify_nonce( $_POST['pwpl_plan_nonce'], 'pwpl_save_plan_' . $post_id );
        $core_nonce_ok   = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $post_id );
        if ( ! $plugin_nonce_ok && ! $core_nonce_ok ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $input = $_POST['pwpl_plan'] ?? [];
        $meta  = new PWPL_Meta();

        $table_id = isset( $input['table_id'] ) ? (int) $input['table_id'] : 0;
        update_post_meta( $post_id, PWPL_Meta::PLAN_TABLE_ID, $table_id );

        $featured = ! empty( $input['featured'] );
        update_post_meta( $post_id, PWPL_Meta::PLAN_FEATURED, $featured ? 1 : 0 );

        $badge_shadow = isset( $input['badge_shadow'] ) ? (int) $input['badge_shadow'] : 0;
        $badge_shadow = max( 0, min( $badge_shadow, 60 ) );
        if ( $badge_shadow > 0 ) {
            update_post_meta( $post_id, PWPL_Meta::PLAN_BADGE_SHADOW, $badge_shadow );
        } else {
            delete_post_meta( $post_id, PWPL_Meta::PLAN_BADGE_SHADOW );
        }

        // Plan subtitle
        $subtitle = isset( $input['subtitle'] ) ? trim( wp_strip_all_tags( (string) $input['subtitle'] ) ) : '';
        if ( $subtitle !== '' ) {
            update_post_meta( $post_id, '_pwpl_plan_subtitle', $subtitle );
        } else {
            delete_post_meta( $post_id, '_pwpl_plan_subtitle' );
        }

        $override_input    = $_POST['pwpl_plan_badges_override'] ?? [];
        $override_enabled  = ! empty( $override_input['enabled'] );
        if ( isset( $override_input['enabled'] ) ) {
            unset( $override_input['enabled'] );
        }

        if ( $override_enabled ) {
            $override_badges = $meta->sanitize_badges( $override_input );
            update_post_meta( $post_id, PWPL_Meta::PLAN_BADGES_OVERRIDE, $override_badges );
        } else {
            delete_post_meta( $post_id, PWPL_Meta::PLAN_BADGES_OVERRIDE );
        }

        $specs = $input['specs'] ?? [];
        if ( is_array( $specs ) ) {
            $specs = array_values( $specs );
        }
        $specs = $meta->sanitize_specs( $specs );
        update_post_meta( $post_id, PWPL_Meta::PLAN_SPECS, $specs );

        $variants = $input['variants'] ?? [];
        if ( is_array( $variants ) ) {
            $variants = array_values( $variants );
        }
        $variants = $meta->sanitize_variants( $variants );
        update_post_meta( $post_id, PWPL_Meta::PLAN_VARIANTS, $variants );
    }
}
