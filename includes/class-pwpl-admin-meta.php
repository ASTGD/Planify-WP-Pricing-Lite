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

        $meta_helper = new PWPL_Meta();
        $table_theme = get_post_meta( $post->ID, PWPL_Meta::TABLE_THEME, true );
        $table_theme = $meta_helper->sanitize_theme( $table_theme ?: 'classic' );
        $themes = [
            'classic'         => __( 'Classic', 'planify-wp-pricing-lite' ),
            'warm'            => __( 'Warm', 'planify-wp-pricing-lite' ),
            'blue'            => __( 'Blue', 'planify-wp-pricing-lite' ),
            'modern-discount' => __( 'Modern Discount', 'planify-wp-pricing-lite' ),
        ];

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
            <div class="pwpl-field">
                <label for="pwpl_table_theme"><strong><?php esc_html_e( 'Theme / Style', 'planify-wp-pricing-lite' ); ?></strong></label>
                <select id="pwpl_table_theme" name="pwpl_table[theme]" class="widefat">
                    <?php foreach ( $themes as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $table_theme, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Applies to every plan within this table. Customize colors via assets/css/themes.css.', 'planify-wp-pricing-lite' ); ?></p>
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
                    <input type="range" id="pwpl_table_badges_shadow" name="pwpl_table_badges[shadow]" min="0" max="60" step="1" value="<?php echo esc_attr( $shadow ); ?>" data-pwpl-shadow-range />
                    <output for="pwpl_table_badges_shadow" data-pwpl-shadow-value><?php echo esc_html( $shadow ); ?></output>
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

            <div class="pwpl-field">
                <label>
                    <input type="checkbox" name="pwpl_plan[featured]" value="1" <?php checked( $featured ); ?> />
                    <strong><?php esc_html_e( 'Mark as featured plan', 'planify-wp-pricing-lite' ); ?></strong>
                </label>
                <p class="description"><?php esc_html_e( 'Use this flag in your theme to highlight a primary plan.', 'planify-wp-pricing-lite' ); ?></p>
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

        $badges_input = $_POST['pwpl_table_badges'] ?? [];
        $badges       = $meta->sanitize_badges( $badges_input );
        update_post_meta( $post_id, PWPL_Meta::TABLE_BADGES, $badges );

        $theme_input = $input['theme'] ?? '';
        $theme       = $meta->sanitize_theme( $theme_input ?: 'classic' );
        update_post_meta( $post_id, PWPL_Meta::TABLE_THEME, $theme );

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

        $featured = ! empty( $input['featured'] );
        update_post_meta( $post_id, PWPL_Meta::PLAN_FEATURED, $featured ? 1 : 0 );

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
