<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * @var WP_Post $table
 * @var int $table_id
 * @var array $plans
 * @var array $counts
 * @var string $status_filter
 * @var string $search_term
 * @var bool $featured_only
 * @var array $catalog
 */

$back_url  = admin_url( 'admin.php?page=pwpl-tables-dashboard' );
$edit_url  = get_edit_post_link( $table_id );
$add_plan  = wp_nonce_url( admin_url( 'admin-post.php?action=pwpl_create_plan_for_table&pwpl_table=' . $table_id ), 'pwpl_create_plan_' . $table_id );
$shortcode = sprintf( '[pwpl_table id="%d"]', $table_id );

$meta_helper = new PWPL_Meta();

$format_price = function( $amount ) {
    if ( $amount === '' || $amount === null ) {
        return '';
    }
    $amount = (float) $amount;
    $decimals = (int) get_option( 'pwpl_price_decimals', 2 );
    if ( $decimals < 0 ) { $decimals = 0; }
    if ( $decimals > 4 ) { $decimals = 4; }
    return number_format_i18n( $amount, $decimals );
};

$price_summary = function( $variants ) use ( $meta_helper, $format_price ) {
    $variants = $meta_helper->sanitize_variants( is_array( $variants ) ? $variants : [] );
    $best = null;
    foreach ( $variants as $variant ) {
        $price = isset( $variant['price'] ) ? (float) $variant['price'] : null;
        $sale  = isset( $variant['sale_price'] ) ? (float) $variant['sale_price'] : null;
        if ( ( $price !== null && $price !== 0.0 ) || ( $sale !== null && $sale !== 0.0 ) ) {
            $best = $variant;
            break;
        }
    }
    if ( ! $best ) {
        return [ 'label' => __( 'No pricing set', 'planify-wp-pricing-lite' ), 'raw' => [] ];
    }
    $price = isset( $best['price'] ) ? (float) $best['price'] : null;
    $sale  = isset( $best['sale_price'] ) ? (float) $best['sale_price'] : null;
    $has_discount = $sale !== null && $sale > 0 && $price !== null && $price > 0 && $sale < $price;
    if ( $has_discount ) {
        return [
            'label' => sprintf(
                /* translators: 1: sale price, 2: base price */
                __( 'From %1$s/mo (was %2$s)', 'planify-wp-pricing-lite' ),
                $format_price( $sale ),
                $format_price( $price )
            ),
            'raw' => [ 'price' => $price, 'sale' => $sale, 'discount' => true ],
        ];
    }
    if ( $price !== null && $price > 0 ) {
        return [
            'label' => sprintf(
                /* translators: %s price */
                __( 'From %s/mo', 'planify-wp-pricing-lite' ),
                $format_price( $price )
            ),
            'raw' => [ 'price' => $price, 'sale' => null, 'discount' => false ],
        ];
    }
    return [ 'label' => __( 'No pricing set', 'planify-wp-pricing-lite' ), 'raw' => [] ];
};

$dim_labels = function( $dim, $slug ) use ( $catalog ) {
    if ( empty( $slug ) ) {
        return '';
    }
    return $catalog[ $dim ][ $slug ]['label'] ?? $slug;
};

