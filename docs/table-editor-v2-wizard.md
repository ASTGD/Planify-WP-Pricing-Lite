# Table Editor V2 & New Table Wizard – Implementation Notes

_Last updated: 2025-11-27 • Branch: `feature/table-wizard-v1`_

This doc captures the Table Editor V2 visual system and the planned **New Table Wizard** that will sit above it. Use this as the starting point in new workspaces.

**Status:** Wizard foundations (`PWPL_Table_Templates`, `PWPL_Table_Wizard`) are in place; preview rendering and REST endpoint (`pwpl/v1/preview-table`) now exist. UI/iframe wiring will follow next.

---

## 1. Table Editor V2 – Current State (Admin V2)

- Two-pane shell: left sidebar tabs, right-hand accordion panels.
- Unified V2 card styling: white cards, `#e2e8f0` border, 12px radius, soft shadow on a pale shell.
- Typography hierarchy:
  - Sidebar tabs: ~14px, weight ~600, Title Case.
  - Accordion titles: 17–18px, weight 700, Title Case, no underline; active title uses soft Planify blue.
  - Group headings inside panels: `.pwpl-group-heading` (all caps, ~13px, muted gray, Planify blue underline).
  - Field labels: 13px, semi-bold, sentence case; helpers: 12–13px muted.
- Layout:
  - Grid/flex with `gap`, `minmax(0,1fr)` to avoid overflow.
  - Single left alignment rail so headings, labels, sliders, and inputs align.
  - 2-column “workbench” layouts where dense: e.g., Plan Card Layout, CTA Size & Layout, Title Text typography (stacks to 1 col on narrow widths).
- Behavior: unchanged meta/schema/save logic, unchanged shortcode/frontend, unchanged onboarding tours.

---

## 2. New Table Wizard – Concept

The wizard is a top layer before Table Editor V2:

1. **Step 1 – Pick Template**: grid of presets in the left pane with category/Pro chips + live preview on the right. Continue button is disabled until a template is selected.
2. **Step 2 – Configure Layout & Columns**: choose a user-friendly layout type (Grid / Carousel / Comparison / Classic mapped to existing layout variants), toggle dimensions (Platform/Period/Location), pick card style, and manage plan columns via a column editor:
   - Column list with 3-dot menu (Edit/Duplicate/Hide/Delete) and Add (+).
   - Edit Column panel for title/subtitle, highlight/featured, specs/features, primary variant price/sale, and CTA text/link (schema-compatible).
3. **Step 3 – Create & handoff**: summary of template/layout/columns/dimensions + table name/theme inputs; actions to create a `pwpl_table` + demo `pwpl_plan` posts and open the editor (or copy shortcode). No new data model; this only changes the seeded plan count/contents and variant choices.

Finish: create a real `pwpl_table` + demo `pwpl_plan` posts with existing meta keys, then redirect into Table Editor V2 (or copy shortcode). No new schema.

---

## 3. Wizard Architecture (planned)

