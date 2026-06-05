# Help & Resources Menu Section — Design

**Date:** 2026-06-05
**Branch:** `feat/help-menu-section`
**Package:** `packages/Webkul/Admin`

## Goal

Add a "Help & Resources" page to the UnoPim admin, reachable from a sidebar menu
item. Port the standalone HTML mockup (`UnoPim Help (standalone).html`) into the
admin using **UnoPim Blade components — no static/raw HTML**. Introduce a single
reusable global card component and drive all card content from config.

## Decisions (locked)

- **Card component scope:** Generic reusable link-card — `<x-admin::card>` — usable
  anywhere, consumed by the help page.
- **Data source:** Config-driven. Cards live in `Config/help.php`; the view loops
  and renders each through `<x-admin::card>`. Extensible by plugins (config merge).
- **Menu placement:** Standalone item at the bottom of the sidebar (high `sort`).
- **Icons:** UnoPim `icon-*` font classes where a good match exists; otherwise the
  mockup's inline SVG markup stored in config. The card SVGs (cloud, support,
  services, extensions, docs, api, contact) have no font-class equivalent, so they
  use the mockup SVGs. The sidebar Help menu item still uses an `icon-*` class
  (`icon-information`) since the sidebar renders font icons.
- **Links:** Real unopim.com URLs from the mockup.

## Page content (from mockup)

- **Head:** title "Help & Resources" + subtitle.
Each card uses the mockup's inline SVG icon (stored in config).
- **Section "Services":**
  - Cloud Hosting — https://unopim.com/cloud-hosting/
  - Support & Maintenance — https://unopim.com/support-maintenance/
  - Paid Services — https://unopim.com/services/
- **Section "Resources & Documentation":**
  - Extensions — https://unopim.com/extensions/
  - User Guide — https://docs.unopim.com/
  - API Docs — https://docs.unopim.com/api/
- **CTA banner "Still need a hand?":** Contact us → https://unopim.com/contacts/

## Components & files

### 1. Global card component — `src/Resources/views/components/card.blade.php`
`<x-admin::card>` — anonymous component (no PHP class).

Props:
- `icon` (string) — raw inline SVG markup (rendered via `{!! $icon !!}`), or a
  UnoPim icon-font class. Component detects: if it starts with `<svg`, render raw;
  else treat as a font class. Help cards pass SVG.
- `title` (string) — already-translated label.
- `url` (string) — link target.
- `host` (string, optional) — small footer host label.
- `target` (string, default `_blank`).
- `external` (bool, default `false`) — shows the ↗ corner badge + `rel="noopener"`.

Body: the description goes in the default slot (so the card is reusable beyond
help). Renders the entire `<a>` card: icon tile, optional external badge, title,
description slot, footer (host + arrow). Tailwind + dark-mode classes consistent
with existing admin components (`bg-white dark:bg-cherry-800`, `box-shadow`,
rounded, hover lift). No raw form/HTML primitives beyond the semantic `<a>`/`<svg>`
the card itself is made of.

### 2. Config — `src/Config/help.php`
```php
return [
    'sections' => [
        [
            'title' => 'admin::app.help.index.services',
            'items' => [
                [
                    'icon'        => '<svg ...>...</svg>', // mockup cloud SVG
                    'title'       => 'admin::app.help.cards.cloud-hosting.title',
                    'description' => 'admin::app.help.cards.cloud-hosting.description',
                    'url'         => 'https://unopim.com/cloud-hosting/',
                    'host'        => 'unopim.com/cloud-hosting',
                    'external'    => true,
                ],
                // support, services ...
            ],
        ],
        // resources & documentation ...
    ],

    'cta' => [
        'icon'  => '<svg ...>...</svg>', // mockup chat SVG
        'title' => 'admin::app.help.cta.title',
        'sub'   => 'admin::app.help.cta.sub',
        'url'   => 'https://unopim.com/contacts/',
        'label' => 'admin::app.help.cta.button',
    ],
];
```
Title/description values are **translation keys**, resolved with `trans()` in the
view before passing to the component.

### 3. View — `src/Resources/views/help/index.blade.php`
- `<x-admin::layouts>` with `<x-slot:title>`.
- Page head (title + subtitle).
- `@foreach (config('help.sections') as $section)` → section label + responsive
  grid (`grid-cols-1 md:grid-cols-2 xl:grid-cols-3`) of `<x-admin::card>`.
- CTA banner rendered inline at the bottom (one-off; not worth its own component).

### 4. Controller — `src/Http/Controllers/HelpController.php`
`index(): View` → `return view('admin::help.index');`

### 5. Route
Add to the appropriate admin routes file (grouped under `admin` prefix +
`['admin']` middleware):
`Route::get('help', [HelpController::class, 'index'])->name('admin.help.index');`

### 6. Menu — `src/Config/menu.php`
```php
[
    'key'   => 'help',
    'name'  => 'admin::app.components.layouts.sidebar.help',
    'route' => 'admin.help.index',
    'sort'  => 11, // bottom; confirm against existing max sort
    'icon'  => 'icon-information',
],
```

### 7. ACL — `src/Config/acl.php`
```php
[
    'key'   => 'help',
    'name'  => 'admin::app.acl.help',
    'route' => 'admin.help.index',
    'sort'  => 11,
],
```

### 8. Translations — `src/Resources/lang/{locale}/app.php`
Add under a new `help` block plus sidebar/acl label keys:
- `help.index.title`, `help.index.subtitle`
- `help.index.services`, `help.index.resources`
- `help.cards.{cloud-hosting,support,services,extensions,user-guide,api-docs}.{title,description}`
- `help.cta.{title,sub,button}`
- `components.layouts.sidebar.help`
- `acl.help`

**en_US first**, then propagate naturally translated values to **all 33 locales**.
Run `php artisan unopim:translations:check` — must pass with zero errors.

## Error handling
Static page, no user input. Auth/ACL via `['admin']` middleware + ACL entry. No DB.

## Testing
- Pest feature test (Admin suite): authenticated GET `admin/help` → 200, view
  `admin::help.index` rendered, response contains a card title and a section label.
- `vendor/bin/pint` after changes; `vendor/bin/pint --test` zero issues.
- Optional Playwright: sidebar Help link navigates and cards render.

## Out of scope (YAGNI)
- No per-card permissions, no admin CRUD for cards, no DB storage.
- Card icons reuse the mockup SVGs (stored in config); no new icon-font glyphs.
- CTA banner stays inline (no component) unless reused elsewhere later.
