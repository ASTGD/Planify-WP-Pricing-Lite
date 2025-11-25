# Planify WP Pricing Lite

Modern, responsive pricing tables for WordPress – with a table editor built for hosting & SaaS plans and a FireVPS‑ready frontend theme.

> This README describes the **V1 editor + rendering** on the current branch, which is newer than the legacy `readme.txt`.

---

## What It Does

Planify WP Pricing Lite lets you:

- Create reusable **Pricing Tables** and attach multiple **Plans** to each table.
- Offer plans across three dimensions:
  - **Platform** (e.g. Linux, Windows, CyberPanel, cPanel),
  - **Period** (Monthly, Annual, etc.),
  - **Location** (US, UK, DE, SG, …).
- Define **price variants** per Platform × Period × Location.
- Configure **badges & promotions** (e.g. “Best value”, “Save 40%”, “Limited time”) at table or per‑plan level.
- Use the new **Table Editor V1** in WP Admin to visually control:
  - Table width, breakpoints, columns and card widths,
  - Plan card layout (split top/specs, border, radius, paddings),
  - Typography and colors,
  - CTA button size, style and font,
  - Specs list style and animation,
  - Trust trio row and sticky mobile CTA bar.
- Render everything on the frontend via a single shortcode:

```text
[pwpl_table id="123"]
```

The FireVPS theme included in this branch ships an opinionated, high‑conversion layout tuned for hosting plans, but the engine is generic enough for other products.

---

## Requirements

- WordPress **6.0+**
- PHP **7.4+**

---

## Getting Started

When you click **Planify** for the first time, the Pricing Tables dashboard shows a guided welcome state with steps to configure currency, create your first table, add plans, and embed the shortcode.

### 1. Install & Activate

1. Copy this folder to:  
   `wp-content/plugins/planify-wp-pricing-lite`
2. In **WP Admin → Plugins**, activate “Planify WP Pricing Lite”.

### 2. Configure Global Settings

Go to **Planify → Settings** (`pwpl-settings`):

- Set **currency** (symbol, position, separators, decimals).
- Define global **Platforms**, **Periods**, and **Locations**:
  - One value per line; labels are used in the UI,
  - Slugs are generated automatically and used internally.

These values power the filters and variant pickers across all tables.

### 3. Create a Pricing Table

1. Click **Planify** in the WP Admin menu to open the Pricing Tables dashboard. On first run you’ll see a welcome view with a “Create your first pricing table” call to action.
2. Use **Add Pricing Table** from the dashboard header (or a table card’s quick action once tables exist) to create a new table. When tables exist, the dashboard shows stats, plan counts, updated dates, and quick actions for each table.
3. Give your table a title (e.g. “VPS Hosting”).
4. Use the **Table Editor – V1 (Preview)** meta box to:
   - Choose a **theme** (e.g. FireVPS, Warm, Blue, Modern Discount, Classic).
   - Configure widths, breakpoints and card layout.
   - Enable filters (Platform / Period / Location) and pick allowed values.
   - Set badges, CTA styling, specs behavior, trust items and sticky mobile bar.
   - Optional: take the built-in **Table Editor tour** for a 60-second walkthrough of layout, theme/colors, filters, and where to grab the shortcode.
5. Publish the table.

### 4. Create Plans & Variants

1. From the Pricing Tables dashboard (Planify → Pricing Tables), click **Manage Plans** on a table to open its per-table Plans Dashboard. The Plans view uses a **two-pane layout**:
   - A tight **plan list** on the left (one row per plan),
   - A wide **Plan Drawer** editor on the right.
   Selecting a row loads the Plan Drawer inline on desktop; on narrow widths the same drawer appears as a modal.
   - When a table has **no plans yet**, the Plans Dashboard shows a guided empty state with a “Create first plan” CTA and a quick explainer of plans/variants/badges.
2. Assign the plan to a Pricing Table (Manage Plans will pre-assign it).
3. Fill in:
   - Specs: CPU, RAM, storage, bandwidth, etc.
   - Pricing Variants:
     - Define one or more variants per Platform / Period / Location combination,
     - Each variant has base price and optional sale price,
     - CTA label, URL, target, `rel`, and availability.
     - Variants are managed in a navigator + detail layout: a middle column list and a right-hand details card, with filters for Platform/Period/Location.
   - Promotions & Advanced:
     - Optional plan‑level badge overrides (period / location / platform groups + priority),
     - “Featured” flag and optional plan theme override.
4. Publish. Repeat for each plan you want in the table.

### 5. Embed in Pages

Use the shortcode anywhere shortcodes are supported:

```text
[pwpl_table id="123"]
```

Replace `123` with the ID of your `pwpl_table` post. The frontend JS handles:

- Filtering by platform / period / location,
- Price + discount display,
- Badge resolution and timing,
- CTA and availability states,
- Plan rail scrolling on smaller screens.

---

### Admin navigation

