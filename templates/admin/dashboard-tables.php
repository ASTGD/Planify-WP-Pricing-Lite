<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$table_counts = $table_counts ?? wp_count_posts( 'pwpl_table' );
$plan_counts  = $plan_counts ?? wp_count_posts( 'pwpl_plan' );
$plan_map     = $plan_map ?? [];
$tables       = is_array( $tables ?? null ) ? $tables : [];

$published_tables = isset( $table_counts->publish ) ? (int) $table_counts->publish : 0;
$draft_tables     = isset( $table_counts->draft ) ? (int) $table_counts->draft : 0;
$published_plans  = isset( $plan_counts->publish ) ? (int) $plan_counts->publish : 0;
$draft_plans      = isset( $plan_counts->draft ) ? (int) $plan_counts->draft : 0;
$tables_without_plans = isset( $tables_without_plans ) ? (int) $tables_without_plans : 0;

$list_view_url = add_query_arg( 'post_type', 'pwpl_table', admin_url( 'edit.php' ) );
$create_table  = admin_url( 'post-new.php?post_type=pwpl_table' );
$create_plan   = admin_url( 'post-new.php?post_type=pwpl_plan' );
$settings_url  = admin_url( 'admin.php?page=pwpl-settings' );
?>
<div class="wrap pwpl-dashboard">
    <div class="pwpl-dash__header">
        <div class="pwpl-dash__titlegroup">
            <h1 class="pwpl-dash__title"><?php esc_html_e( 'Pricing Tables', 'planify-wp-pricing-lite' ); ?></h1>
            <p class="pwpl-dash__subtitle">
                <?php esc_html_e( 'Create a pricing table, attach plans, and embed it anywhere with the shortcode.', 'planify-wp-pricing-lite' ); ?>
            </p>
        </div>
        <div class="pwpl-dash__actions">
            <a class="button button-primary pwpl-dash__btn" href="<?php echo esc_url( $create_table ); ?>">
                <?php esc_html_e( 'Create Pricing Table', 'planify-wp-pricing-lite' ); ?>
            </a>
            <a class="button pwpl-dash__btn" href="<?php echo esc_url( $create_plan ); ?>">
                <?php esc_html_e( 'Create Plan', 'planify-wp-pricing-lite' ); ?>
            </a>
            <a class="button-link pwpl-dash__link" href="<?php echo esc_url( $list_view_url ); ?>">
                <?php esc_html_e( 'Switch to list view', 'planify-wp-pricing-lite' ); ?>
            </a>
        </div>
    </div>

    <div class="pwpl-dash__stats">
        <div class="pwpl-dash__stat">
            <div class="pwpl-dash__stat-label"><?php esc_html_e( 'Pricing Tables', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-dash__stat-value"><?php echo esc_html( $published_tables ); ?></div>
            <div class="pwpl-dash__stat-help">
                <?php printf( esc_html__( '%d draft', 'planify-wp-pricing-lite' ), $draft_tables ); ?>
            </div>
        </div>
        <div class="pwpl-dash__stat">
            <div class="pwpl-dash__stat-label"><?php esc_html_e( 'Plans', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-dash__stat-value"><?php echo esc_html( $published_plans ); ?></div>
            <div class="pwpl-dash__stat-help">
                <?php printf( esc_html__( '%d draft', 'planify-wp-pricing-lite' ), $draft_plans ); ?>
            </div>
        </div>
        <div class="pwpl-dash__stat">
            <div class="pwpl-dash__stat-label"><?php esc_html_e( 'Tables without plans', 'planify-wp-pricing-lite' ); ?></div>
            <div class="pwpl-dash__stat-value"><?php echo esc_html( $tables_without_plans ); ?></div>
            <div class="pwpl-dash__stat-help"><?php esc_html_e( 'Add plans to complete your tables.', 'planify-wp-pricing-lite' ); ?></div>
        </div>
    </div>

    <div class="pwpl-dash__layout">
        <div class="pwpl-dash__main">
            <?php if ( empty( $tables ) ) : ?>
                <div class="pwpl-dash__empty">
                    <p class="pwpl-dash__empty-title"><?php esc_html_e( 'No pricing tables yet', 'planify-wp-pricing-lite' ); ?></p>
                    <p class="pwpl-dash__empty-text"><?php esc_html_e( 'Create your first table and attach plans to start publishing pricing.', 'planify-wp-pricing-lite' ); ?></p>
                    <a class="button button-primary" href="<?php echo esc_url( $create_table ); ?>"><?php esc_html_e( 'Create Pricing Table', 'planify-wp-pricing-lite' ); ?></a>
                </div>
            <?php else : ?>
                <div class="pwpl-dash__grid">
                    <?php foreach ( $tables as $table ) :
                        $table_id   = (int) $table->ID;
                        $is_draft   = ( 'publish' !== $table->post_status );
                        $count_info = $plan_map[ $table_id ] ?? [ 'total' => 0, 'publish' => 0, 'draft' => 0 ];
                        $shortcode  = sprintf( '[pwpl_table id="%d"]', $table_id );
                        $input_id   = 'pwpl-shortcode-' . $table_id;
                        ?>
                        <div class="pwpl-card">
                            <div class="pwpl-card__header">
                                <div class="pwpl-card__titlewrap">
                                    <a class="pwpl-card__title" href="<?php echo esc_url( get_edit_post_link( $table_id ) ); ?>">
                                        <?php echo esc_html( get_the_title( $table_id ) ?: sprintf( __( 'Table #%d', 'planify-wp-pricing-lite' ), $table_id ) ); ?>
                                    </a>
                                    <span class="pwpl-card__status pwpl-card__status--<?php echo $is_draft ? 'draft' : 'pub'; ?>">
                                        <?php echo $is_draft ? esc_html__( 'Draft', 'planify-wp-pricing-lite' ) : esc_html__( 'Published', 'planify-wp-pricing-lite' ); ?>
                                    </span>
                                </div>
                                <div class="pwpl-card__date">
                                    <?php
                                    $modified = get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $table );
                                    printf(
                                        /* translators: %s: modified date */
                                        esc_html__( 'Updated %s', 'planify-wp-pricing-lite' ),
                                        esc_html( $modified )
                                    );
                                    ?>
                                </div>
                            </div>

                            <div class="pwpl-card__meta">
                                <div class="pwpl-card__pill">
                                    <?php printf(
                                        /* translators: %d: number of plans */
                                        esc_html__( '%d plan(s)', 'planify-wp-pricing-lite' ),
                                        (int) $count_info['total']
                                    ); ?>
                                </div>
                                <?php if ( (int) $count_info['draft'] > 0 ) : ?>
                                    <div class="pwpl-card__pill pwpl-card__pill--muted">
                                        <?php printf(
                                            /* translators: %d: number of drafts */
                                            esc_html__( '%d draft', 'planify-wp-pricing-lite' ),
                                            (int) $count_info['draft']
                                        ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="pwpl-card__shortcode">
                                <label for="<?php echo esc_attr( $input_id ); ?>">
                                    <?php esc_html_e( 'Shortcode', 'planify-wp-pricing-lite' ); ?>
                                </label>
                                <div class="pwpl-card__copywrap">
                                    <input type="text" readonly id="<?php echo esc_attr( $input_id ); ?>" class="widefat" value="<?php echo esc_attr( $shortcode ); ?>" />
                                    <button type="button" class="button pwpl-copy-shortcode" data-target="<?php echo esc_attr( $input_id ); ?>">
                                        <?php esc_html_e( 'Copy', 'planify-wp-pricing-lite' ); ?>
                                    </button>
                                </div>
                                <p class="pwpl-copy-feedback" data-pwpl-feedback aria-live="polite"></p>
                            </div>

                            <div class="pwpl-card__actions">
                                <a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $table_id ) ); ?>">
                                    <?php esc_html_e( 'Edit Table', 'planify-wp-pricing-lite' ); ?>
                                </a>
                                <a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id ], admin_url( 'admin.php' ) ) ); ?>">
                                    <?php esc_html_e( 'Manage Plans', 'planify-wp-pricing-lite' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="pwpl-dash__aside">
            <div class="pwpl-help">
                <h2><?php esc_html_e( 'Help & Shortcodes', 'planify-wp-pricing-lite' ); ?></h2>
                <p><?php esc_html_e( 'Embed a table anywhere:', 'planify-wp-pricing-lite' ); ?></p>
                <code>[pwpl_table id="123"]</code>
                <p><?php esc_html_e( 'Replace 123 with your Pricing Table ID.', 'planify-wp-pricing-lite' ); ?></p>
                <ul class="pwpl-help__links">
                    <li><a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'planify-wp-pricing-lite' ); ?></a></li>
                    <li><a href="<?php echo esc_url( $list_view_url ); ?>"><?php esc_html_e( 'All Tables (List)', 'planify-wp-pricing-lite' ); ?></a></li>
                </ul>
            </div>
        </aside>
    </div>
</div>
