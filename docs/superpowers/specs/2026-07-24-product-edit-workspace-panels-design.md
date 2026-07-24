# Product Edit — Section Cards + Tabbed Workspace Overlay

**Date:** 2026-07-24
**Branch:** `feature/configurable-associations`
**Status:** Approved design (pending spec review)

## Problem

The product edit right column is a fixed ~360px rail that stacks Product Info, the
full Categories tree, the Associations editor (dynamic types + per-link custom
fields), and the Digital Product Passport (DPP) table. That content is far wider
than 360px in practice, so trees wrap, association cards cramp, and the DPP locale
table is unreadable. The rail cannot grow without starving the main attribute form.

## Goal

Replace the heavy inline sections in the rail with **compact summary cards** that
open a **full-width workspace overlay**. Inside the overlay, a **segmented tab
switcher** moves between the heavy sections (Categories / Associations / Passport /
any package-injected) without closing. One product **Save** still persists
everything; cards show a **dirty** cue for pending edits.

Layout and interaction follow the provided reference screenshots. **All visual
styling — typography, fonts, colors, spacing, card chrome — follows the existing
UnoPim admin design system and its Blade components**, not the mockup's ad-hoc look.

## Non-goals

- No change to how product values are persisted server-side (same form submit, same
  `ProductController::update` payload shape).
- No per-section AJAX save. One Save button (existing) persists all.
- No change to the Product Info card — it stays inline in the rail as today.
- No redesign of the main attribute-group form (left/center column) beyond what is
  needed to host the overlay.

## UX

### Right rail (product edit)

Vertical stack of cards, styled with the existing card tokens
(`p-4 bg-white dark:bg-cherry-900 rounded box-shadow`, title
`text-base font-semibold text-gray-800 dark:text-white`):

1. **Section cards** — one per registered section. Each card shows:
   - leading icon (section-provided),
   - title (localized),
   - **summary line** — localized count/status, e.g. "6 categories selected",
     "4 linked products", "en_US — not published",
   - **dirty dot** — small amber indicator, shown only when that section has
     unsaved edits,
   - **`View →`** action (primary/cherry link) that opens the workspace on that
     section.
   Core registers `categories` and `associations`; the Publication package injects
   `dpp`.
2. **Product Info card** — unchanged, stays inline (Status switch, Family, Product
   Type, Updated/Created).

The whole card is clickable (opens the workspace); the `View →` link is the visible
affordance. Cards are keyboard-focusable (`role="button"`, Enter/Space).

### Workspace overlay

Opens when a card is activated.

- **Coverage:** fills the content area to the right of the admin icon-nav rail
  (the ~64px left nav stays visible and usable), full viewport height,
  `position: fixed`, above page content, below the admin nav. Backdrop under it is
  the admin page background (not a dim scrim over the whole screen), matching the
  reference.
- **Header:** section icon + localized title + localized subtitle
  (e.g. "Assign this product to catalog categories.").
- **Tab switcher (top-right):** a segmented control listing every registered
  section in registration order — `Categories | Associations | Passport | …`. The
  active tab uses the cherry/primary pill treatment; others are muted. Clicking a
  tab swaps the body **without closing** and without losing edits. Each tab shows a
  dirty dot when its section is dirty.
- **Close `[X]`:** returns to the edit page. Edits are retained (not reverted).
- **Body:** the active section's editor at full width — category tree with search
  and "N selected" badge; association groups (Related / Up-Sell / Cross-Sell /
  custom types) with product cards and `+ Add`; DPP locale table.
- **Open focus:** the workspace opens on whichever card was activated, with that
  tab pre-selected.
- **Dismissal:** `[X]`, `Esc`, or a back/overlay affordance. Opening is idempotent
  (activating another card while open just switches the tab).

### Save & dirty

- The existing product **Save** button (page top-right) is the only save. It submits
  the whole `<x-admin::form>`, unchanged.
- Section editors keep their reactive state; edits mutate in-form state live.
- **Dirty** = section's live serialized state ≠ its loaded-from-server value. Each
  section component emits `dirty(bool)`; the card and its tab render the dot.
- After a successful save (full page reload on submit today), dirty resets naturally.

## Architecture

### Fields stay in the form

The overlay markup is authored as a **DOM descendant of the single
`<x-admin::form>`**. When open it is `position: fixed` (visually escapes the rail;
DOM-wise still inside the form). Form submission is by DOM ancestry, not visual
position, so every input inside the overlay submits with the main Save. No
`<Teleport>`, no hidden-input syncing, no AJAX.

All sections are mounted at once and toggled with `v-show` (never `v-if`), so
switching tabs never unmounts an editor or drops unsaved edits.

