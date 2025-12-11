# Changelog

All notable changes to this project will be documented in this file.

The V1 work has been developed on the `feature/admin-ui-ux-v1` branch and will replace the older main branch once merged.

---

## Unreleased

### Added
- Internal: layout/preset table meta (`layout_type`, `preset`) seeded from wizard templates and exposed to renderers for future layout/preset-specific themes. No visual change yet.
- Added `App Pricing – Soft Cards` wizard template (SaaS/app style) with soft cards, purple CTAs, and a flat feature list; middle card lightly highlighted.
- Added a **Hospitality Cards** wizard template (services category) with three room/service demos, full-bleed hero photography sourced from `assets/admin/img/template-demo/…`, `/night` price units, CTA reassurance copy, and a polished template thumbnail generated via `npm run capture:thumbs`. Template metadata advertises hotel/salon/coaching use cases and supports hero images out of the box.

### Changed
- FireVPS now differentiates several wizard presets (e.g., SaaS Grid V2, Image Hero, Minimal) with preset-scoped styling driven by table meta (`layout_type`/`preset`) while preserving existing layouts/DOM contracts.
- Five wizard presets (SaaS 3 Column, Startup Pricing Grid, Feature Comparison Table, Service Plans, App Pricing) now render with distinct FireVPS visual systems: unique palettes, card surfaces, specs treatment, and CTA placement, without altering data/schema.
- Consolidated Service templates: Service Plans is the canonical Services preset (ticked feature list); Service Columns remains for existing tables but is hidden from the wizard.
- Service Plans preset expanded to four tiers (Free, Starter, Pro, Premium) with richer spec lists, subtle Pro emphasis, slider layout, and crisp white single-surface cards.
- FireVPS layout files now act as lightweight routers: each layout family (`grid`, `columns`, `comparison`) looks for a preset-named partial (`grid-*`, `columns-*`, `comparison-*`) with a shared base fallback so adding or tweaking a preset no longer requires touching the core router.
- FireVPS preset cards polished: CTAs align on a shared baseline and card surfaces fill spare height so shorter cards no longer show blank bands when plan content differs.
- Comparison table preset redesigned as a courses-style comparison matrix: teal feature stub column, three plan headers with prices/CTAs, and centered tick/cross cells in the spec grid.
- SaaS 3 Column preset now supports a per-plan hero image meta (`PLAN_HERO_IMAGE`) rendered as a full-bleed card header image above the plan title, with a unified white content surface (pricing + specs), a bottom-anchored primary CTA, and an optional reassurance note under the button (filterable via `pwpl_firevps_saas3_cta_note`); existing tables remain unchanged unless a hero image is set.
- Starter Pricing Grid (`saas-grid-v2`) redesigned as an app-style hero grid: billing toggle + “Save 15%” badge, illustrated cards in a rounded frame, striped feature rows, and a “Most Popular” ribbon on the featured plan.
- FireVPS now honors per-plan trust trio overrides coming from wizard sample sets or plan meta via the new `_pwpl_plan_trust_items_override` meta key (with sanitized fallback to legacy data), allowing each plan card to surface tailored reassurance copy beneath its CTA.
- Wizard template metadata now expands inline beneath the selected card (toggled via a “Details ▾” link) so highlights/best-for/sample specs stay visible next to the picker instead of hiding at the bottom of the sidebar, and Step 1 now presents a grid of real preview thumbnails grouped by sticky category headings + category chips (All, SaaS, Services, Comparison, etc.) while the old search/tag chip row remains hidden until the library grows. Thumbnails can be regenerated via `npm run capture:thumbs`, which renders each preset and replaces the PNGs in `assets/admin/img/wizard-thumbs/`.
- Wizard template cards now feature a subtle glassy hover/selection treatment with a gradient border and hovering sheen so each tile clearly communicates “click to preview” while keeping the grid feeling premium.
- Table Wizard’s template column now sits on a clean white canvas with softened card shadows, and the selected card gets a crisp glowing border so the picker feels calm while clearly indicating the active choice.
- FireVPS columns layout now supports `_pwpl_plan_hero_image_url` fallbacks, a Hospitality-specific hero block, cream card surfaces, a clearer amenities list, and CTA reassurance footers; plan variants also accept custom `unit` strings (e.g., `/night`) so the hospitality preset can show realistic nightly pricing while grid cards fall back to `/mo` when no unit is provided.
- Hospitality Cards preset restyled to a warm pastry-style look: full-bleed hero, cream content panel, bold dark CTA, a ribbon-style Featured label, and text-based tick/plus amenities that wrap cleanly without external icons.
- Hospitality amenities/specs now span the full card width (no inset panel) so long lines breathe without feeling boxed-in.
- Hospitality specs background is now fully transparent in hospitality cards (no white inset) while keeping the top divider only.
- FireVPS is now the default renderer for wizard-created tables; theme selection is locked for wizard presets, and preset routing now covers all layout families (grid/columns/comparison) via partials so wizard previews and frontend shortcodes stay aligned as new presets are added.

