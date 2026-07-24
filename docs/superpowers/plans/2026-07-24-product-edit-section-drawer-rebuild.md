# Product-Edit Section-Drawer Rebuild — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the bespoke fullscreen product-edit "workspace" (Categories + Associations) with a contained, right-anchored drawer per section built from UnoPim components, responsive on every screen.

**Architecture:** Each section renders a `x-admin::product.section-card` (in the right-column) that opens a new `x-admin::product.section-drawer`. The drawer teleports its overlay + panel into `#main-content`, positions `absolute` at a responsive width (70% xl → 100% phone), locks main-content scroll while open, and leaves the app header / left sidebar / right AI panel visible and undimmed. A slim client-only reactive store (`$productWorkspace`) carries only `count`/`dirty` for card badges. The fullscreen frame + tab switcher + panel shell are deleted.

**Tech Stack:** Laravel 12 Blade components, Vue 3 (inline `app.component` + `<teleport>`), Tailwind 3 (UnoPim tokens), Pest 3 feature tests, Playwright 1.52 E2E.

## Global Constraints

- Component-first: no raw `<select>/<input>/<button>` where an `x-admin::` equivalent exists; wrap in `x-admin::form.*` / use `x-admin::button`.
- Localize all user-facing text via `trans('admin::...')`; reuse existing keys under `admin::app.catalog.products.edit.{workspace,sections,categories,links}` — do NOT invent keys unless required, and if required add to `en_US` first then all 33 locales, `:placeholders` untranslated.
- UnoPim design tokens only: `bg-unopim-primary-page`, `dark:bg-cherry-800/900`, `dark:border-cherry-800`, `text-gray-*` — NO `violet-*` / `amber-*` literals except the existing amber dirty-dot convention already used on cards.
- Association submission contract is byte-compatible: keep hidden `associations[<type>][__present]=1`, `associations[<type>][<i>][sku]`, and custom-field bracket paths exactly; keep `data-section-id="associations"` on the panel wrapper so `publishState()` count query still matches.
- Store is client-only (no server/singleton/static state) → Octane-safe.
- Responsive width ladder (both drawers): `w-full md:w-[90%] lg:w-[80%] xl:w-[70%]`, hard floor `max-sm:!w-full`. No horizontal page overflow at any viewport.
- After code changes: `vendor/bin/pint`, `vendor/bin/pest` (Admin suite), `vendor/bin/phpstan analyse <changed> --memory-limit=1G`, Playwright spec, `php artisan unopim:translations:check` — all clean. (Per [[running-pest-this-workspace]], run pest with host-MySQL env overrides.)

## File Structure

**Create:**
- `packages/Webkul/Admin/src/Resources/views/components/product/section-drawer.blade.php` — contained drawer component + `v-product-section-drawer` Vue component (teleport overlay+panel into `#main-content`).
- `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/section-store.blade.php` — slim `$productWorkspace` store (count/dirty only), pushed once.
- `tests/e2e-pw/tests/product-section-drawer.spec.js` — viewport-matrix E2E.

**Modify:**
- `packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php` — sit in drawer `toggle` slot; drop its own `open()`; tokens.
- `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php` — card + section-drawer; stack search row (`flex-col sm:flex-row`).
- `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php` — card + section-drawer; `x-admin::button` add/delete; rows `flex-col sm:flex-row`; drop custom-field `max-w-[280px]` cap below `sm`; tokens.
- `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php:230` — swap `@include(...workspace)` → `@include(...section-store)`.
- `packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php` — assert new markup.

**Delete:**
- `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php`
- `packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php`

**Note (deviation from spec):** section cards live in the existing right-column (`w-[360px] max-sm:w-full`), already responsive — no new card grid is added. The spec's "section-cards grid" note is superseded by this.

---

### Task 1: Slim store + contained `section-drawer` component