### New components (Webkul/Admin)

Vue 3 components, registered the same way existing product-edit components are
(inline `app.component(...)` in a blade partial with an `x-template`), styled with
UnoPim Blade components and theme classes.

1. **`v-product-workspace`** — the overlay shell.
   - Owns: open/closed state, `activeSection` id, the ordered section list (from the
     registry), tab switcher, header, `Esc`/close handling, focus management.
   - Renders each registered section's body in a `v-show` panel; the section bodies
     are provided as **named slots / registered child components** (see registry).
   - Props: none required; reads registry + listens on the panel bus.
2. **`v-section-card`** — the compact rail card.
   - Props: `section-id`, `icon`, `title`, `summary` (string, reactive via the
     section's exposed count), `dirty` (bool).
   - Emits: activate → panel bus `open(section-id)`.

### Panel registry + bus

A small client-side module (`panelRegistry`) plus an event emitter (`panelBus`),
exposed on the admin app (e.g. `window.UnoPim.productPanels`), Octane-irrelevant
(pure browser state).

- `panelRegistry.register({ id, title, icon, order, cardComponent?, bodyComponent })`
  — sections register themselves at script eval time.
- `panelBus.emit('open', id)` / `panelBus.on('open', …)` — cards open the workspace;
  the workspace listens.
- `panelBus.emit('dirty', { id, dirty })` — section bodies report dirty; cards +
  tabs subscribe.

Registration order drives both the card stack order and the tab order.

### Extensibility (PIM framework)

- **Core** registers `categories` and `associations` in the product-edit blade
  bundle.
- **Publication** injects `dpp` via the existing
  `unopim.admin.catalog.product.edit.form.column_after` view-render event — it
  outputs its own `<v-section-card>` + registers its body component. No core edit.
- **Third parties** do the same: listen on `column_after`, render a card, register a
  body with a unique `id`. The rail and the tab switcher render whatever is
  registered. Adding a section requires **zero changes to core files**.

This preserves the current injection contract (DPP already enters through a
`column_*` render event) and the existing per-field extension points
(`links.field.before/after`, `categories.controls.*`) remain untouched.

### Blade wiring (edit.blade.php)

The right column changes from "include full section partials" to:

- include `product-info` (unchanged),
- include `product-workspace` (the overlay shell) once,
- render core section cards (`categories`, `associations`),
- keep the `column_before` / `column_after` render events so packages inject their
  cards.

The existing section partials (`categories.blade.php`, `links.blade.php`) are
refactored so their Vue bodies mount inside the workspace instead of the rail, and
their loaded data + summary counts are exposed to the card. No server contract
change.

## Styling / typography

- Use UnoPim admin Blade components (`x-admin::form.control-group[.label|.control|
  .error]`, buttons) for all controls — no raw HTML form controls.
- Fonts, sizes, weights, and colors come from the existing admin Tailwind theme
  (cherry/primary palette, `box-shadow`, `rounded`, gray text scale, dark-mode
  `dark:bg-cherry-900/800`). No new font, no mockup-specific hex values.
- Tab switcher uses the primary pill treatment already used elsewhere in admin.
- Respect dark mode across cards, overlay, tabs, and bodies.

## Localization

All new user-facing strings via `trans('admin::…')`, added to `en_US` first, then
propagated to all 33 locales (naturally translated, placeholders preserved). New
keys (under `admin::app.catalog.products.edit`):

- `workspace.categories.title` / `.subtitle`
- `workspace.associations.title` / `.subtitle`
- `workspace.close`
- `sections.categories.summary` (":count categories selected")
- `sections.associations.summary` (":count linked products")
- `sections.unsaved` ("Unsaved changes")

DPP strings live in the Publication package's lang files. Run
`php artisan unopim:translations:check` after.

## Backward compatibility

- Server payload and `ProductController::update` unchanged.
- View-render events preserved; DPP and any existing injected content keep working
  through `column_after`.
- Product Info card unchanged.
- If a package injects raw markup on `column_after` (not a registered card), it
  still renders in the rail as today — the registry is additive, not a gate.

## Testing

- **Pest (feature):** product edit page renders the workspace shell + core cards;
  submitting the form with categories/associations set inside the overlay persists
  correctly (payload unchanged) — reuse existing association/category save tests.
- **Playwright (E2E):** open a card → workspace opens on that section; switch tabs
  without losing edits; edit associations → dirty dot appears on card; Save →
  persists and dot clears; Esc/`[X]` closes; dark mode renders.
- **Pint + Larastan** on all changed PHP.

## Open questions

None blocking. DPP card/summary wording is owned by the Publication package and
mirrors its existing passport status text.
