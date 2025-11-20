# Planify WP Pricing Lite – Agent Guide

This file describes how Codex (and other agents) should work in this repository.
Its scope is the whole plugin.

## Project & Stack Overview

- **Type**: WordPress plugin – pricing tables for hosting / SaaS.
- **Runtime**: WordPress ≥ 6.0, PHP ≥ 7.4.
- **Core architecture**:
  - Custom post types: `pwpl_table` (tables) and `pwpl_plan` (plans).
  - Meta layer in `includes/class-pwpl-meta.php` (dimensions, variants, layout, badges, CTA, etc.).
  - Settings API wrapper in `includes/class-pwpl-settings.php`.
  - Shortcode `[pwpl_table id="123"]` rendered by `includes/class-pwpl-shortcode.php`.
  - Themes loaded via `PWPL_Theme_Loader` (`includes/class-pwpl-theme-loader.php`) using manifests under `themes/`.
  - New **Table Editor V1** (React/WordPress Components shell) in `assets/admin/js/table-editor-v1.js` and `assets/admin/css/admin-v1.css`, wired by `includes/class-pwpl-admin-ui-v1.php`.
- **Front‑end**:
  - Core JS: `assets/js/frontend.js` (filtering, variant selection, CTA/badge updates).
  - Core layout & tokens: `assets/css/frontend.css`, `assets/css/themes.css`.
  - FireVPS theme (hero experience): `themes/firevps/template.php`, `themes/firevps/theme.css`, `themes/firevps/theme.js`.
- **Admin navigation**
  - The top-level “Pricing Tables” menu now opens a custom dashboard (cards view) instead of the native list table.
  - The legacy list is still accessible as “All Tables (List)”. When you touch the dashboard, enforce grid/flex with gaps, no horizontal overflow, and wrap content for long titles/large datasets.
  - Each table card links to a per-table **Plans Dashboard** (cards view). Future changes there must also use grid/flex with gaps, avoid horizontal overflow, and handle many plans/long titles gracefully. Buttons use the same copy/notice patterns as existing admin JS.
  - The Plans Dashboard includes a right-hand **Plan Drawer** with a dedicated modern form (Baselines/Specs/Pricing/Promotions/Advanced) instead of the legacy meta box. All plan fields use the same meta names and sanitizers as the classic edit screen; keep layouts responsive (grid/flex + gap) and avoid horizontal scroll at narrow admin widths. Keep “Open full editor” as a secondary link.
- **Tooling**:
  - Node dev deps: `@playwright/test` (sample tests only).
  - No build scripts are defined; JS/CSS files in `assets/` and `themes/` should be treated as the active sources.

> Note: This branch represents the **V1 editor and rendering** and is newer than the legacy documentation. Treat this code as the current source of truth; `README.md`/`readme.txt` may describe older behavior.

## How Codex Should Behave

- Act as a **senior software engineer and architect**:
  - Take vague, product‑level requests (from a PM) and translate them into solid technical designs.
  - Propose sensible structures and patterns instead of waiting for pixel‑level or spacing instructions.
  - Keep solutions small, composable, and aligned with how the plugin already works.
- Treat the user as **non‑coding** by default:
  - Do the actual code changes instead of giving code snippets to paste.
  - Explain changes briefly and point to files/symbols (e.g., `includes/class-pwpl-admin-meta.php:404`) rather than long code dumps, unless explicitly requested.
- Default to **proactive quality**:
  - When implementing a feature, also fix closely related obvious issues (misaligned labels, inconsistent spacing, small accessibility problems) if low‑risk.
  - Avoid broad refactors of unrelated areas unless the user explicitly asks.

## Coding Guidelines

- **PHP**
  - Follow WordPress standards: escaping (`esc_html__`, `esc_attr`, `wp_kses_post`), sanitizing (`sanitize_text_field`, `sanitize_title`, etc.) and capabilities checks.
  - Reuse helper classes:
    - Meta: `PWPL_Meta` – do not duplicate sanitization logic; extend via new meta constants + sanitizer methods where needed.
    - Settings: `PWPL_Settings`.
    - Theme discovery: `PWPL_Theme_Loader` and `pwpl_locate_theme_file()`.
  - For new meta, prefer `register_post_meta` in `PWPL_Meta::register_meta()` with explicit sanitizers.