**Files:**
- Create: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/section-store.blade.php`
- Create: `packages/Webkul/Admin/src/Resources/views/components/product/section-drawer.blade.php`
- Test: `packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php` (temp render assertion, finalized in Task 6)

**Interfaces:**
- Produces (JS store on `app.config.globalProperties.$productWorkspace`): `setCount(id,n)`, `getCount(id)`, `setDirty(id,bool)`, `isDirty(id)`.
- Produces (Blade): `<x-admin::product.section-drawer :id :title :subtitle :icon>` with named slots `toggle` and `content`. Opening is driven by clicking anything in the `toggle` slot.
- Consumes: `#main-content` element from `components/layouts/index.blade.php`.

- [ ] **Step 1: Write the failing test** — append to `ProductWorkspacePanelsTest.php`:

```php
it('renders section-drawer teleport target and slim store', function () {
    $product = Product::factory()->simple()->create();

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSee('v-product-section-drawer', false)
        ->assertSee('$productWorkspace', false)
        ->assertDontSee('v-product-workspace', false);
});
```

- [ ] **Step 2: Run it, verify fail**

Run: `vendor/bin/pest --filter='renders section-drawer teleport target'`
Expected: FAIL — page still emits `v-product-workspace` (old frame) and no `v-product-section-drawer`.

- [ ] **Step 3: Create the slim store** `section-store.blade.php`:

```blade
@pushOnce('scripts')
    <script type="module">
        const store = window.Vue.reactive({
            count: {},
            dirty: {},
            setCount(id, n) { this.count[id] = n; },
            getCount(id) { return this.count[id] ?? 0; },
            setDirty(id, val) { this.dirty[id] = !! val; },
            isDirty(id) { return !! this.dirty[id]; },
        });

        app.config.globalProperties.$productWorkspace = store;
    </script>
@endPushOnce
```

- [ ] **Step 4: Create `section-drawer.blade.php`** — Blade shell + Vue component with teleport:

```blade
@props([
    'id'       => '',
    'title'    => '',
    'subtitle' => '',
    'icon'     => '',
])

<v-product-section-drawer
    id="{{ $id }}"
    title="{{ $title }}"
    subtitle="{{ $subtitle }}"
    icon="{{ $icon }}"
>
    <template #toggle>
        {{ $toggle }}
    </template>

    <template #content>
        {{ $content }}
    </template>
</v-product-section-drawer>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-section-drawer-template">
        <div>
            <div @click="open">
                <slot name="toggle"></slot>
            </div>

            <teleport to="#main-content" v-if="mounted">
                <transition
                    enter-active-class="transition-opacity ease-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition-opacity ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-show="isOpen"
                        class="absolute inset-0 z-[30] bg-gray-500/30 dark:bg-black/40"
                        @click="close"
                    ></div>
                </transition>

                <transition
                    enter-active-class="transform transition ease-in-out duration-200"
                    :enter-from-class="offClass"
                    leave-active-class="transform transition ease-in-out duration-200"
                    :leave-to-class="offClass"
                >
                    <div
                        v-show="isOpen"
                        class="absolute inset-y-0 ltr:right-0 rtl:left-0 z-[31] flex flex-col
                               w-full md:w-[90%] lg:w-[80%] xl:w-[70%] max-sm:!w-full
                               bg-unopim-primary-page dark:bg-cherry-800 shadow-2xl"
                        :data-section-id="id"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4
                                    border-b border-gray-200 dark:border-cherry-800
                                    bg-white dark:bg-cherry-900">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="grid place-items-center w-9 h-9 rounded
                                             bg-gray-100 dark:bg-cherry-800 shrink-0">
                                    <span :class="icon" class="text-xl text-gray-600 dark:text-gray-300"></span>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-gray-800 dark:text-white truncate">@{{ title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ subtitle }}</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="close"
                                class="grid place-items-center w-9 h-9 rounded border
                                       border-gray-200 dark:border-cherry-800 text-gray-500
                                       hover:text-gray-800 dark:hover:text-white shrink-0"
                                :aria-label="closeLabel"
                            >
                                <span class="icon-cancel text-lg"></span>
                            </button>
                        </div>

                        <div class="flex-1 min-h-0 overflow-auto p-6">
                            <slot name="content"></slot>
                        </div>
                    </div>
                </transition>
            </teleport>
        </div>
    </script>

    <script type="module">
        app.component('v-product-section-drawer', {
            template: '#v-product-section-drawer-template',

            props: ['id', 'title', 'subtitle', 'icon'],

            data: () => ({
                isOpen: false,
                mounted: false,
                closeLabel: "@lang('admin::app.catalog.products.edit.workspace.close')",
            }),

            computed: {
                offClass() {
                    return document.dir === 'rtl'
                        ? '-translate-x-full'
                        : 'translate-x-full';
                },
            },

            mounted() {
                this.mounted = true;
                this._esc = (e) => { if (e.key === 'Escape' && this.isOpen) this.close(); };
                window.addEventListener('keydown', this._esc);
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this._esc);
                this.unlock();
            },

            methods: {
                main() { return document.getElementById('main-content'); },

                open() {
                    const main = this.main();
                    if (! main) return;
                    main.classList.add('relative', 'overflow-hidden');
                    this.isOpen = true;
                },

                close() {
                    this.isOpen = false;
                    this.unlock();
                },

                unlock() {
                    this.main()?.classList.remove('overflow-hidden');
                },
            },
        });
    </script>
@endPushOnce
```

