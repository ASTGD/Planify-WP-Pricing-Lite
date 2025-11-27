# Planify WP Pricing Lite — Future Plan & Implementation Sheet

_Last updated: 2025-10-02 • Owner: Shafin • Project: Planify WP Pricing Lite_

This document captures the last working session’s decisions and turns them into a concrete, step-by-step roadmap. Keep this file in the repo for context and to brief Codex/GitHub contributors.

---

## 0) Context & Current State

- **Plugin core**: Pricing Tables (CPT) with Plans (CPT), dynamic dimensions (Platform/OS, Period, Location), **Badges & Promotions**, **CTA per variant**, **shortcode** output compatible with Divi.
- **Admin**: Global dimension values; per Table selection of enabled values; per Plan variants; Badges (table-level + plan-level override); CTA fields per variant.
- **Frontend**: Tabbed dimensions; horizontally scrollable plan rail; **arrows need to sit outside the rail**; “modern-discount” theme tokens.
- **Branching & PRs**: Work is done on feature/fix branches, tested locally (LocalWP), then merged to `main` via PR.
- **Outstanding UI items**: Arrows positioned outside plan cards; correct enable/disable logic; badges on dimension tabs (in addition to cards) based on priority; theme dropdown at **Table** level (not per Plan).

---

## 1) Immediate Stabilization (Phase A)

### A1. Carousel arrows outside (desktop + mobile)
- Two arrows (left & right) positioned at **bottom-left** and **bottom-right** of the rail, outside the card area.
- Show only when overflow exists; disable at true bounds (epsilon).
- Keyboard: Left/Right when track focused; Touch: native swipe; RTL-supported.
- **Testing**: 8–10 cards scenario; 1280/1024/768/430 widths; GIF in PR.

### A2. Cache-busting & caching hygiene
- Enqueue CSS/JS with `filemtime()` versions (fallback to plugin version) for both frontend and admin assets.
- QA notes: Hard refresh; Divi → Clear static CSS; disable minifiers during dev.

### A3. Theme selector scope
- **Theme/Style dropdown** at **Table** level only.
- Per Plan: keep **Featured** toggle but **remove** theme selector.
- Migration: If any plan has legacy theme meta, ignore and rely on table-level theme.

### A4. Dimension tab badges
- Show compact badge **on the tab** (Period/Location/Platform) reflecting admin-configured promotions.
- Badge conflict resolution uses **priority** (e.g., `['period','location','platform']`); render only the winner for now (Option A).

**Acceptance for Phase A**: Arrows outside + working; badges on tabs; theme selector at Table; asset cache-busting verified.

---

## 2) CSS Layering & Per-Theme Files (Phase B)

**Goal**: Allow per-theme CSS swapping without touching shared layout.

- Files:
  - `assets/css/tokens.css` (variables)
  - `assets/css/base.css` (resets & basics)
  - `assets/css/components.css` (tabs, track, cards, arrows)
  - `assets/css/theme-*.css` (classic, warm, blue, modern-discount)
- Load order: tokens → base → components → selected theme.
- **Override lookup**: Child theme path `planify-wp-pricing/theme-<slug>.css` → parent theme → plugin.
- Hooks: `pwpl_table_style_handles`, `pwpl_table_theme_slug`, `pwpl_table_style_uri`.
- Version via `filemtime`.

**Acceptance**: Switching theme changes visuals only; overrides from child theme work.

---

## 3) Badges, Promotions & CTA Model (Phase C)

### C1. Table-level promotions (primary)
- Meta `_pwpl_badges` structure:
  ```php
  [
    'period'   => [ ['slug'=>'yearly', 'label'=>'50% Off', 'color'=>'#33cc66'] ],
    'location' => [ ['slug'=>'usa',    'label'=>'10% Off', 'color'=>'#2c7be5'] ],
    'platform' => [ ['slug'=>'windows','label'=>'15% Off', 'color'=>'#ff9f1a'] ],
    'priority' => ['period','location','platform']
  ]
  ```
- UI: Repeater per dimension (enable → rows with Value/Label/Color/TextColor/Icon/TTL).

### C2. Plan-level override (optional)
- Meta `_pwpl_badges_override` with same shape; win order:
  **plan override > table mapping > none**.

### C3. CTA per variant
- Variant map includes `cta_label`, `cta_url`, `target`, `rel`.
- Hide button gracefully if URL missing.

