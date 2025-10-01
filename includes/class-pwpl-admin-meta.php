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
            'pwpl_plan_details',
            __( 'Plan Details', 'planify-wp-pricing-lite' ),
            [ $this, 'render_plan_meta' ],
            'pwpl_plan',
            'normal',
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
        ?>
        <div class="pwpl-meta pwpl-meta--table" data-pwpl-dimensions>
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
                                    <input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $config['allowed'], true ) ); ?> />
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No values defined. Add options in Settings → Dimensions & Variants.', 'planify-wp-pricing-lite' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function render_plan_meta( $post ) {
        wp_nonce_field( 'pwpl_save_plan_' . $post->ID, 'pwpl_plan_nonce' );

        $table_id = (int) get_post_meta( $post->ID, PWPL_Meta::PLAN_TABLE_ID, true );
        $theme    = get_post_meta( $post->ID, PWPL_Meta::PLAN_THEME, true );
        $specs    = get_post_meta( $post->ID, PWPL_Meta::PLAN_SPECS, true );
        $variants = get_post_meta( $post->ID, PWPL_Meta::PLAN_VARIANTS, true );

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

        $themes = [
            'classic' => __( 'Classic', 'planify-wp-pricing-lite' ),
            'warm'    => __( 'Warm', 'planify-wp-pricing-lite' ),
            'blue'    => __( 'Blue', 'planify-wp-pricing-lite' ),
        ];

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

            <div class="pwpl-field">
                <label for="pwpl_plan_theme"><strong><?php esc_html_e( 'Theme / Style', 'planify-wp-pricing-lite' ); ?></strong></label>
                <select id="pwpl_plan_theme" name="pwpl_plan[theme]" class="widefat">
                    <?php foreach ( $themes as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $theme ?: 'classic', $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pwpl-field">
                <label><strong><?php esc_html_e( 'Specifications', 'planify-wp-pricing-lite' ); ?></strong></label>
                <p class="description"><?php esc_html_e( 'Add spec rows like CPU, RAM, Bandwidth. Leave blank rows to remove.', 'planify-wp-pricing-lite' ); ?></p>
                <table class="widefat pwpl-repeatable" data-pwpl-repeatable="specs" data-next-index="<?php echo esc_attr( max( $spec_count, count( $specs ) ) ); ?>">
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
                <table class="widefat pwpl-repeatable" data-pwpl-repeatable="variants" data-next-index="<?php echo esc_attr( max( $variant_count, count( $variants ) ) ); ?>">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Platform', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Period', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Location', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'planify-wp-pricing-lite' ); ?></th>
                            <th><?php esc_html_e( 'Sale Price', 'planify-wp-pricing-lite' ); ?></th>
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
                            ?>
                            <tr>
                                <td><?php $this->render_select( "pwpl_plan[variants][{$index}][platform]", $platforms, $platform, __( 'Any', 'planify-wp-pricing-lite' ) ); ?></td>
                                <td><?php $this->render_select( "pwpl_plan[variants][{$index}][period]", $periods, $period, __( 'Any', 'planify-wp-pricing-lite' ) ); ?></td>
                                <td><?php $this->render_select( "pwpl_plan[variants][{$index}][location]", $locations, $location, __( 'Any', 'planify-wp-pricing-lite' ) ); ?></td>
                                <td><input type="text" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" class="widefat" /></td>
                                <td><input type="text" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][sale_price]" value="<?php echo esc_attr( $sale ); ?>" class="widefat" /></td>
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
                <td><button type="button" class="button pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button></td>
            </tr>
        </script>
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
        if ( ! isset( $_POST['pwpl_table_nonce'] ) || ! wp_verify_nonce( $_POST['pwpl_table_nonce'], 'pwpl_save_table_' . $post_id ) ) {
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

        $dimensions = isset( $input['dimensions'] ) ? (array) $input['dimensions'] : [];
        $dimensions = $meta->sanitize_dimensions( $dimensions );
        update_post_meta( $post_id, PWPL_Meta::DIMENSION_META, $dimensions );

        $allowed = $input['allowed'] ?? [];

        $platforms = isset( $allowed['platform'] ) ? (array) $allowed['platform'] : [];
        $platform_slugs = wp_list_pluck( (array) $settings->get( 'platforms' ), 'slug' );
        $platforms = array_values( array_intersect( array_map( 'sanitize_title', $platforms ), $platform_slugs ) );
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

    public function save_plan( $post_id ) {
        if ( ! isset( $_POST['pwpl_plan_nonce'] ) || ! wp_verify_nonce( $_POST['pwpl_plan_nonce'], 'pwpl_save_plan_' . $post_id ) ) {
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

        $theme = isset( $input['theme'] ) ? sanitize_key( $input['theme'] ) : 'classic';
        update_post_meta( $post_id, PWPL_Meta::PLAN_THEME, $theme );

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