Rationale notes (keep as-is, they explain non-obvious "why"): teleport moves the overlay/panel out of the narrow right-column into `#main-content` so width % is measured against the whole edit area; `overflow-hidden` on open makes `absolute inset-0` equal the visible box (main-content is normally the scroll container); `relative` is added so the absolute children anchor to main-content, not the viewport.

- [ ] **Step 5: Temporarily include the store** so the assertion can pass before Task 5 wires it properly — add to `edit.blade.php` right after line 230's workspace include (removed in Task 5):

```blade
@include('admin::catalog.products.edit.section-store')
```

- [ ] **Step 6: Run test, verify the two positive `assertSee` pass** (the `assertDontSee('v-product-workspace')` will still FAIL until Task 5 removes the frame — that's expected; comment that one line out for now, restore in Task 6).

Run: `vendor/bin/pest --filter='renders section-drawer teleport target'`
Expected: PASS for the store + component-registration assertions.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Resources/views packages/Webkul/Admin/tests
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/section-store.blade.php \
        packages/Webkul/Admin/src/Resources/views/components/product/section-drawer.blade.php \
        packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php \
        packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php
git commit -m "feat(admin): contained product section-drawer component + slim count/dirty store"
```

---

### Task 2: Refactor `section-card` to drawer trigger

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php`

**Interfaces:**
- Consumes: `$productWorkspace.isDirty(id)`, `$productWorkspace.getCount(id)` (Task 1 store).
- Produces: a clickable card element to place inside a `section-drawer` `toggle` slot. It must NOT call any `open()` itself — the drawer's `toggle` wrapper handles the click.

- [ ] **Step 1: Rewrite the card** — remove `@click/@keydown` open handlers (drawer wrapper owns the click) and swap `violet-*`/`box-shadow hover:border-violet-300` for UnoPim tokens:

```blade
@props([
    'id'      => '',
    'title'   => '',
    'icon'    => '',
    'summary' => '',
])

<div
    role="button"
    tabindex="0"
    class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow flex items-center gap-3
           cursor-pointer border border-transparent hover:border-gray-300
           dark:hover:border-cherry-800 transition-all"
>
    <span class="grid place-items-center w-10 h-10 rounded bg-gray-100 dark:bg-cherry-800 shrink-0">
        <span class="{{ $icon }} text-2xl text-gray-600 dark:text-gray-300"></span>
    </span>

    <div class="min-w-0 flex-1">
        <p class="flex items-center gap-1.5 text-base font-semibold text-gray-800 dark:text-white truncate">
            {{ $title }}
            <span
                v-show="$productWorkspace?.isDirty('{{ $id }}')"
                class="w-2 h-2 rounded-full bg-amber-500 shrink-0"
                :title="'{{ trans('admin::app.catalog.products.edit.sections.unsaved') }}'"
            ></span>
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            @if (trim($slot) !== '')
                {{ $slot }}
            @else
                {{ $summary }}
            @endif
        </p>
    </div>

    <span class="text-sm font-medium text-blue-600 dark:text-blue-400 shrink-0">
        @lang('admin::app.catalog.products.edit.sections.view') <span class="rtl:hidden">&rarr;</span>
    </span>
</div>
```

(The amber dirty-dot is the existing convention and stays; `text-blue-600` matches UnoPim's primary-action link colour used elsewhere in admin. Verify the admin's link/primary colour by grepping an existing view, e.g. `grep -rn "text-blue-600\|text-violet" packages/Webkul/Admin/src/Resources/views/catalog/products/edit/product-info.blade.php` and match whatever that file uses; adjust if it differs.)

- [ ] **Step 2: Keyboard activation via the drawer wrapper** — because the drawer's `toggle` wrapper is a plain `<div @click>`, add Enter/Space forwarding there is unnecessary; instead ensure the card `role=button tabindex=0` bubbles a click. Confirm by leaving `@keydown.enter`/`@keydown.space` OFF the card and relying on the wrapper `@click`. (Native click on a focused `role=button` div does not fire on Enter automatically; to keep keyboard access, add to the drawer template's toggle wrapper in Task 1: `@keydown.enter="open" @keydown.space.prevent="open" tabindex="-1"`. Apply this small edit to `section-drawer.blade.php` now if not already present.)

