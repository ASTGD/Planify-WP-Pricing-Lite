<?php
/**
 * Helper used by the New Table Wizard.
 *
 * Provides in-memory preview configs and a safe way to create demo tables/plans
 * using the existing meta keys and sanitizers.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Table_Wizard {

    /**
     * Build an in-memory preview configuration for the given template + variants.
     *
     * No database writes occur here.
     *
     * @param string      $template_id
     * @param string|null $layout_id
     * @param string|null $card_style_id
     * @return array|null
     */
    public static function build_preview_config( string $template_id, ?string $layout_id = null, ?string $card_style_id = null ): ?array {
        $template = PWPL_Table_Templates::get_template( $template_id );
        if ( ! $template ) {
            return null;
        }

        $layout_variant = self::resolve_variant( $template['layouts'] ?? [], $layout_id );
        $card_variant   = self::resolve_variant( $template['card_styles'] ?? [], $card_style_id );

        $base_meta = isset( $template['defaults']['table_meta'] ) && is_array( $template['defaults']['table_meta'] )
            ? $template['defaults']['table_meta']
            : [];

        $meta = self::merge_meta(
            $base_meta,
            $layout_variant['meta'] ?? [],
            $card_variant['meta'] ?? []
        );

        if ( empty( $meta[ PWPL_Meta::TABLE_THEME ] ) && ! empty( $template['theme'] ) ) {
            $meta[ PWPL_Meta::TABLE_THEME ] = $template['theme'];
        }

        $plans = self::prepare_plans_for_preview(
            $template['defaults']['plans'] ?? [],
            $meta[ PWPL_Meta::TABLE_THEME ] ?? ''
        );

        return [
            'template_id'   => $template_id,
            'layout_id'     => $layout_variant['id'],
            'card_style_id' => $card_variant['id'],
            'table'         => [
                'post_title'   => $template['label'] ?? '',
                'post_excerpt' => $template['description'] ?? '',
                'meta'         => $meta,
            ],
            'plans'         => $plans,
            'dimensions'    => [
                'enabled' => $meta[ PWPL_Meta::DIMENSION_META ] ?? [],
                'allowed' => [
                    'platform' => $meta[ PWPL_Meta::ALLOWED_PLATFORMS ] ?? [],
                    'period'   => $meta[ PWPL_Meta::ALLOWED_PERIODS ] ?? [],
                    'location' => $meta[ PWPL_Meta::ALLOWED_LOCATIONS ] ?? [],
                ],
            ],
        ];
    }

    /**
     * Create a real table + demo plans from a template selection.
     *
     * @param string      $template_id
     * @param string|null $layout_id
     * @param string|null $card_style_id
     * @param array       $args               Optional args: post_title, post_status.
     * @return int|null                       New table ID or null on failure.
     */
    public static function create_table_from_selection( string $template_id, ?string $layout_id = null, ?string $card_style_id = null, array $args = [] ): ?int {
        $config = self::build_preview_config( $template_id, $layout_id, $card_style_id );
        if ( ! $config ) {
            return null;
        }

        $table_post = [
            'post_type'   => 'pwpl_table',
            'post_status' => $args['post_status'] ?? 'publish',
            'post_title'  => $args['post_title'] ?? ( $config['table']['post_title'] ?: __( 'New Pricing Table', 'planify-wp-pricing-lite' ) ),
            'post_content'=> '',
        ];

        $table_id = wp_insert_post( $table_post, true );
        if ( is_wp_error( $table_id ) ) {
            return null;
        }

        $table_meta = $config['table']['meta'];

        if ( ! empty( $args['theme'] ) ) {
            $table_meta[ PWPL_Meta::TABLE_THEME ] = sanitize_key( $args['theme'] );
        }

        if ( isset( $args['dimensions'] ) && is_array( $args['dimensions'] ) ) {
            $dims = array_filter( (array) $args['dimensions'] );
            $enabled = [];
            foreach ( [ 'platform', 'period', 'location' ] as $dim_key ) {
                if ( ! empty( $dims[ $dim_key ] ) ) {
                    $enabled[] = $dim_key;
                }
            }
            if ( ! empty( $enabled ) ) {
                $table_meta[ PWPL_Meta::DIMENSION_META ] = $enabled;
            }
            if ( empty( $dims['platform'] ) ) {
                $table_meta[ PWPL_Meta::ALLOWED_PLATFORMS ] = [];
            }
            if ( empty( $dims['period'] ) ) {
                $table_meta[ PWPL_Meta::ALLOWED_PERIODS ] = [];
            }
            if ( empty( $dims['location'] ) ) {
                $table_meta[ PWPL_Meta::ALLOWED_LOCATIONS ] = [];
            }
        }

        foreach ( $table_meta as $key => $value ) {
            update_post_meta( $table_id, $key, $value );
        }

        // Create plans
        foreach ( $config['plans'] as $plan ) {
            $plan_post = [
                'post_type'   => 'pwpl_plan',
                'post_status' => 'publish',
                'post_title'  => $plan['post_title'] ?? '',
                'post_excerpt'=> $plan['post_excerpt'] ?? '',
                'post_parent' => $table_id,
            ];

            $plan_id = wp_insert_post( $plan_post, true );
            if ( is_wp_error( $plan_id ) ) {
                continue;
            }

            update_post_meta( $plan_id, PWPL_Meta::PLAN_TABLE_ID, $table_id );
            foreach ( $plan['meta'] as $meta_key => $meta_value ) {
                update_post_meta( $plan_id, $meta_key, $meta_value );
            }
        }

        return (int) $table_id;
    }

    /**
     * Resolve a variant (layout/card style) with a fallback to the first defined entry.
     *
     * @param array       $variants
     * @param string|null $requested_id
     * @return array
     */
    private static function resolve_variant( array $variants, ?string $requested_id ): array {
        if ( empty( $variants ) ) {
            return [
                'id'   => null,
                'meta' => [],
            ];
        }

        if ( $requested_id && isset( $variants[ $requested_id ] ) ) {
            return array_merge(
                [ 'id' => $requested_id ],
                $variants[ $requested_id ]
            );
        }

        $first_id = array_key_first( $variants );
        return array_merge(
            [ 'id' => $first_id ],
            $variants[ $first_id ]
        );
    }

    /**
     * Shallow merge helpers for meta.
     *
     * @param array ...$meta_groups
     * @return array
     */
    private static function merge_meta( ...$meta_groups ): array {
        $merged = [];
        foreach ( $meta_groups as $group ) {
            if ( ! is_array( $group ) ) {
                continue;
            }
            $merged = array_replace_recursive( $merged, $group );
        }
        return $merged;
    }

    /**
     * Prepare plan definitions for preview consumption.
     *
     * @param array  $plans
     * @param string $theme
     * @return array
     */
    private static function prepare_plans_for_preview( array $plans, string $theme ): array {
        $prepared = [];
        $counter  = 1;

        foreach ( $plans as $plan ) {
            $meta = isset( $plan['meta'] ) && is_array( $plan['meta'] ) ? $plan['meta'] : [];
            if ( $theme && empty( $meta[ PWPL_Meta::PLAN_THEME ] ) ) {
                $meta[ PWPL_Meta::PLAN_THEME ] = $theme;
            }

            $prepared[] = [
                'ID'           => $counter++,
                'post_title'   => $plan['post_title'] ?? '',
                'post_excerpt' => $plan['post_excerpt'] ?? '',
                'meta'         => $meta,
            ];
        }

        return $prepared;
    }
}
