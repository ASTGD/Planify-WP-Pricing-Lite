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

1. **Select Template**: grid of presets + live preview.
2. **Select Layout**: width/columns/rail style variant.
3. **Select Plan Card Style**: column/card visual pattern.

Finish: create a real `pwpl_table` + demo `pwpl_plan` posts with existing meta keys, then redirect into Table Editor V2. No new data model.

---

## 3. Wizard Architecture (planned)

**Backend**
- `PWPL_Table_Templates`: registry of starter templates; table + plan defaults using existing meta keys only.
- `PWPL_Table_Wizard`: builds in-memory preview configs and can create actual tables/plans from a selection (no new schema).

**Preview**
- REST: `pwpl/v1/preview-table` (to be added) should call `build_preview_config()` and reuse the shortcode renderer (or helper) to return HTML.
- Preview is rendered in an iframe to isolate CSS.

**UI**
- Admin page: `admin.php?page=pwpl-table-wizard`.
- React app (wp.element/wp.components) with two panes: left selections, right live preview.
- Steps: template → layout → card style → create & redirect.

---

## 4. Implementation phases (recommended)

1. **Foundations (done here):** template registry + wizard helper (no UI/REST).
2. Add `pwpl/v1/preview-table` + minimal preview frame.
3. Add wizard admin page + React shell; implement Step 1 (template picker + preview).
4. Add Step 2 (layout) and Step 3 (card style), wired to preview (done).
5. Add `pwpl/v1/create-table-from-wizard` to create table/plans and redirect to Table Editor V2 (done).
6. Wire entry points from the dashboard and update docs/changelog (done).

Keep all meta keys/sanitizers unchanged; reuse the existing data contract and rendering stack.

## 5. Entry points & flow (current)

- Wizard lives at `admin.php?page=pwpl-table-wizard` under the Planify menu.
- Dashboard links:
  - Empty state: primary CTA “Create your first pricing table (wizard)” plus a classic fallback.
  - Populated: secondary action “New table (wizard)” in the header actions.
- Flow:
  - Choose template → layout → card style → Create table.
  - Wizard calls `pwpl/v1/create-table-from-wizard`, creates `pwpl_table` + demo `pwpl_plan`, then redirects to the table edit screen (Table Editor V2).