- [ ] **Step 3: Apply the keyboard edit to the drawer toggle wrapper** in `section-drawer.blade.php`:

```blade
<div @click="open" @keydown.enter="open" @keydown.space.prevent="open">
    <slot name="toggle"></slot>
</div>
```

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Resources/views/components/product
git add packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php \
        packages/Webkul/Admin/src/Resources/views/components/product/section-drawer.blade.php
git commit -m "refactor(admin): section-card as drawer trigger with UnoPim tokens + keyboard open"
```

---

### Task 3: Categories → section-drawer

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php`

**Interfaces:**
- Consumes: `x-admin::product.section-card`, `x-admin::product.section-drawer` (Tasks 1–2); `$productWorkspace.setCount/setDirty`.
- Produces: unchanged category tree submission (inputs `categories[]`).

- [ ] **Step 1: Replace the card + workspace-panel block** (lines 8–30) with section-drawer wrapping the card as trigger and the tree as content:

```blade
<x-admin::product.section-drawer
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.categories.subtitle')"
    icon="icon-folder"
>
    <x-slot:toggle>
        <x-admin::product.section-card
            id="categories"
            :title="trans('admin::app.catalog.products.edit.categories.title')"
            icon="icon-folder"
        >
            <span v-text='($productWorkspace?.getCount("categories") ?? 0) + " " + @json(trans("admin::app.catalog.products.edit.workspace.categories.selected"))'></span>
        </x-admin::product.section-card>
    </x-slot:toggle>

    <x-slot:content>
        {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.before', ['product' => $product]) !!}

        <v-product-categories>
            <x-admin::shimmer.tree />
        </v-product-categories>

        {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.after', ['product' => $product]) !!}
    </x-slot:content>
</x-admin::product.section-drawer>
```

- [ ] **Step 2: Stack the inner search row** — in the `v-product-categories` template, change the search row wrapper from `flex items-center gap-3 mb-4` to responsive:

```blade
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
```

and the count chip `<span class="shrink-0 ...">` → add `self-start sm:self-auto` so it doesn't stretch on mobile.

- [ ] **Step 3: Render check**

Run: `vendor/bin/pest --filter='ProductWorkspacePanels'`
Expected: existing category assertions still PASS (title, tree present).

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php
git commit -m "feat(admin): categories section rendered in contained drawer, responsive search row"
```

---

### Task 4: Associations → section-drawer + `x-admin::button`

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php`

**Interfaces:**
- Consumes: `x-admin::product.section-card`, `x-admin::product.section-drawer`, `x-admin::button`, `x-admin::products.search`, `x-admin::associations.link-fields`.
- Produces: unchanged association submission contract (`__present` sentinel, `[i][sku]`, custom-field bracket paths); `data-section-id="associations"` preserved on the content root so `publishState()` count query matches.

