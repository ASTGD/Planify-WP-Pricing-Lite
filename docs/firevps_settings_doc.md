# FireVPS & Planify — Table/Carousel Settings (Mobile & Beyond)

## Goals

- Make pricing tables feel modern across devices.
- Let site owners opt into rails/arrows/affordances without editing code.
- Prevent scroll traps on mobile.
- Keep backwards compatibility with existing tables.

---

## Admin → Pricing Table Settings (new controls)

### 1) Mobile Layout Mode

**Field ID:** `pwpl_mobile_layout_mode`\
**Type:** Select\
**Options:**

- `auto` (default) — decide per table via rule: use **carousel** if `(card_min × cards) > 1.4 × viewport`; else **vertical list**.
- `carousel` — always horizontal rail on mobile.
- `list` — stack plans vertically on mobile.

**Advanced rule knobs (optional, hidden under “Advanced”)**

- Threshold (number): default `1.4`
- Card min width source: `editor-active | editor-global` (default: `editor-active`)

### 2) Rail Visibility (Tabs & Plans)

**Field ID:** `pwpl_rail_visibility`\
**Type:** Select\
**Scope:** Global table (applies to tabs & plans; per-rail overrides below)\
**Options:**

- `hoverfocus` (default desktop) — rail shows on hover/focus.
- `always` — rail always visible.
- `hidden` — rail hidden (still scrollable).

**Per-rail override toggles**

- `pwpl_tabs_rail_visibility` (inherit / hoverfocus / always / hidden) — default: inherit
- `pwpl_plans_rail_visibility` (inherit / hoverfocus / always / hidden) — default: inherit

### 3) Arrow Controls

**Field ID:** `pwpl_arrow_visibility`\
**Type:** Select\
**Options:**

- `auto` (default) — show only when overflow exists.
- `always`
- `hidden`

**Placement:**

- `outside` (default) — arrows sit just outside the scroller, never overlap pills/cards.
- `inside` — arrows overlay edges of the rail.

**Device overrides (checkboxes)**

- `pwpl_arrows_desktop` (default: on)
- `pwpl_arrows_tablet` (default: on)
- `pwpl_arrows_mobile` (default: off if page dots enabled)

### 4) Affordances (make scrolling obvious)

**Field group:** `pwpl_affordances`

- `show_edge_peek` (default: on) — next card/pill peeks \~16–24px.
- `show_edge_gradients` (default: on) — subtle left/right fades.
- `show_page_dots_on_mobile` (default: on) — below the plans rail; hide if ≤2 pages.
- `show_swipe_hint_once` (default: on) — one-time “Swipe to see more →”; auto-dismiss on first interaction.
- `rail_thickness` (range: 1–6px; default: 3px)
- `rail_color` (color; default: `currentColor` with 40–50% opacity)

### 5) Motion & Snap

**Field group:** `pwpl_motion`

- `smooth_scroll` (default: on) — smooth programmatic scroll.
- `scroll_snap` (select: `off` (default), `proximity`, `mandatory`)\
  *Tip: keep **``** only for tabs; plans look better fluid.*

### 6) Wheel-to-Horizontal Mapping

**Field ID:** `pwpl_wheel_x_desktop`\
**Type:** Checkbox\
**Default:** on (desktop only)\
**Note:** Automatically off on touch devices.

### 7) Touch Behavior (anti–scroll trap)

**Field group:** `pwpl_touch_behavior`

- `touch_action_rail` (read-only hint): `pan-x` (we enforce this)
- `touch_action_card_content` (read-only hint): `pan-y` (we enforce this)

### 8) Per-table CSS Vars (optional expert controls)

**Field group:** `pwpl_css_vars`

- `table_max_width` (px, default: from editor)
- `card_min_width` (px, default: from editor)
- `gap_inline` (px, default: theme)
- `gap_block` (px, default: theme)
- `tabs_height` (px, default: theme)

> All of these are emitted as inline CSS variables on the table wrapper. Editor sliders still win unless explicitly overridden here.

---

## Defaults by Device

- **Desktop:** mobile layout = `auto` (resolves to carousel if many cards), rail = `hoverfocus`, arrows = `auto`, wheel→X = on.
- **Tablet:** rail = `always`, arrows = `auto`.
- **Mobile:** layout = `auto`, arrows = off (page dots on), rail = `always`, wheel→X = off.

