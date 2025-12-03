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
     * Define starter templates.
     *
     * Each template is expressed entirely with existing meta keys (see PWPL_Meta).
     *
     * @return array
     */
    private static function templates(): array {
        $cta_defaults  = self::default_cta_config();
        $card_defaults = self::default_card_config();

        $templates = [
            'app-soft-cards' => [
                'id'          => 'app-soft-cards',
                'label'       => __( 'App Pricing – Soft Cards', 'planify-wp-pricing-lite' ),
                'description' => __( 'Three-plan app pricing grid with soft cards and a featured middle plan.', 'planify-wp-pricing-lite' ),
                'category'    => 'saas',
                'premium'     => false,
                'theme'       => 'firevps',
                'layout_type' => 'grid',
                'preset'      => 'app-soft-cards',
                'layouts'     => [
                    'default' => [
                        'label' => __( '3-column soft cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 24,
                        ],
                    ],
                ],
                'card_styles' => [
                    'default' => [
                        'label' => __( 'Soft cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'radius' => 20,
                                        'pad_t'  => 22,
                                        'pad_b'  => 22,
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
                            PWPL_Meta::LAYOUT_CARD_WIDTHS => [ 'global' => 400 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 24,
                            PWPL_Meta::SPECS_STYLE    => 'flat',
                            PWPL_Meta::CTA_CONFIG     => array_replace_recursive(
                                self::default_cta_config(),
                                [
                                    'normal' => [ 'bg' => '#8b5cf6', 'color' => '#ffffff' ],
                                    'hover'  => [ 'bg' => '#7c3aed', 'color' => '#ffffff' ],
                                ]
                            ),
                        ]
                    ),
                    'plans'      => [
                        [
                            'post_title'   => __( 'Individual', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Ideal for freelancers and individuals.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => false,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( '1 user', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Up to 100 tasks per month', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( '5GB storage', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Limited integrations', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Email support', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'No custom branding', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '10.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Get Started', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'post_title'   => __( 'Team', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Perfect for small teams.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => true,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Up to 5 users', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( '500 tasks per month', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( '50GB storage', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Full integrations', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( '24/7 live chat support', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Basic branding', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '50.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Get Started', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'post_title'   => __( 'Business', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Best for growing businesses.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => false,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Up to 20 users', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( '2,000 tasks per month', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( '200GB storage', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Full integrations', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Priority support', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                    [ 'label' => __( 'Custom branding', 'planify-wp-pricing-lite' ), 'value' => '' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '150.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Get Started', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'saas-3-col' => [
                'id'          => 'saas-3-col',
                'label'       => __( 'SaaS – 3 Column', 'planify-wp-pricing-lite' ),
                'description' => __( 'Three-plan SaaS grid with monthly/yearly billing.', 'planify-wp-pricing-lite' ),
                'category'    => 'saas',
                'premium'     => false,
                'theme'       => 'firevps',
                'layout_type' => 'grid',
                'preset'      => 'saas-3-col',
                'layouts'     => [
                    'default' => [
                        'label' => __( 'Hero pricing grid', 'planify-wp-pricing-lite' ),
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
			'service-columns-lite' => [
				'id'          => 'service-columns-lite',
				'label'       => __( 'Service Columns', 'planify-wp-pricing-lite' ),
				'description' => __( 'Three service cards with concise specs for agencies and freelancers.', 'planify-wp-pricing-lite' ),
				'category'    => 'services',
				'wizard_hidden' => true,
				'premium'     => false,
				'theme'       => 'firevps',
				'layout_type' => 'columns',
				'preset'      => 'service-columns-lite',
                'layouts'     => [
                    'default' => [
                        'label' => __( 'Tight columns', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 18,
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
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 18,
                            PWPL_Meta::CTA_CONFIG     => array_replace_recursive(
                                self::default_cta_config(),
                                [
                                    'normal' => [ 'bg' => '#0ea5e9', 'color' => '#ffffff' ],
                                    'hover'  => [ 'bg' => '#0284c7', 'color' => '#ffffff' ],
                                ]
                            ),
                        ]
                    ),
                    'plans'      => [
                        [
                            'post_title'   => __( 'Starter Service', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'For quick one-off projects.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => false,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => '1' ],
                                    [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '20GB' ],
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email', 'planify-wp-pricing-lite' ) ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '149.00',
                                        'sale_price' => '129.00',
                                        'cta_label'  => __( 'Book now', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 8,
                            ],
                        ],
                        [
                            'post_title'   => __( 'Managed', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Ongoing care for small teams.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => true,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => '5' ],
                                    [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '80GB' ],
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Chat + Email', 'planify-wp-pricing-lite' ) ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '249.00',
                                        'sale_price' => '199.00',
                                        'cta_label'  => __( 'Get managed', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [
                                    [ 'label' => __( 'Popular', 'planify-wp-pricing-lite' ), 'slug' => 'popular', 'color' => '#f59e0b' ],
                                ],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 10,
                            ],
                        ],
                        [
                            'post_title'   => __( 'Premium Care', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'For teams needing priority response.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => false,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB' ],
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority', 'planify-wp-pricing-lite' ) ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '499.00',
                                        'sale_price' => '449.00',
                                        'cta_label'  => __( 'Talk to us', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 8,
                            ],
                        ],
                    ],
                ],
            ],
            'saas-grid-v2' => [
                'id'          => 'saas-grid-v2',
                'label'       => __( 'Starter Pricing Grid', 'planify-wp-pricing-lite' ),
                'description' => __( 'Three-plan app-style pricing grid with illustrated hero cards and striped feature rows.', 'planify-wp-pricing-lite' ),
                'category'    => 'saas',
                'premium'     => false,
                'theme'       => 'firevps',
                'layout_type' => 'grid',
                'preset'      => 'saas-grid-v2',
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
                        'label' => __( 'Hero cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'radius' => 16,
                                        'pad_t' => 28,
                                        'pad_b' => 24,
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
                            PWPL_Meta::LAYOUT_GAP_X   => 24,
                            PWPL_Meta::CTA_CONFIG     => array_replace_recursive(
                                self::default_cta_config(),
                                [
                                    'width'  => 'auto',
                                    'height' => 36,
                                ]
                            ),
                            PWPL_Meta::SPECS_STYLE    => 'flat',
                        ]
                    ),
                    'plans'      => self::demo_plans_saas(),
                ],
            ],
            'comparison-table' => [
                'id'          => 'comparison-table',
                'label'       => __( 'Comparison table', 'planify-wp-pricing-lite' ),
                'description' => __( 'Feature comparison layout across four plans.', 'planify-wp-pricing-lite' ),
                'category'    => 'comparison',
                'premium'     => false,
                'theme'       => 'firevps',
                'layout_type' => 'comparison',
                'preset'      => 'comparison-table',
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
                'category'    => 'services',
                'premium'     => false,
                'theme'       => 'firevps',
                'layout_type' => 'grid',
                'preset'      => 'service-plans',
                'layouts'     => [
                    'default' => [
                        'label' => __( 'Service grid', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS => [ 'global' => 4 ],
                            PWPL_Meta::LAYOUT_GAP_X   => 24,
                        ],
                    ],
                ],
                'card_styles' => [
                    'default' => [
                        'label' => __( 'Service cards', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'radius' => 14,
                                        'pad_t'  => 22,
                                        'pad_b'  => 22,
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
                            PWPL_Meta::LAYOUT_GAP_X   => 24,
                            PWPL_Meta::CTA_CONFIG     => array_replace_recursive(
                                self::default_cta_config(),
                                [
                                    'width'  => 'auto',
                                    'height' => 36,
                                    'normal' => [ 'bg' => '#ffffff', 'color' => '#f97316', 'border' => '#f97316' ],
                                    'hover'  => [ 'bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#ea580c' ],
                                ]
                            ),
                            PWPL_Meta::SPECS_STYLE    => 'flat',
                        ]
                    ),
                    'plans'      => [
                        [
                            'post_title'   => __( 'Free', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Get started with the basics.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED        => false,
                                PWPL_Meta::PLAN_SPECS           => [
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email (business hours)', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Onboarding', 'planify-wp-pricing-lite' ), 'value' => __( 'Self-guided checklist', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                    [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '1 active project/site', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                    [ 'label' => __( 'Reports', 'planify-wp-pricing-lite' ), 'value' => __( 'Quarterly email summary', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Branding', 'planify-wp-pricing-lite' ), 'value' => __( 'No custom branding', 'planify-wp-pricing-lite' ), 'icon' => 'ssl' ],
                                    [ 'label' => __( 'Resources', 'planify-wp-pricing-lite' ), 'value' => __( 'Community + knowledge base', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS        => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '0.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Start for free', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 6,
                            ],
                        ],
                        [
                            'post_title'   => __( 'Starter', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'For small teams and consultants.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED        => false,
                                PWPL_Meta::PLAN_SPECS           => [
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email (business hours)', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Onboarding', 'planify-wp-pricing-lite' ), 'value' => __( 'Kickoff strategy call', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                    [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 3 projects/sites', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                    [ 'label' => __( 'Reports', 'planify-wp-pricing-lite' ), 'value' => __( 'Monthly performance email', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Basic automation recipes', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
                                    [ 'label' => __( 'Branding', 'planify-wp-pricing-lite' ), 'value' => __( 'Basic branding setup', 'planify-wp-pricing-lite' ), 'icon' => 'ssl' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS        => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '149.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Choose Starter', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 8,
                            ],
                        ],
                        [
                            'post_title'   => __( 'Pro', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'For growing service businesses.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED        => true,
                                PWPL_Meta::PLAN_SPECS           => [
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email + chat', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Onboarding', 'planify-wp-pricing-lite' ), 'value' => __( 'Dedicated onboarding specialist', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                    [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 10 projects/sites', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                    [ 'label' => __( 'Reports', 'planify-wp-pricing-lite' ), 'value' => __( 'Weekly performance reports', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Advanced automation setup', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
                                    [ 'label' => __( 'Branding', 'planify-wp-pricing-lite' ), 'value' => __( 'Light custom branding', 'planify-wp-pricing-lite' ), 'icon' => 'ssl' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS        => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '249.00',
                                        'sale_price' => '199.00',
                                        'cta_label'  => __( 'Choose Pro', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 12,
                            ],
                        ],
                        [
                            'post_title'   => __( 'Premium', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'For agencies and high-touch teams.', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED        => false,
                                PWPL_Meta::PLAN_SPECS           => [
                                    [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email, chat & phone', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Onboarding', 'planify-wp-pricing-lite' ), 'value' => __( 'Full onboarding & migration', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                    [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited projects/sites', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                    [ 'label' => __( 'Reports', 'planify-wp-pricing-lite' ), 'value' => __( 'Weekly performance + strategy review', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                    [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Advanced automation & integrations', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
                                    [ 'label' => __( 'Branding', 'planify-wp-pricing-lite' ), 'value' => __( 'Full custom branding', 'planify-wp-pricing-lite' ), 'icon' => 'ssl' ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS        => [
                                    [
                                        'period'     => 'monthly',
                                        'price'      => '499.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Talk to sales', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /**
         * Filter the wizard templates registry.
         *
         * Each template should include:
         * id (string), label (string), description (string), theme (string),
         * layouts (array), card_styles (array), defaults (array with table_meta/plans),
         * category (string), and premium (bool).
         *
         * @since 1.8.9
         *
         * @param array $templates Array of templates keyed by template ID.
         */
        $templates = apply_filters( 'pwpl_table_wizard_templates', $templates );

        return $templates;
    }

    /**
     * Normalize templates after external filters.
     *
     * @param mixed $templates
     * @return array
     */
    private static function normalize_templates( $templates ): array {
        if ( ! is_array( $templates ) ) {
            return [];
        }

        $normalized = [];
        foreach ( $templates as $id => $template ) {
            if ( ! is_array( $template ) ) {
                continue;
            }

            $template_id = '';
            if ( is_string( $id ) && '' !== $id ) {
                $template_id = $id;
            } elseif ( isset( $template['id'] ) && is_scalar( $template['id'] ) && '' !== $template['id'] ) {
                $template_id = (string) $template['id'];
            }

            if ( '' === $template_id ) {
                continue;
            }

            $template['id']       = $template_id;
            $template['category'] = ( isset( $template['category'] ) && is_string( $template['category'] ) && '' !== $template['category'] )
                ? $template['category']
                : 'uncategorized';
            $template['premium']  = isset( $template['premium'] ) ? (bool) $template['premium'] : false;
            $layout_type = isset( $template['layout_type'] ) ? sanitize_key( (string) $template['layout_type'] ) : '';
            if ( '' === $layout_type ) {
                $layout_type = 'grid';
            }
            $preset = isset( $template['preset'] ) ? sanitize_key( (string) $template['preset'] ) : '';
            if ( '' === $preset ) {
                $preset = $template_id;
            }
            $template['layout_type'] = $layout_type;
            $template['preset']      = $preset;

            $normalized[ $template_id ] = $template;
        }

        return $normalized;
    }

    /**
     * Return all starter templates.
     *
     * @return array
     */
    public static function get_templates(): array {
        return array_values( self::normalize_templates( self::templates() ) );
    }

    /**
     * Retrieve a single template by ID.
     *
     * @param string $id
     * @return array|null
     */
    public static function get_template( string $id ): ?array {
        $templates = self::normalize_templates( self::templates() );
        return $templates[ $id ] ?? null;
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
                        [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => '1', 'icon' => 'websites' ],
                        [ 'label' => __( 'SSD Storage', 'planify-wp-pricing-lite' ), 'value' => '20GB', 'icon' => 'ssd-storage' ],
                        [ 'label' => __( 'Email', 'planify-wp-pricing-lite' ), 'value' => __( '5 accounts', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
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
                    PWPL_Meta::PLAN_HERO_IMAGE      => 0,
                ],
            ],
            [
                'post_title'   => __( 'Growth', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'Best for growing teams.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED        => true,
                    PWPL_Meta::PLAN_SPECS           => [
                        [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => '5', 'icon' => 'websites' ],
                        [ 'label' => __( 'SSD Storage', 'planify-wp-pricing-lite' ), 'value' => '80GB', 'icon' => 'ssd-storage' ],
                        [ 'label' => __( 'Email', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                        [ 'label' => __( 'Bandwidth', 'planify-wp-pricing-lite' ), 'value' => __( 'Unmetered', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
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
                    PWPL_Meta::PLAN_HERO_IMAGE      => 0,
                ],
            ],
            [
                'post_title'   => __( 'Scale', 'planify-wp-pricing-lite' ),
                'post_excerpt' => __( 'For established teams and agencies.', 'planify-wp-pricing-lite' ),
                'meta'         => [
                    PWPL_Meta::PLAN_FEATURED        => false,
                    PWPL_Meta::PLAN_SPECS           => [
                        [ 'label' => __( 'Websites', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                        [ 'label' => __( 'SSD Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB', 'icon' => 'ssd-storage' ],
                        [ 'label' => __( 'Email', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                        [ 'label' => __( 'Premium support', 'planify-wp-pricing-lite' ), 'value' => __( '24/7', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
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
                    PWPL_Meta::PLAN_HERO_IMAGE      => 0,
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
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '20GB' ],
                        [ 'label' => __( 'Course exercises', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Certificates', 'planify-wp-pricing-lite' ), 'value' => '' ],
                        [ 'label' => __( 'Access to forum', 'planify-wp-pricing-lite' ), 'value' => '' ],
                        [ 'label' => __( 'Weekly live sessions', 'planify-wp-pricing-lite' ), 'value' => '' ],
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
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '100GB' ],
                        [ 'label' => __( 'Course exercises', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Certificates', 'planify-wp-pricing-lite' ), 'value' => '' ],
                        [ 'label' => __( 'Access to forum', 'planify-wp-pricing-lite' ), 'value' => __( 'Full access', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Weekly live sessions', 'planify-wp-pricing-lite' ), 'value' => __( '2 per month', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email and chat', 'planify-wp-pricing-lite' ) ],
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
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB' ],
                        [ 'label' => __( 'Course exercises', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Certificates', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Access to forum', 'planify-wp-pricing-lite' ), 'value' => __( 'Full access', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Weekly live sessions', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
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
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Course exercises', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Certificates', 'planify-wp-pricing-lite' ), 'value' => __( 'Included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Access to forum', 'planify-wp-pricing-lite' ), 'value' => __( 'Full access', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Weekly live sessions', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited', 'planify-wp-pricing-lite' ) ],
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