- [ ] **Step 1: Wrap card + panel in section-drawer** — replace the card + workspace-panel block (lines 7–24) with:

```blade
<x-admin::product.section-drawer
    id="associations"
    :title="trans('admin::app.catalog.products.edit.links.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.associations.subtitle')"
    icon="icon-product"
>
    <x-slot:toggle>
        <x-admin::product.section-card
            id="associations"
            :title="trans('admin::app.catalog.products.edit.links.title')"
            icon="icon-product"
        >
            <span v-text='($productWorkspace?.getCount("associations") ?? 0) + " " + @json(trans("admin::app.catalog.products.edit.workspace.associations.linked"))'></span>
        </x-admin::product.section-card>
    </x-slot:toggle>

    <x-slot:content>
        <v-product-links :association-types='@json($associationTypes)'></v-product-links>
    </x-slot:content>
</x-admin::product.section-drawer>
```

- [ ] **Step 2: Preserve the count-scope wrapper** — in the `v-product-links` template root, add `data-section-id="associations"` to the outermost `<div class="grid gap-2.5">` so `publishState()`'s selector `.product-workspace-panel[data-section-id="associations"] ...` still matches. Since `.product-workspace-panel` is gone, update BOTH: change the wrapper to `<div class="grid gap-2.5" data-section-id="associations">` AND update `publishState()`'s query string to `'[data-section-id="associations"] input[name^="associations["][name$="][sku]"]'` (drop the `.product-workspace-panel` prefix).

- [ ] **Step 3: Add button → `x-admin::button`** — replace:

```blade
<div class="secondary-button text-xs" @click="selectedTypeCode = '{{ $type['code'] }}'; $refs.productSearch.openDrawer()">
    @lang('admin::app.catalog.products.edit.links.add-btn')
</div>
```

with:

```blade
<x-admin::button
    button-type="secondary-button"
    class="text-xs"
    ::button-text="'{{ trans('admin::app.catalog.products.edit.links.add-btn') }}'"
    @click="selectedTypeCode = '{{ $type['code'] }}'; $refs.productSearch.openDrawer()"
/>
```