**Backend**
- `PWPL_Table_Templates`: registry of starter templates; table + plan defaults using existing meta keys only.
- Templates can be extended via the `pwpl_table_wizard_templates` filter; each template may declare a `category` and `premium` flag that the wizard surfaces as chips/Pro badges.
- Templates now also declare `layout_type` (e.g., `grid`, `comparison`, `hero`) and a visual `preset` slug; these are persisted to table meta so themes can swap layouts/presets without changing schema.
- FireVPS routes through `template.php` to layout partials (`layouts/grid.php`, `layouts/columns.php`, `layouts/comparison.php`) based on `layout_type`; `columns` and `comparison` now render dedicated layouts (service-style columns and a comparison matrix) while keeping the same meta/rendering model.
- Five wizard presets (SaaS 3 Column, Startup Pricing Grid, Feature Comparison Table, Service Plans, App Pricing) map to distinct FireVPS systems (colors, card style, CTA treatment, specs styling) and will use the appropriate layout partial. SaaS 3 Column also supports an optional per‑plan hero image (`PLAN_HERO_IMAGE`) rendered above the plan title for that preset only.
- Service Plans is the single Services preset; it now ships with four tiers (Free, Starter, Pro, Premium), crisp white single-surface cards, larger ticked features, and a horizontal slider layout. Legacy Service Columns remains for existing tables but is hidden from the wizard.
- FireVPS cards now align CTAs on a shared baseline and use preset card-surface tokens to avoid bottom “white bands” when plan content heights differ.
- Comparison preset now renders as a courses-style comparison matrix: a teal feature stub column on the left and three plan columns with prices/CTAs and tick/cross cells in the spec grid.
- New template: **App Pricing – Soft Cards** (SaaS) uses the grid layout with soft cards, purple CTAs, and a flat spec list; middle card is lightly highlighted.
- **Starter Pricing Grid (saas-grid-v2)** is now an app-style hero grid: billing toggle + “Save 15%” badge, three illustrated hero cards inside a unified frame, striped feature rows, and a “Most Popular” ribbon on the featured plan.
- `PWPL_Table_Wizard`: builds in-memory preview configs and can create actual tables/plans from a selection (no new schema).
- `PWPL_Rest_Wizard`: exposes preview/create endpoints; handles missing templates safely and supports an optional debug mode (constant/filter) that logs wizard selections and timings to the PHP error log (local only, no external tracking).
- Preview/create endpoints accept an optional `plan_count` to clone demo plans for the starter table (no schema change) and an optional `plans_override` payload when columns are edited (title/subtitle/specs/variants/CTA/featured/highlight), still using existing meta keys.
- Layout type selection in the wizard maps to existing template layout variants (Grid/Carousel/Comparison/Classic labels are UX-friendly aliases; fall back gracefully to available variants).

**Preview**
- REST: `pwpl/v1/preview-table` (to be added) should call `build_preview_config()` and reuse the shortcode renderer (or helper) to return HTML.
- Preview is rendered in an iframe to isolate CSS.

**UI**
- Admin page: `admin.php?page=pwpl-table-wizard`.
- Two-pane layout (vanilla JS): left steps, right live preview (iframe).
- Steps: template → layout & columns (layout type, dimensions, card style, column editor with per-column edit/duplicate/hide/delete + Add column) → create & redirect/copy.
- Starter pack includes multiple distinct templates (e.g., SaaS grids, service columns, comparison matrix, image hero, minimal) defined in `PWPL_Table_Templates`, with thumbnails mapped via `TEMPLATE_VISUALS`.

---

## 4. Implementation phases (recommended)

1. **Foundations (done here):** template registry + wizard helper (no UI/REST).
2. Add `pwpl/v1/preview-table` + minimal preview frame.
3. Add wizard admin page + React shell; implement Step 1 (template picker + preview).
4. Add Step 2 (layout & columns) and Step 3 (card style/create), wired to preview (done).
5. Add `pwpl/v1/create-table-from-wizard` to create table/plans and redirect to Table Editor V2 (done).
6. Wire entry points from the dashboard and update docs/changelog (done).

Keep all meta keys/sanitizers unchanged; reuse the existing data contract and rendering stack.

## 5. Entry points & flow (current)

- Wizard lives at `admin.php?page=pwpl-table-wizard` under the Planify menu.
- Dashboard links:
  - Empty state: primary CTA “Create your first pricing table (wizard)” plus a classic fallback.
  - Populated: secondary action “New table (wizard)” in the header actions.
- Flow:
  - Step 1: choose template and continue.
  - Step 2: pick layout type (mapped to existing variants), dimensions, card style, and manage plan columns (list + 3-dot menu edit/duplicate/hide/delete, Add column, per-column editor for basics/specs/primary price/CTA).
  - Step 3: review summary, set name/theme, create table (open editor) or create + copy shortcode.
  - Wizard calls `pwpl/v1/create-table-from-wizard`, creates `pwpl_table` + demo `pwpl_plan`, then redirects to the table edit screen (Table Editor V2) or returns the shortcode.
