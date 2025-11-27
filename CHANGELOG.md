# Changelog

All notable changes to this project will be documented in this file.

The V1 work has been developed on the `feature/admin-ui-ux-v1` branch and will replace the older main branch once merged.

---

## 1.8.9 – New Table Wizard (v1)

### Added
- New Table Wizard under the Planify menu: pick a template, layout, and card style with live preview, then create a table with demo plans and jump into Table Editor V2.
- Pricing Tables dashboard links to the wizard from the empty state and as a secondary “New table (wizard)” action when tables exist.

### Changed
- No behavioral changes to existing editor/frontend; this is a guided creation flow on top of current meta.

---

## 1.8.8 – Table Wizard foundations

### Added
- Backend scaffolding for the upcoming New Table Wizard: template registry with starter presets and a wizard helper to build preview configs and create tables/plans using existing meta keys (no UI yet).
- Preview rendering via new `pwpl/v1/preview-table` REST endpoint and a minimal admin preview frame for iframe use.
- Admin wizard page shell (`admin.php?page=pwpl-table-wizard`) with script enqueue/localized data for the future React UI (templates + REST/preview URLs).

### Changed
- No behavioral changes to the editor or frontend rendering; this is preparatory work only.

---

## 1.8.7 – Dashboard card polish

### Added
- “Move to Trash” action on each Pricing Table card with capability checks and confirmation, so tables can be trashed without leaving the dashboard.

### Changed
- Refined dashboard header/actions and card grid for cleaner spacing and responsiveness across 1, 2, or many tables; shortcode + actions stay aligned without overflow.

---

## 1.8.6 – Plan Editor V2 styling

### Added
- Refined Plan Drawer layout in the Plans Dashboard: structured cards for Basics, Specs, Pricing Variants, Promotions, and Advanced, aligned with Table Editor V1 styling.
- Plan status control inside the Plan Drawer, allowing Draft/Published changes without opening the classic editor.

### Changed
- Improved spacing, typography, and responsiveness in the Plan Drawer without changing behavior or meta schema.

---

## 1.8.5 – Table Editor onboarding tour

### Added
- Generic onboarding/coachmarks system plus an optional 60-second tour for Table Editor V1 (layout, theme/colors, filters, shortcode/publish).

### Changed
- Table Editor V1 now surfaces a guided tour without altering existing editing behavior. Polished flow: centered modal, correct targets (title, sections, each tab: layout, typography, colors, animation, badges, advanced, filters), shortcode/publish, and working Back/Finish controls with tab switching.

---

## 1.8.4 – First table → first plan journey

### Added
- Post-creation notice on the Pricing Tables dashboard with a direct “Manage Plans” link after creating a table from the dashboard CTA.
- Guided empty state for the per-table Plans Dashboard when no plans exist: hero copy, “Create first plan” CTA, and bullets explaining plans/variants/promotions.

### Changed
- Plans Dashboard now includes helper text for populated tables, clarifying inline editing vs. full editor without altering behavior.

---
## 1.8.3 – Populated dashboard polish

### Added
- Refined Pricing Tables dashboard header/actions and help panel for sites with existing tables, aligning visuals with the first-run experience.

### Changed
- Table stats, cards, and shortcode sections now use the updated Planify styling with clearer plan counts, updated dates, and quick actions.

---

## 1.8.2 – First-run welcome dashboard

### Added
- Guided first-run empty-state for the Planify dashboard with hero CTA, getting-started checklist (settings → table → plans → embed), and inline learning links.

### Changed
- When no tables exist, the Pricing Tables dashboard now shows a product-style welcome view instead of the grid, while the populated state remains unchanged.

---

## 1.8.1 – Admin menu cleanup

### Added
- Dedicated top-level **Planify** menu that opens the Pricing Tables dashboard cards view and acts as the plugin’s home.
- **Settings** now lives as a submenu under Pricing Tables for quicker access.

### Changed
- Removed raw CPT menus (Tables/Plans lists and add-new shortcuts) from the sidebar so the dashboard is the primary entry point and tweaked the main menu label to match the plugin name.

---

## 1.8.0 – Two‑Pane Plans Dashboard & Inline Drawer

### Added
- **Two‑pane Plans Dashboard** for each pricing table: a tight, scrollable plan list on the left and a wide editor pane on the right to clearly distinguish plans management from the main tables grid.
- **Inline Plan Drawer** in the right pane on desktop, reusing the existing V1 Plan Drawer form (Plan Basics, Specs, Pricing Variants, Promotions, Advanced) without covering the plan list; on narrow widths the drawer still uses the centered modal.
- **Variant navigator filters** rendered as compact dropdowns with checkbox lists for Platform / Period / Location, allowing multi-select filtering of variants in the middle column.
- **Advanced toggle** section beneath the drawer that expands into a full-width area, grouping Promotions (Overrides) and Plan theme override controls and leaving space for future advanced options.