(First verify the button component's exact prop names: `grep -n "props\|button-text\|button-type" packages/Webkul/Admin/src/Resources/views/components/button/index.blade.php`. If the component does not expose a `secondary-button` style or `button-text`, fall back to `<x-admin::button button-type="submit" class="secondary-button text-xs">@lang(...)</x-admin::button>` using its default slot. Use whatever the component actually supports.)

- [ ] **Step 4: Delete control → `x-admin::button` icon** — replace the raw delete `<p><i class="icon-delete ...></i></p>` with:

```blade
<x-admin::button
    button-type="button"
    class="text-red-600 hover:text-red-700 !p-0 !bg-transparent !border-0"
    @click="remove('{{ $type['code'] }}', link)"
    :title="trans('admin::app.catalog.products.index.datagrid.delete')"
>
    <span class="icon-delete text-xl"></span>
</x-admin::button>
```

(If `x-admin::button` forces padding/background that can't be overridden cleanly, keep a semantic `<button type="button">` with these classes instead of a `<p>` — the goal is a real button element, not a paragraph. Do not reintroduce a `<div>`/`<p>` click target.)

- [ ] **Step 5: Responsive link rows** — change each link row wrapper from `flex gap-2.5 justify-between p-4 border-b ...` to `flex flex-col sm:flex-row gap-2.5 sm:justify-between p-4 border-b ...`; and the custom-fields group `grid gap-2.5 flex-1 max-w-[280px]` → `grid gap-2.5 flex-1 sm:max-w-[280px]` (full width on phones, capped from `sm`).

- [ ] **Step 6: Token pass** — replace any `border-slate-*` with `border-gray-*`/`dark:border-cherry-800` to match UnoPim tokens; leave `text-red-600` (delete) and placeholder greys as-is.

- [ ] **Step 7: Render check**

Run: `vendor/bin/pest --filter='ProductWorkspacePanels'`
Expected: association assertions still PASS; hidden-input contract intact.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php
git commit -m "feat(admin): associations in contained drawer; x-admin::button add/delete; responsive rows"
```

---

### Task 5: Wire edit page + delete the old frame

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php:230`
- Delete: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php`
- Delete: `packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php`

- [ ] **Step 1: Swap the include** — replace `@include('admin::catalog.products.edit.workspace')` with `@include('admin::catalog.products.edit.section-store')` (removing the temporary duplicate include added in Task 1 Step 5, so the store is included exactly once).

- [ ] **Step 2: Delete the dead frame files**

```bash
git rm packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php \
       packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php
```

- [ ] **Step 3: Grep for stragglers**

Run: `grep -rn "v-product-workspace\|workspace-panel\|setWorkspaceBounds\|product-workspace-frame\|product-workspace-panel" packages/Webkul/Admin/src/Resources/views`
Expected: no matches (all removed). Fix any remaining reference.

- [ ] **Step 4: Full render check**

Run: `vendor/bin/pest --filter='ProductWorkspacePanels'`
Expected: PASS, and the page no longer emits the old frame.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php
git commit -m "refactor(admin): mount slim section-store, remove fullscreen workspace frame + panel shell"
```

---

### Task 6: Finalize Pest coverage

**Files:**
- Modify: `packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php`

- [ ] **Step 1: Restore + expand assertions** — re-enable the `assertDontSee('v-product-workspace')` line (now valid) and add:

```php
it('renders both sections as contained drawers with intact association contract', function () {
    $type = AssociationType::factory()->create(['code' => 'kit', 'is_user_defined' => 1]);
    $product = Product::factory()->simple()->create();

    $html = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSee('v-product-section-drawer', false)
        ->assertSee('data-section-id="associations"', false)
        ->assertSee('associations[kit][__present]', false)   // sentinel preserved
        ->assertDontSee('v-product-workspace', false)
        ->assertDontSee('secondary-button text-xs" @click', false) // raw div add-btn gone
        ->getContent();

    expect($html)->not->toContain('workspace-panel');
});
```

(Adjust the `AssociationType` factory/fields to match this repo's existing factory used by the current test — read the top of the file first and reuse its setup helpers rather than inventing new ones.)

- [ ] **Step 2: Run the file**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php
git commit -m "test(admin): assert section-drawer markup + association contract, frame removed"
```

---

### Task 7: Playwright viewport-matrix E2E

**Files:**
- Create: `tests/e2e-pw/tests/product-section-drawer.spec.js`

**Interfaces:**
- Consumes: existing auth state `tests/e2e-pw/.state/admin-auth.json`, base URL from `playwright.config.js`. Target an existing simple product edit route.

- [ ] **Step 1: Write the spec**

```js
const { test, expect } = require('@playwright/test');

const PRODUCT_EDIT = '/admin/catalog/products/edit/3';

const widthRatio = async (page) => page.evaluate(() => {
  const main = document.getElementById('main-content');
  const panel = document.querySelector('#main-content [data-section-id]');
  if (!main || !panel) return null;
  return panel.getBoundingClientRect().width / main.getBoundingClientRect().width;
});

const noOverflow = async (page) => page.evaluate(() =>
  document.body.scrollWidth <= document.documentElement.clientWidth + 1);

test.describe('product-edit section drawer', () => {
  for (const [w, h, expected] of [[1440, 900, 0.70], [1024, 800, 0.80], [768, 900, 0.90], [375, 720, 1.0]]) {
    test(`categories drawer at ${w}px ≈ ${expected * 100}% and no overflow`, async ({ page }) => {
      await page.setViewportSize({ width: w, height: h });
      await page.goto(PRODUCT_EDIT);
      await page.getByRole('button', { name: /categories/i }).first().click();
      const panel = page.locator('#main-content [data-section-id="categories"]');
      await expect(panel).toBeVisible();
      const ratio = await widthRatio(page);
      expect(ratio).toBeGreaterThan(expected - 0.06);
      expect(ratio).toBeLessThan(Math.min(1.02, expected + 0.06));
      expect(await noOverflow(page)).toBe(true);
      // sidebar stays visible/undimmed
      await expect(page.locator('#unopim-sidebar')).toBeVisible();
    });
  }

  test('associations add/remove updates count badge', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(PRODUCT_EDIT);
    await page.getByRole('button', { name: /link|associations/i }).first().click();
    await expect(page.locator('#main-content [data-section-id="associations"]')).toBeVisible();
  });

  test('rows stack vertically at 375px', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 720 });
    await page.goto(PRODUCT_EDIT);
    await page.getByRole('button', { name: /link|associations/i }).first().click();
    expect(await noOverflow(page)).toBe(true);
  });
});
```

- [ ] **Step 2: Run it**

Run: `cd tests/e2e-pw && npx playwright test tests/product-section-drawer.spec.js`
Expected: all pass. If auth state is stale, refresh via the project's login setup, then re-run. If the card's accessible name differs, adjust the `getByRole` name regex to the rendered title.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e-pw/tests/product-section-drawer.spec.js
git commit -m "test(e2e): section-drawer width ladder + no-overflow across viewport matrix"
```

