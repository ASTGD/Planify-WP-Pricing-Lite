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
            'thumbnail'   => self::thumbnail_url( 'app-soft-cards.png' ),
                'metadata'    => [
                    'tags'         => [ 'saas', 'grid', 'soft-cards' ],
                    'best_for'     => __( 'App or SaaS launches that need soft, friendly cards with a highlighted middle plan.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 3,
                    'supports_hero'=> false,
                    'highlights'   => [
                        __( 'Soft, rounded cards with pastel gradients.', 'planify-wp-pricing-lite' ),
                        __( 'Middle column emphasis for your primary plan.', 'planify-wp-pricing-lite' ),
                        __( 'Flat specs list that stays tidy with short bullets.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 5 seats', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Tasks', 'planify-wp-pricing-lite' ), 'value' => __( '500 per month', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '50GB' ],
                        [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Slack, Zapier, Notion', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email + live chat', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                    ],
                    'sample_sets' => [
                        [
                            'id'    => 'individual',
                            'label' => __( 'Individual plan sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( '1 user', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Tasks / month', 'planify-wp-pricing-lite' ), 'value' => __( '100 tasks', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '5GB' ],
                                [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Limited', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                            ],
                        ],
                        [
                            'id'    => 'team',
                            'label' => __( 'Team plan sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 5 seats', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Tasks / month', 'planify-wp-pricing-lite' ), 'value' => __( '500 tasks', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '50GB' ],
                                [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Full integrations', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( '24/7 live chat', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                            ],
                        ],
                        [
                            'id'    => 'business',
                            'label' => __( 'Business plan sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 20 seats', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Tasks / month', 'planify-wp-pricing-lite' ), 'value' => __( '2,000 tasks', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB' ],
                                [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Full integrations', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                            ],
                        ],
                    ],
                ],
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
                'thumbnail'   => self::thumbnail_url( 'saas-3-col.png' ),
                'metadata'    => [
                    'tags'         => [ 'saas', 'grid', 'hero', 'premium' ],
                    'best_for'     => __( 'Product or SaaS sites that want per-plan hero images and a premium card layout.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 3,
                    'supports_hero'=> true,
                    'highlights'   => [
                        __( 'Optional hero image per plan (full-bleed).', 'planify-wp-pricing-lite' ),
                        __( 'Bottom-anchored CTA with reassurance note.', 'planify-wp-pricing-lite' ),
                        __( 'Per-plan badges and featured styling.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 10 seats', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited projects', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '100GB' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                        [ 'label' => __( 'Security', 'planify-wp-pricing-lite' ), 'value' => __( 'SSO + audit logs', 'planify-wp-pricing-lite' ) ],
                    ],
                    'sample_sets' => [
                        [
                            'id'    => 'starter',
                            'label' => __( 'Starter sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 3 seats', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '10 active projects', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '10GB' ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Standard email support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Slack & Zapier', 'planify-wp-pricing-lite' ) ],
                            ],
                            'cta'   => [
                                'label' => __( 'Start free', 'planify-wp-pricing-lite' ),
                                'url'   => 'https://example.com/starter',
                            ],
                            'pricing' => [
                                'price'      => '9.99',
                                'sale_price' => '',
                                'period'     => 'monthly',
                                'billing'    => __( 'Billed monthly', 'planify-wp-pricing-lite' ),
                            ],
                        ],
                        [
                            'id'    => 'growth',
                            'label' => __( 'Growth sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 10 seats', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '50 active projects', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '100GB' ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                [ 'label' => __( 'Reporting', 'planify-wp-pricing-lite' ), 'value' => __( 'Advanced analytics', 'planify-wp-pricing-lite' ) ],
                            ],
                            'cta'   => [
                                'label' => __( 'Choose Growth', 'planify-wp-pricing-lite' ),
                                'url'   => 'https://example.com/growth',
                            ],
                            'pricing' => [
                                'price'      => '19.99',
                                'sale_price' => '16.99',
                                'period'     => 'monthly',
                                'billing'    => __( 'Monthly billing', 'planify-wp-pricing-lite' ),
                            ],
                            'badge' => [
                                'label' => __( 'Most Popular', 'planify-wp-pricing-lite' ),
                                'color' => '#fde68a',
                                'text_color' => '#92400e',
                            ],
                            'featured' => true,
                        ],
                        [
                            'id'    => 'scale',
                            'label' => __( 'Scale sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited seats', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited projects', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '1TB' ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( '24/7 priority support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                [ 'label' => __( 'Security', 'planify-wp-pricing-lite' ), 'value' => __( 'SSO & audit logs', 'planify-wp-pricing-lite' ) ],
                            ],
                            'cta'   => [
                                'label' => __( 'Talk to us', 'planify-wp-pricing-lite' ),
                                'url'   => 'https://example.com/scale',
                            ],
                            'pricing' => [
                                'price'      => '39.00',
                                'sale_price' => '',
                                'period'     => 'monthly',
                                'billing'    => __( 'Billed monthly', 'planify-wp-pricing-lite' ),
                            ],
                        ],
                    ],
                ],
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
                'metadata'    => [
                    'tags'         => [ 'services', 'columns', 'minimal' ],
                    'best_for'     => __( 'Agencies that need lean service packages with short feature lists.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 3,
                    'supports_hero'=> false,
                    'highlights'   => [
                        __( 'Tight columns optimized for short copy.', 'planify-wp-pricing-lite' ),
                        __( 'Minimal styling for embedding on content-heavy pages.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Deliverables', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 5 tasks', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Turnaround', 'planify-wp-pricing-lite' ), 'value' => __( '3 business days', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email + chat', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                        [ 'label' => __( 'Reporting', 'planify-wp-pricing-lite' ), 'value' => __( 'Monthly summary', 'planify-wp-pricing-lite' ) ],
                    ],
                    'sample_sets' => [
                        [
                            'id'    => 'basic-services',
                            'label' => __( 'Basic service', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Deliverables', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 5 tasks', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Turnaround', 'planify-wp-pricing-lite' ), 'value' => __( '5 business days', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email only', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                            ],
                            'pricing' => [
                                'price'      => '0.00',
                                'sale_price' => '',
                                'period'     => 'monthly',
                                'billing'    => __( 'Starter tier', 'planify-wp-pricing-lite' ),
                            ],
                        ],
                        [
                            'id'    => 'standard-services',
                            'label' => __( 'Standard service', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Deliverables', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 12 tasks', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Turnaround', 'planify-wp-pricing-lite' ), 'value' => __( '3 business days', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email + chat', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                [ 'label' => __( 'Reporting', 'planify-wp-pricing-lite' ), 'value' => __( 'Monthly summary', 'planify-wp-pricing-lite' ) ],
                            ],
                            'pricing' => [
                                'price'      => '149.00',
                                'sale_price' => '',
                                'period'     => 'monthly',
                                'billing'    => __( 'Monthly retainer', 'planify-wp-pricing-lite' ),
                            ],
                        ],
                        [
                            'id'    => 'premium-services',
                            'label' => __( 'Premium service', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Deliverables', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited tasks', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Turnaround', 'planify-wp-pricing-lite' ), 'value' => __( '2 business days', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority chat & phone', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                [ 'label' => __( 'Reporting', 'planify-wp-pricing-lite' ), 'value' => __( 'Weekly summary', 'planify-wp-pricing-lite' ) ],
                            ],
                            'pricing' => [
                                'price'      => '499.00',
                                'sale_price' => '429.00',
                                'period'     => 'monthly',
                                'billing'    => __( 'Premium retainer', 'planify-wp-pricing-lite' ),
                            ],
                            'badge' => [
                                'label' => __( 'VIP', 'planify-wp-pricing-lite' ),
                                'color' => '#fde047',
                                'text_color' => '#78350f',
                            ],
                            'featured' => true,
                        ],
                    ],
                ],
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
                'thumbnail'   => self::thumbnail_url( 'saas-grid-v2.png' ),
                'metadata'    => [
                    'tags'         => [ 'saas', 'grid', 'illustrated' ],
                    'best_for'     => __( 'Marketing pages that want illustrated hero cards with a savings badge.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 3,
                    'supports_hero'=> true,
                    'highlights'   => [
                        __( 'Hero illustrations per plan (built-in SVGs).', 'planify-wp-pricing-lite' ),
                        __( 'Billing toggle with savings callout.', 'planify-wp-pricing-lite' ),
                        __( 'Striped feature rows with modern typography.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Seats', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 5 users', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Automation runs', 'planify-wp-pricing-lite' ), 'value' => __( '2k / month', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB' ],
                        [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( '20+ tools', 'planify-wp-pricing-lite' ) ],
                    ],
                    'sample_sets' => [
                        [
                            'id'    => 'basic-app',
                            'label' => __( 'Basic sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Seats', 'planify-wp-pricing-lite' ), 'value' => __( '3 users', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Automation runs', 'planify-wp-pricing-lite' ), 'value' => __( '1k / month', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '50GB' ],
                            ],
                        ],
                        [
                            'id'    => 'standard-app',
                            'label' => __( 'Standard sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Seats', 'planify-wp-pricing-lite' ), 'value' => __( '10 users', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Automation runs', 'planify-wp-pricing-lite' ), 'value' => __( '2k / month', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '200GB' ],
                                [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( '20+ tools', 'planify-wp-pricing-lite' ) ],
                            ],
                        ],
                        [
                            'id'    => 'premium-app',
                            'label' => __( 'Premium sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Seats', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited users', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Automation runs', 'planify-wp-pricing-lite' ), 'value' => __( '10k / month', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '1TB' ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                            ],
                        ],
                    ],
                ],
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
                'thumbnail'   => self::thumbnail_url( 'comparison-table.png' ),
                'metadata'    => [
                    'tags'         => [ 'comparison', 'matrix', 'features' ],
                    'best_for'     => __( 'Course catalogs or product lines that need a spec matrix with clear ticks/crosses.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 4,
                    'supports_hero'=> false,
                    'highlights'   => [
                        __( 'Dedicated feature column with plan headers.', 'planify-wp-pricing-lite' ),
                        __( 'Tick/cross cells for quick scanning.', 'planify-wp-pricing-lite' ),
                        __( 'Optional CTA under each column.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( '5 / 20 / 50 / Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '3 / 10 / 25 / Unlimited', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Basic / Advanced / Full', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email / Chat / 24-7', 'planify-wp-pricing-lite' ) ],
                    ],
                    'sample_sets' => [
                        [
                            'id'    => 'compare-basic',
                            'label' => __( 'Basic matrix', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( '5 / 20 / 50 / Unlimited', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '3 / 10 / 25 / Unlimited', 'planify-wp-pricing-lite' ) ],
                            ],
                        ],
                        [
                            'id'    => 'compare-advanced',
                            'label' => __( 'Advanced matrix', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Basic / Advanced / Full', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email / Chat / 24-7', 'planify-wp-pricing-lite' ) ],
                                [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Core / Pro / Enterprise', 'planify-wp-pricing-lite' ) ],
                            ],
                        ],
                    ],
                ],
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
                'thumbnail'   => self::thumbnail_url( 'service-plans.png' ),
                'metadata'    => [
                    'tags'         => [ 'services', 'grid', 'packages' ],
                    'best_for'     => __( 'Agencies or service providers listing retainer tiers or onboarding packages.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 4,
                    'supports_hero'=> false,
                    'highlights'   => [
                        __( 'Four-up grid ideal for retainers.', 'planify-wp-pricing-lite' ),
                        __( 'Flat specs with optional icons.', 'planify-wp-pricing-lite' ),
                        __( 'Bright CTA palette for marketing sites.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Monthly hours', 'planify-wp-pricing-lite' ), 'value' => __( '10 / 25 / 60 / Custom', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Reporting', 'planify-wp-pricing-lite' ), 'value' => __( 'Monthly / Weekly / Custom', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Point of contact', 'planify-wp-pricing-lite' ), 'value' => __( 'Shared / Dedicated AM', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Business hours / 24-7', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                    ],
                    'sample_sets' => [
                        [
                            'id'    => 'free-services',
                            'label' => __( 'Free sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email (business hours)', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                [ 'label' => __( 'Onboarding', 'planify-wp-pricing-lite' ), 'value' => __( 'Self-guided checklist', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '1 active project', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                            ],
                        ],
                        [
                            'id'    => 'starter-services',
                            'label' => __( 'Starter sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Email (business hours)', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 3 projects', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                [ 'label' => __( 'Reports', 'planify-wp-pricing-lite' ), 'value' => __( 'Monthly performance email', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                            ],
                        ],
                        [
                            'id'    => 'pro-services',
                            'label' => __( 'Pro sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email + chat', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 10 projects', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Advanced automation setup', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
                            ],
                        ],
                        [
                            'id'    => 'premium-services',
                            'label' => __( 'Premium sample', 'planify-wp-pricing-lite' ),
                            'specs' => [
                                [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email, chat & phone', 'planify-wp-pricing-lite' ), 'icon' => 'email' ],
                                [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited projects', 'planify-wp-pricing-lite' ), 'icon' => 'websites' ],
                                [ 'label' => __( 'Automation', 'planify-wp-pricing-lite' ), 'value' => __( 'Advanced automation & integrations', 'planify-wp-pricing-lite' ), 'icon' => 'bandwidth' ],
                            ],
                        ],
                    ],
                ],
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
            'hospitality-cards' => [
                'id'          => 'hospitality-cards',
                'label'       => __( 'Hospitality cards', 'planify-wp-pricing-lite' ),
                'description' => __( 'Image-forward room or service cards with amenities lists and full-bleed hero photos.', 'planify-wp-pricing-lite' ),
                'category'    => 'services',
                'premium'     => false,
                'theme'       => 'firevps',
                'layout_type' => 'columns',
                'preset'      => 'hospitality-cards',
                'thumbnail'   => self::thumbnail_url( 'hospitality-cards.png' ),
                'metadata'    => [
                    'tags'         => [ 'services', 'hotel', 'image-hero', 'columns' ],
                    'best_for'     => __( 'Hotel rooms, boutique stays, salons, or coaching packages that need photography-led pricing.', 'planify-wp-pricing-lite' ),
                    'plan_count'   => 3,
                    'supports_hero'=> true,
                    'highlights'   => [
                        __( 'Full-bleed hero image for each room or package.', 'planify-wp-pricing-lite' ),
                        __( 'Amenities grid with subtle serif headings.', 'planify-wp-pricing-lite' ),
                        __( 'CTA anchored to a cream footer with a reassurance line.', 'planify-wp-pricing-lite' ),
                    ],
                    'sample_specs' => [
                        [ 'label' => __( 'Guests', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 4', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Meals', 'planify-wp-pricing-lite' ), 'value' => __( 'Breakfast included', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'WiFi', 'planify-wp-pricing-lite' ), 'value' => __( 'Ultra-fast 500 Mbps', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Perks', 'planify-wp-pricing-lite' ), 'value' => __( 'Late checkout, concierge, spa access', 'planify-wp-pricing-lite' ) ],
                    ],
                ],
                'layouts'     => [
                    'default' => [
                        'label' => __( 'Hospitality columns', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::LAYOUT_COLUMNS    => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X      => 20,
                            PWPL_Meta::LAYOUT_WIDTHS     => [ 'global' => 1240 ],
                            PWPL_Meta::LAYOUT_CARD_WIDTHS=> [ 'global' => 360 ],
                        ],
                    ],
                ],
                'card_styles' => [
                    'default' => [
                        'label' => __( 'Hospitality surfaces', 'planify-wp-pricing-lite' ),
                        'meta'  => [
                            PWPL_Meta::CARD_CONFIG => array_replace_recursive(
                                $card_defaults,
                                [
                                    'layout' => [
                                        'radius' => 26,
                                        'pad_t'  => 24,
                                        'pad_b'  => 24,
                                    ],
                                ]
                            ),
                        ],
                    ],
                ],
                'defaults'    => [
                    'table_meta' => array_replace_recursive(
                        self::base_table_meta( 'firevps', [] ),
                        [
                            PWPL_Meta::LAYOUT_COLUMNS      => [ 'global' => 3 ],
                            PWPL_Meta::LAYOUT_GAP_X        => 22,
                            PWPL_Meta::LAYOUT_WIDTHS       => [ 'global' => 1240 ],
                            PWPL_Meta::LAYOUT_CARD_WIDTHS  => [ 'global' => 360 ],
                            PWPL_Meta::TABLE_LAYOUT_TYPE => 'columns',
                            PWPL_Meta::TABLE_PRESET      => 'hospitality-cards',
                            PWPL_Meta::TRUST_TRIO_ENABLED => true,
                            PWPL_Meta::CTA_CONFIG     => array_replace_recursive(
                                self::default_cta_config(),
                                [
                                    'width'  => 'full',
                                    'height' => 46,
                                    'normal' => [ 'bg' => '#1d4ed8', 'color' => '#ffffff', 'border' => '#1d4ed8' ],
                                    'hover'  => [ 'bg' => '#1e40af', 'color' => '#ffffff', 'border' => '#1e40af' ],
                                    'font'   => [
                                        'family' => '',
                                        'size'   => 15,
                                        'tracking' => '0.08em',
                                    ],
                                ]
                            ),
                        ]
                    ),
                    'plans'      => [
                        [
                            'post_title'   => __( 'Standard Room', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( '1 king bed · City skyline', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => false,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Guests', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 2 adults', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Meals', 'planify-wp-pricing-lite' ), 'value' => __( 'Breakfast included', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Workspace', 'planify-wp-pricing-lite' ), 'value' => __( 'Dedicated desk + USB-C charging', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'WiFi', 'planify-wp-pricing-lite' ), 'value' => __( '500 Mbps fiber', 'planify-wp-pricing-lite' ) ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => '',
                                        'price'      => '129.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Reserve Standard', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                        'unit'       => __( '/night', 'planify-wp-pricing-lite' ),
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 8,
                                PWPL_Meta::PLAN_HERO_IMAGE      => 0,
                                PWPL_Meta::PLAN_TRUST_ITEMS_OVERRIDE => [
                                    __( 'Free cancellation within 48h', 'planify-wp-pricing-lite' ),
                                ],
                                PWPL_Meta::PLAN_HERO_IMAGE_URL => self::template_demo_url( 'hospitality-standard.png' ),
                                'billing' => __( 'Per night', 'planify-wp-pricing-lite' ),
                            ],
                        ],
                        [
                            'post_title'   => __( 'Deluxe Suite', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Private lounge · Garden view', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => true,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Guests', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 3 adults', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Extras', 'planify-wp-pricing-lite' ), 'value' => __( 'Evening canapés + minibar', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Spa', 'planify-wp-pricing-lite' ), 'value' => __( '30-min treatment credit', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Late checkout', 'planify-wp-pricing-lite' ), 'value' => __( '2 p.m. guaranteed', 'planify-wp-pricing-lite' ) ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => '',
                                        'price'      => '189.00',
                                        'sale_price' => '169.00',
                                        'cta_label'  => __( 'Reserve Suite', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                        'unit'       => __( '/night', 'planify-wp-pricing-lite' ),
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [
                                    [ 'label' => __( 'Most booked', 'planify-wp-pricing-lite' ), 'color' => '#fde047', 'text_color' => '#854d0e' ],
                                ],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 12,
                                PWPL_Meta::PLAN_HERO_IMAGE      => 0,
                                PWPL_Meta::PLAN_TRUST_ITEMS_OVERRIDE => [
                                    __( 'Free cancellation within 48h', 'planify-wp-pricing-lite' ),
                                ],
                                PWPL_Meta::PLAN_HERO_IMAGE_URL => self::template_demo_url( 'hospitality-deluxe.png' ),
                                'billing' => __( 'Per night', 'planify-wp-pricing-lite' ),
                            ],
                        ],
                        [
                            'post_title'   => __( 'Penthouse Loft', 'planify-wp-pricing-lite' ),
                            'post_excerpt' => __( 'Rooftop terrace · Butler service', 'planify-wp-pricing-lite' ),
                            'meta'         => [
                                PWPL_Meta::PLAN_FEATURED => false,
                                PWPL_Meta::PLAN_SPECS    => [
                                    [ 'label' => __( 'Guests', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 5 guests', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Perks', 'planify-wp-pricing-lite' ), 'value' => __( 'Airport transfer + private chef', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Wellness', 'planify-wp-pricing-lite' ), 'value' => __( 'In-room sauna', 'planify-wp-pricing-lite' ) ],
                                    [ 'label' => __( 'Concierge', 'planify-wp-pricing-lite' ), 'value' => __( '24/7 dedicated host', 'planify-wp-pricing-lite' ) ],
                                ],
                                PWPL_Meta::PLAN_VARIANTS => [
                                    [
                                        'period'     => '',
                                        'price'      => '349.00',
                                        'sale_price' => '',
                                        'cta_label'  => __( 'Book the Penthouse', 'planify-wp-pricing-lite' ),
                                        'cta_url'    => '#',
                                        'target'     => '_self',
                                        'unit'       => __( '/night', 'planify-wp-pricing-lite' ),
                                    ],
                                ],
                                PWPL_Meta::PLAN_BADGES_OVERRIDE => [],
                                PWPL_Meta::PLAN_BADGE_SHADOW    => 10,
                                PWPL_Meta::PLAN_HERO_IMAGE      => 0,
                                PWPL_Meta::PLAN_TRUST_ITEMS_OVERRIDE => [
                                    __( 'Free cancellation within 48h', 'planify-wp-pricing-lite' ),
                                ],
                                PWPL_Meta::PLAN_HERO_IMAGE_URL => self::template_demo_url( 'hospitality-penthouse.png' ),
                                'billing' => __( 'Per night', 'planify-wp-pricing-lite' ),
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

            $raw_metadata = [];
            if ( isset( $template['metadata'] ) && is_array( $template['metadata'] ) ) {
                $raw_metadata = $template['metadata'];
            }

            /**
             * Filter template metadata before it is normalized for the wizard.
             *
             * @since 1.9.0
             *
             * @param array  $raw_metadata Metadata array defined by the template.
             * @param string $template_id  Template slug.
             */
            $raw_metadata = apply_filters( 'pwpl_wizard_template_metadata', $raw_metadata, $template_id );

            $template['metadata'] = self::sanitize_template_metadata( $template_id, $raw_metadata );

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
     * Ensure metadata arrays stay structured + sanitized.
     *
     * @param string $template_id
     * @param mixed  $metadata
     * @return array
     */
    private static function sanitize_template_metadata( string $template_id, $metadata ): array {
        $defaults = [
            'tags'          => [],
            'best_for'      => '',
            'plan_count'    => 3,
            'supports_hero' => false,
            'highlights'    => [],
            'sample_specs'  => [],
            'sample_sets'   => [],
        ];

        if ( ! is_array( $metadata ) ) {
            $metadata = [];
        }

        $metadata = wp_parse_args( $metadata, $defaults );

        $metadata['tags'] = array_values( array_filter( array_map(
            function( $tag ) {
                $tag = sanitize_key( (string) $tag );
                return '' === $tag ? null : $tag;
            },
            (array) $metadata['tags']
        ) ) );

        $metadata['best_for']      = sanitize_text_field( (string) $metadata['best_for'] );
        $metadata['plan_count']    = max( 1, (int) $metadata['plan_count'] );
        $metadata['supports_hero'] = (bool) $metadata['supports_hero'];

        $metadata['highlights'] = array_values( array_filter( array_map(
            function( $highlight ) {
                $highlight = sanitize_text_field( (string) $highlight );
                return '' === $highlight ? null : $highlight;
            },
            (array) $metadata['highlights']
        ) ) );

        $metadata['sample_specs'] = self::sanitize_specs_list( $metadata['sample_specs'] );

        $metadata['sample_sets'] = array_values( array_filter( array_map(
            function( $set, $index ) {
                if ( ! is_array( $set ) ) {
                    return null;
                }
                $specs = self::sanitize_specs_list( $set['specs'] ?? [] );
                if ( empty( $specs ) ) {
                    return null;
                }
                $id = '';
                if ( ! empty( $set['id'] ) ) {
                    $id = sanitize_key( (string) $set['id'] );
                }
                if ( '' === $id ) {
                    $id = 'set-' . ( (int) $index + 1 );
                }
                $cta = [];
                if ( ! empty( $set['cta'] ) && is_array( $set['cta'] ) ) {
                    $cta_label = sanitize_text_field( (string) ( $set['cta']['label'] ?? '' ) );
                    $cta_url   = esc_url_raw( (string) ( $set['cta']['url'] ?? '' ) );
                    $cta_target = in_array( $set['cta']['target'] ?? '', [ '_self', '_blank' ], true ) ? $set['cta']['target'] : '';
                    if ( '' !== $cta_label || '' !== $cta_url ) {
                        $cta = [
                            'label'  => $cta_label,
                            'url'    => $cta_url,
                            'target' => $cta_target,
                        ];
                    }
                }
                $hero = '';
                if ( ! empty( $set['hero_image'] ) ) {
                    $hero = esc_url_raw( (string) $set['hero_image'] );
                }
                $pricing = [];
                if ( ! empty( $set['pricing'] ) && is_array( $set['pricing'] ) ) {
                    $pricing['price']      = isset( $set['pricing']['price'] ) ? sanitize_text_field( (string) $set['pricing']['price'] ) : '';
                    $pricing['sale_price'] = isset( $set['pricing']['sale_price'] ) ? sanitize_text_field( (string) $set['pricing']['sale_price'] ) : '';
                    $pricing['period']     = isset( $set['pricing']['period'] ) ? sanitize_key( (string) $set['pricing']['period'] ) : '';
                    $pricing['billing']    = isset( $set['pricing']['billing'] ) ? sanitize_text_field( (string) $set['pricing']['billing'] ) : '';
                }
                $trust = [];
                if ( ! empty( $set['trust_items'] ) && is_array( $set['trust_items'] ) ) {
                    $trust = array_values( array_filter( array_map( 'sanitize_text_field', (array) $set['trust_items'] ) ) );
                }
                $badge = [];
                if ( ! empty( $set['badge'] ) && is_array( $set['badge'] ) ) {
                    $badge_label = sanitize_text_field( (string) ( $set['badge']['label'] ?? '' ) );
                    $badge_color = sanitize_hex_color( $set['badge']['color'] ?? '' );
                    $badge_text  = sanitize_hex_color( $set['badge']['text_color'] ?? '' );
                    if ( '' !== $badge_label ) {
                        $badge = [
                            'label' => $badge_label,
                        ];
                        if ( $badge_color ) {
                            $badge['color'] = $badge_color;
                        }
                        if ( $badge_text ) {
                            $badge['text_color'] = $badge_text;
                        }
                    }
                }
                $featured = ! empty( $set['featured'] );

                return [
                    'id'          => $id,
                    'label'       => sanitize_text_field( (string) ( $set['label'] ?? ucfirst( $id ) ) ),
                    'specs'       => $specs,
                    'cta'         => $cta,
                    'hero_image'  => $hero,
                    'pricing'     => $pricing,
                    'badge'       => $badge,
                    'featured'    => $featured,
                    'trust_items' => $trust,
                ];
            },
            (array) $metadata['sample_sets'],
            array_keys( (array) $metadata['sample_sets'] )
        ) ) );

        return $metadata;
    }

    /**
     * Normalize spec helpers.
     *
     * @param mixed $specs
     * @return array
     */
    private static function sanitize_specs_list( $specs ): array {
        return array_values( array_filter( array_map(
            function( $spec ) {
                if ( ! is_array( $spec ) ) {
                    return null;
                }
                $label = sanitize_text_field( (string) ( $spec['label'] ?? '' ) );
                $value = sanitize_text_field( (string) ( $spec['value'] ?? '' ) );
                $icon  = isset( $spec['icon'] ) ? sanitize_key( (string) $spec['icon'] ) : '';

                if ( '' === $label && '' === $value ) {
                    return null;
                }

                $clean = [
                    'label' => $label,
                ];

                if ( '' !== $value ) {
                    $clean['value'] = $value;
                }

                if ( '' !== $icon ) {
                    $clean['icon'] = $icon;
                }

                return $clean;
            },
            (array) $specs
        ) ) );
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
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 3 seats', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '10 active projects', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '10GB' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Standard email support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                        [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( 'Slack & Zapier', 'planify-wp-pricing-lite' ) ],
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
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Up to 10 seats', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( '50 active projects', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '100GB' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( 'Priority email support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                        [ 'label' => __( 'Integrations', 'planify-wp-pricing-lite' ), 'value' => __( '20+ integrations', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Reporting', 'planify-wp-pricing-lite' ), 'value' => __( 'Advanced analytics', 'planify-wp-pricing-lite' ) ],
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
                        [ 'label' => __( 'Users', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited seats', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Projects', 'planify-wp-pricing-lite' ), 'value' => __( 'Unlimited projects', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'Storage', 'planify-wp-pricing-lite' ), 'value' => '1TB' ],
                        [ 'label' => __( 'Support', 'planify-wp-pricing-lite' ), 'value' => __( '24/7 priority support', 'planify-wp-pricing-lite' ), 'icon' => 'support-agent' ],
                        [ 'label' => __( 'Security', 'planify-wp-pricing-lite' ), 'value' => __( 'SSO & audit logs', 'planify-wp-pricing-lite' ) ],
                        [ 'label' => __( 'API access', 'planify-wp-pricing-lite' ), 'value' => __( 'Full REST API', 'planify-wp-pricing-lite' ) ],
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

    private static function thumbnail_url( string $filename ): string {
        $filename = ltrim( $filename, '/' );
        return trailingslashit( PWPL_URL ) . 'assets/admin/img/wizard-thumbs/' . $filename;
    }

    private static function template_demo_url( string $filename ): string {
        $filename = ltrim( $filename, '/' );
        return trailingslashit( PWPL_URL ) . 'assets/admin/img/template-demo/' . $filename;
    }
}