### Changed
- Plans Dashboard no longer renders plans as large cards; instead it uses compact rows showing title, subtitle, primary price summary, dimension chips and status/featured badges. Clicking a row highlights it and loads its drawer in the right pane.
- Drawer layout spacing and container sizing tuned for the new inline mode: the right pane now scrolls vertically while keeping the drawer header/footer and three-column internals intact.

### Fixed
- Drawer no longer opens below the detail pane when triggered from the Plans Dashboard; in desktop view it consistently renders inside the right-hand panel.
- Avoided overlapping scrollbars and horizontal overflow in the new two-pane layout when many plans or variants are present.

---

## 1.7.3 – Plan Sheet width tweak

### Fixed
- Increased Plan Sheet max width to 1180px (with tighter horizontal margin) so the right column no longer overflows, especially when variants are expanded.

---

## 1.7.2 – Plan Sheet scroll fix

### Fixed
- Made the sheet form a proper 3-row grid (header/body/footer) so the inner body always scrolls; converted the body to a flex column for predictable gaps.

---

## 1.7.1 – Plan Sheet UX fixes

### Added
- Background scroll lock while the Plan Sheet is open, ensuring scroll stays inside the modal.
- Default-compact variants: only the first variant opens by default; others start collapsed with summary chips.

### Changed
- Modal grid now uses `minmax(0, 1fr)` for the body row and explicit `overflow-y: auto` + `min-height: 0` to restore reliable scrolling.
- Two-column layout rebalanced to ~60/40 and collapses earlier on narrower screens to avoid cramped fields.

### Fixed
- Scrollability issues where the page behind could “steal” scroll; mitigated vertical overflow without horizontal scrollbars.

---

## 1.7.0 – Plan Sheet Editor

### Added
- Centered Plan Sheet modal with improved desktop two-column layout and collapsible variant cards (summary headers + expandable details).

### Changed
- Fixed sheet scroll/sizing: sticky header/footer with the body now scrolling cleanly (no hidden content), refined spacing and inputs to feel closer to V1.
- Pricing variants are more scannable via summary chips (platform/period/location/price) with status chips for sale/unavailable; details expand on demand.

### Fixed
- Reduced cramped layout and overflow issues when editing plans with many specs/variants in the modal.

---

## 1.6.0 – Plan Sheet Modal

### Added
- Centered plan editing **sheet modal** that opens from the Plans Dashboard with a wider viewport and two-column desktop layout (Basics/Specs left, Pricing/Promotions/Advanced right).

### Changed
- Replaced the narrow right-hand drawer with a spacious, scrollable sheet: sticky header/footer, modern V1-styled cards, and improved spacing; fully collapses to one column on narrow admin widths.
- Modal now traps focus, supports overlay/ESC close, and keeps all existing meta field names and save behavior intact.

### Fixed
- Reduced cramped layout and scrolling pain when editing plans with many specs/variants; mitigated horizontal overflow risks.

---

## 1.5.0 – Plan Drawer UI polish

### Added
- Refined Plan Drawer visuals to mirror the V1 design system (cards, accent bars, modern buttons/inputs, consistent spacing).
- Re-grouped pricing variants into compact rows (dims, price/sale, CTA, meta) to keep the drawer readable in narrow widths.

### Changed
- Removed the duplicate plan title field inside “Plan Basics” (title now lives in the sticky header only); tightened basics grid and table context.
- Restyled specs/variants remove buttons, inputs, and footer actions to eliminate legacy meta-box styling and align with dashboard/Table Editor V1 look.
- Updated section padding, shadows, and background for better hierarchy and comfort when many specs/variants are present.

### Fixed
- Reduced visual residue from old WP styles and mitigated horizontal overflow risks inside the drawer.

---

## 1.4.1 – Plan Drawer polish

### Added
- Refined Plan Drawer layout to match V1 styling more closely: consistent grids, section headers, and controls, plus bespoke input styling in the header.

### Changed
- Unified spacing/typography for drawer sections (Basics, Specs, Pricing, Promotions, Advanced), tightened grids for specs/variants, and styled promos groups to avoid legacy table look.
- Ensured repeatable specs/variants use the new layout while preserving field names/sanitizers; footer/buttons remain responsive on narrow widths.
- Version bumped to **1.4.1**.

### Fixed
- Reduced visual residue of old meta box styling and mitigated horizontal overflow risks in the drawer.

---

## 1.4.0 – Plan Drawer Form

### Added
- Bespoke Plan Drawer form template for the per-table Plans Dashboard with modern V1 styling (cards, grids) and logical sections: Plan Basics, Specifications, Pricing Variants, Promotions (override), and Advanced.
- Inline templates and JS for adding/removing specs and variants within the drawer; overlay/close/focus behaviors retained.