---

### Task 8: Full verification gate

- [ ] **Step 1: Pint**

Run: `vendor/bin/pint --test packages/Webkul/Admin`
Expected: no issues. If any, run `vendor/bin/pint packages/Webkul/Admin` and re-check.

- [ ] **Step 2: Larastan on changed PHP** (only Blade changed; run on the Admin package to be safe)

Run: `vendor/bin/phpstan analyse packages/Webkul/Admin/src --memory-limit=1G`
Expected: no new errors vs baseline. Fix root cause; never suppress.

- [ ] **Step 3: Pest (Admin suite)** — per [[running-pest-this-workspace]] use host-MySQL env overrides:

Run: `vendor/bin/pest --testsuite=Admin`
Expected: PASS.

- [ ] **Step 4: Playwright** — full product spec set:

Run: `cd tests/e2e-pw && npx playwright test tests/product-section-drawer.spec.js`
Expected: PASS.

- [ ] **Step 5: Translations check**

Run: `php artisan unopim:translations:check`
Expected: zero errors (no new keys were introduced; reused existing ones).

- [ ] **Step 6: Manual smoke via Playwright MCP** (this repo runs at `192.168.15.243:8023`, creds in [[docker-run-8023]]): open `/admin/catalog/products/edit/3`, click each card, confirm drawer at ~70% desktop, sidebar + header undimmed, ESC + overlay-click + close all shut it, dark mode intact. Resize to 375 and confirm full-width, no overflow, rows stacked.

- [ ] **Step 7: Final commit if any fixups**

```bash
git add -A packages/Webkul/Admin
git commit -m "chore(admin): section-drawer verification fixups (pint/phpstan/pest/e2e)"
```

---

## Self-Review

- **Spec coverage:** contained drawer (Task 1) ✓; slim store (Task 1) ✓; section-card refactor + tokens (Task 2) ✓; categories native + responsive search (Task 3) ✓; associations x-admin::button + responsive rows + contract preserved (Task 4) ✓; delete frame/panel (Task 5) ✓; width ladder + no-overflow matrix + sidebar undimmed (Task 7) ✓; app-chrome coexistence via teleport-into-main + absolute % width (Task 1 design) ✓; i18n reuse (Global Constraints) ✓; verification gate incl. translations:check (Task 8) ✓.
- **Spec deviation logged:** section cards stay in the existing right-column (already responsive); no new card grid — noted in File Structure.
- **Type/name consistency:** store methods `setCount/getCount/setDirty/isDirty` used identically in Tasks 1–4; `data-section-id="associations"` selector aligned between Task 4 Step 2 and `publishState()`; component tag `v-product-section-drawer` consistent across Tasks 1, 4, 6, 7.
- **Open verification during impl (not placeholders — explicit checks):** exact `x-admin::button` prop names (Task 4 Steps 3–4) and the admin primary-link colour (Task 2 Step 1) must be confirmed against the real component/view before finalizing — instructions included inline.