- The **Planify** top-level menu opens the Pricing Tables dashboard. On first run it shows a guided welcome/empty state; once tables exist it shows a cards view with stats, plan counts, updated dates, shortcodes with copy buttons, and quick actions (Edit Table, Manage Plans). After creating a table from the dashboard CTA, you’ll get a notice with a direct link to Manage Plans.
- Each table’s **Manage Plans** action opens the per-table Plans Dashboard (two-pane layout with inline Plan Drawer and “Open full editor” links for each plan).
- Plugin **Settings** live under **Planify → Settings** using the existing `pwpl-settings` slug.
- Table Editor V1 includes an optional onboarding tour to highlight key areas (layout, theme/colors, filters, shortcode/publish).

---

## Architecture Overview

High‑level structure:

- **Bootstrap**: `planify-wp-pricing-lite.php`
  - Defines constants and a small autoloader for `PWPL_` classes.
  - Hooks `PWPL_Plugin::init()` on `plugins_loaded`.
  - Registers activation/deactivation hooks for CPT rewrites.

- **Core classes (`includes/`)**
  - `class-pwpl-plugin.php` – wires together CPTs, meta, shortcode, admin, settings, and V1 UI.
  - `class-pwpl-cpt.php` – registers:
    - `pwpl_table` (pricing tables, hidden from the default CPT menu),
    - `pwpl_plan` (plans attached to a table, hidden from menus; navigated via dashboards).
  - `class-pwpl-meta.php` – all structured post meta:
    - Dimensions, allowed values and badges,
    - Layout widths, breakpoints, card config, CTA config,
    - Specs and variants sanitization,
    - Trust trio and sticky bar toggles.
  - `class-pwpl-settings.php` – settings page and option sanitization.
  - `class-pwpl-shortcode.php` – turns a table ID into:
    - A rendered markup shell for tabs, plan rail, and badges,
    - Inline CSS variables based on stored layout meta,
    - Enqueued frontend assets and localized currency settings.
  - `class-pwpl-admin.php` – classic admin enhancements:
    - Enqueues `assets/admin/css/admin.css` and `assets/admin/js/admin.js`,
    - Adds sortable columns and ordering for plans,
    - Registers the top-level Pricing Tables dashboard and hidden Plans Dashboard route.
  - `class-pwpl-admin-meta.php` – legacy meta boxes for table & plan configuration (still used for some controls).
  - `class-pwpl-admin-ui-v1.php` – V1 Table Editor meta box:
    - Enqueues `assets/admin/css/admin-v1.css` and `assets/admin/js/table-editor-v1.js`,
    - Localizes all layout/meta state into a React app powered by `wp-element` / `wp-components`.
  - `class-pwpl-theme-loader.php` – discovers “themes” from:
    - `/wp-content/uploads/planify-themes/…`,
    - The active theme’s `/planify-themes/…`,
    - This plugin’s `/themes/…`,
    - Using `manifest.json` files.
  - `functions-theme.php` – helper to locate overridable theme files.

- **Frontend assets (`assets/`)**
  - `css/frontend.css` – core layout for tabs, plan rail, cards, CTA, badges, and responsive width behavior via CSS vars.
  - `css/themes.css` – design tokens per theme (Warm, Blue, Modern Discount, etc.).
  - `js/frontend.js` – handles:
    - Dimension selection and active state,
    - Variant resolution and price formatting,
    - Badges, location chips, CTA states,
    - Platform‑specific availability for periods/locations,
    - Plan rail navigation.

- **Themes (`themes/`)**
  - `firevps/` – production FireVPS theme:
    - `manifest.json` – theme metadata and asset mapping.
    - `template.php` – FireVPS‑specific markup for the table and cards.
    - `theme.css` – full FireVPS visual design (glass tabs, card rails, icons).
    - `theme.js` – tab overflow management, scroll rails, plan rail controls.
  - `warm/`, `blue/`, `modern-discount/` – additional starter themes with manifests and placeholder templates/styles.

---

## Theming & Overrides

You can ship additional themes without modifying core plugin code:

1. Create a folder under one of:
   - `wp-content/uploads/planify-themes/<your-theme>/`
   - `wp-content/themes/<your-active-theme>/planify-themes/<your-theme>/`
   - `wp-content/plugins/planify-wp-pricing-lite/themes/<your-theme>/`
2. Add a `manifest.json` describing the theme:
   - At minimum: `slug`, `name`, `assets` (or `styles`/`scripts` in older manifests).
3. Provide `template.php`, `theme.css`, and optionally `theme.js`.
4. The theme will appear in the table editor as a selectable option.

The FireVPS theme is the best reference for a fully‑featured implementation.

---

## Development Notes

- Text domain: `planify-wp-pricing-lite`
- PHP class prefix: `PWPL_`
- Frontend and admin assets:
  - Versioned with `filemtime()` for cache‑busting in development.
  - Admin assets only load on `pwpl_table` / `pwpl_plan` screens and the settings page.
- Node tooling:
  - `@playwright/test` is included but only ships example specs; there is no full E2E suite yet.

For contribution guidelines and how Codex should behave in this repo, see `AGENTS.md`.

---

## License

License is defined by the project owner (not yet finalized in this repository).
