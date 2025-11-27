# Table Editor V2 & New Table Wizard – Implementation Notes

_Last updated: 2025-11-27 • Branch: `feature/table-editor-v2-layout`_

This doc captures the current Table Editor V2 visual system and the planned **New Table Wizard** that will sit above it. Use this as the starting point in new workspaces.

---

## 1. Table Editor V2 – Current State (Admin V2)

- Lives on the `pwpl_table` edit screen as a single **carded shell**: left sidebar tabs + right‑hand accordion panels.
- All work on `feature/table-editor-v2-layout` is **visual/layout only** – no meta schema or save‑logic changes.

### 1.1 Shell, cards, sidebar

- Cards (Table Editor, Plan Drawer, dashboards) share a unified V2 style:
  - Background `#ffffff`, border `1px solid #e2e8f0`, radius `12px`, shadow `0 10px 24px rgba(15,23,42,.06)`, padding `16–20px`.
  - Panels sit on a pale shell background (`#f8fafc`/`#f9fafb`) so cards pop.
- Sidebar tabs (Layout & Spacing, Typography, Colors & Surfaces, Animation, Badges & Promotions, Advanced, Filters):
  - 14px, weight 600, Title Case labels.
  - Inactive: `#f8fafc` bg, `#e2e8f0` border.
  - Active: soft blue tint (`#eef3ff`), border `#c3d4ff`, subtle shadow.
  - Radius matches cards (≈12px) so tabs feel like small cards in a narrow column.

### 1.2 Accordion headers & panels

- Right‑pane sections are accordions (e.g. “Table Width & Columns”, “Plan Card Layout, Sizing & Spacing”).
- Accordion headers:
  - Title case, 17–18px, weight 700.
  - Color `#0f172a` when closed, soft Planify blue `#2563eb` when open.
  - Open header has a slim left accent bar in blue and slightly deeper shadow.
- Panels (`.pwpl-acc__panel`) host one or more inner cards; panel and card widths align, with a small vertical gap between header and panel.

### 1.3 Group headings, labels, typography

- Inner group captions (e.g. “CARD CONTAINER”, “CARD LAYOUT & MARGINS”, “CTA SIZE & LAYOUT”) use `.pwpl-group-heading`:
  - Uppercase, 13px, weight 700, letter‑spacing 0.06em, muted slate `#5c667a`.
  - Blue underline via `::after` (2px, ~42px wide, `#2563eb`, pill radius).
  - Used only for **group headings**, not for field labels or accordion titles.
- Field labels:
  - 13px, weight 600, color `#0f172a`, sentence case, no underline.
- Helper text:
  - 12–13px, weight 400, color `#6b7280`.
- Section descriptions under the main tab title:
  - 13px, color `#64748b`, comfortable line‑length (~68ch).

### 1.4 Layout patterns & alignment

- All cards and controls follow a single **left alignment rail**:
  - Card padding is unified so headings, labels, and controls start on the same x‑position.
- Rows/dividers:
  - `pwpl-row`, `pwpl-row__left`, `pwpl-row__control`, `pwpl-range`, `pwpl-sides` use grid/flex with `minmax(0, 1fr)` so sliders and four‑side inputs never overflow horizontally.
- 2‑column “workbench” sections:
  - Layout → “Plan Card Layout, Sizing & Spacing”.
  - Layout → “CTA Size & Layout” (size/spacing vs limits).
  - Typography → “Title Text” (left: base typography; right: style & shadow).
  - 2 columns on desktop; stack to 1 column below ~960px.

> **Important:** Table Editor V2 must not change meta keys, data shapes, save logic, frontend rendering, or onboarding behavior. All changes here are visual only.

---

## 2. New Table Wizard – Concept

The wizard is a **top layer** before Table Editor V2. It helps non‑technical users create a table using presets, then hands the result off to the existing editor.

### 2.1 User flow

