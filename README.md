# Planify WP Pricing Lite

Lightweight, developer‑friendly WordPress plugin to create and render pricing tables.

## Features
- Custom post types: `pwpl_table` (tables) and `pwpl_plan` (plans)
- Shortcode: `[pwpl_table id=]`
- Minimal frontend and admin asset scaffolding
- Clean class structure with a tiny autoloader

## Requirements
- WordPress 6.0+
- PHP 7.4+

## Installation (dev)
1. Place this folder in `wp-content/plugins/planify-wp-pricing-lite`.
2. Activate “Planify WP Pricing Lite” from WP Admin → Plugins.
3. Create a Pricing Table (`pwpl_table`) and use the shortcode: `\[pwpl_table id=123]`.

## Project Structure
- `planify-wp-pricing-lite.php` — main plugin bootstrap
- `includes/`
  - `class-pwpl-plugin.php` — orchestrates initialization
  - `class-pwpl-cpt.php` — registers CPTs
  - `class-pwpl-shortcode.php` — shortcode rendering
  - `class-pwpl-admin.php` — admin assets/hooks
- `assets/`
  - `css/frontend.css` — public styles
  - `js/frontend.js` — public script
  - `admin/css/admin.css` — admin styles
  - `admin/js/admin.js` — admin script
- `templates/table.php` — markup placeholder

## Development Notes
- Text domain: `planify-wp-pricing-lite`
- PHP class prefix: `PWPL_`
- Enqueues:
  - Admin CSS/JS only on `pwpl_table` and `pwpl_plan` screens
  - Frontend CSS/JS when the shortcode renders
- Asset versions use `filemtime()` for cache busting during development.

## Roadmap
- Admin UI to compose tables (meta fields / block editor)
- Template system with overrides via theme
- Gutenberg block for inserting tables

## License
Proprietary or TBD by project owner.

