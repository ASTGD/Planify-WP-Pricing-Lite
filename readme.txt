=== Planify WP Pricing Lite ===
Contributors: planify
Tags: pricing, pricing tables, tables
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 0.1.0
Requires PHP: 7.4
License: Proprietary

Lightweight, developer‑friendly pricing tables for WordPress.

== Description ==
Planify WP Pricing Lite provides a foundation for managing pricing tables via custom post types and rendering them with a shortcode. A top-level **Planify** menu opens the Pricing Tables dashboard cards view where you can add tables, jump into per-table Plans Dashboards, and reach plugin settings. On first run, a guided welcome state shows you how to configure settings, create your first table, add plans, and embed the shortcode.

== Features ==
* CPTs: `pwpl_table` and `pwpl_plan`
* Shortcode: `[pwpl_table id=]`
* Pricing Tables dashboard (cards view) and per-table Plans Dashboard
* First-run dashboard guidance (configure settings → create table → add plans → embed)
* Dashboard cards show stats, plan counts, updated dates, shortcodes, and quick actions
* Guided journey from first table to first plan (dashboard notice + Plans Dashboard empty state)
* Optional onboarding tour for the Table Editor to guide layout, theme, filters, and shortcode
* Modern Plan Editor drawer (two-pane layout with structured sections for basics, specs, variants, promotions, and advanced options)
* Admin settings page for basic defaults under Planify → Settings
* Frontend/admin assets scaffold

== Installation ==
1. Upload the plugin to `/wp-content/plugins/planify-wp-pricing-lite`.
2. Activate from Plugins screen.
3. Open **Planify** in the WP Admin menu to create a Pricing Table, then insert it via `[pwpl_table id=123]`.

== Frequently Asked Questions ==
= Where are the settings? =
Under Pricing Tables → Settings.

= Can I customize templates? =
Yes, see `templates/table.php` as a starting point.

== Changelog ==
= 0.1.0 =
* Initial skeleton with CPTs, shortcode, assets, and settings page.
