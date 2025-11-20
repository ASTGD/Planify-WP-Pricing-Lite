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

    <div class="pwpl-plans__layout">
        <div class="pwpl-plans__main">
            <?php if ( empty( $plans ) ) : ?>
                <div class="pwpl-plans__empty">
                    <p class="pwpl-plans__empty-title"><?php esc_html_e( 'No plans yet', 'planify-wp-pricing-lite' ); ?></p>
                    <p class="pwpl-plans__empty-text"><?php esc_html_e( 'Create a plan to add it to this pricing table.', 'planify-wp-pricing-lite' ); ?></p>
                    <a class="button button-primary" href="<?php echo esc_url( $add_plan ); ?>"><?php esc_html_e( 'Add Plan', 'planify-wp-pricing-lite' ); ?></a>
                </div>
            <?php else : ?>
                <div class="pwpl-plans__grid">
                    <?php foreach ( $plans as $plan ) :
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
                        <div class="pwpl-plan-card" data-plan-id="<?php echo esc_attr( $plan->ID ); ?>" data-table-id="<?php echo esc_attr( $table_id ); ?>">
                            <div class="pwpl-plan-card__header">
                                <div class="pwpl-plan-card__titlewrap">
                                    <a class="pwpl-plan-card__title" href="<?php echo esc_url( get_edit_post_link( $plan->ID ) ); ?>">
                                        <?php echo esc_html( $plan->post_title ?: sprintf( __( 'Plan #%d', 'planify-wp-pricing-lite' ), $plan->ID ) ); ?>
                                    </a>
                                    <span class="pwpl-plan-card__status pwpl-plan-card__status--<?php echo $is_draft ? 'draft' : 'pub'; ?>">
                                        <?php echo esc_html( $short_status ); ?>
                                    </span>
                                    <?php if ( $is_featured ) : ?>
                                        <span class="pwpl-plan-card__badge"><?php esc_html_e( 'Featured', 'planify-wp-pricing-lite' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="pwpl-plan-card__date">
                                    <?php
                                    $modified = get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $plan );
                                    printf( esc_html__( 'Updated %s', 'planify-wp-pricing-lite' ), esc_html( $modified ) );
                                    ?>
                                </div>
                            </div>

                            <div class="pwpl-plan-card__subtitle">
                                <?php echo $subtitle ? esc_html( $subtitle ) : '<span class="pwpl-plan-card__muted">' . esc_html__( 'No subtitle yet', 'planify-wp-pricing-lite' ) . '</span>'; ?>
                            </div>

                            <div class="pwpl-plan-card__pricing">
                                <?php if ( $summary['raw']['discount'] ?? false ) : ?>
                                    <span class="pwpl-plan-card__price pwpl-plan-card__price--strike"><?php echo esc_html( $format_price( $summary['raw']['price'] ) ); ?></span>
                                    <span class="pwpl-plan-card__price"><?php echo esc_html( $format_price( $summary['raw']['sale'] ) ); ?><span class="pwpl-plan-card__unit">/mo</span></span>
                                <?php elseif ( isset( $summary['raw']['price'] ) && $summary['raw']['price'] ) : ?>
                                    <span class="pwpl-plan-card__price"><?php echo esc_html( $format_price( $summary['raw']['price'] ) ); ?><span class="pwpl-plan-card__unit">/mo</span></span>
                                <?php else : ?>
                                    <span class="pwpl-plan-card__muted"><?php esc_html_e( 'No pricing set', 'planify-wp-pricing-lite' ); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ( ! empty( $dim_labels_list ) ) : ?>
                                <div class="pwpl-plan-card__chips">
                                    <?php
                                    $chip_limit = 6;
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
                                        <span class="pwpl-chip"><?php echo esc_html( $chip ); ?></span>
                                    <?php endforeach;
                                    if ( $extra > 0 ) : ?>
                                        <span class="pwpl-chip pwpl-chip--muted"><?php printf( esc_html__( '+%d more', 'planify-wp-pricing-lite' ), $extra ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="pwpl-plan-card__actions">
                                <a class="button button-primary pwpl-plan-card__edit" href="<?php echo esc_url( get_edit_post_link( $plan->ID ) ); ?>">
                                    <?php esc_html_e( 'Edit Plan', 'planify-wp-pricing-lite' ); ?>
                                </a>
                                <a class="button" href="<?php echo esc_url( $dup_link ); ?>">
                                    <?php esc_html_e( 'Duplicate', 'planify-wp-pricing-lite' ); ?>
                                </a>
                                <a class="button-link pwpl-plan-card__trash" href="<?php echo esc_url( $trash_link ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Move this plan to the trash?', 'planify-wp-pricing-lite' ) ); ?>');">
                                    <?php esc_html_e( 'Trash', 'planify-wp-pricing-lite' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="pwpl-plans__aside">
            <div class="pwpl-help">
                <h2><?php esc_html_e( 'How plans work', 'planify-wp-pricing-lite' ); ?></h2>
                <p><?php esc_html_e( 'Plans attach to a Pricing Table and inherit its theme and layout. Variants (platform/period/location) are configured inside each plan.', 'planify-wp-pricing-lite' ); ?></p>
                <ul class="pwpl-help__links">
                    <li><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Open table editor', 'planify-wp-pricing-lite' ); ?></a></li>
                    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pwpl-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'planify-wp-pricing-lite' ); ?></a></li>
                    <li><a href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Back to Pricing Tables', 'planify-wp-pricing-lite' ); ?></a></li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<?php
// Optional server-rendered drawer when selected_plan is provided.
if ( ! empty( $selected_plan ) ) :
    $admin_helper = new PWPL_Admin();
    $drawer_markup = $admin_helper->render_plan_drawer_markup( (int) $selected_plan, (int) $table_id );
    ?>
    <div id="pwpl-plan-drawer-overlay" class="pwpl-drawer__overlay"></div>
    <div id="pwpl-plan-drawer" class="pwpl-drawer" aria-hidden="false">
        <?php echo $drawer_markup; ?>
    </div>
<?php else : ?>
    <div id="pwpl-plan-drawer-overlay" class="pwpl-drawer__overlay" hidden></div>
    <div id="pwpl-plan-drawer" class="pwpl-drawer" aria-hidden="true" hidden></div>
<?php endif; ?>
