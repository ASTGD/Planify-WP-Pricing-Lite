<?php
/**
 * Template registry for the New Table Wizard.
 *
 * This file only defines preset table/plan configurations using existing meta keys.
 * No new meta schema is introduced here.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Table_Templates {

    /**
     * Return all starter templates.
     *
     * @return array
     */
    public static function get_templates(): array {
        return array_values( self::templates() );
    }

    /**
     * Retrieve a single template by ID.
     *
     * @param string $id
     * @return array|null
     */
    public static function get_template( string $id ): ?array {
        $templates = self::templates();
        return $templates[ $id ] ?? null;
    }

    /**
     * Define starter templates.
     *
     * Each template is expressed entirely with existing meta keys (see PWPL_Meta).
     *
     * @return array
     */
    private static function templates(): array {
        $cta_defaults  = self::default_cta_config();
        $card_defaults = self::default_card_config();

        return [
            'saas-3-col' => [
                'id'          => 'saas-3-col',
                'label'       => __( 'SaaS – 3 Column', 'planify-wp-pricing-lite' ),
                'description' => __( 'Three-plan SaaS grid with monthly/yearly billing.', 'planify-wp-pricing-lite' ),
                'theme'       => 'firevps',
                'layouts'     => [
                    'default' => [
                        'label' => __( 'Standard grid', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 24,
                        ],
                    ],
                ],
                'card_styles' => [
                    'default' => [
                        'label' => __( 'Standard cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => $card_defaults,
                        ],
                    ],
                    'featured-middle' => [
                        'label' => __( 'Featured middle column', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'pad_t' => 24,
                                        'pad_b' => 24,
                                    ],
                                ]
                            ),
                        ],
                    ],
                ],
                'defaults'    => [
                    'table_meta' => array_replace_recursive(
                        self::base_table_meta( 'firevps' ),
                        [
                            PWPL_Meta::CTA_CONFIG    => $cta_defaults,
                            PWPL_Meta::CARD_CONFIG   => $card_defaults,
                        ]
                    ),
                    'plans'      => self::demo_plans_saas(),
                ],
            ],
            'comparison-table' => [
                'id'          => 'comparison-table',
                'label'       => __( 'Comparison table', 'planify-wp-pricing-lite' ),
                'description' => __( 'Feature comparison layout across four plans.', 'planify-wp-pricing-lite' ),
                'theme'       => 'firevps',
                'layouts'     => [
                    'default' => [
                        'label' => __( '4-column comparison', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 4 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 18,
                        ],
                    ],
                ],
                'card_styles' => [
                    'default' => [
                        'label' => __( 'Comparison cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'pad_t' => 18,
                                        'pad_b' => 18,
                                    ],
                                ]
                            ),
                        ],
                    ],
                ],
                'defaults'    => [
                    'table_meta' => array_replace_recursive(
                        self::base_table_meta( 'firevps', [ 'period' ] ),
                        [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 4 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 18,
                        ]
                    ),
                    'plans'      => self::demo_plans_comparison(),
                ],
            ],
            'service-plans' => [
                'id'          => 'service-plans',
                'label'       => __( 'Service plans', 'planify-wp-pricing-lite' ),
                'description' => __( 'Simple service packages with upfront pricing.', 'planify-wp-pricing-lite' ),
                'theme'       => 'firevps',
                'layouts'     => [
                    'default' => [
                        'label' => __( 'Compact grid', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 20,
                        ],
                    ],
                ],
                'card_styles' => [
                    'default' => [
                        'label' => __( 'Minimal cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'radius' => 10,
                                        'pad_t'  => 18,
                                        'pad_b'  => 18,
                                    ],
                                ]
                            ),
                        ],
                    ],
                ],
                'defaults'    => [
                    'table_meta' => array_replace_recursive(
                        self::base_table_meta( 'firevps', [ 'period' ] ),
                        [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 20,
                        ]
                    ),
                    'plans'      => self::demo_plans_services(),
                ],
            ],
        ];
    }

    /**
     * Base table meta shared across templates.
     *
     * @param string $theme
     * @param array  $dimensions
     * @return array
     */
    private static function base_table_meta( string $theme, array $dimensions = [ 'period' ] ): array {
        return [
            PWPL_Meta::DIMENSION_META     => $dimensions,
            PWPL_Meta::ALLOWED_PERIODS    => [ 'monthly', 'yearly' ],
            PWPL_Meta::ALLOWED_PLATFORMS  => [],
            PWPL_Meta::ALLOWED_LOCATIONS  => [],
            PWPL_Meta::TABLE_THEME        => $theme,
            PWPL_Meta::LAYOUT_COLUMNS     => [ 'global' => 3 ],
            PWPL_Meta::LAYOUT_WIDTHS      => [],
            PWPL_Meta::LAYOUT_CARD_WIDTHS => [],
            PWPL_Meta::LAYOUT_GAP_X       => 24,
            PWPL_Meta::CTA_CONFIG         => self::default_cta_config(),
            PWPL_Meta::CARD_CONFIG        => self::default_card_config(),
            PWPL_Meta::SPECS_STYLE        => 'default',
            PWPL_Meta::TRUST_TRIO_ENABLED => 0,
            PWPL_Meta::STICKY_CTA_MOBILE  => 0,
        ];
    }

    /**
     * Default CTA config within sanitizer bounds.
     *
     * @return array
     */
    private static function default_cta_config(): array {
        return [
            'width'        => 'full',
            'height'       => 48,
            'pad_x'        => 22,
            'radius'       => 12,
            'border_width' => 1.5,
            'weight'       => 700,
            'lift'         => 1,
            'focus'        => '',
            'min_w'        => 0,
            'max_w'        => 0,
            'normal'       => [
                'bg'     => '',
                'color'  => '',
                'border' => '',
            ],
            'hover'        => [
                'bg'     => '',
                'color'  => '',
                'border' => '',
            ],
            'font'         => [
                'family'    => '',
                'size'      => 0,
                'transform' => 'none',
                'tracking'  => '',
            ],
        ];
    }

    /**
     * Default card config scaffold.
     *
     * @return array
     */
    private static function default_card_config(): array {
        return [
            'layout' => [
                'radius'  => 12,
                'border_w'=> 1,
                'pad_t'   => 20,
                'pad_r'   => 20,
                'pad_b'   => 20,
                'pad_l'   => 20,
            ],
            'style'  => [],
            'specs'  => [],
        ];
    }

    /**
     * Demo plans for SaaS template.
     *
     * @return array
     */
    private static function demo_plans_saas(): array {
        return [
            [
                'post_title'   => __( 'Starter', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Ideal for small projects.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED        => false,
                    PWPL_Meta::PLAN_SPECS           => [
                        [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => '1' ],
                        [ 'label' => __( 'SSD Storage', 'planify-wp-pricing-lite' ), 'value' => '20GB' ],
                        [ 'label' => __( 'Email', 'planify-wp-pricing-lite' ), 'value' => __( '5 accounts', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS        => [
                        [
                            'period'    => 'monthly',
                            'price'     => '9.99',
                            'sale_price'=> '',
                            'cta_label' => __( 'Start now', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/starter',
                            'target'    => '_blank',
                        ],
                        [
                            'period'    => 'yearly',
                            'price'     => '99.00',
                            'sale_price'=> '79.00',
                            'cta_label' => __( 'Start yearly', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/starter',
                            'target'    => '_blank',
                        ],
                    ],
                    PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                    PWPL_Meta::PLAN_BADGE_SHADOW    => 8,
                ],
            ],
            [
                'post_title'   => __( 'Growth', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Best for growing teams.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED        => true,
                    PWPL_Meta::PLAN_SPECS           => [
                        [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => '5' ],
                        [ 'label' => __( 'SSD Storage', 'planify-wp-pricing-lite' ), 'value' => '80GB' ],
                        [ 'label' => __( 'Email', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Bandwidth', 'planify-wp-pricing-lite' ), 'value' => __( 'Unmetered', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS        => [
                        [
                            'period'    => 'monthly',
                            'price'     => '19.99',
                            'sale_price'=> '16.99',
                            'cta_label' => __( 'Choose Growth', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/growth',
                            'target'    => '_blank',
                        ],
                        [
                            'period'    => 'yearly',
                            'price'     => '199.00',
                            'sale_price'=> '159.00',
                            'cta_label' => __( 'Choose yearly', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/growth',
                            'target'    => '_blank',
                        ],
                    ],
                    PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                    PWPL_Meta::PLAN_BADGE_SHADOW    => 10,
                ],
            ],
            [
                'post_title'   => __( 'Scale', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'For established teams and agencies.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED        => false,
                    PWPL_Meta::PLAN_SPECS           => [
                        [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'SSD Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB' ],
                        [ 'label' => __( 'Email', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Premium support', 'planify-wp-pricing-lite' ), 'value' => __( '24/7', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS        => [
                        [
                            'period'    => 'monthly',
                            'price'     => '39.00',
                            'sale_price'=> '',
                            'cta_label' => __( 'Talk to sales', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/scale',
                            'target'    => '_blank',
                        ],
                        [
                            'period'    => 'yearly',
                            'price'     => '399.00',
                            'sale_price'=> '329.00',
                            'cta_label' => __( 'Talk to sales', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/scale',
                            'target'    => '_blank',
                        ],
                    ],
                    PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                    PWPL_Meta::PLAN_BADGE_SHADOW    => 8,
                ],
            ],
        ];
    }

    /**
     * Demo plans for comparison table.
     *
     * @return array
     */
    private static function demo_plans_comparison(): array {
        return [
            [
                'post_title'   => __( 'Basic', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Core features for starters.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => false,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => '5' ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => '10' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'    => 'monthly',
                            'price'     => '12.00',
                            'sale_price'=> '',
                            'cta_label' => __( 'Choose Basic', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/basic',
                            'target'    => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'post_title'   => __( 'Standard', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Adds collaboration tools.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => true,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => '25' ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => '50' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Chat', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'    => 'monthly',
                            'price'     => '24.00',
                            'sale_price'=> '20.00',
                            'cta_label' => __( 'Choose Standard', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/standard',
                            'target'    => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'post_title'   => __( 'Pro', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'More power and priority support.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => false,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => '100' ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => 'Unlimited' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'    => 'monthly',
                            'price'     => '49.00',
                            'sale_price'=> '',
                            'cta_label' => __( 'Choose Pro', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/pro',
                            'target'    => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'post_title'   => __( 'Enterprise', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Custom SLAs and onboarding.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => false,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Dedicated', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'    => 'monthly',
                            'price'     => '99.00',
                            'sale_price'=> '',
                            'cta_label' => __( 'Contact sales', 'planify-wp-pricing-lite' ),
                            'cta_url'   => 'https://example.com/enterprise',
                            'target'    => '_blank',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Demo plans for service packages.
     *
     * @return array
     */
    private static function demo_plans_services(): array {
        return [
            [
                'post_title'   => __( 'Starter Service', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'One-off setup with basics.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => false,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Setup', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Turnaround', 'planify-wp-pricing-lite' ), 'value' => __( '3 days', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'     => 'monthly',
                            'price'      => '199.00',
                            'sale_price' => '149.00',
                            'cta_label'  => __( 'Book setup', 'planify-wp-pricing-lite' ),
                            'cta_url'    => 'https://example.com/service-starter',
                            'target'     => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'post_title'   => __( 'Managed', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Monthly management included.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => true,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Updates', 'planify-wp-pricing-lite' ), 'value' => __( 'Weekly', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Chat & Email', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'     => 'monthly',
                            'price'      => '299.00',
                            'sale_price' => '249.00',
                            'cta_label'  => __( 'Choose Managed', 'planify-wp-pricing-lite' ),
                            'cta_url'    => 'https://example.com/service-managed',
                            'target'     => '_blank',
                        ],
                        [
                            'period'     => 'yearly',
                            'price'      => '2999.00',
                            'sale_price' => '2499.00',
                            'cta_label'  => __( 'Choose yearly', 'planify-wp-pricing-lite' ),
                            'cta_url'    => 'https://example.com/service-managed',
                            'target'     => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'post_title'   => __( 'Premium Care', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Hands-on support and consulting.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED => false,
                    PWPL_Meta::PLAN_SPECS    => [
                        [ 'label' => __( 'Account manager', 'planify-wp-pricing-lite' ), 'value' => __( 'Dedicated', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Priority fixes', 'planify-wp-pricing-lite' ), 'value' => __( 'Same-day', 'planify-wp-pricing-lite' ) ],
                    ],
                    PWPL_Meta::PLAN_VARIANTS => [
                        [
                            'period'     => 'monthly',
                            'price'      => '499.00',
                            'sale_price' => '',
                            'cta_label'  => __( 'Schedule a call', 'planify-wp-pricing-lite' ),
                            'cta_url'    => 'https://example.com/service-premium',
                            'target'     => '_blank',
                        ],
                    ],
                ],
            ],
        ];
    }
}