---

## Data Model / Storage

Per **table** (post ID):

```php
get_post_meta( $table_id, 'pwpl_mobile_layout_mode', true );        // 'auto'|'carousel'|'list'
get_post_meta( $table_id, 'pwpl_rail_visibility', true );           // 'hoverfocus'|'always'|'hidden'
get_post_meta( $table_id, 'pwpl_tabs_rail_visibility', true );      // or 'inherit'
get_post_meta( $table_id, 'pwpl_plans_rail_visibility', true );     // or 'inherit'
get_post_meta( $table_id, 'pwpl_arrow_visibility', true );          // 'auto'|'always'|'hidden'
get_post_meta( $table_id, 'pwpl_arrows_desktop', true );            // '1'|'0'
get_post_meta( $table_id, 'pwpl_arrows_tablet', true );             // '1'|'0'
get_post_meta( $table_id, 'pwpl_arrows_mobile', true );             // '1'|'0'
get_post_meta( $table_id, 'pwpl_affordances', true );               // array: peek, gradients, dots, hint_once, rail_thickness, rail_color
get_post_meta( $table_id, 'pwpl_motion', true );                    // array: smooth_scroll, scroll_snap
get_post_meta( $table_id, 'pwpl_wheel_x_desktop', true );           // '1'|'0'
get_post_meta( $table_id, 'pwpl_css_vars', true );                  // array: table_max_width, card_min_width, gaps, tabs_height
```

---

## Frontend Contract (what PHP/CSS/JS should do)

### PHP (template)

- Add `data-` attributes on the **table wrapper**:
  ```html
  <div class="pwpl-table pwpl-table--theme-firevps"
       data-mobile-layout="auto|carousel|list"
       data-rail-visibility="hoverfocus|always|hidden"
       data-tabs-rail-visibility="inherit|hoverfocus|always|hidden"
       data-plans-rail-visibility="inherit|hoverfocus|always|hidden"
       data-arrows="auto|always|hidden"
       data-arrows-desktop="1|0"
       data-arrows-tablet="1|0"
       data-arrows-mobile="1|0"
       data-affordances='{"peek":true,"gradients":true,"dots":true,"hint_once":true,"rail_thickness":3,"rail_color":"currentColor"}'
       data-motion='{"smooth":true,"snap":"off"}'
       data-wheel-x-desktop="1">
  ```
- Emit CSS vars inline if overridden:
  ```html
  style="--pwpl-width-global:1320px; --pwpl-card-min-global:350px; --pwpl-gap-inline:20px;"
  ```

### CSS

- Enforce:
  - Rails: `overflow-x:auto; -webkit-overflow-scrolling:touch;`
  - Plan rail: `overscroll-behavior-x:contain; overscroll-behavior-y:none;`
  - Touch: rail `touch-action:pan-x;` card contents `touch-action:pan-y;`
- Affordances:
  - Edge peek via padding + `mask-image`/gradients.
  - Dots + rail thickness/color controlled by CSS vars (fallbacks).

### JS

- Read wrapper `data-*`, choose **mobile mode** (auto/carousel/list).
- Bind arrows/rails consistently (tabs & plans).
- On **mobile list**, tear down rails/arrows and ensure vertical scroll works everywhere.
- On **mobile carousel**, enable smooth scrolling; if `snap != off` and mode is tabs, use `proximity`.
- Wheel→X mapping **desktop only**; use passive listeners.
- One-time swipe hint using `localStorage`.

---

## Migration / Back-compat

- If a setting is missing, default to current behavior: **desktop rails on hover**, **arrows auto**, **mobile auto** layout.
- No schema change required; just new meta keys.

---

## QA Checklist

- Desktop: overflow shows rails on hover, arrows appear only when needed; wheel/drag/arrow all smooth in both rails.
- Tablet: rails always visible; arrows auto; touch drag works.
- Mobile: **Auto** picks carousel for many cards, list for few; no vertical scroll trap; dots appear if arrows are off; swipe hint shows once.
- A11y: arrows are proper buttons with `aria-label`, rails focusable with ArrowLeft/Right.