### C4. Conflict logic
- **Option A (now)**: Show **only the highest-priority** badge.
- **Option B (later)**: Allow stacked badges (configurable).

**Acceptance**: Admin promos persist; tab + card badges resolve via priority; CTA reflects current variant.

---

## 4) Layout vs Theme Separation (Phase D)

- **Layout** = HTML structure (template PHP). Examples: Cards, Grid, Carousel-Pro.
- **Theme** = visual skin (CSS/JS tokens). Examples: Classic, Warm, Blue, Modern Discount.
- Admin UI: separate **Layout selector** (optional, future) from **Theme selector** (present).
- **Custom templates**: allow overrides from child theme (no core edits).

**Acceptance**: Code reads layout (template) and theme (css/js) independently to allow mix-and-match.

---

## 5) Theme Packages as Folders (Registry & Manifest) (Phase E)

**Goal**: Each theme is a self-contained folder with manifest, template(s), assets.

### E1. Paths & discovery order
1. `/wp-content/uploads/planify-themes/` (user themes)
2. `/wp-content/themes/<child>/planify-themes/`
3. `plugins/planify-wp-pricing-lite/themes/` (built-ins)

### E2. Manifest
```json
{
  "slug": "modern-discount",
  "name": "Modern Discount",
  "version": "1.1.0",
  "author": "Planify",
  "assets": { "css": ["assets/css/theme.css"], "js": ["assets/js/theme.js"] },
  "templates": { "table": "templates/table.php" },
  "compat": { "min_plugin": "0.2.0" },
  "premium": false
}
```

### E3. Loader/registry
- Functions to discover themes, validate manifests, build registry, locate files, enqueue assets w/ cache-busting.
- **Data contract** provided to template (`$table`, `$plans`, `$variants`, `$activeDims`, `$badges`, `$cta`).

### E4. JS integration
- Theme JS attaches to `#pwpl-table-{id}`; listens to namespaced events (e.g., `pwpl:dimension-change`).
- No globals; load after base JS.

### E5. Safety
- Sanitize manifest; restrict extensions; escape outputs; prevent path traversal.
- Transient cache for registry; “Rescan Themes” admin button.
- **Safe Mode** fallback to Classic if custom theme fails.

**Acceptance**: New theme folders (in uploads) show up in dropdown and render without touching plugin core.

---

## 6) Carousel UX (Phase F)

- Outside arrows bottom-left/right; no overlap; higher z-index; pointer events correct.
- Scroll by ~0.9 × `clientWidth`; smooth scrolling; recompute enable/disable on load/scroll/resize (use ResizeObserver).
- Touch-friendly drag/swipe; keyboard support.
- RTL: logical → physical scroll mapping.

**Acceptance**: Clicks, keyboard, and swipe all work; arrows show/hide correctly; GIF proof in PR.

---

## 7) Freemium Architecture (Phase G)

### G1. Split features
- **Lite**: Core features, built-in themes, carousel, badges, CTAs.
- **Pro**: Visual **Theme Editor**, premium theme packs, advanced badge logic, import/export, white-label, priority support.

### G2. Packaging
- **Option A (two plugins)**: Lite (wp.org) + Pro (add-on, sold from site).
- **Option B (one repo → two ZIPs)**: build pipeline outputs `planify-lite.zip` and `planify-pro.zip`.
- Lite must be fully functional and non-naggy (follow wp.org guidelines).

### G3. Licensing/updates (Pro)
- Freemius or EDD Software Licensing for license activation & auto-updates.
- Feature flags:
  ```php
  function pwpl_is_pro_active(): bool { return defined('PWPL_PRO'); }
  function pwpl_can_use_editor(): bool { return pwpl_is_pro_active(); }
  function pwpl_can_use_theme($slug): bool {
      $pro = ['modern-discount','neon','premium-x'];
      return pwpl_is_pro_active() || !in_array($slug, $pro, true);
  }
  ```

**Acceptance**: Lite and Pro artifacts build cleanly; Pro-only features hidden in Lite; polite single upgrade card in settings.

---

## 8) Theme Editor (Pro, Future) (Phase H)

**Incremental plan**

1. **Groundwork**: finalize tokens, per-theme CSS; stable loader & manifest.
2. **Preset editor**: a form/controls to tweak tokens → emits `theme-<slug>.css` (+ optional `theme.js`).
3. **Live Preview (iframe)**: reloads shortcode on-the-fly using saved assets.
4. **Drag & drop (later)**: React-based layout editor producing `templates/table.php` and assets in `/uploads/planify-themes/<slug>/`.
5. Export/import themes (zip of the theme folder).