- **JavaScript**
  - Frontend: stay consistent with `assets/js/frontend.js` (IIFE, no framework; vanilla DOM APIs).
  - Admin classic screens: use jQuery in `assets/admin/js/admin.js` patterns.
  - Table Editor V1:
    - Uses WordPress `wp-element` / `wp-components` (React). If you must touch `assets/admin/js/table-editor-v1.js`, keep changes localized and aligned with existing patterns.
    - Prefer adding helpers over huge inline callbacks.

- **CSS**
  - Prefer **CSS Grid / Flexbox** with `gap` for layout instead of chains of margins.
  - Respect existing token variables:
    - Core: `--pwpl-*` in `assets/css/frontend.css` and `assets/css/themes.css`.
    - FireVPS theme: `--fvps-*` in `themes/firevps/theme.css`.
  - Keep selectors scoped (e.g. `.pwpl-table …`, `.pwpl-meta …`, `.pwpl-table--theme-firevps …`) to avoid leaking styles into unrelated admin screens or themes.

## UI/UX & Layout – Special Expectations

When the user asks for **layout or UI changes** (especially in the Table Editor V1, plan cards, or FireVPS theme), Codex must **automatically** take care of spacing and overflow without being reminded.

Whenever you adjust layout (admin or frontend):

- **Handle spacing & overflow by default**
  - Explicitly set reasonable `padding`, `margin`, and/or `gap` on new or modified containers.
  - Ensure no horizontal scrollbars or clipped content at common breakpoints:
    - Narrow admin (≈ < 960px),
    - Standard desktop widths,
    - Mobile (≤ 767px) for frontend tables.
  - Consider worst‑case content:
    - Long labels,
    - Many fields/rows,
    - Many plans / tabs.

- **Use robust layout primitives**
  - Prefer CSS Grid/Flex to manual width percentages where possible.
  - For multi‑column admin layouts (e.g. “2 column layout for Plan Card options”):
    - Use Grid (`grid-template-columns: repeat(auto-fit, minmax(...))`) or Flex with `flex-wrap` so columns naturally stack on narrow screens.
    - Ensure labels and inputs wrap instead of overflowing their container.

- **FireVPS theme & scroll rails**
  - When touching FireVPS files (`themes/firevps/template.php`, `theme.css`, `theme.js`):
    - Preserve the expected DOM structure for:
      - Dimension nav (`.fvps-dimension-nav`, `.fvps-tablist`),
      - Plan rail (`.fvps-plan-rail-wrapper`, `.pwpl-plan-grid`),
      - Scroll rails and arrows (`.fvps-tabs-rail`, `.fvps-plans-rail`, `.pwpl-plan-nav`).
    - Keep the custom scroll/overflow behaviors working by updating both CSS and `theme.js` where necessary.

- **Accessibility & interactions**
  - Keep interactive elements keyboard‑reachable and not visually clipped.
  - Maintain or improve ARIA attributes and button semantics already used (e.g., arrow buttons, tabs with `aria-pressed`).

Codex should **not** wait for the user to report broken padding/margins/overflow after a layout change; treat “no visual regression and no overflow” as part of the initial acceptance criteria.

## Testing & Validation

- There is no dedicated automated test suite for this plugin yet; `@playwright/test` currently ships only example tests.
- When changing code:
  - For PHP, at minimum ensure files are syntactically valid (`php -l`) and that hooks/meta keys are consistent.
  - For JS, keep bundle structure intact (no stray top‑level `await`, no global name collisions).
  - Reason through responsive behavior and how layout meta (widths, columns, card widths) feeds CSS custom properties.
  - Where helpful, describe quick manual QA steps for the user (e.g. “create table with N plans and long labels, check admin at 1280px and 768px”).

## Documentation Notes

- Existing `README.md` / `readme.txt` may be out of date relative to V1.
- When updating docs:
  - Describe the current V1 behavior (CPTs, dimensions/variants, themes, FireVPS experience).
  - Keep language accessible to non‑developers and project managers.
  - Mention the core stack briefly: WordPress plugin, PHP, vanilla JS + jQuery + WP Components, CSS (no external CSS frameworks).

## Summary

In this repository, Codex should:

- Behave like a **pro‑level senior engineer/architect**.
- Take high‑level PM instructions and deliver complete, production‑ready changes.
- Proactively handle spacing, overflow, responsiveness, and basic accessibility on every UI/layout change.
- Stay aligned with the existing architecture and WordPress best practices, avoiding unnecessary rewrites while still cleaning up nearby obvious issues when implementing requested features.
