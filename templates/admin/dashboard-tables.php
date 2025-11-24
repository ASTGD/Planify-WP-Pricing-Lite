<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$table_counts = $table_counts ?? wp_count_posts( 'pwpl_table' );
$plan_counts  = $plan_counts ?? wp_count_posts( 'pwpl_plan' );
$plan_map     = $plan_map ?? [];
$tables       = is_array( $tables ?? null ) ? $tables : [];

$published_tables = isset( $table_counts->publish ) ? (int) $table_counts->publish : 0;
$draft_tables     = isset( $table_counts->draft ) ? (int) $table_counts->draft : 0;
$total_tables     = $published_tables + $draft_tables;
$published_plans  = isset( $plan_counts->publish ) ? (int) $plan_counts->publish : 0;
$draft_plans      = isset( $plan_counts->draft ) ? (int) $plan_counts->draft : 0;
$tables_without_plans = isset( $tables_without_plans ) ? (int) $tables_without_plans : 0;

$list_view_url = add_query_arg( 'post_type', 'pwpl_table', admin_url( 'edit.php' ) );
$create_table  = admin_url( 'post-new.php?post_type=pwpl_table' );
$create_plan   = admin_url( 'post-new.php?post_type=pwpl_plan' );
$settings_url  = admin_url( 'admin.php?page=pwpl-settings' );
$has_tables    = ! empty( $tables ) || $total_tables > 0;
$needs_plans   = $tables_without_plans > 0;
?>
<div class="wrap pwpl-dashboard">
    <?php if ( ! $has_tables ) : ?>
        <div class="pwpl-empty">
            <div class="pwpl-empty__hero">
                <div class="pwpl-empty__intro">
                    <h1 class="pwpl-empty__title"><?php esc_html_e( 'Welcome to Planify Pricing Tables', 'planify-wp-pricing-lite' ); ?></h1>
                    <p class="pwpl-empty__subtitle">
                        <?php esc_html_e( 'Create beautiful, conversion-ready pricing tables in minutes. Start with your first table, then add plans and embed anywhere.', 'planify-wp-pricing-lite' ); ?>
                    </p>
                    <div class="pwpl-empty__actions">
                        <a class="button button-primary" href="<?php echo esc_url( $create_table ); ?>">
                            <?php esc_html_e( 'Create your first pricing table', 'planify-wp-pricing-lite' ); ?>
                        </a>
                        <a class="button" href="<?php echo esc_url( $settings_url ); ?>">
                            <?php esc_html_e( 'Configure currency & dimensions', 'planify-wp-pricing-lite' ); ?>
                        </a>
                    </div>
                    <p class="pwpl-empty__hint">
                        <?php esc_html_e( 'You can add plans after creating your table, then embed it with a simple shortcode.', 'planify-wp-pricing-lite' ); ?>
                    </p>
                </div>
                <div class="pwpl-empty__visual" aria-hidden="true">
                    <div class="pwpl-empty__icon">
                        <span class="dashicons dashicons-index-card"></span>
                        <span class="pwpl-empty__spark"></span>
                    </div>
                </div>
            </div>

            <div class="pwpl-empty__grid">
                <div class="pwpl-empty__steps">
                    <h2><?php esc_html_e( 'Getting started', 'planify-wp-pricing-lite' ); ?></h2>
                    <div class="pwpl-stepgrid">
                        <div class="pwpl-step">
                            <span class="pwpl-step__icon dashicons dashicons-yes"></span>
                            <div>
                                <h3><?php esc_html_e( 'Set your currency & dimensions', 'planify-wp-pricing-lite' ); ?></h3>
                                <p><?php esc_html_e( 'Choose currency, platforms, periods, and locations for all tables.', 'planify-wp-pricing-lite' ); ?></p>
                                <a class="pwpl-step__link" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Open Settings', 'planify-wp-pricing-lite' ); ?></a>
                            </div>
                        </div>
                        <div class="pwpl-step">
                            <span class="pwpl-step__icon dashicons dashicons-yes"></span>
                            <div>
                                <h3><?php esc_html_e( 'Create your first pricing table', 'planify-wp-pricing-lite' ); ?></h3>
                                <p><?php esc_html_e( 'Name it (e.g. VPS Hosting) and pick a theme in the editor.', 'planify-wp-pricing-lite' ); ?></p>
                                <a class="pwpl-step__link" href="<?php echo esc_url( $create_table ); ?>"><?php esc_html_e( 'Add Pricing Table', 'planify-wp-pricing-lite' ); ?></a>
                            </div>
                        </div>
                        <div class="pwpl-step">
                            <span class="pwpl-step__icon dashicons dashicons-yes"></span>
                            <div>
                                <h3><?php esc_html_e( 'Add plans to your table', 'planify-wp-pricing-lite' ); ?></h3>
                                <p><?php esc_html_e( 'Each plan is a card with specs and pricing variants for platform/period/location.', 'planify-wp-pricing-lite' ); ?></p>
                            </div>
                        </div>
                        <div class="pwpl-step">
                            <span class="pwpl-step__icon dashicons dashicons-yes"></span>
                            <div>
                                <h3><?php esc_html_e( 'Embed on a page', 'planify-wp-pricing-lite' ); ?></h3>
                                <p><?php esc_html_e( 'Drop the shortcode anywhere shortcodes are supported.', 'planify-wp-pricing-lite' ); ?></p>
                                <code class="pwpl-step__code">[pwpl_table id="123"]</code>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pwpl-empty__learn">
                    <h2><?php esc_html_e( 'Learn Planify', 'planify-wp-pricing-lite' ); ?></h2>
                    <p><?php esc_html_e( 'Key concepts to get you moving fast:', 'planify-wp-pricing-lite' ); ?></p>
                    <ul>
                        <li><a href="<?php echo esc_url( $create_table ); ?>"><?php esc_html_e( 'What is a Pricing Table?', 'planify-wp-pricing-lite' ); ?></a></li>
                        <li><a href="<?php echo esc_url( $create_plan ); ?>"><?php esc_html_e( 'What is a Plan?', 'planify-wp-pricing-lite' ); ?></a></li>
                        <li><a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'How variants work (Platform × Period × Location)', 'planify-wp-pricing-lite' ); ?></a></li>
                    </ul>
                    <div class="pwpl-empty__note">
                        <span class="dashicons dashicons-clipboard"></span>
                        <div>
                            <strong><?php esc_html_e( 'Quick embed', 'planify-wp-pricing-lite' ); ?></strong>
                            <p><?php esc_html_e( 'Publish a table and use its shortcode to place it on pages or posts.', 'planify-wp-pricing-lite' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="pwpl-dash__header">
            <div class="pwpl-dash__titlegroup">
                <h1 class="pwpl-dash__title"><?php esc_html_e( 'Pricing Tables', 'planify-wp-pricing-lite' ); ?></h1>
                <p class="pwpl-dash__subtitle">
                    <?php esc_html_e( 'Review your tables, manage plans, and copy shortcodes for pages and posts.', 'planify-wp-pricing-lite' ); ?>
                </p>
            </div>
            <div class="pwpl-dash__actions">
                <a class="button button-primary pwpl-dash__btn" href="<?php echo esc_url( $create_table ); ?>">
                    <?php esc_html_e( 'Add Pricing Table', 'planify-wp-pricing-lite' ); ?>
                </a>
                <a class="button pwpl-dash__btn" href="<?php echo esc_url( $create_plan ); ?>">
                    <?php esc_html_e( 'Create Plan', 'planify-wp-pricing-lite' ); ?>
                </a>
                <a class="pwpl-dash__link" href="<?php echo esc_url( $settings_url ); ?>">
                    <?php esc_html_e( 'Global Settings', 'planify-wp-pricing-lite' ); ?>
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
                    <?php printf( esc_html__( '%d draft tables', 'planify-wp-pricing-lite' ), $draft_tables ); ?>
                </div>
            </div>
            <div class="pwpl-dash__stat">
                <div class="pwpl-dash__stat-label"><?php esc_html_e( 'Plans', 'planify-wp-pricing-lite' ); ?></div>
                <div class="pwpl-dash__stat-value"><?php echo esc_html( $published_plans ); ?></div>
                <div class="pwpl-dash__stat-help">
                    <?php printf( esc_html__( '%d draft plans', 'planify-wp-pricing-lite' ), $draft_plans ); ?>
                </div>
            </div>
            <div class="pwpl-dash__stat<?php echo $needs_plans ? ' pwpl-dash__stat--alert' : ''; ?>">
                <div class="pwpl-dash__stat-label"><?php esc_html_e( 'Tables without plans', 'planify-wp-pricing-lite' ); ?></div>
                <div class="pwpl-dash__stat-value"><?php echo esc_html( $tables_without_plans ); ?></div>
                <div class="pwpl-dash__stat-help">
                    <?php esc_html_e( 'Add plans to complete your tables.', 'planify-wp-pricing-lite' ); ?>
                </div>
            </div>
        </div>

        <div class="pwpl-dash__layout">
            <div class="pwpl-dash__main">
                <div class="pwpl-dash__grid">
                    <?php foreach ( $tables as $table ) :
                        $table_id   = (int) $table->ID;
                        $is_draft   = ( 'publish' !== $table->post_status );
                        $count_info = $plan_map[ $table_id ] ?? [ 'total' => 0, 'publish' => 0, 'draft' => 0 ];
                        $shortcode  = sprintf( '[pwpl_table id="%d"]', $table_id );
                        $input_id   = 'pwpl-shortcode-' . $table_id;
                        ?>
                        <div class="pwpl-card">
                            <div class="pwpl-card__header pwpl-card__section">
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

                            <div class="pwpl-card__meta pwpl-card__section">
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

                            <div class="pwpl-card__shortcode pwpl-card__section">
                                <div class="pwpl-card__shortcode-head">
                                    <label for="<?php echo esc_attr( $input_id ); ?>">
                                        <?php
                                        printf(
                                            /* translators: %s: table ID */
                                            esc_html__( 'Shortcode (ID %s)', 'planify-wp-pricing-lite' ),
                                            esc_html( $table_id )
                                        );
                                        ?>
                                    </label>
                                    <span class="pwpl-card__helper"><?php esc_html_e( 'Copy to embed on pages or posts.', 'planify-wp-pricing-lite' ); ?></span>
                                </div>
                                <div class="pwpl-card__copywrap">
                                    <input type="text" readonly id="<?php echo esc_attr( $input_id ); ?>" class="widefat" value="<?php echo esc_attr( $shortcode ); ?>" />
                                    <button type="button" class="button pwpl-copy-shortcode" data-target="<?php echo esc_attr( $input_id ); ?>">
                                        <?php esc_html_e( 'Copy', 'planify-wp-pricing-lite' ); ?>
                                    </button>
                                </div>
                                <p class="pwpl-copy-feedback" data-pwpl-feedback aria-live="polite"></p>
                            </div>

                            <div class="pwpl-card__actions pwpl-card__section">
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
            </div>

            <aside class="pwpl-dash__aside">
                <div class="pwpl-help">
                    <h2><?php esc_html_e( 'Help & Shortcodes', 'planify-wp-pricing-lite' ); ?></h2>
                    <p><?php esc_html_e( 'Embed a table anywhere:', 'planify-wp-pricing-lite' ); ?></p>
                    <code>[pwpl_table id="123"]</code>
                    <p><?php esc_html_e( 'Replace 123 with your Pricing Table ID.', 'planify-wp-pricing-lite' ); ?></p>
                    <p class="pwpl-help__tip"><?php esc_html_e( 'Use “Manage Plans” on each card to edit the plans shown in that table.', 'planify-wp-pricing-lite' ); ?></p>
                    <ul class="pwpl-help__links">
                        <li><a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'planify-wp-pricing-lite' ); ?></a></li>
                        <li><a href="<?php echo esc_url( $list_view_url ); ?>"><?php esc_html_e( 'All Tables (List)', 'planify-wp-pricing-lite' ); ?></a></li>
                    </ul>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
