<?php
/**
 * Plan Drawer Form (bespoke layout for drawer, reusing legacy field names/meta).
 *
 * Expects the following variables to be defined by the caller:
 * - $plan        WP_Post
 * - $table       WP_Post (pricing table)
 * - $table_id    int
 * - $meta        array of plan meta values:
 *   - specs (array)
 *   - variants (array)
 *   - featured (bool)
 *   - badge_shadow (int)
 *   - subtitle (string)
 *   - badges_override (array)
 *   - plan_theme (string)
 * - $options     array of global options for select fields:
 *   - platforms, periods, locations
 * - $tables      list of table objects for assignment dropdown
 *
 * Note: Field names mirror the legacy plan meta UI so save_plan() continues to work.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$specs          = isset( $meta['specs'] ) && is_array( $meta['specs'] ) ? $meta['specs'] : [];
$variants       = isset( $meta['variants'] ) && is_array( $meta['variants'] ) ? $meta['variants'] : [];
$featured       = ! empty( $meta['featured'] );
$badge_shadow   = isset( $meta['badge_shadow'] ) ? (int) $meta['badge_shadow'] : 0;
$subtitle       = isset( $meta['subtitle'] ) ? (string) $meta['subtitle'] : '';
$badges_override= isset( $meta['badges_override'] ) && is_array( $meta['badges_override'] ) ? $meta['badges_override'] : [];
$plan_theme     = isset( $meta['plan_theme'] ) ? (string) $meta['plan_theme'] : '';

if ( empty( $specs ) ) {
    $specs = [ [] ];
}
if ( empty( $variants ) ) {
    $variants = [ [] ];
}
$platforms = isset( $options['platforms'] ) ? (array) $options['platforms'] : [];
$periods   = isset( $options['periods'] ) ? (array) $options['periods'] : [];
$locations = isset( $options['locations'] ) ? (array) $options['locations'] : [];

// Helper: render select options from options list.
$render_select = function( $name, $list, $current, $placeholder = '' ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>" class="pwpl-control">
        <option value=""><?php echo esc_html( $placeholder ); ?></option>
        <?php foreach ( (array) $list as $item ) :
            $slug  = isset( $item['slug'] ) ? (string) $item['slug'] : '';
            $label = isset( $item['label'] ) ? (string) $item['label'] : $slug;
            ?>
            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
};
?>

<div class="pwpl-sheet-columns pwpl-sheet-columns--three">
    <div class="pwpl-sheet-col pwpl-sheet-col--primary">
        <div class="pwpl-drawer-section">
            <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Plan Basics', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-drawer-grid pwpl-drawer-grid--basics">
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Assign to Pricing Table', 'planify-wp-pricing-lite' ); ?></span>
                    <select name="pwpl_plan[table_id]" class="pwpl-control">
                        <option value="0"><?php esc_html_e( '— Select a Pricing Table —', 'planify-wp-pricing-lite' ); ?></option>
                        <?php foreach ( (array) $tables as $table_item ) : ?>
                            <option value="<?php echo esc_attr( $table_item->ID ); ?>" <?php selected( $table_id, $table_item->ID ); ?>>
                                <?php echo esc_html( $table_item->post_title ?: sprintf( __( 'Table #%d', 'planify-wp-pricing-lite' ), $table_item->ID ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Plan subtitle', 'planify-wp-pricing-lite' ); ?></span>
                    <input type="text" name="pwpl_plan[subtitle]" class="pwpl-control" placeholder="<?php esc_attr_e( 'e.g. Perfect for starters', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $subtitle ); ?>" />
                    <span class="pwpl-field__hint"><?php esc_html_e( 'Shown under the plan title. Falls back to excerpt if empty.', 'planify-wp-pricing-lite' ); ?></span>
                </label>
                <label class="pwpl-field pwpl-field--inline">
                    <input type="checkbox" name="pwpl_plan[featured]" value="1" <?php checked( $featured ); ?> />
                    <span class="pwpl-field__label"><?php esc_html_e( 'Mark as featured plan', 'planify-wp-pricing-lite' ); ?></span>
                </label>
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Badge glow (override)', 'planify-wp-pricing-lite' ); ?></span>
                    <div class="pwpl-range">
                        <input type="range" name="pwpl_plan[badge_shadow]" min="0" max="60" step="1" value="<?php echo esc_attr( $badge_shadow ); ?>" data-pwpl-range data-pwpl-range-output="#pwpl_plan_badge_shadow_value" />
                        <output id="pwpl_plan_badge_shadow_value"><?php echo esc_html( $badge_shadow ); ?></output>
                    </div>
                    <span class="pwpl-field__hint"><?php esc_html_e( '0 inherits from table. Increase to intensify the badge glow.', 'planify-wp-pricing-lite' ); ?></span>
                </label>
                <div class="pwpl-field pwpl-field--muted">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Table context', 'planify-wp-pricing-lite' ); ?></span>
                    <p class="pwpl-field__hint"><?php echo esc_html( $table->post_title ?: sprintf( __( 'Table #%d', 'planify-wp-pricing-lite' ), $table_id ) ); ?> • <?php printf( esc_html__( 'ID %d', 'planify-wp-pricing-lite' ), $table_id ); ?></p>
                </div>
            </div>
        </div>

        <div class="pwpl-drawer-section">
            <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Specifications', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-specs" data-target="specs" data-next-index="<?php echo esc_attr( count( $specs ) ); ?>">
                <?php foreach ( $specs as $index => $row ) :
                    $label = $row['label'] ?? '';
                    $value = $row['value'] ?? '';
                    ?>
                    <div class="pwpl-spec-row">
                        <input type="text" class="pwpl-control" name="pwpl_plan[specs][<?php echo esc_attr( $index ); ?>][label]" placeholder="<?php esc_attr_e( 'Label', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $label ); ?>" />
                        <input type="text" class="pwpl-control" name="pwpl_plan[specs][<?php echo esc_attr( $index ); ?>][value]" placeholder="<?php esc_attr_e( 'Value', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
                        <button type="button" class="pwpl-icon-btn pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button>
                    </div>
                <?php endforeach; ?>
                <div class="pwpl-specs__actions">
                    <button type="button" class="button button-secondary pwpl-add-row" data-target="specs"><?php esc_html_e( 'Add Specification', 'planify-wp-pricing-lite' ); ?></button>
                </div>
            </div>
        </div>

    </div>

    <div class="pwpl-sheet-col pwpl-sheet-col--variants-nav">
        <div class="pwpl-drawer-section">
            <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Pricing Variants', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-variants-filters" aria-label="<?php esc_attr_e( 'Filter variants', 'planify-wp-pricing-lite' ); ?>">
                <div class="pwpl-filter-row">
                    <div class="pwpl-filter-field pwpl-filter-menu" data-filter-dim="platform">
                        <span class="pwpl-filter-field__label"><?php esc_html_e( 'Platform', 'planify-wp-pricing-lite' ); ?></span>
                        <button type="button" class="pwpl-filter-toggle" aria-haspopup="true" aria-expanded="false"><?php esc_html_e( 'All', 'planify-wp-pricing-lite' ); ?></button>
                        <div class="pwpl-filter-menu__list" role="menu" hidden>
                            <label class="pwpl-filter-option">
                                <input type="checkbox" class="pwpl-filter-checkbox" data-filter-dim="platform" data-filter-value="" checked />
                                <span><?php esc_html_e( 'All', 'planify-wp-pricing-lite' ); ?></span>
                            </label>
                            <?php foreach ( $platforms as $plat ) :
                                $p_slug  = $plat['slug'] ?? '';
                                $p_label = $plat['label'] ?? $p_slug;
                                if ( '' === $p_label ) {
                                    continue;
                                }
                                ?>
                                <label class="pwpl-filter-option">
                                    <input type="checkbox" class="pwpl-filter-checkbox" data-filter-dim="platform" data-filter-value="<?php echo esc_attr( $p_slug ); ?>" />
                                    <span><?php echo esc_html( $p_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pwpl-filter-field pwpl-filter-menu" data-filter-dim="period">
                        <span class="pwpl-filter-field__label"><?php esc_html_e( 'Period', 'planify-wp-pricing-lite' ); ?></span>
                        <button type="button" class="pwpl-filter-toggle" aria-haspopup="true" aria-expanded="false"><?php esc_html_e( 'All', 'planify-wp-pricing-lite' ); ?></button>
                        <div class="pwpl-filter-menu__list" role="menu" hidden>
                            <label class="pwpl-filter-option">
                                <input type="checkbox" class="pwpl-filter-checkbox" data-filter-dim="period" data-filter-value="" checked />
                                <span><?php esc_html_e( 'All', 'planify-wp-pricing-lite' ); ?></span>
                            </label>
                            <?php foreach ( $periods as $per ) :
                                $pe_slug  = $per['slug'] ?? '';
                                $pe_label = $per['label'] ?? $pe_slug;
                                if ( '' === $pe_label ) {
                                    continue;
                                }
                                ?>
                                <label class="pwpl-filter-option">
                                    <input type="checkbox" class="pwpl-filter-checkbox" data-filter-dim="period" data-filter-value="<?php echo esc_attr( $pe_slug ); ?>" />
                                    <span><?php echo esc_html( $pe_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pwpl-filter-field pwpl-filter-menu" data-filter-dim="location">
                        <span class="pwpl-filter-field__label"><?php esc_html_e( 'Location', 'planify-wp-pricing-lite' ); ?></span>
                        <button type="button" class="pwpl-filter-toggle" aria-haspopup="true" aria-expanded="false"><?php esc_html_e( 'All', 'planify-wp-pricing-lite' ); ?></button>
                        <div class="pwpl-filter-menu__list" role="menu" hidden>
                            <label class="pwpl-filter-option">
                                <input type="checkbox" class="pwpl-filter-checkbox" data-filter-dim="location" data-filter-value="" checked />
                                <span><?php esc_html_e( 'All', 'planify-wp-pricing-lite' ); ?></span>
                            </label>
                            <?php foreach ( $locations as $loc ) :
                                $l_slug  = $loc['slug'] ?? '';
                                $l_label = $loc['label'] ?? $l_slug;
                                if ( '' === $l_label ) {
                                    continue;
                                }
                                ?>
                                <label class="pwpl-filter-option">
                                    <input type="checkbox" class="pwpl-filter-checkbox" data-filter-dim="location" data-filter-value="<?php echo esc_attr( $l_slug ); ?>" />
                                    <span><?php echo esc_html( $l_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pwpl-variants-nav" role="tablist" aria-label="<?php esc_attr_e( 'Pricing variants', 'planify-wp-pricing-lite' ); ?>">
                <?php foreach ( $variants as $index => $row ) :
                    $platform = $row['platform'] ?? '';
                    $period   = $row['period'] ?? '';
                    $location = $row['location'] ?? '';
                    $price    = $row['price'] ?? '';
                    $sale     = $row['sale_price'] ?? '';
                    $has_sale = ( '' !== $sale );
                    $header_platform = $platform ?: __( 'Any platform', 'planify-wp-pricing-lite' );
                    $header_period   = $period ?: __( 'Any period', 'planify-wp-pricing-lite' );
                    $header_location = $location ?: __( 'Any location', 'planify-wp-pricing-lite' );
                    $summary_price   = $price ? $price : __( 'No price', 'planify-wp-pricing-lite' );
                    $summary_sale    = $has_sale ? sprintf( /* translators: 1: sale price */ __( 'Sale %s', 'planify-wp-pricing-lite' ), $sale ) : '';
                    $unavail  = ! empty( $row['unavailable'] );
                    $is_first = ( 0 === (int) $index );
                    ?>
                    <button type="button" class="pwpl-variant-nav-item<?php echo $is_first ? ' is-active' : ''; ?>" data-variant-index="<?php echo esc_attr( $index ); ?>" data-platform="<?php echo esc_attr( $platform ); ?>" data-period="<?php echo esc_attr( $period ); ?>" data-location="<?php echo esc_attr( $location ); ?>" role="tab" aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>">
                        <div class="pwpl-variant-nav-item__labels">
                            <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $header_platform ); ?></span>
                            <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $header_period ); ?></span>
                            <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $header_location ); ?></span>
                        </div>
                        <div class="pwpl-variant-nav-item__meta">
                            <span class="pwpl-variant-nav-item__price"><?php echo esc_html( $summary_price ); ?></span>
                            <?php if ( $summary_sale ) : ?>
                                <span class="pwpl-variant-nav-item__sale"><?php echo esc_html( $summary_sale ); ?></span>
                            <?php endif; ?>
                            <?php if ( $has_sale ) : ?>
                                <span class="pwpl-chip pwpl-chip--accent"><?php esc_html_e( 'Sale', 'planify-wp-pricing-lite' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $unavail ) : ?>
                                <span class="pwpl-chip pwpl-chip--warning"><?php esc_html_e( 'Unavailable', 'planify-wp-pricing-lite' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
                <div class="pwpl-variants-empty" data-variants-nav-empty <?php echo empty( $variants ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No variants yet. Add one to start pricing this plan.', 'planify-wp-pricing-lite' ); ?></div>
            </div>
        </div>
    </div>

    <div class="pwpl-sheet-col pwpl-sheet-col--secondary">
        <div class="pwpl-drawer-section">
            <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Variant details', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-variants" data-target="variants" data-next-index="<?php echo esc_attr( count( $variants ) ); ?>">
                <?php foreach ( $variants as $index => $row ) :
                    $platform = $row['platform'] ?? '';
                    $period   = $row['period'] ?? '';
                    $location = $row['location'] ?? '';
                    $price    = $row['price'] ?? '';
                    $sale     = $row['sale_price'] ?? '';
                    $cta_label= $row['cta_label'] ?? '';
                    $cta_url  = $row['cta_url'] ?? '';
                    $target   = $row['target'] ?? '';
                    $rel      = $row['rel'] ?? '';
                    $unavail  = ! empty( $row['unavailable'] );
                    $header_platform = $platform ?: __( 'Any platform', 'planify-wp-pricing-lite' );
                    $header_period   = $period ?: __( 'Any period', 'planify-wp-pricing-lite' );
                    $header_location = $location ?: __( 'Any location', 'planify-wp-pricing-lite' );
                    $has_sale        = ( '' !== $sale );
                    $summary_price   = $price ? $price : __( 'No price', 'planify-wp-pricing-lite' );
                    $summary_sale    = $has_sale ? sprintf( /* translators: 1: sale price */ __( 'Sale %s', 'planify-wp-pricing-lite' ), $sale ) : '';
                    $is_first      = ( 0 === (int) $index );
                    $card_classes  = $is_first ? 'pwpl-variant-card is-active' : 'pwpl-variant-card is-collapsed';
                    $aria_expanded = $is_first ? 'true' : 'false';
                    ?>
                    <div class="<?php echo esc_attr( $card_classes ); ?>" data-variant-card data-variant-index="<?php echo esc_attr( $index ); ?>" data-platform="<?php echo esc_attr( $platform ); ?>" data-period="<?php echo esc_attr( $period ); ?>" data-location="<?php echo esc_attr( $location ); ?>">
                        <button type="button" class="pwpl-variant-summary" aria-expanded="<?php echo esc_attr( $aria_expanded ); ?>">
                            <div class="pwpl-variant-summary__labels">
                                <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $header_platform ); ?></span>
                                <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $header_period ); ?></span>
                                <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $header_location ); ?></span>
                            </div>
                            <div class="pwpl-variant-summary__price">
                                <span class="pwpl-variant-price"><?php echo esc_html( $summary_price ); ?></span>
                                <?php if ( $summary_sale ) : ?>
                                    <span class="pwpl-variant-sale"><?php echo esc_html( $summary_sale ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pwpl-variant-summary__status">
                                <?php if ( $has_sale ) : ?>
                                    <span class="pwpl-chip pwpl-chip--accent"><?php esc_html_e( 'On sale', 'planify-wp-pricing-lite' ); ?></span>
                                <?php endif; ?>
                                <?php if ( $unavail ) : ?>
                                    <span class="pwpl-chip pwpl-chip--warning"><?php esc_html_e( 'Unavailable', 'planify-wp-pricing-lite' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="pwpl-variant-summary__chev" aria-hidden="true">⌄</span>
                        </button>
                        <div class="pwpl-variant-details" data-variant-details>
                            <div class="pwpl-variant-row pwpl-variant-row--dims">
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Platform', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $render_select( "pwpl_plan[variants][{$index}][platform]", $platforms, $platform, __( 'Any', 'planify-wp-pricing-lite' ) ); ?>
                                </label>
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Period', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $render_select( "pwpl_plan[variants][{$index}][period]", $periods, $period, __( 'Any', 'planify-wp-pricing-lite' ) ); ?>
                                </label>
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Location', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php $render_select( "pwpl_plan[variants][{$index}][location]", $locations, $location, __( 'Any', 'planify-wp-pricing-lite' ) ); ?>
                                </label>
                            </div>
                            <div class="pwpl-variant-row pwpl-variant-row--pricing">
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Price', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" />
                                </label>
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Sale price', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][sale_price]" value="<?php echo esc_attr( $sale ); ?>" />
                                </label>
                            </div>
                            <div class="pwpl-variant-row pwpl-variant-row--cta-label">
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'CTA label', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][cta_label]" value="<?php echo esc_attr( $cta_label ); ?>" />
                                </label>
                            </div>
                            <div class="pwpl-variant-row pwpl-variant-row--cta-url">
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'CTA URL', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="url" class="pwpl-control" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][cta_url]" value="<?php echo esc_attr( $cta_url ); ?>" placeholder="https://" />
                                </label>
                            </div>
                            <div class="pwpl-variant-row pwpl-variant-row--meta">
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Target', 'planify-wp-pricing-lite' ); ?></span>
                                    <select class="pwpl-control" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][target]">
                                        <option value=""><?php esc_html_e( 'Default', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="_self" <?php selected( $target, '_self' ); ?>><?php esc_html_e( 'Same tab', 'planify-wp-pricing-lite' ); ?></option>
                                        <option value="_blank" <?php selected( $target, '_blank' ); ?>><?php esc_html_e( 'New tab', 'planify-wp-pricing-lite' ); ?></option>
                                    </select>
                                </label>
                                <label class="pwpl-field">
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Rel', 'planify-wp-pricing-lite' ); ?></span>
                                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][rel]" value="<?php echo esc_attr( $rel ); ?>" placeholder="<?php esc_attr_e( 'nofollow noopener', 'planify-wp-pricing-lite' ); ?>" />
                                </label>
                            </div>
                            <div class="pwpl-variant-row pwpl-variant-row--checkbox">
                                <label class="pwpl-field pwpl-field--inline">
                                    <input type="checkbox" name="pwpl_plan[variants][<?php echo esc_attr( $index ); ?>][unavailable]" value="1" <?php checked( $unavail ); ?> />
                                    <span class="pwpl-field__label"><?php esc_html_e( 'Mark as unavailable', 'planify-wp-pricing-lite' ); ?></span>
                                </label>
                            </div>
                            <div class="pwpl-variant-card__actions">
                                <button type="button" class="pwpl-icon-btn pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove variant', 'planify-wp-pricing-lite' ); ?>">&times;</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="pwpl-variants__actions">
                    <button type="button" class="button button-secondary pwpl-add-row" data-target="variants"><?php esc_html_e( 'Add Variant', 'planify-wp-pricing-lite' ); ?></button>
                </div>
            </div>
            <div class="pwpl-variants-empty" data-variants-empty hidden><?php esc_html_e( 'No variants yet. Add one to start pricing this plan.', 'planify-wp-pricing-lite' ); ?></div>
        </div>
    </div>