### Fixed
- Hospitality Cards preset in the FireVPS columns layout now respects grid-based card sizing so plan cards no longer visually overlap and consistently render with comfortable gaps in the New Table Wizard preview and on the frontend.
- Hospitality Cards tables now default to a wider layout and slightly larger column cards so amenities/spec text has more room to breathe, reducing awkward line breaks in items like “Workspace” on both the wizard preview and the live FireVPS theme.
- Hospitality Cards amenities/spec list now spans more of the card width and wraps text more cleanly, so longer lines (e.g., workspace or cancellation notes) don’t look broken in the wizard preview or on the frontend.
- Hospitality specs no longer rely on SVG icons; bullets fall back to tick/plus pseudo-elements so missing icons can’t break the list.

---

## 1.8.9 – New Table Wizard (v1)

### Added
- New Table Wizard under the Planify menu: pick a template, layout, and card style with live preview, then create a table with demo plans and jump into Table Editor V2.
- Pricing Tables dashboard links to the wizard from the empty state and as a secondary “New table (wizard)” action when tables exist.
- Expanded starter pack with distinct templates (SaaS grid v2, service columns, comparison matrix, image hero, minimal) and richer template thumbnails in the wizard.

### Changed
- Template registry now extensible via `pwpl_table_wizard_templates`; wizard shows template category chips and respects premium flags for Pro-only templates.
- Wizard UI now a clear 3-step flow: pick template → configure layout/tabs/card style and add starter columns → create with summary, table name/theme, and options to open editor or copy shortcode. Layout types map to existing variants; no schema changes.
- Wizard preview/create flows accept an optional plan count (clones demo plans) with inline preview error messaging, safe handling when templates are missing, and an optional local debug mode (constant/filter) that logs wizard selections and timings to the PHP error log.
- Step 2 now includes a per-column editor: list with 3-dot menu (edit/duplicate/hide/delete), Add column, and an Edit Column panel for title/subtitle, highlight/featured, specs, primary variant price/sale, and CTA text/link; edited columns are sent via `plans_override` without changing schema.
- No behavioral changes to existing editor/frontend rendering beyond the guided creation flow.

### Fixed
- Resolved a wizard shell JS regression (DOM order/duplicate declarations) that could prevent the New Table Wizard UI from rendering.
- Fixed a missing `buildTemplateThumb` helper that caused the New Table Wizard sidebar to fail rendering (ReferenceError) in the 3-step flow.
- Added a small admin-only shim for Divi's `et_pb_custom` on the wizard screen to prevent third-party scripts from throwing ReferenceError in the console without changing Planify behavior.
- Wizard plan overrides now correctly send highlight/featured, primary price/sale, CTA values, and preserve all variants from the Step 2 column editor into preview/create so edited plans render accurately.

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