1. From the Pricing Tables dashboard, user clicks “Create table” (or the empty‑state hero CTA).
2. Wizard opens (dedicated admin page or modal).
3. Steps:
   - **Step 1 – Select Template**
     - Left: grid of template thumbnails (e.g. “Pricing Grid”, “Pricing Columns”, “Comparison table”, “Service plans”).
     - Right: live preview of the selected template with demo plans.
   - **Step 2 – Select Layout**
     - Choose layout variant for the table (e.g. full‑width vs boxed, 3 vs 4 columns, carousel vs static).
   - **Step 3 – Select Column / Plan Card Style**
     - Choose card visual style (Classic, Minimal, Comparison, “Hero” featured column, etc.).
4. Finish:
   - Wizard creates a regular `pwpl_table` and a set of demo `pwpl_plan` posts using existing meta keys.
   - User is redirected to the `pwpl_table` edit screen where **Table Editor V2** can do deeper edits.

There is **no new data model**: the wizard only chooses and applies presets over the current table/plan meta.

---

## 3. Wizard Architecture (Planned)

### 3.1 PHP: templates & wizard helper

- `PWPL_Table_Templates` (new class)
  - Registry of built‑in templates.
  - Each template entry contains:
    - `id`, `label`, `description`, `thumbnail`, `theme`.
    - `defaults`:
      - Table meta (dimensions enabled, layout values, card options).
      - Demo plan definitions (titles, prices, features, badges, CTA).
- `PWPL_Table_Wizard` (new class)
  - Orchestrates preview + creation.
  - Key methods:
    - `build_preview_config( $template_id, $layout_id, $card_style_id )`
      - Returns an in‑memory config shaped like the data the frontend renderer already expects.
    - `create_table_from_selection( $template_id, $layout_id, $card_style_id )`
      - Creates a `pwpl_table` post and associated demo `pwpl_plan` posts.
      - Writes meta via existing `PWPL_Meta` logic (no new keys/sanitizers).

### 3.2 REST endpoints

- `pwpl/v1/preview-table` (POST)
  - Params: `template_id`, `layout_id`, `card_style_id`.
  - Uses `build_preview_config()` and the existing table renderer (or a small `render_from_config()` helper) to produce HTML.
  - Returns preview HTML (and, optionally, info about which CSS handles to enqueue).
- `pwpl/v1/create-table-from-wizard` (POST)
  - Params: template/layout/card style selection + table title.
  - Uses `create_table_from_selection()` and returns `{ table_id, redirect_url }`.

### 3.3 Wizard UI shell

- New admin page: e.g. `admin.php?page=pwpl-table-wizard` under the Planify top‑level menu.
- React app (new bundle, e.g. `assets/admin/js/table-wizard.js`) using `wp.element` / `wp.components`.
- Two‑pane layout:
  - Left: step content (template tiles, layout options, card style options).
  - Right: live preview pane.
- Preview uses an `<iframe>` that loads either:
  - A simple admin preview page that calls the shortcode, or
  - A REST‑driven HTML endpoint.  
  The iframe keeps wizard CSS isolated from frontend/table CSS.

---

## 4. Suggested Implementation Phases

When you pick this up in a new workspace, follow roughly this order:

1. **Audit & tidy Table Editor V2**
   - Confirm `admin-v1.css` and `table-editor-v1.js` match the visual rules above.
   - Fix any remaining overflow/misalignment.
2. **Introduce `PWPL_Table_Templates`**
   - Add a small set of built‑in templates expressed purely in existing meta/plan structures.
3. **Add `PWPL_Table_Wizard` + preview endpoint**
   - Implement `build_preview_config()` and `pwpl/v1/preview-table`.
   - Verify preview HTML matches shortcode output.
4. **Build Wizard Step 1 (template picker + preview)**
   - New admin page + React shell, wired to preview endpoint.
5. **Add Steps 2 & 3 (layout + card style)**
   - UI for layout and card style options, feeding into preview.
6. **Implement create‑table endpoint + redirect**
   - `pwpl/v1/create-table-from-wizard` creates the real table + plans and redirects to Table Editor V2.
7. **Docs & changelog**
   - Update `README.md`, `readme.txt`, `CHANGELOG.md`, and `Planify_Future_Plan.md` once the wizard ships.

Throughout all phases, keep:

- Meta keys, data shape, and sanitization **unchanged**.
- Onboarding tours and `data-pwpl-tour` targets intact.
- Frontend rendering (themes/templates) driven by the same data contract.

