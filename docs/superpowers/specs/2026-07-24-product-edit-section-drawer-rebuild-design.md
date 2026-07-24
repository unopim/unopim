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
- **Responsive width ladder** (details below): 70% on `xl` → 80% `lg` → 90% `md` → 100% on
  phones, with inner content (cards, header, association rows, tree search) reflowing at each
  breakpoint. Responsive on all screen sizes is a first-class requirement, not an afterthought.

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

Props: `id`, `title`, `subtitle`, `icon`. Width comes from the responsive class ladder
(Responsiveness section) — no per-instance width prop; both sections share the ladder.
Slots: `toggle` (the section card), `content` (the panel body).

Behaviour:
- On open: add `relative` positioning context to `#main-content` if absent; lock its scroll;
  render overlay (`absolute inset-0`, dims main-content only) + drawer panel
  (`absolute inset-y-0 right-0`, responsive width `w-full md:w-[90%] lg:w-[80%] xl:w-[70%]`,
  hard floor `max-sm:!w-full`).
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

## Responsiveness (all screen sizes — first-class requirement)

Drawer width scales down as the viewport narrows so it is usable on every screen, not just
"70% or full". Responsive **width ladder** (Tailwind breakpoints, both drawers identical):

| Breakpoint | Viewport | Drawer width |
|------------|----------|--------------|
| `xl`  | ≥ 1280px | **70%** of main content |
| `lg`  | 1024–1279px | 80% |
| `md`  | 768–1023px | 90% |
| `< md` (incl. `sm`) | < 768px | **100%** (full main-content width) |

Implemented with responsive utility classes on the drawer panel
(e.g. `w-full md:w-[90%] lg:w-[80%] xl:w-[70%]`), not a single inline width — so the ladder
is declarative and testable. `max-sm:!w-full` retained as a hard floor.

**Inner content must reflow too** (not just the shell):
- **Section cards** container: responsive grid — 1 column on `< sm`, 2 columns from `sm`
  (`grid-cols-1 sm:grid-cols-2`), so cards never squash side-by-side on phones.
- **Drawer header**: allowed to wrap (`flex-wrap`), title truncates (`min-w-0 truncate`),
  close button never overlaps title at any width.
- **Association link rows**: stack vertically on narrow (`flex-col sm:flex-row`) — image +
  details + custom fields + delete reflow to a column below `sm` instead of overflowing;
  custom-field group drops its `max-w-[280px]` cap on narrow so inputs stay full-width.
- **Category tree**: already scrolls; ensure its search row stacks (`flex-col sm:flex-row`)
  so the count chip doesn't crowd the search box on phones.
- Any wide/unbounded region scrolls inside the drawer body (`overflow-auto`); the page body
  itself must never scroll horizontally at any breakpoint.

**Acceptance:** at every tested viewport (375, 768, 1024, 1440px) the drawer, header, cards,
and association rows render without horizontal page overflow and without clipped/overlapping
controls.

## Coexistence with app chrome (left sidebar + right AI panel)

Layout (`components/layouts/index.blade.php`): `#app` → app header → flex row
[`sidebar` + `<main id="main-content" class="flex-1 min-w-0 ... transition-all duration-300">`].
The left sidebar collapses/expands (container class `sidebar-collapsed` /
`sidebar-not-collapsed`); `#main-content` is `flex-1` and animates its width. A right agentic
AI panel is also present.

Because the drawer is **`absolute` inside `#main-content` and sized as a percentage of it**,
it inherits all of this for free — this is a deliberate advantage over the old fixed-frame +
`setWorkspaceBounds()` JS math (which had to recompute on every sidebar `transitionend`/resize
and still mis-clipped):

- **Left sidebar collapse/expand** → `#main-content` resizes → drawer (% width) tracks it
  automatically. No JS bounds recompute, no resize/`transitionend` listeners.
- **Right AI panel** → if it shrinks `#main-content` (flex sibling), the drawer follows; if it
  overlays, the drawer stays contained within main-content beneath it.
- **Z-index / stacking:** the drawer + its dim overlay are scoped **inside** `#main-content`,
  so they must sit **below** the app header, left sidebar, and right AI panel — those remain
  visible and usable while a drawer is open. This explicitly reverses the earlier
  raise-above-header/sidebar z-index workaround (commit `dbd12cc1`), which the contained
  approach makes unnecessary.
- The dim overlay covers **only** `#main-content`, never the header/sidebar/AI panel.

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
- **Playwright** — new/updated spec, run across a **viewport matrix** (375, 768, 1024,
  1440px):
  - Click Categories card → drawer visible; app header + left sidebar + right AI panel
    undimmed and still visible.
  - Click Associations card → drawer visible; add/remove link updates count badge.
  - **Width ladder** asserted per viewport: 1440 ≈ 70%, 1024 ≈ 80%, 768 ≈ 90%, 375 = 100%
    of `#main-content` width (with tolerance).
  - **No horizontal page overflow at any viewport** (`document.body.scrollWidth <=
    document.documentElement.clientWidth`) and no clipped/overlapping drawer-header controls.
  - Association rows stack (column layout) at 375px.
  - **Sidebar-collapse reflow:** toggle the left sidebar while a drawer is open → drawer
    width tracks `#main-content` (no JS bounds recompute needed, it is % of main).
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