**Safety**: Editor lives in Pro; Lite unaffected; fallback to Classic; “Reset to defaults”.

---

## 9) Developer Docs (Phase I)

- Manifest spec, theme folder layout, data contract for templates (variables provided).
- Example starter theme zip.
- Hooks/filters reference.
- “Rescan Themes” tool.

---

## 10) Git/Codex Workflow Cheat Sheet

### PM-style prompts (no file paths needed)

**Merge a tested branch**
> Merge the PR for branch `<branch-name>` into `main` using Squash & merge; delete the branch; output merge SHA and local sync commands.

**Fix arrows outside & disabled cursor**
> The left/right arrows must sit outside the rail, not overlap, show/hide only on overflow; cursor should be pointer when active; fix click logic and boundary detection; add GIF proof.

**Add theme registry**
> Introduce theme registry + manifest discovery (uploads → child → plugin), enqueue assets w/ cache-busting, and render theme’s `templates/table.php` with the provided data contract.

**Introduce per-theme CSS layering**
> Split tokens/base/components vs per-theme CSS; enqueue only selected theme; allow child theme overrides; document how to add custom theme files.

**Badges on tabs & conflict priority**
> Render compact badges on Period/Location/Platform tabs per admin mapping; when multiple apply, show only the highest-priority badge (table setting).

**Move theme selector back to Table**
> Remove theme dropdown from Plan editor; keep Featured toggle only; ensure migration for legacy meta.

### Local test checklist
- `git branch --show-current` → confirm branch.
- `git fetch && git pull` → latest code.
- Hard refresh + Divi cache clear.
- Verify acceptance criteria per PR.

---

## 11) Risks & Mitigations

- **Stale assets** → Always version via `filemtime`; document cache clear.
- **Custom theme breakage** → Safe Mode fallback + clear error message.
- **Conflicting arrows logic** → Single source of truth for enable/disable; RTL aware; ResizeObserver.
- **wp.org guidelines** → Keep Lite useful; limit upsell to a single Upgrade screen; no admin-wide nags.

---

## 12) Next Actionable Steps

1. **Phase A**: Ship arrows outside + tab badges + table-level theme selector + cache-busting (single PR).
2. **Phase B**: Introduce CSS layering and per-theme files; update enqueue logic.
3. **Phase E**: Implement theme registry + manifest loader; add “Rescan Themes” admin tool.
4. **Phase G**: Prepare freemium split (Lite/Pro build pipeline); choose licensing (Freemius/EDD).

When each step is merged into `main`, tag a minor release and capture changelog.

---

## Appendices

### A. Data contract (to theme templates)
```php
[
  'table_id'      => 123,
  'theme_slug'    => 'modern-discount',
  'enabled_dims'  => ['period','location','platform'],
  'active'        => ['period'=>'yearly','location'=>'usa','platform'=>'linux'],
  'allowed'       => ['period'=>[...], 'location'=>[...], 'platform'=>[...]],
  'plans'         => [
     [
       'ID'=>456, 'title'=>'Starter', 'featured'=>true, 'specs'=>[...],
       'variants'=>[ 'linux|yearly|usa' => ['price'=>'19.99','currency'=>'USD','old_price'=>'39.99','cta_label'=>'Order Now','cta_url'=>'https://...','target'=>'_blank','rel'=>'nofollow noopener'] ],
       'badge' => ['label'=>'50% Off','color'=>'#33cc66']
     ]
 ]
]

### B. Admin V2 – Table Editor V2 & New Table Wizard

- The `feature/table-editor-v2-layout` branch introduces a modern **Table Editor V2** shell:
  - Left sidebar tabs, right‑hand accordion panels, unified card styling, and a clear typography hierarchy.
  - This work is **visual/layout only**; meta schema, save logic, and frontend rendering stay the same.
- A future **New Table Wizard** will sit above the editor:
  - Step 1: choose a template (grid of presets + live preview).
  - Step 2: choose layout variant (width, column count, rail behavior).
  - Step 3: choose plan card/column style.
  - Wizard creates a real `pwpl_table` + demo `pwpl_plan` posts, then redirects into Table Editor V2.
- See `docs/table-editor-v2-wizard.md` for the detailed spec (visual rules, REST endpoints, and implementation phases).
```