</div>

<div class="pwpl-drawer-section pwpl-drawer-section--full">
    <div class="pwpl-advanced-toggle">
        <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Advanced', 'planify-wp-pricing-lite' ); ?></div>
        <button type="button" class="pwpl-advanced-toggle__btn" aria-expanded="false"><?php esc_html_e( 'Show advanced', 'planify-wp-pricing-lite' ); ?></button>
    </div>
    <div class="pwpl-advanced-content" hidden>
        <div class="pwpl-drawer-grid pwpl-drawer-grid--fullwidth">
            <div class="pwpl-drawer-section pwpl-drawer-section--nested">
                <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Promotions (Overrides)', 'planify-wp-pricing-lite' ); ?></div>
                <div class="pwpl-promos">
                    <?php
                    $override_enabled = isset( $badges_override['enabled'] ) ? (bool) $badges_override['enabled'] : ! empty( array_filter( $badges_override ) );
                    ?>
                    <label class="pwpl-field pwpl-field--inline">
                        <input type="checkbox" name="pwpl_plan_badges_override[enabled]" value="1" <?php checked( $override_enabled ); ?> />
                        <span class="pwpl-field__label"><?php esc_html_e( 'Override table promotions for this plan', 'planify-wp-pricing-lite' ); ?></span>
                    </label>
                    <p class="pwpl-field__hint"><?php esc_html_e( 'Define plan-specific badges. Leave blank to inherit table promotions.', 'planify-wp-pricing-lite' ); ?></p>
                    <div class="pwpl-promos__notice">
                        <?php esc_html_e( 'Badges override uses the same fields as the classic editor. For detailed badge entry, use the Full Editor if needed.', 'planify-wp-pricing-lite' ); ?>
                    </div>
                    <?php
                    $groups = [
                        'period'   => __( 'Period promotions', 'planify-wp-pricing-lite' ),
                        'location' => __( 'Location promotions', 'planify-wp-pricing-lite' ),
                        'platform' => __( 'Platform promotions', 'planify-wp-pricing-lite' ),
                    ];
                    foreach ( $groups as $dimension => $label ) :
                        $rows = isset( $badges_override[ $dimension ] ) && is_array( $badges_override[ $dimension ] ) ? $badges_override[ $dimension ] : [];
                        ?>
                        <details class="pwpl-promos__group" <?php echo $rows ? 'open' : ''; ?>>
                            <summary><?php echo esc_html( $label ); ?></summary>
                            <div class="pwpl-promos__rows">
                                <?php foreach ( $rows as $idx => $badge ) :
                                    $slug  = $badge['slug'] ?? '';
                                    $b_label = $badge['label'] ?? '';
                                    $color = $badge['color'] ?? '';
                                    $textc = $badge['text_color'] ?? '';
                                    $icon  = $badge['icon'] ?? '';
                                    $tone  = $badge['tone'] ?? '';
                                    $start = $badge['start'] ?? '';
                                    $end   = $badge['end'] ?? '';
                                    ?>
                                    <div class="pwpl-promo-row">
                                        <input type="text" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][slug]" placeholder="<?php esc_attr_e( 'Slug', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $slug ); ?>" />
                                        <input type="text" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][label]" placeholder="<?php esc_attr_e( 'Label', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $b_label ); ?>" />
                                        <input type="text" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][color]" placeholder="<?php esc_attr_e( 'BG color (hex)', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $color ); ?>" />
                                        <input type="text" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][text_color]" placeholder="<?php esc_attr_e( 'Text color (hex)', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $textc ); ?>" />
                                        <input type="text" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][icon]" placeholder="<?php esc_attr_e( 'Icon', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $icon ); ?>" />
                                        <input type="text" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][tone]" placeholder="<?php esc_attr_e( 'Tone', 'planify-wp-pricing-lite' ); ?>" value="<?php echo esc_attr( $tone ); ?>" />
                                        <input type="date" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][start]" value="<?php echo esc_attr( $start ); ?>" />
                                        <input type="date" class="pwpl-control" name="pwpl_plan_badges_override[<?php echo esc_attr( $dimension ); ?>][<?php echo esc_attr( $idx ); ?>][end]" value="<?php echo esc_attr( $end ); ?>" />
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    <?php
                    $priority = isset( $badges_override['priority'] ) && is_array( $badges_override['priority'] ) ? $badges_override['priority'] : [];
                    if ( empty( $priority ) ) {
                        $priority = [ 'period', 'location', 'platform' ];
                    }
                    ?>
                    <div class="pwpl-field">
                        <span class="pwpl-field__label"><?php esc_html_e( 'Badge priority (period/location/platform)', 'planify-wp-pricing-lite' ); ?></span>
                        <div class="pwpl-chip-row">
                            <?php foreach ( $priority as $p ) : ?>
                                <span class="pwpl-chip"><?php echo esc_html( $p ); ?></span>
                                <input type="hidden" name="pwpl_plan_badges_override[priority][]" value="<?php echo esc_attr( $p ); ?>" />
                            <?php endforeach; ?>
                        </div>
                        <span class="pwpl-field__hint"><?php esc_html_e( 'Priority is shown for reference; adjust in the full editor if needed.', 'planify-wp-pricing-lite' ); ?></span>
                    </div>
                </div>
            </div>

            <div class="pwpl-drawer-section pwpl-drawer-section--nested">
                <div class="pwpl-drawer-section__header"><?php esc_html_e( 'Plan theme override', 'planify-wp-pricing-lite' ); ?></div>
                <div class="pwpl-drawer-grid">
                    <label class="pwpl-field">
                        <span class="pwpl-field__label"><?php esc_html_e( 'Plan theme (optional)', 'planify-wp-pricing-lite' ); ?></span>
                        <input type="text" class="pwpl-control" name="pwpl_plan[theme]" value="<?php echo esc_attr( $plan_theme ); ?>" placeholder="<?php esc_attr_e( 'e.g. firevps', 'planify-wp-pricing-lite' ); ?>" />
                        <span class="pwpl-field__hint"><?php esc_html_e( 'Override the table theme for this plan if supported.', 'planify-wp-pricing-lite' ); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="pwpl-tpl-specs">
    <div class="pwpl-spec-row">
        <input type="text" class="pwpl-control" name="pwpl_plan[specs][__INDEX__][label]" placeholder="<?php esc_attr_e( 'Label', 'planify-wp-pricing-lite' ); ?>" />
        <input type="text" class="pwpl-control" name="pwpl_plan[specs][__INDEX__][value]" placeholder="<?php esc_attr_e( 'Value', 'planify-wp-pricing-lite' ); ?>" />
        <button type="button" class="pwpl-icon-btn pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'planify-wp-pricing-lite' ); ?>">&times;</button>
    </div>