### Changed
- Drawer no longer embeds the legacy plan meta box; it now renders the dedicated template while keeping the same field names and sanitizers (save_plan still applies).
- Promotions override is presented in a compact, collapsible layout; full editor link remains for edge cases.
- Version bumped to **1.4.0**.

### Fixed
- Improved drawer spacing and avoided horizontal overflow; fields wrap cleanly in the side panel and at narrow admin widths.

---

## 1.3.0 – Plan Drawer

### Added
- Right-hand **Plan Drawer** on the per-table Plans Dashboard:
  - Opens via clicking a plan card or “Edit Plan” button.
  - Contains all plan fields (subtitle, featured, badge glow, specs, variants, badges override, assignment).
  - Inline save (“Save changes” / “Save & Close”) via admin-post; supports reopening selected plan after save.
  - Keeps “Open full editor” link as secondary.
- AJAX loader for drawer content and server-side initial render when `selected_plan` is present.
- Drawer overlay and responsive styling; new JS/CSS assets.

### Changed
- “Edit Plan” now opens the drawer instead of navigating to the classic edit screen by default.
- Plans Dashboard includes plan IDs for JS and supports selected plan reopen after redirect.
- Version bumped to **1.3.0**.

### Fixed
- Ensured drawer layout uses grid/flex with gaps and avoids horizontal overflow on narrow admin widths.

---

## 1.2.0 – Plans dashboard per table

### Added
- Per-table **Plans Dashboard** opened from “Manage Plans” on the Pricing Tables dashboard:
  - Header with table info, shortcode copy, and actions (Add Plan pre-assigned, Back to Pricing Tables, Open table editor).
  - Stats for total/published/draft/featured plans.
  - Filters (search, status pills, featured toggle).
  - Plan cards showing title, status, featured badge, subtitle, price summary (from first variant), dimension chips, and quick actions (Edit, Duplicate, Trash).
  - Empty state when no plans exist.
- Plan duplication action (`admin-post.php?action=pwpl_duplicate_plan`) and plan trash action (`pwpl_trash_plan`) with notices.
- Inline “Add Plan” action creates a draft pre-assigned to the table and redirects to edit.
- Dedicated template and stylesheet for plans dashboard (cards, responsive grid).

### Changed
- “Manage Plans” buttons on the Pricing Tables dashboard now point to the per-table Plans Dashboard instead of a generic list view.
- Plugin version bumped to **1.2.0**.

### Fixed
- Ensured plans dashboard layout wraps cleanly on narrow admin widths, avoids horizontal overflow, and reuses copy-feedback behaviors.

---

## 1.1.0 – Pricing Tables dashboard

### Added
- Modern admin **Pricing Tables dashboard** that replaces the default list view:
  - Header with primary actions to create tables and plans.
  - Stat cards for published/draft tables and plans, plus tables without plans.
  - Grid of table cards (title, status, plan counts, last updated, shortcode with copy, quick actions to edit table or manage plans).
  - Help panel with shortcode and links to Settings and classic list view.
- New dashboard stylesheet (`assets/admin/css/dashboard.css`) enqueued only on the dashboard screen.

### Changed
- Top-level “Pricing Tables” menu now opens the dashboard; the legacy list is still available as “All Tables (List)”.
- Menu reordering updated to keep dashboard first so the parent menu points to it.
- Plugin version bumped to **1.1.0**.

### Fixed
- Ensured dashboard layout uses grid/flex with `gap`, avoids horizontal overflow, and wraps content on narrow admin widths.

---

## 1.0.0 – V1 Table Editor & FireVPS

### Added

- **Table Editor V1 (WP Admin)**
  - New React/`wp-components` meta box _“Table Editor — V1 (Preview)”_ on `pwpl_table` edit screens.
  - Sidebar navigation with blocks for:
    - Table Layout (widths & columns, breakpoints),
    - Plan Card (layout, padding, radius, border),
    - Typography,
    - Colors & Surfaces,
    - CTA,
    - Specs (style & interactions),
    - Badges & Promotions,
    - Filters (dimensions & variants).
  - Hydration of all existing `pwpl_table[...]` meta into the editor and back into hidden inputs on save.

- **Layout & sizing controls**
  - Device‑specific table width and column controls using friendly labels (Big screens, Desktop, Laptop, Tablet, Mobile).
  - Global and per‑breakpoint **Card Min Width** controls wired to new `LAYOUT_CARD_WIDTHS` meta, used to calculate responsive card sizes.
  - Optional legacy breakpoint container retained for card min height overrides.

