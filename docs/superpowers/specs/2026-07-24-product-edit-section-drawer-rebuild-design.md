# Product-Edit Categories & Associations — Section-Drawer Rebuild

**Date:** 2026-07-24
**Branch:** `feature/configurable-associations`
**Supersedes the UI layer of:** `2026-07-24-product-edit-workspace-panels-design.md`

## Problem

The current product-edit "workspace" for Categories and Associations was built as a bespoke
fullscreen overlay: a `position: fixed` frame anchored to `#main-content` via CSS variables,
with a hand-rolled tab switcher and per-section panels. Two defects:

1. **Bypasses UnoPim components.** Section cards, the frame header/tabs, panel shells, the
   associations add-button (`<div class="secondary-button">`) and delete control (raw `<i>`)
   are hand-written Tailwind markup. Off-palette colours (`violet-*`, `amber-*`) instead of
   UnoPim design tokens. Violates the component-first admin-UI rule. Only the categories
   inner tree already uses `x-admin::` components correctly.
2. **Not responsive.** The frame header is a single `flex-wrap: nowrap` row (title +
   tab-switcher + close). Measured at a 365px viewport: title block collapses to width 0,
   tab-switcher + close overflow to x=278 while the header box clips at 48px
   (`headerScrollW 278` vs `clientW 48`). The tab strip grows unbounded per association type
   with no wrap and no scroll container. Nothing reflows for small screens.

## Goal

Replace the fullscreen frame with a **contained, right-anchored drawer per section**, built
from UnoPim components and design tokens, responsive by construction. Preserve the association
form-submission contract exactly.

## Chosen UX

- **Contained drawer** (not viewport-fixed). `#main-content` becomes the positioning context
  (`position: relative`). Each section drawer is `absolute` within it, slides in from the
  right edge, **width 70% of main content**. Overlay dims **only** the main-content area;
  the app header and sidebar stay fully visible and undimmed.
- **Trigger:** the section card is the drawer's `toggle` slot. Click card → that section's
  drawer opens. Two independent drawers (categories, associations) — no shared frame, no tab
  switcher.
- **Mobile:** drawer goes full-width (`max-sm:!w-full`); no custom breakpoints needed beyond
  that single responsive rule.

## Architecture

### Delete
- `catalog/products/edit/workspace.blade.php` — the fullscreen frame, tab switcher,
  `setWorkspaceBounds()`, viewport/z-index math, escape+resize wiring tied to the frame.
- `components/product/workspace-panel.blade.php` — the bespoke fixed panel shell.

### Keep (slimmed)
- A small client-side reactive store exposing **only** `count` and `dirty` maps
  (`setCount` / `getCount` / `setDirty` / `isDirty`) for the section-card badges. All
  open/close/isActive/frame logic is removed. Store is client-only (no server/singleton
  state) → Octane-safe.

### New component: `x-admin::product.section-drawer`
A thin drawer that mirrors the native `x-admin::drawer` behaviour (slide transition,
scroll-lock, close button, ESC-to-close, dim overlay, dark mode) but is **`absolute` inside
`#main-content`** rather than viewport-`fixed`.

Props: `id`, `title`, `subtitle`, `icon`, `width` (default `70%`).
Slots: `toggle` (the section card), `content` (the panel body).

Behaviour:
- On open: add `relative` positioning context to `#main-content` if absent; lock its scroll;
  render overlay (`absolute inset-0`, dims main-content only) + drawer panel
  (`absolute inset-y-0 right-0`, `style="width: {{ $width }}"`, `max-sm:!w-full`).
- Header: icon + title + subtitle + close button, built with UnoPim tokens; wraps safely on
  narrow widths (no fixed single-row nowrap that can clip).
- Close on: close button, ESC, overlay click.
- Uses UnoPim design tokens (`bg-unopim-primary-page`, `dark:bg-cherry-800`,
  `dark:border-cherry-800`, etc.) — no `violet-*` / `amber-*` literals except the existing
  dirty-badge convention already used elsewhere in admin.

### Refactor: `x-admin::product.section-card`
- Rendered inside each drawer's `toggle` slot.
- Keeps: icon tile, title, **dirty dot** (`isDirty(id)`), **count summary** (`getCount(id)`),
  hover/focus affordance, keyboard activation (`role=button`, Enter/Space).
- Swap off-palette colours for UnoPim tokens.

### Content swaps
| Section | Change |
|---------|--------|
| Categories (`edit/categories.blade.php`) | Inner tree/search already native (`form.control-group`, `tree.category.view`, `shimmer.tree`) — unchanged, moved into `section-drawer` content. |
| Associations (`edit/links.blade.php`) | Add button `<div class="secondary-button">` → `x-admin::button`. Delete raw `<i>` → `x-admin::button` (icon variant). Row/empty-state markup tokenized. Custom fields keep `x-admin::associations.link-fields`. |

### Preserved — association submission contract (do NOT touch)
- Hidden `associations[<typeCode>][__present]=1` sentinel per active type.
- Hidden `associations[<typeCode>][<index>][sku]` per link.
- Custom-field bracket paths
  (`associations[<typeCode>][<index>][additional_data][common|locale_specific][<locale>][<code>]`).
- `publishState()` count still derived from the hidden `[sku]` inputs scoped by
  `data-section-id="associations"`.

## Widths
- Both drawers: **70%** of main content on desktop.
- `max-sm:!w-full` → full width on mobile.

## Data flow
1. Blade renders section card (in `toggle` slot) + drawer content per section.
2. Card click → `section-drawer` opens (local component state; no cross-section store needed
   for open/close).
3. Category tree / association rows mutate their own Vue component state and update
   `count`/`dirty` in the slim store → card badges react.
4. Product form submit carries the unchanged hidden association inputs; categories submit via
   the existing tree inputs.

## Error / edge handling
- Drawer open with `#main-content` missing → no-op guard (defensive).
- ESC / overlay click / close button all resolve to the same `close()`.
- Scroll-lock always released on `close()` and `beforeUnmount()` (no leaked
  `overflow-hidden`).
- Unbounded content (many association types / long trees) scrolls inside the drawer body
  (`overflow-auto`), never overflows the page.

## Testing
- **Pest** — update `ProductWorkspacePanelsTest`: assert section cards render, drawer markup
  present per section, count/dirty hooks present, association hidden-input contract intact.
- **Playwright** — new/updated spec:
  - Click Categories card → drawer visible, width ≈ 70% of main content, sidebar undimmed.
  - Click Associations card → drawer visible; add/remove link updates count badge.
  - At 375px viewport: drawer full-width, **no horizontal overflow** of the drawer/header
    (`scrollWidth <= clientWidth`).
- **Pint / Larastan** — clean on all changed PHP (Blade has no PHP logic changes of note, but
  run anyway per workflow).
- **`unopim:translations:check`** — any new keys (drawer close/aria labels) added to `en_US`
  first, propagated to all 33 locales.

## Out of scope
- Backend association processing (`AbstractType::prepareRichAssociations()` etc.) — unchanged.
- The association-type CRUD / field builder — separate work.
- Any change to categories persistence.

## Backward compatibility
- `view_render_event` hooks around categories/links preserved.
- Submission payload byte-compatible with current backend.
- Additive component (`section-drawer`); removed frame components were feature-internal
  (introduced on this branch), not public extension points.