</script>

<script type="text/html" id="pwpl-tpl-variants-nav">
    <button type="button" class="pwpl-variant-nav-item" data-variant-index="__INDEX__" role="tab" aria-selected="false">
        <div class="pwpl-variant-nav-item__labels">
            <span class="pwpl-chip pwpl-chip--muted"><?php esc_html_e( 'Any platform', 'planify-wp-pricing-lite' ); ?></span>
            <span class="pwpl-chip pwpl-chip--muted"><?php esc_html_e( 'Any period', 'planify-wp-pricing-lite' ); ?></span>
            <span class="pwpl-chip pwpl-chip--muted"><?php esc_html_e( 'Any location', 'planify-wp-pricing-lite' ); ?></span>
        </div>
        <div class="pwpl-variant-nav-item__meta">
            <span class="pwpl-variant-nav-item__price"><?php esc_html_e( 'No price', 'planify-wp-pricing-lite' ); ?></span>
        </div>
    </button>
</script>

<script type="text/html" id="pwpl-tpl-variants">
    <div class="pwpl-variant-card" data-variant-card data-variant-index="__INDEX__">
        <button type="button" class="pwpl-variant-summary" aria-expanded="true">
            <div class="pwpl-variant-summary__labels">
                <span class="pwpl-chip pwpl-chip--muted"><?php esc_html_e( 'Any platform', 'planify-wp-pricing-lite' ); ?></span>
                <span class="pwpl-chip pwpl-chip--muted"><?php esc_html_e( 'Any period', 'planify-wp-pricing-lite' ); ?></span>
                <span class="pwpl-chip pwpl-chip--muted"><?php esc_html_e( 'Any location', 'planify-wp-pricing-lite' ); ?></span>
            </div>
            <div class="pwpl-variant-summary__price">
                <span class="pwpl-variant-price"><?php esc_html_e( 'No price', 'planify-wp-pricing-lite' ); ?></span>
            </div>
            <div class="pwpl-variant-summary__status"></div>
            <span class="pwpl-variant-summary__chev" aria-hidden="true">⌄</span>
        </button>
        <div class="pwpl-variant-details" data-variant-details>
            <div class="pwpl-variant-row pwpl-variant-row--dims">
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Platform', 'planify-wp-pricing-lite' ); ?></span>
                    <?php $render_select( 'pwpl_plan[variants][__INDEX__][platform]', $platforms, '', __( 'Any', 'planify-wp-pricing-lite' ) ); ?>
                </label>
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Period', 'planify-wp-pricing-lite' ); ?></span>
                    <?php $render_select( 'pwpl_plan[variants][__INDEX__][period]', $periods, '', __( 'Any', 'planify-wp-pricing-lite' ) ); ?>
                </label>
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Location', 'planify-wp-pricing-lite' ); ?></span>
                    <?php $render_select( 'pwpl_plan[variants][__INDEX__][location]', $locations, '', __( 'Any', 'planify-wp-pricing-lite' ) ); ?>
                </label>
            </div>
            <div class="pwpl-variant-row pwpl-variant-row--pricing">
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Price', 'planify-wp-pricing-lite' ); ?></span>
                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][__INDEX__][price]" />
                </label>
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Sale price', 'planify-wp-pricing-lite' ); ?></span>
                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][__INDEX__][sale_price]" />
                </label>
            </div>
            <div class="pwpl-variant-row pwpl-variant-row--cta-label">
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'CTA label', 'planify-wp-pricing-lite' ); ?></span>
                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][__INDEX__][cta_label]" />
                </label>
            </div>
            <div class="pwpl-variant-row pwpl-variant-row--cta-url">
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'CTA URL', 'planify-wp-pricing-lite' ); ?></span>
                    <input type="url" class="pwpl-control" name="pwpl_plan[variants][__INDEX__][cta_url]" placeholder="https://" />
                </label>
            </div>
            <div class="pwpl-variant-row pwpl-variant-row--meta">
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Target', 'planify-wp-pricing-lite' ); ?></span>
                    <select class="pwpl-control" name="pwpl_plan[variants][__INDEX__][target]">
                        <option value=""><?php esc_html_e( 'Default', 'planify-wp-pricing-lite' ); ?></option>
                        <option value="_self"><?php esc_html_e( 'Same tab', 'planify-wp-pricing-lite' ); ?></option>
                        <option value="_blank"><?php esc_html_e( 'New tab', 'planify-wp-pricing-lite' ); ?></option>
                    </select>
                </label>
                <label class="pwpl-field">
                    <span class="pwpl-field__label"><?php esc_html_e( 'Rel', 'planify-wp-pricing-lite' ); ?></span>
                    <input type="text" class="pwpl-control" name="pwpl_plan[variants][__INDEX__][rel]" placeholder="<?php esc_attr_e( 'nofollow noopener', 'planify-wp-pricing-lite' ); ?>" />
                </label>
            </div>
            <div class="pwpl-variant-row pwpl-variant-row--checkbox">
                <label class="pwpl-field pwpl-field--inline">
                    <input type="checkbox" name="pwpl_plan[variants][__INDEX__][unavailable]" value="1" />
                    <span class="pwpl-field__label"><?php esc_html_e( 'Mark as unavailable', 'planify-wp-pricing-lite' ); ?></span>
                </label>
            </div>
            <div class="pwpl-variant-card__actions">
                <button type="button" class="pwpl-icon-btn pwpl-remove-row" aria-label="<?php esc_attr_e( 'Remove variant', 'planify-wp-pricing-lite' ); ?>">&times;</button>
            </div>
        </div>
    </div>
</script>