- **CTA configuration & typography**
  - Table‑level CTA settings saved in `PWPL_Meta::CTA_CONFIG`:
    - Width (auto/full), height, horizontal padding, border radius, border width,
    - Min/max width, “lift” on hover, focus style,
    - Normal / hover background, text and border colors.
  - CTA typography:
    - Font size, weight, transform, letter‑spacing (numeric input converted to `em`),
    - Preset font stacks (system, Inter, Poppins, Open Sans, Montserrat, Lato, Space Grotesk, Rubik) with mapping to full families.
  - Frontend/FireVPS theme reads these as `--cta-*` CSS variables to control primary buttons and inline CTA.

- **Plan card typography & colors**
  - Dedicated Typography section for:
    - Plan title, subtitle, price, billing, specs text, and CTA text.
  - Controls for size, weight, line height and alignment, wired to new card config meta and CSS variables.
  - Colors & Surfaces block:
    - Top/specs backgrounds, gradients, keyline color/opacity, card border color.

- **Inline color picker**
  - Custom HSV + saturation + alpha color picker used across the editor:
    - Canvas for hue/value, vertical rails for saturation and alpha,
    - Live preview swatch, HEX/RGBA pill with copy and visual feedback,
    - Keyboard accessibility and clipboard fallback.
  - Replaces reliance on the legacy WP color picker for these panels.

- **Specs & interactions**
  - Specs list style selector: `default`, `flat`, `segmented`, `chips`.
  - Specs animation preset and flags (row, icon, divider, chip, stagger), plus intensity and mobile toggle.
  - Stored via new meta keys and used by FireVPS theme to add subtle interactions.

- **Filters (Dimensions & Variants)**
  - Filters block to enable/disable Platform / Period / Location filters per table.
  - Checklists of allowed values sourced from global settings.
  - Platform order UI to define preferred platform order and default active value; persists to meta for frontend to consume.

- **FireVPS theme**
  - FireVPS theme set as the preferred default theme when available (via sanitizer + admin + shortcode).
  - Dedicated template (`themes/firevps/template.php`), CSS (`theme.css`) and JS (`theme.js`) implementing:
    - Frameless table layout with a FireVPS‑specific card design,
    - Scrollable tab row with custom overflow handling, arrows and optional “glass” treatment,
    - Horizontal plan rail with scroll rails, keyboard‑friendly controls and subtle scroll hint,
    - Trust trio row under CTA and optional sticky mobile CTA bar.

### Changed

- **Admin UX**
  - Legacy “Layout & Size” and “Badges & Promotions” meta boxes are removed or hidden when V1 is active (reducing duplicate controls).
  - Legacy Dimensions & Variants meta box is removed once the Filters block is available.
  - Spacing, device labels and range controls updated to avoid overflow and keep layouts clean at common admin widths.
  - LocalStorage remembered the last opened V1 editor tab so returning to a table restores context.

- **Pricing display & billing copy (FireVPS + core frontend)**
  - Price markup refactored to split currency symbol, numeric value and unit so typography is consistent for discounted and non‑discounted plans.
  - Billing copy now derives readable labels from selected period, e.g.:
    - “Billed monthly”,
    - “Billed annually*”,
    - “Billed quarterly”,
    - or fallback `Billed {label}`.
  - Fine‑tuned spacing (currency to digits, old vs new price, inline discount badges) for a more professional, “tight but readable” appearance.
  - Variant prices are treated consistently between PHP and JS; `/mo` is added in a controlled, predictable way.

- **CTA visual style**
  - Default CTA style changed to an outline‑first button that fills on hover, using the active theme accent color.
  - FireVPS buttons now use table‑level CTA config for both primary and inline CTAs, with better focus styles and consistent weights.

- **Badges**
  - Header badges and inline discount badges coordinated:
    - When a plan variant has a genuine discount (sale < base price), header “discount” badges are suppressed so only one discount signal shows.
  - Badge sanitization and priority logic extended to support stacked dimensions (period, location, platform) with configurable priority and shadow strength.

### Fixed

- **V1 editor reliability**
  - Ensured hidden field lists are initialized before usage, preventing JavaScript `ReferenceError`s during editor mount.
  - Fixed cases where values were lost when switching between tabs; values now persist across all sections on save.
  - Fixed gradient UI logic so irrelevant fields are hidden/cleared when gradient type is “none” or when required fields are empty.

- **Frontend CSS variable emission**
  - Added `wp_add_inline_style` fallback to attach per‑table CSS variables to registered theme handles, so styling works even when `<style>` blocks or inline `style` attributes are filtered out.
  - Ensured layout width/columns/card width meta is normalized through `PWPL_Meta` sanitizers before being turned into CSS variables.

- **Autoload & file naming**
  - Renamed admin UI class file(s) to follow the `includes/class-pwpl-*.php` convention so the autoloader reliably loads `PWPL_Admin_UI_V1`.

---

Future releases should add new sections below with the same structure (`## 1.x.y`, grouped into Added / Changed / Fixed), based on commits since the previous release tag. Use `git log v1.0.0..HEAD` as a starting point and summarize user‑visible changes.***
