<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Admin {
    public function init() {
        add_action( 'admin_menu', [ $this, 'register_dashboard_page' ], 9 );
        add_action( 'admin_menu', [ $this, 'register_plans_dashboard_page' ], 9 );
        add_action( 'admin_post_pwpl_duplicate_plan', [ $this, 'handle_duplicate_plan' ] );
        add_action( 'admin_post_pwpl_trash_plan', [ $this, 'handle_trash_plan' ] );
        add_action( 'admin_post_pwpl_create_plan_for_table', [ $this, 'handle_create_plan_for_table' ] );
        add_action( 'admin_post_pwpl_save_plan_drawer', [ $this, 'handle_save_plan_drawer' ] );
        add_action( 'wp_ajax_pwpl_render_plan_drawer', [ $this, 'ajax_render_plan_drawer' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'manage_edit-pwpl_plan_columns', [ $this, 'plan_columns' ] );
        add_action( 'manage_pwpl_plan_posts_custom_column', [ $this, 'render_plan_columns' ], 10, 2 );
        add_filter( 'manage_edit-pwpl_plan_sortable_columns', [ $this, 'sortable_plan_columns' ] );
        add_action( 'pre_get_posts', [ $this, 'order_plan_admin_query' ] );
    }

    public function enqueue( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $post_type = $screen && isset( $screen->post_type ) ? $screen->post_type : '';
        $screen_id = $screen && isset( $screen->id ) ? $screen->id : '';
        $is_plugin_screen = in_array( $post_type, [ 'pwpl_table', 'pwpl_plan' ], true ) || ( $screen_id && false !== strpos( $screen_id, 'pwpl' ) );
        if ( ! $is_plugin_screen ) {
            return; // Only load on our plugin screens
        }

        $css = PWPL_DIR . 'assets/admin/css/admin.css';
        $js  = PWPL_DIR . 'assets/admin/js/admin.js';

        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'pwpl-admin', PWPL_URL . 'assets/admin/css/admin.css', [], filemtime( $css ) );
        }
        if ( file_exists( $js ) ) {
            wp_enqueue_script( 'pwpl-admin', PWPL_URL . 'assets/admin/js/admin.js', [ 'jquery', 'wp-util' ], filemtime( $js ), true );
            wp_localize_script( 'pwpl-admin', 'PWPL_Admin', [
                'copySuccess' => __( 'Shortcode copied to clipboard.', 'planify-wp-pricing-lite' ),
                'copyError'   => __( 'Unable to copy. Please copy manually.', 'planify-wp-pricing-lite' ),
            ] );
        }

        // Dashboard-only styles
        if ( $screen_id && false !== strpos( $screen_id, 'pwpl-tables-dashboard' ) ) {
            $dash_css = PWPL_DIR . 'assets/admin/css/dashboard.css';
            if ( file_exists( $dash_css ) ) {
                wp_enqueue_style( 'pwpl-admin-dashboard', PWPL_URL . 'assets/admin/css/dashboard.css', [ 'pwpl-admin' ], filemtime( $dash_css ) );
            }
        }
        if ( $screen_id && false !== strpos( $screen_id, 'pwpl-plans-dashboard' ) ) {
            $plans_css = PWPL_DIR . 'assets/admin/css/plans-dashboard.css';
            if ( file_exists( $plans_css ) ) {
                wp_enqueue_style( 'pwpl-admin-plans-dashboard', PWPL_URL . 'assets/admin/css/plans-dashboard.css', [ 'pwpl-admin' ], filemtime( $plans_css ) );
            }
            $drawer_css = PWPL_DIR . 'assets/admin/css/plan-drawer.css';
            if ( file_exists( $drawer_css ) ) {
                wp_enqueue_style( 'pwpl-plan-drawer', PWPL_URL . 'assets/admin/css/plan-drawer.css', [ 'pwpl-admin-plans-dashboard' ], filemtime( $drawer_css ) );
            }
            $plans_js = PWPL_DIR . 'assets/admin/js/plans-dashboard.js';
            if ( file_exists( $plans_js ) ) {
                wp_enqueue_script( 'pwpl-admin-plans-dashboard', PWPL_URL . 'assets/admin/js/plans-dashboard.js', [ 'jquery', 'wp-util' ], filemtime( $plans_js ), true );
                wp_localize_script( 'pwpl-admin-plans-dashboard', 'PWPL_Plans', [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'pwpl_plans_nonce' ),
                    'i18n'    => [
                        'loading' => __( 'Loading…', 'planify-wp-pricing-lite' ),
                        'error'   => __( 'Unable to load the plan.', 'planify-wp-pricing-lite' ),
                    ],
                ] );
            }
        }
    }

    public function plan_columns( $columns ) {
        $columns['menu_order'] = __( 'Order', 'planify-wp-pricing-lite' );
        return $columns;
    }

    public function render_plan_columns( $column, $post_id ) {
        if ( 'menu_order' === $column ) {
            echo (int) get_post_field( 'menu_order', $post_id );
        }
    }

    public function sortable_plan_columns( $columns ) {
        $columns['menu_order'] = 'menu_order';
        return $columns;
    }

    public function order_plan_admin_query( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }
        $post_type = $query->get( 'post_type' );
        if ( is_array( $post_type ) ) {
            if ( ! in_array( 'pwpl_plan', $post_type, true ) ) {
                return;
            }
        } elseif ( 'pwpl_plan' !== $post_type ) {
            return;
        }

        $orderby = $query->get( 'orderby' );
        if ( ! $orderby ) {
            $query->set( 'orderby', [ 'menu_order' => 'ASC', 'title' => 'ASC' ] );
        }
    }

    /**
     * Register the top-level Pricing Tables menu and dashboard page.
     */
    public function register_dashboard_page() {
        add_menu_page(
            __( 'Pricing Tables', 'planify-wp-pricing-lite' ),
            __( 'Planify', 'planify-wp-pricing-lite' ),
            'edit_posts',
            'pwpl-tables-dashboard',
            [ $this, 'render_tables_dashboard' ],
            'dashicons-index-card',
            25
        );
    }

    /**
     * Render the custom Pricing Tables dashboard cards view.
     */
    public function render_tables_dashboard() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'planify-wp-pricing-lite' ) );
        }

        // Handle notices (e.g., duplication)
        if ( ! empty( $_GET['pwpl_notice'] ) ) {
            $notice = sanitize_key( $_GET['pwpl_notice'] );
            $messages = [
                'plan_duplicated' => __( 'Plan duplicated.', 'planify-wp-pricing-lite' ),
                'plan_deleted'    => __( 'Plan moved to trash.', 'planify-wp-pricing-lite' ),
                'plan_error'      => __( 'Unable to complete the action.', 'planify-wp-pricing-lite' ),
            ];
            if ( isset( $messages[ $notice ] ) ) {
                printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $notice ] ) );
            }
        }

        $meta_helper = new PWPL_Meta();

        $tables = get_posts( [
            'post_type'      => 'pwpl_table',
            'post_status'    => [ 'publish', 'draft' ],
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'posts_per_page' => 20,
            'no_found_rows'  => true,
        ] );

        $table_counts = wp_count_posts( 'pwpl_table' );
        $plan_counts  = wp_count_posts( 'pwpl_plan' );

        $plan_map = $this->map_plans_to_tables();
        $tables_without_plans = 0;
        foreach ( $tables as $table ) {
            $tid = (int) $table->ID;
            $has_plans = isset( $plan_map[ $tid ] ) && $plan_map[ $tid ]['total'] > 0;
            if ( ! $has_plans ) {
                $tables_without_plans++;
            }
        }

        $vars = [
            'tables'               => $tables,
            'table_counts'         => $table_counts,
            'plan_counts'          => $plan_counts,
            'plan_map'             => $plan_map,
            'tables_without_plans' => $tables_without_plans,
        ];

        $template = trailingslashit( PWPL_DIR ) . 'templates/admin/dashboard-tables.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__( 'Pricing Tables', 'planify-wp-pricing-lite' ) . '</h1><p>' . esc_html__( 'Dashboard template missing.', 'planify-wp-pricing-lite' ) . '</p></div>';
        }
    }

    /**
     * Return map of table_id => [ 'total' => int, 'publish' => int, 'draft' => int ]
     */
    private function map_plans_to_tables() {
        $plan_map = [];
        $plans = get_posts( [
            'post_type'      => 'pwpl_plan',
            'post_status'    => [ 'publish', 'draft' ],
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ] );

        foreach ( $plans as $plan ) {
            $table_id = (int) get_post_meta( $plan->ID, PWPL_Meta::PLAN_TABLE_ID, true );
            if ( $table_id <= 0 ) {
                continue;
            }
            if ( ! isset( $plan_map[ $table_id ] ) ) {
                $plan_map[ $table_id ] = [
                    'total'   => 0,
                    'publish' => 0,
                    'draft'   => 0,
                ];
            }
            $plan_map[ $table_id ]['total']++;
            $plan_map[ $table_id ][ $plan->post_status === 'publish' ? 'publish' : 'draft' ]++;
        }

        return $plan_map;
    }

    /**
     * Hidden submenu and renderer for per-table Plans Dashboard.
     */
    public function register_plans_dashboard_page() {
        add_submenu_page(
            null,
            __( 'Plans Dashboard', 'planify-wp-pricing-lite' ),
            __( 'Plans Dashboard', 'planify-wp-pricing-lite' ),
            'edit_posts',
            'pwpl-plans-dashboard',
            [ $this, 'render_plans_dashboard' ]
        );
    }

    public function render_plans_dashboard() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'planify-wp-pricing-lite' ) );
        }

        $table_id = isset( $_GET['pwpl_table'] ) ? (int) $_GET['pwpl_table'] : 0;
        $table    = $table_id ? get_post( $table_id ) : null;
        if ( ! $table || $table->post_type !== 'pwpl_table' ) {
            wp_die( __( 'Invalid table.', 'planify-wp-pricing-lite' ) );
        }

        $status_filter  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
        $search_term    = isset( $_GET['s'] ) ? wp_unslash( $_GET['s'] ) : '';
        $search_term    = is_string( $search_term ) ? trim( $search_term ) : '';
        $featured_only  = ! empty( $_GET['featured'] );

        $plans = get_posts( [
            'post_type'      => 'pwpl_plan',
            'post_status'    => [ 'publish', 'draft' ],
            'posts_per_page' => -1,
            'meta_key'       => PWPL_Meta::PLAN_TABLE_ID,
            'meta_value'     => $table_id,
            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
            'no_found_rows'  => true,
        ] );

        // Filter plans in PHP for search/status/featured
        $plans = array_values( array_filter( $plans, function( $plan ) use ( $status_filter, $search_term, $featured_only ) {
            if ( $featured_only ) {
                $is_feat = (bool) get_post_meta( $plan->ID, PWPL_Meta::PLAN_FEATURED, true );
                if ( ! $is_feat ) {
                    return false;
                }
            }
            if ( $status_filter === 'publish' && $plan->post_status !== 'publish' ) {
                return false;
            }
            if ( $status_filter === 'draft' && $plan->post_status !== 'draft' ) {
                return false;
            }
            if ( $search_term !== '' ) {
                $haystack = strtolower( $plan->post_title );
                if ( false === strpos( $haystack, strtolower( $search_term ) ) ) {
                    return false;
                }
            }
            return true;
        } ) );

        $counts = [
            'total'    => 0,
            'publish'  => 0,
            'draft'    => 0,
            'featured' => 0,
        ];

        foreach ( $plans as $plan ) {
            $counts['total']++;
            if ( 'publish' === $plan->post_status ) {
                $counts['publish']++;
            } else {
                $counts['draft']++;
            }
            $is_featured = (bool) get_post_meta( $plan->ID, PWPL_Meta::PLAN_FEATURED, true );
            if ( $is_featured ) {
                $counts['featured']++;
            }
        }

        $settings = new PWPL_Settings();
        $catalog = [
            'platform' => $this->index_by_slug( (array) $settings->get( 'platforms' ) ),
            'period'   => $this->index_by_slug( (array) $settings->get( 'periods' ) ),
            'location' => $this->index_by_slug( (array) $settings->get( 'locations' ) ),
        ];

        $vars = [
            'table'        => $table,
            'table_id'     => $table_id,
            'plans'        => $plans,
            'counts'       => $counts,
            'status_filter'=> $status_filter,
            'search_term'  => $search_term,
            'featured_only'=> $featured_only,
            'catalog'      => $catalog,
            'selected_plan'=> isset( $_GET['selected_plan'] ) ? (int) $_GET['selected_plan'] : 0,
        ];

        $template = trailingslashit( PWPL_DIR ) . 'templates/admin/plans-dashboard.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__( 'Plans', 'planify-wp-pricing-lite' ) . '</h1><p>' . esc_html__( 'Dashboard template missing.', 'planify-wp-pricing-lite' ) . '</p></div>';
        }
    }

    /**
     * Handle plan duplication via admin-post.
     */
    public function handle_duplicate_plan() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'planify-wp-pricing-lite' ) );
        }
        $plan_id  = isset( $_GET['plan_id'] ) ? (int) $_GET['plan_id'] : 0;
        $table_id = isset( $_GET['pwpl_table'] ) ? (int) $_GET['pwpl_table'] : 0;
        $nonce    = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'pwpl_duplicate_plan_' . $plan_id ) || $plan_id <= 0 || $table_id <= 0 ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-tables-dashboard', 'pwpl_notice' => 'plan_error' ], admin_url( 'admin.php' ) ) );
            exit;
        }
        $plan = get_post( $plan_id );
        if ( ! $plan || $plan->post_type !== 'pwpl_plan' ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-tables-dashboard', 'pwpl_notice' => 'plan_error' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        $new_title = $plan->post_title ? $plan->post_title . ' (Copy)' : sprintf( __( 'Plan #%d (Copy)', 'planify-wp-pricing-lite' ), $plan_id );
        $new_id = wp_insert_post( [
            'post_type'   => 'pwpl_plan',
            'post_status' => 'draft',
            'post_title'  => $new_title,
            'menu_order'  => $plan->menu_order,
        ], true );

        if ( is_wp_error( $new_id ) || ! $new_id ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id, 'pwpl_notice' => 'plan_error' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        // Copy relevant meta
        $meta_keys = [
            PWPL_Meta::PLAN_TABLE_ID,
            PWPL_Meta::PLAN_SPECS,
            PWPL_Meta::PLAN_VARIANTS,
            PWPL_Meta::PLAN_FEATURED,
            PWPL_Meta::PLAN_BADGES_OVERRIDE,
            '_pwpl_plan_subtitle',
            PWPL_Meta::PLAN_BADGE_SHADOW,
            PWPL_Meta::PLAN_THEME,
        ];
        foreach ( $meta_keys as $key ) {
            $val = get_post_meta( $plan_id, $key, true );
            if ( ! empty( $val ) ) {
                update_post_meta( $new_id, $key, $val );
            }
        }
        // Ensure table assignment
        update_post_meta( $new_id, PWPL_Meta::PLAN_TABLE_ID, $table_id );

        wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id, 'pwpl_notice' => 'plan_duplicated' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Move a plan to trash from the Plans Dashboard.
     */
    public function handle_trash_plan() {
        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'planify-wp-pricing-lite' ) );
        }
        $plan_id  = isset( $_GET['plan_id'] ) ? (int) $_GET['plan_id'] : 0;
        $table_id = isset( $_GET['pwpl_table'] ) ? (int) $_GET['pwpl_table'] : 0;
        $nonce    = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'pwpl_trash_plan_' . $plan_id ) || $plan_id <= 0 || $table_id <= 0 ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id, 'pwpl_notice' => 'plan_error' ], admin_url( 'admin.php' ) ) );
            exit;
        }
        $plan = get_post( $plan_id );
        if ( ! $plan || $plan->post_type !== 'pwpl_plan' ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id, 'pwpl_notice' => 'plan_error' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        wp_trash_post( $plan_id );
        wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id, 'pwpl_notice' => 'plan_deleted' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Create a draft plan pre-assigned to the table, then redirect to edit screen.
     */
    public function handle_create_plan_for_table() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'planify-wp-pricing-lite' ) );
        }
        $table_id = isset( $_GET['pwpl_table'] ) ? (int) $_GET['pwpl_table'] : 0;
        $nonce    = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'pwpl_create_plan_' . $table_id ) || $table_id <= 0 ) {
            wp_safe_redirect( admin_url( 'admin.php?page=pwpl-tables-dashboard' ) );
            exit;
        }
        $table = get_post( $table_id );
        if ( ! $table || $table->post_type !== 'pwpl_table' ) {
            wp_safe_redirect( admin_url( 'admin.php?page=pwpl-tables-dashboard' ) );
            exit;
        }

        $new_title = $table->post_title ? sprintf( __( '%s — New Plan', 'planify-wp-pricing-lite' ), $table->post_title ) : __( 'New Plan', 'planify-wp-pricing-lite' );
        $new_id = wp_insert_post( [
            'post_type'   => 'pwpl_plan',
            'post_status' => 'draft',
            'post_title'  => $new_title,
            'menu_order'  => 0,
        ], true );

        if ( is_wp_error( $new_id ) || ! $new_id ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'pwpl-plans-dashboard', 'pwpl_table' => $table_id, 'pwpl_notice' => 'plan_error' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        update_post_meta( $new_id, PWPL_Meta::PLAN_TABLE_ID, $table_id );
        wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
        exit;
    }

    /**
     * Render the plan drawer content (used by AJAX and initial render).
     */
    public function render_plan_drawer_markup( $plan_id, $table_id ) {
        $plan = get_post( $plan_id );
        if ( ! $plan || $plan->post_type !== 'pwpl_plan' ) {
            return '';
        }
        $meta_helper = new PWPL_Meta();
        $admin_meta = new PWPL_Admin_Meta();
        $settings = new PWPL_Settings();

        $specs    = get_post_meta( $plan_id, PWPL_Meta::PLAN_SPECS, true );
        $variants = get_post_meta( $plan_id, PWPL_Meta::PLAN_VARIANTS, true );
        $badges   = get_post_meta( $plan_id, PWPL_Meta::PLAN_BADGES_OVERRIDE, true );
        $plan_theme = get_post_meta( $plan_id, PWPL_Meta::PLAN_THEME, true );

        $payload = [
            'specs'           => is_array( $specs ) ? $meta_helper->sanitize_specs( $specs ) : [],
            'variants'        => is_array( $variants ) ? $meta_helper->sanitize_variants( $variants ) : [],
            'featured'        => (bool) get_post_meta( $plan_id, PWPL_Meta::PLAN_FEATURED, true ),
            'badge_shadow'    => (int) get_post_meta( $plan_id, PWPL_Meta::PLAN_BADGE_SHADOW, true ),
            'subtitle'        => (string) get_post_meta( $plan_id, '_pwpl_plan_subtitle', true ),
            'badges_override' => is_array( $badges ) ? $badges : [],
            'plan_theme'      => $plan_theme ?: '',
        ];

        $options = [
            'platforms' => (array) $settings->get( 'platforms' ),
            'periods'   => (array) $settings->get( 'periods' ),
            'locations' => (array) $settings->get( 'locations' ),
        ];

        $tables = get_posts( [
            'post_type'      => 'pwpl_table',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        ob_start();
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pwpl-drawer__form">
            <input type="hidden" name="action" value="pwpl_save_plan_drawer" />
            <input type="hidden" name="plan_id" value="<?php echo esc_attr( $plan_id ); ?>" />
            <input type="hidden" name="pwpl_table" value="<?php echo esc_attr( $table_id ); ?>" />
            <?php wp_nonce_field( 'pwpl_save_plan_' . $plan_id, 'pwpl_plan_nonce' ); ?>
            <?php wp_nonce_field( 'update-post_' . $plan_id ); ?>
            <div class="pwpl-drawer__header">
                <div class="pwpl-drawer__titlewrap">
                    <label for="pwpl-drawer-title"><?php esc_html_e( 'Plan title', 'planify-wp-pricing-lite' ); ?></label>
                    <input type="text" id="pwpl-drawer-title" class="pwpl-control" name="post_title" value="<?php echo esc_attr( $plan->post_title ); ?>" />
                </div>
                <button type="button" class="pwpl-drawer__close" aria-label="<?php esc_attr_e( 'Close plan editor', 'planify-wp-pricing-lite' ); ?>">&times;</button>
            </div>
            <div class="pwpl-drawer__body">
                <?php
                $plan = $plan; // keep variable available in template
                $table = get_post( $table_id );
                $meta  = $payload;
                $tables_list = $tables;
                $options_list = $options;
                $table_id_local = $table_id;
                include trailingslashit( PWPL_DIR ) . 'templates/admin/plan-drawer-form.php';
                ?>
            </div>
            <div class="pwpl-drawer__footer">
                <button type="submit" name="pwpl_save_mode" value="stay" class="button button-primary"><?php esc_html_e( 'Save changes', 'planify-wp-pricing-lite' ); ?></button>
                <button type="submit" name="pwpl_save_mode" value="close" class="button"><?php esc_html_e( 'Save & Close', 'planify-wp-pricing-lite' ); ?></button>
                <a class="button-link" href="<?php echo esc_url( get_edit_post_link( $plan_id, '' ) ); ?>"><?php esc_html_e( 'Open full editor', 'planify-wp-pricing-lite' ); ?></a>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function ajax_render_plan_drawer() {
        check_ajax_referer( 'pwpl_plans_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'planify-wp-pricing-lite' ) ], 403 );
        }
        $plan_id  = isset( $_GET['plan_id'] ) ? (int) $_GET['plan_id'] : 0;
        $table_id = isset( $_GET['table_id'] ) ? (int) $_GET['table_id'] : 0;
        if ( ! $plan_id || ! $table_id ) {
            wp_send_json_error( [ 'message' => __( 'Missing plan or table.', 'planify-wp-pricing-lite' ) ], 400 );
        }
        $html = $this->render_plan_drawer_markup( $plan_id, $table_id );
        if ( ! $html ) {
            wp_send_json_error( [ 'message' => __( 'Unable to load plan.', 'planify-wp-pricing-lite' ) ], 500 );
        }
        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Save handler for the drawer (reuses meta save logic).
     */
    public function handle_save_plan_drawer() {
        $plan_id  = isset( $_POST['plan_id'] ) ? (int) $_POST['plan_id'] : 0;
        $table_id = isset( $_POST['pwpl_table'] ) ? (int) $_POST['pwpl_table'] : 0;
        if ( ! $plan_id || ! current_user_can( 'edit_post', $plan_id ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'planify-wp-pricing-lite' ) );
        }
        if ( ! wp_verify_nonce( $_POST['pwpl_plan_nonce'] ?? '', 'pwpl_save_plan_' . $plan_id ) ) {
            wp_die( __( 'Invalid nonce.', 'planify-wp-pricing-lite' ) );
        }

        // Persist title
        if ( isset( $_POST['post_title'] ) ) {
            wp_update_post( [
                'ID'         => $plan_id,
                'post_title' => sanitize_text_field( wp_unslash( $_POST['post_title'] ) ),
            ] );
        }

        // Reuse existing save handler.
        $admin_meta = new PWPL_Admin_Meta();
        $admin_meta->save_plan( $plan_id );

        $mode     = isset( $_POST['pwpl_save_mode'] ) && $_POST['pwpl_save_mode'] === 'close' ? 'close' : 'stay';
        $args = [
            'page'           => 'pwpl-plans-dashboard',
            'pwpl_table'     => $table_id,
            'pwpl_notice'    => 'plan_saved',
        ];
        if ( $mode === 'stay' ) {
            $args['selected_plan'] = $plan_id;
        }

        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }

    private function index_by_slug( array $items ) {
        $indexed = [];
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $slug  = sanitize_title( $item['slug'] ?? '' );
            $label = isset( $item['label'] ) ? (string) $item['label'] : $slug;
            if ( $slug ) {
                $indexed[ $slug ] = [ 'slug' => $slug, 'label' => $label ];
            }
        }
        return $indexed;
    }
}