// Notices
if ( ! empty( $_GET['pwpl_notice'] ) ) {
    $notice = sanitize_key( $_GET['pwpl_notice'] );
    $messages = [
        'plan_duplicated' => __( 'Plan duplicated.', 'planify-wp-pricing-lite' ),
        'plan_deleted'    => __( 'Plan moved to trash.', 'planify-wp-pricing-lite' ),
        'plan_error'      => __( 'Unable to complete the action.', 'planify-wp-pricing-lite' ),
        'plan_saved'      => __( 'Plan saved.', 'planify-wp-pricing-lite' ),
    ];
    if ( isset( $messages[ $notice ] ) ) {
        printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $notice ] ) );
    }
}
?>
<div class="wrap pwpl-plans">
    <div class="pwpl-plans__header">
        <div class="pwpl-plans__titlegroup">
            <h1 class="pwpl-plans__title">
                <?php
                printf(
                    /* translators: %s table title */
                    esc_html__( 'Plans for: %s', 'planify-wp-pricing-lite' ),
                    esc_html( $table->post_title ?: sprintf( __( 'Table #%d', 'planify-wp-pricing-lite' ), $table_id ) )
                );
                ?>
            </h1>
            <p class="pwpl-plans__subline">
                <?php
                $status_label = ( 'publish' === $table->post_status ) ? __( 'Published', 'planify-wp-pricing-lite' ) : __( 'Draft', 'planify-wp-pricing-lite' );
                printf(
                    /* translators: 1: status, 2: table id */
                    esc_html__( '%1$s • Table ID %2$d', 'planify-wp-pricing-lite' ),
                    esc_html( $status_label ),
                    (int) $table_id
                );
                ?>
            </p>
            <div class="pwpl-plans__shortcode">
                <label for="pwpl-sc-<?php echo esc_attr( $table_id ); ?>"><?php esc_html_e( 'Shortcode', 'planify-wp-pricing-lite' ); ?></label>
                <div class="pwpl-plans__copywrap">
                    <input type="text" readonly id="pwpl-sc-<?php echo esc_attr( $table_id ); ?>" value="<?php echo esc_attr( $shortcode ); ?>" />
                    <button type="button" class="button pwpl-copy-shortcode" data-target="<?php echo esc_attr( 'pwpl-sc-' . $table_id ); ?>">
                        <?php esc_html_e( 'Copy', 'planify-wp-pricing-lite' ); ?>
                    </button>
                </div>
                <p class="pwpl-copy-feedback" data-pwpl-feedback aria-live="polite"></p>
            </div>
        </div>
        <div class="pwpl-plans__actions">
            <a class="button button-primary" href="<?php echo esc_url( $add_plan ); ?>">
                <?php esc_html_e( 'Add Plan', 'planify-wp-pricing-lite' ); ?>
            </a>
            <a class="button" href="<?php echo esc_url( $back_url ); ?>">
                <?php esc_html_e( 'Back to Pricing Tables', 'planify-wp-pricing-lite' ); ?>
            </a>
            <a class="button-link" href="<?php echo esc_url( $edit_url ); ?>">
                <?php esc_html_e( 'Open table editor', 'planify-wp-pricing-lite' ); ?>
            </a>
        </div>
    </div>

    <div class="pwpl-plans__stats">
        <div class="pwpl-plans__stat">
            <span class="pwpl-plans__stat-label"><?php esc_html_e( 'Total plans', 'planify-wp-pricing-lite' ); ?></span>
            <span class="pwpl-plans__stat-value"><?php echo esc_html( $counts['total'] ); ?></span>
            <span class="pwpl-plans__stat-help"><?php esc_html_e( 'Across this table', 'planify-wp-pricing-lite' ); ?></span>
        </div>
        <div class="pwpl-plans__stat">
            <span class="pwpl-plans__stat-label"><?php esc_html_e( 'Published', 'planify-wp-pricing-lite' ); ?></span>
            <span class="pwpl-plans__stat-value"><?php echo esc_html( $counts['publish'] ); ?></span>
            <span class="pwpl-plans__stat-help"><?php printf( esc_html__( '%d draft', 'planify-wp-pricing-lite' ), (int) $counts['draft'] ); ?></span>
        </div>
        <div class="pwpl-plans__stat">
            <span class="pwpl-plans__stat-label"><?php esc_html_e( 'Featured plans', 'planify-wp-pricing-lite' ); ?></span>
            <span class="pwpl-plans__stat-value"><?php echo esc_html( $counts['featured'] ); ?></span>
            <span class="pwpl-plans__stat-help"><?php esc_html_e( 'Highlight in your layout', 'planify-wp-pricing-lite' ); ?></span>
        </div>
    </div>

    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="pwpl-plans__filters">
        <input type="hidden" name="page" value="pwpl-plans-dashboard" />
        <input type="hidden" name="pwpl_table" value="<?php echo esc_attr( $table_id ); ?>" />
        <div class="pwpl-plans__filters-left">
            <input type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search plans…', 'planify-wp-pricing-lite' ); ?>" />
            <div class="pwpl-plans__pills">
                <?php
                $statuses = [
                    'all'     => __( 'All', 'planify-wp-pricing-lite' ),
                    'publish' => __( 'Published', 'planify-wp-pricing-lite' ),
                    'draft'   => __( 'Draft', 'planify-wp-pricing-lite' ),
                ];
                foreach ( $statuses as $key => $label ) {
                    $is_active = ( $status_filter === $key );
                    $url = add_query_arg( [
                        'page'        => 'pwpl-plans-dashboard',
                        'pwpl_table'  => $table_id,
                        'status'      => $key,
                        'featured'    => $featured_only ? 1 : 0,
                        's'           => rawurlencode( $search_term ),
                    ], admin_url( 'admin.php' ) );
                    ?>
                    <a class="pwpl-pill <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="pwpl-plans__filters-right">
            <label class="pwpl-checkbox">
                <input type="checkbox" name="featured" value="1" <?php checked( $featured_only ); ?> />
                <span><?php esc_html_e( 'Featured only', 'planify-wp-pricing-lite' ); ?></span>
            </label>
            <button class="button"><?php esc_html_e( 'Apply', 'planify-wp-pricing-lite' ); ?></button>
        </div>
    </form>

    <div class="pwpl-plans-layout">
        <div class="pwpl-plans-layout__sidebar">
            <div class="pwpl-plan-list">
                <?php if ( empty( $plans ) ) : ?>
                    <div class="pwpl-plans__empty-list">
                        <span class="dashicons dashicons-index-card"></span>
                        <p><?php esc_html_e( 'Plans will appear here once you add them.', 'planify-wp-pricing-lite' ); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ( $plans as $index => $plan ) :
                        $is_draft    = ( 'publish' !== $plan->post_status );
                        $is_featured = (bool) get_post_meta( $plan->ID, PWPL_Meta::PLAN_FEATURED, true );
                        $subtitle    = get_post_meta( $plan->ID, '_pwpl_plan_subtitle', true );
                        $variants    = get_post_meta( $plan->ID, PWPL_Meta::PLAN_VARIANTS, true );
                        $summary     = $price_summary( $variants );
                        $short_status= $is_draft ? __( 'Draft', 'planify-wp-pricing-lite' ) : __( 'Published', 'planify-wp-pricing-lite' );
                        $dup_link    = wp_nonce_url( admin_url( 'admin-post.php?action=pwpl_duplicate_plan&plan_id=' . $plan->ID . '&pwpl_table=' . $table_id ), 'pwpl_duplicate_plan_' . $plan->ID );
                        $trash_link  = wp_nonce_url( admin_url( 'admin-post.php?action=pwpl_trash_plan&plan_id=' . $plan->ID . '&pwpl_table=' . $table_id ), 'pwpl_trash_plan_' . $plan->ID );

                        $dim_slugs = [ 'platform' => [], 'period' => [], 'location' => [] ];
                        $variants = $meta_helper->sanitize_variants( is_array( $variants ) ? $variants : [] );
                        foreach ( $variants as $variant ) {
                            foreach ( [ 'platform', 'period', 'location' ] as $dim ) {
                                $slug = sanitize_title( $variant[ $dim ] ?? '' );
                                if ( $slug ) {
                                    $dim_slugs[ $dim ][] = $slug;
                                }
                            }
                        }
                        $dim_labels_list = [];
                        foreach ( $dim_slugs as $dim => $slugs ) {
                            $unique = array_values( array_unique( array_filter( $slugs ) ) );
                            $labels = [];
                            foreach ( $unique as $slug ) {
                                $labels[] = $dim_labels( $dim, $slug );
                            }
                            if ( $labels ) {
                                $dim_labels_list[ $dim ] = $labels;
                            }
                        }
                        ?>
                        <div class="pwpl-plan-row" data-plan-id="<?php echo esc_attr( $plan->ID ); ?>" data-table-id="<?php echo esc_attr( $table_id ); ?>" role="button" tabindex="0">
                            <div class="pwpl-plan-row__header">
                                <div class="pwpl-plan-row__titles">
                                    <span class="pwpl-plan-row__title"><?php echo esc_html( $plan->post_title ?: sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan->ID ) ); ?></span>
                                    <span class="pwpl-plan-row__subtitle"><?php echo $subtitle ? esc_html( $subtitle ) : esc_html__( 'No subtitle yet', 'planify-wp-pricing-lite' ); ?></span>
                                </div>
                                <div class="pwpl-plan-row__status">
                                    <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $short_status ); ?></span>
                                    <?php if ( $is_featured ) : ?>
                                        <span class="pwpl-chip pwpl-chip--accent"><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="pwpl-plan-row__meta">
                                <?php if ( $summary['raw']['discount'] ?? false ) : ?>
                                    <span class="pwpl-plan-row__price"><?php echo esc_html( $format_price( $summary['raw']['sale'] ) ); ?><?php esc_html_e( '/mo', 'planify-wp-pricing-lite' ); ?></span>
                                    <span class="pwpl-plan-row__muted"><?php printf( esc_html__( 'was %s', 'planify-wp-pricing-lite' ), esc_html( $format_price( $summary['raw']['price'] ) ) ); ?></span>
                                <?php elseif ( isset( $summary['raw']['price'] ) && $summary['raw']['price'] ) : ?>
                                    <span class="pwpl-plan-row__price"><?php echo esc_html( $format_price( $summary['raw']['price'] ) ); ?><?php esc_html_e( '/mo', 'planify-wp-pricing-lite' ); ?></span>
                                <?php else : ?>
                                    <span class="pwpl-plan-row__muted"><?php esc_html_e( 'No pricing set', 'planify-wp-pricing-lite' ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $dim_labels_list ) ) : ?>
                                    <div class="pwpl-plan-row__chips">
                                        <?php
                                        $chip_limit = 4;
                                        $chips = [];
                                        foreach ( $dim_labels_list as $labels ) {
                                            foreach ( $labels as $label ) {
                                                $chips[] = $label;
                                            }
                                        }
                                        $chips = array_values( array_unique( array_filter( $chips ) ) );
                                        $extra = max( 0, count( $chips ) - $chip_limit );
                                        $chips = array_slice( $chips, 0, $chip_limit );
                                        foreach ( $chips as $chip ) : ?>
                                            <span class="pwpl-chip pwpl-chip--muted"><?php echo esc_html( $chip ); ?></span>
                                        <?php endforeach;
                                        if ( $extra > 0 ) : ?>
                                            <span class="pwpl-chip pwpl-chip--muted"><?php printf( esc_html__( '+%d more', 'planify-wp-pricing-lite' ), $extra ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pwpl-plan-row__actions">
                                <a class="pwpl-plan-row__link" href="<?php echo esc_url( get_edit_post_link( $plan->ID ) ); ?>"><?php esc_html_e( 'Open full editor', 'planify-wp-pricing-lite' ); ?></a>
                                <a class="pwpl-plan-row__link" href="<?php echo esc_url( $dup_link ); ?>"><?php esc_html_e( 'Duplicate', 'planify-wp-pricing-lite' ); ?></a>
                                <a class="pwpl-plan-row__link pwpl-plan-row__link--danger" href="<?php echo esc_url( $trash_link ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Move this plan to the trash?', 'planify-wp-pricing-lite' ) ); ?>');">
                                    <?php esc_html_e( 'Trash', 'planify-wp-pricing-lite' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="pwpl-plans-layout__detail">
            <?php if ( empty( $plans ) ) : ?>
                <div class="pwpl-plans-empty">
                    <div class="pwpl-plans-empty__header">
                        <div>
                            <p class="pwpl-plans-empty__eyebrow"><?php echo esc_html( $table->post_title ?: sprintf( __( 'Table #%d', 'planify-wp-pricing-lite' ), $table_id ) ); ?></p>
                            <h2><?php esc_html_e( 'Create your first plan', 'planify-wp-pricing-lite' ); ?></h2>
                            <p class="pwpl-plans-empty__subtitle">
                                <?php esc_html_e( 'Add plans to this table and configure specs, pricing variants, promotions, and featured flags.', 'planify-wp-pricing-lite' ); ?>
                            </p>
                        </div>
                        <div class="pwpl-plans-empty__icon" aria-hidden="true">
                            <span class="dashicons dashicons-admin-multisite"></span>
                        </div>
                    </div>
                    <div class="pwpl-plans-empty__content">
                        <div class="pwpl-plans-empty__actions">
                            <a class="button button-primary" href="<?php echo esc_url( $add_plan ); ?>">
                                <?php esc_html_e( 'Create first plan', 'planify-wp-pricing-lite' ); ?>
                            </a>
                            <p class="pwpl-plans-empty__hint"><?php esc_html_e( 'You can still open the full editor after creating a plan.', 'planify-wp-pricing-lite' ); ?></p>
                        </div>
                        <ul class="pwpl-plans-empty__list">
                            <li><?php esc_html_e( 'Each plan becomes a card inside this table.', 'planify-wp-pricing-lite' ); ?></li>
                            <li><?php esc_html_e( 'Add pricing variants across Platform × Period × Location.', 'planify-wp-pricing-lite' ); ?></li>
                            <li><?php esc_html_e( 'Mark plans as Featured and configure badges/promotions.', 'planify-wp-pricing-lite' ); ?></li>
                        </ul>
                    </div>
                </div>
            <?php else : ?>
                <div id="pwpl-plan-drawer-panel">
                    <div class="pwpl-plan-drawer__helper">
                        <?php esc_html_e( 'Select a plan on the left to edit it inline. Use “Open full editor” for edge cases.', 'planify-wp-pricing-lite' ); ?>
                    </div>
                    <div class="pwpl-drawer-placeholder"><?php esc_html_e( 'Select a plan to edit.', 'planify-wp-pricing-lite' ); ?></div>
                    <div id="pwpl-plan-drawer-inline" class="pwpl-drawer pwpl-drawer--inline" aria-hidden="true" hidden></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
