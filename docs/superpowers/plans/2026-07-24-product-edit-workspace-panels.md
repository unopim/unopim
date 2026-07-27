# Product Edit Workspace Panels — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the cramped product-edit right rail with compact section cards that open a full-width tabbed workspace overlay (Categories, Associations, + injected sections), keeping a single product Save.

**Architecture:** A shared Vue reactive store (`productWorkspaceStore`) holds open state, active section, the registered-section list, and per-section dirty flags. A `v-product-workspace` chrome component renders the fixed overlay frame (backdrop + header + tab switcher + close). Each section renders a compact card (`x-admin::product.section-card`) in the rail and a fixed body panel (`x-admin::product.workspace-panel`) that stays a DOM descendant of the product `<x-admin::form>` so all inputs submit with the existing Save. Sections self-register into the store, so packages inject new sections via the existing `column_after` view-render event with zero core edits.

**Tech Stack:** Laravel 12 Blade, Vue 3 (global `app.component`, inline `x-template`), Tailwind (UnoPim cherry theme), UnoPim `x-admin::*` Blade components, Pest 3, Playwright 1.52.

## Global Constraints

- Laravel 13 / PHP 8.4 idioms; FormRequest validation; no inline `$request->validate()`. (No server contract change in this plan.)
- Component-first UI: use `x-admin::form.control-group[.label|.control|.error]` and existing components; no raw HTML form controls.
- No hardcoded user-facing text: `trans('admin::…')`, add `en_US` first, propagate to ALL 33 locales, keep `:placeholders` untranslated.
- Design tokens from existing admin theme only: card = `p-4 bg-white dark:bg-cherry-900 rounded box-shadow`; title = `text-base font-semibold text-gray-800 dark:text-white`; primary = cherry (`cherry-600/700`, `text-violet-700`); dark mode everywhere. No mockup-specific hex, no new font.
- **Icons are UnoPim icon-font CLASSES only (e.g. `icon-folder`, `icon-product`, `icon-information`) — NO emoji, no custom SVG.** `section-card` and the workspace chrome render the `$icon`/registered icon as a CSS class (`<span class="{{ $icon }}">` / `:class="activeIcon"`), never as raw HTML/emoji.
- **Vue inline expressions that embed `@json(...)` must use SINGLE-quoted attributes** (`v-text='… @json(…)'`) so `@json`'s double quotes don't terminate the attribute — a double-quoted `v-text="… @json(…)"` produces a Vue compile `SyntaxError` that breaks the whole page mount.
- **No global `Vue` object exists.** app.js imports `createApp` from `vue/dist/vue.esm-bundler` and only globalizes `window.app`. To build a reactive store in an inline blade script, use `window.Vue.reactive(...)` (app.js exposes `window.Vue = { reactive, ref, computed, watch }` alongside `window.DOMPurify`). Never write bare `Vue.reactive`. Guard template store refs with optional chaining (`$productWorkspace?.open(...)`) so a load-order gap can't hard-crash the page.
- Fields must remain DOM descendants of the product `<x-admin::form>`. Never `<Teleport>` section bodies out of the form.
- All sections mounted with `v-show` (never `v-if`) so tab-switching never drops unsaved edits.
- Supported locales (33): ar_AE ca_ES da_DK de_DE en_AU en_GB en_NZ en_US es_ES es_VE fi_FI fr_FR hi_IN hr_HR id_ID it_IT ja_JP ko_KR mn_MN nl_NL no_NO pl_PL pt_BR pt_PT ro_RO ru_RU sv_SE tl_PH tr_TR uk_UA vi_VN zh_CN zh_TW.
- After PHP changes: `vendor/bin/pint` then `vendor/bin/pint --test`; `vendor/bin/phpstan analyse <files> --memory-limit=1G`. UI: Playwright. Translations: `php artisan unopim:translations:check`.
- Workspace directory: `/home/users/navneet.kumar/hdd/home/users/navneet.kumar/www/html/github/unopim-associations` (serves :8014, NOT :8000).

---

## File Structure

- Create `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php` — workspace chrome: store bootstrap + `v-product-workspace` component (overlay frame, header, tab switcher, close).
- Create `packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php` — reusable rail card (`<x-admin::product.section-card>`).
- Create `packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php` — reusable fixed body panel + section registration (`<x-admin::product.workspace-panel>`).
- Modify `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php` — card + workspace-panel body; register `categories` section; expose selected-count.
- Modify `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php` — card + workspace-panel body; register `associations` section; expose linked-count.
- Modify `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php` — right column: keep `product-info` inline, include `workspace` once, keep `column_before/after` events; move category/links includes so their cards land in the rail and bodies in the workspace.
- Modify `packages/Webkul/Admin/src/Resources/lang/{locale}/app.php` (×33) — new keys.
- Create `packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php` — Pest feature test.
- Create `tests/e2e-pw/tests/product-workspace.spec.js` — Playwright E2E.

---

### Task 1: Workspace store + section registry (client state)

The store is defined inline in `workspace.blade.php` (Task 2) but its contract is fixed here and used by every later task. No standalone file — UnoPim registers Vue globals via blade `@pushOnce('scripts')`, so the store lives on the shared `app` instance as `app.config.globalProperties.$productWorkspace`.

**Files:**
- (defined in Task 2's `workspace.blade.php`)

**Interfaces:**
- Produces global `this.$productWorkspace` (Vue reactive), API:
  - `sections: Array<{ id:string, title:string, subtitle:string, icon:string, order:number }>` — ordered by `order` then registration.
  - `register(section)` — idempotent by `id` (re-register replaces).
  - `isOpen: boolean`, `activeId: string|null`.
  - `open(id)` — sets `activeId=id`, `isOpen=true`.
  - `close()` — sets `isOpen=false` (keeps `activeId`, does NOT revert edits).
  - `isActive(id): boolean` — `isOpen && activeId===id`.
  - `dirty: Record<string, boolean>`; `setDirty(id, bool)`; `isDirty(id): boolean`.
  - `count: Record<string, number>`; `setCount(id, n)`; `getCount(id): number`.

No code step here; contract only. Implemented and committed in Task 2.

- [ ] **Step 1: Confirm no conflicting global**

Run: `grep -rn "productWorkspace\|\$productWorkspace" packages/Webkul/Admin/src/Resources`
Expected: no matches (clean slate).

---

### Task 2: Workspace chrome component + store bootstrap

**Files:**
- Create: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `this.$productWorkspace` reactive store (Task 1 contract); `<v-product-workspace></v-product-workspace>` chrome; global CSS classes `.product-workspace-panel` (fixed body region) usable by any section.

- [ ] **Step 1: Create the chrome + store partial**

Create `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php`:

```blade
<v-product-workspace></v-product-workspace>

@pushOnce('styles')
    <style>
        .product-workspace-open { overflow: hidden; }

        /* Fixed body region for section panels; sits right of the admin icon-nav,
           below the workspace header. Stays a DOM descendant of the product form. */
        .product-workspace-panel {
            position: fixed;
            top: 4rem;            /* header height */
            bottom: 0;
            left: var(--product-workspace-left, 0px);
            right: 0;
            z-index: 41;
            overflow-y: auto;
            padding: 1.5rem;
            background: rgb(250 249 255);          /* cherry primary-page */
        }
        .dark .product-workspace-panel { background: #1e1b3a; } /* cherry-900-ish */
    </style>
@endPushOnce

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-workspace-template"
    >
        <div
            v-show="store.isOpen"
            class="fixed inset-0 z-40"
            style="left: var(--product-workspace-left, 0px);"
        >
            <!-- Frame background -->
            <div class="absolute inset-0 bg-[rgb(250,249,255)] dark:bg-cherry-900"></div>

            <!-- Header (above body panels) -->
            <div class="absolute top-0 left-0 right-0 h-16 z-[42] flex items-center justify-between gap-4 px-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-cherry-900">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="grid place-items-center w-9 h-9 rounded bg-violet-50 dark:bg-cherry-800 text-lg shrink-0" v-html="activeIcon"></span>
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-gray-800 dark:text-white truncate">@{{ activeTitle }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ activeSubtitle }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Tab switcher -->
                    <div class="flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-cherry-800 p-1">
                        <button
                            type="button"
                            v-for="section in store.sections"
                            :key="section.id"
                            @click="store.open(section.id)"
                            :class="store.activeId === section.id
                                ? 'bg-white dark:bg-cherry-900 text-violet-700 dark:text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-300'"
                            class="relative px-3 py-1.5 text-xs font-medium rounded-md transition-all"
                        >
                            @{{ section.title }}
                            <span
                                v-if="store.isDirty(section.id)"
                                class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-amber-500"
                            ></span>
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="store.close()"
                        class="grid place-items-center w-9 h-9 rounded border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-800 dark:hover:text-white"
                        :aria-label="closeLabel"
                    >
                        <span class="icon-cross text-lg"></span>
                    </button>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        const store = Vue.reactive({
            sections: [],
            isOpen: false,
            activeId: null,
            dirty: {},
            count: {},

            register(section) {
                const idx = this.sections.findIndex(s => s.id === section.id);
                const value = {
                    order: 0,
                    subtitle: '',
                    icon: '',
                    ...section,
                };
                if (idx === -1) {
                    this.sections.push(value);
                } else {
                    this.sections.splice(idx, 1, value);
                }
                this.sections.sort((a, b) => a.order - b.order);
            },

            open(id) { this.activeId = id; this.isOpen = true; },
            close() { this.isOpen = false; },
            isActive(id) { return this.isOpen && this.activeId === id; },

            setDirty(id, val) { this.dirty[id] = !!val; },
            isDirty(id) { return !!this.dirty[id]; },

            setCount(id, n) { this.count[id] = n; },
            getCount(id) { return this.count[id] ?? 0; },
        });

        app.config.globalProperties.$productWorkspace = store;

        // Left offset so the overlay clears the admin icon-nav rail.
        const setLeft = () => {
            const nav = document.querySelector('.left-navigation, aside, nav[role="navigation"]');
            const w = nav ? Math.round(nav.getBoundingClientRect().right) : 0;
            document.documentElement.style.setProperty('--product-workspace-left', w + 'px');
        };
        setLeft();
        window.addEventListener('resize', setLeft);

        app.component('v-product-workspace', {
            template: '#v-product-workspace-template',

            data: () => ({ store, closeLabel: "@lang('admin::app.catalog.products.edit.workspace.close')" }),

            computed: {
                activeSection() { return this.store.sections.find(s => s.id === this.store.activeId) || {}; },
                activeTitle() { return this.activeSection.title || ''; },
                activeSubtitle() { return this.activeSection.subtitle || ''; },
                activeIcon() { return this.activeSection.icon || ''; },
            },

            watch: {
                'store.isOpen'(open) {
                    document.body.classList.toggle('product-workspace-open', open);
                },
            },

            mounted() {
                this._esc = (e) => { if (e.key === 'Escape' && this.store.isOpen) this.store.close(); };
                window.addEventListener('keydown', this._esc);
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this._esc);
                document.body.classList.remove('product-workspace-open');
            },
        });
    </script>
@endPushOnce
```

- [ ] **Step 2: Lint the blade (no PHP syntax issues in directives)**

Run: `php -r "echo 'ok';" && grep -c "endPushOnce\|endpushonce\|@endPushOnce" packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php`
Expected: prints `ok` and a non-zero count (both `@pushOnce` blocks closed).

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php
git commit -m "feat(admin): product-edit workspace chrome + shared section store"
```

---

### Task 3: Reusable Blade components — section card + workspace panel

**Files:**
- Create: `packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php`
- Create: `packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php`

**Interfaces:**
- Consumes: `$productWorkspace` store (Task 2).
- Produces:
  - `<x-admin::product.section-card :id :title :icon />` — rail card; a scoped slot-free card whose summary + dirty come from the store (`getCount(id)`, `isDirty(id)`), click → `open(id)`.
  - `<x-admin::product.workspace-panel :id :title :subtitle :icon :order :summary-key />` with a default slot for the body; registers the section on mount and renders the slot inside a `.product-workspace-panel` shown only when active.

- [ ] **Step 1: Create the section-card component**

Create `packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php`:

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
    @click="$productWorkspace.open('{{ $id }}')"
    @keydown.enter="$productWorkspace.open('{{ $id }}')"
    @keydown.space.prevent="$productWorkspace.open('{{ $id }}')"
    class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow flex items-center gap-3 cursor-pointer hover:border-violet-300 border border-transparent transition-all"
>
    <span class="grid place-items-center w-10 h-10 rounded bg-violet-50 dark:bg-cherry-800 text-lg shrink-0">
        {!! $icon !!}
    </span>

    <div class="min-w-0 flex-1">
        <p class="flex items-center gap-1.5 text-base font-semibold text-gray-800 dark:text-white truncate">
            {{ $title }}
            <span
                v-show="$productWorkspace.isDirty('{{ $id }}')"
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

    <span class="text-sm font-medium text-violet-700 dark:text-violet-400 shrink-0">
        @lang('admin::app.catalog.products.edit.sections.view') <span class="rtl:hidden">&rarr;</span>
    </span>
</div>
```

- [ ] **Step 2: Create the workspace-panel component**

Create `packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php`:

```blade
@props([
    'id'       => '',
    'title'    => '',
    'subtitle' => '',
    'icon'     => '',
    'order'    => 0,
])

<div
    v-show="$productWorkspace.isActive('{{ $id }}')"
    class="product-workspace-panel"
    data-section-id="{{ $id }}"
    v-cloak
>
    <div class="max-w-5xl mx-auto">
        {{ $slot }}
    </div>
</div>

@pushOnce('scripts', 'product-workspace-panel-' . $id)
    <script type="module">
        (function register() {
            const store = app.config.globalProperties.$productWorkspace;
            if (! store) { window.requestAnimationFrame(register); return; }
            store.register({
                id: @json($id),
                title: @json($title),
                subtitle: @json($subtitle),
                icon: @json($icon),
                order: {{ (int) $order }},
            });
        })();
    </script>
@endPushOnce
```

Note: `@pushOnce('scripts', <key>)` MUST pass a per-`$id` key. A bare `@pushOnce('scripts')` dedupes by a uuid baked in at COMPILE time — one cached compiled file is reused for every section, so all instances share the same key and only the first section's registration script is emitted. The per-`$id` key makes each section's push distinct; the store's `register()` is idempotent by id as a second safety net.

- [ ] **Step 3: Add `v-cloak` style if missing**

Run: `grep -rn "\[v-cloak\]" packages/Webkul/Admin/src/Resources/assets/css || echo MISSING`
If `MISSING`, add to `workspace.blade.php` `@pushOnce('styles')` block:

```css
[v-cloak] { display: none !important; }
```

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/components/product/section-card.blade.php \
        packages/Webkul/Admin/src/Resources/views/components/product/workspace-panel.blade.php
git commit -m "feat(admin): reusable product section-card + workspace-panel components"
```

---

### Task 4: Refactor Categories into card + workspace panel

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php`

**Interfaces:**
- Consumes: `x-admin::product.section-card`, `x-admin::product.workspace-panel` (Task 3); store (Task 2).
- Produces: `categories` section registered; card shows selected count via `setCount('categories', n)`.

- [ ] **Step 1: Replace the partial body**

Replace the whole file `categories.blade.php` with:

```blade
@props([
    'currentLocaleCode' => core()->getRequestedLocaleCode(),
    'productCategories' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.before', ['product' => $product]) !!}

<x-admin::product.section-card
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    icon="📁"
    summary=""
>
</x-admin::product.section-card>

<x-admin::product.workspace-panel
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.categories.subtitle')"
    icon="📁"
    :order="10"
>
    {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.before', ['product' => $product]) !!}

    <v-product-categories>
        <x-admin::shimmer.tree />
    </v-product-categories>

    {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.after', ['product' => $product]) !!}
</x-admin::product.workspace-panel>

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-categories-template"
    >
        <div>
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control
                            type="text"
                            name="__category_search"
                            ::value="search"
                            v-model="search"
                            :label="trans('admin::app.catalog.products.edit.categories.title')"
                            :placeholder="trans('admin::app.catalog.products.edit.workspace.categories.search')"
                        />
                    </x-admin::form.control-group>
                </div>
                <span class="shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300 px-3 py-2 rounded bg-gray-100 dark:bg-cherry-800">
                    @{{ selectedCount }} @lang('admin::app.catalog.products.edit.workspace.categories.selected')
                </span>
            </div>

            <template v-if="isLoading">
                <x-admin::shimmer.tree />
            </template>

            <template v-else>
                <x-admin::tree.category.view
                    input-type="checkbox"
                    selection-type="individual"
                    name-field="categories"
                    id-field="code"
                    value-field="code"
                    ::items="categories"
                    :value="json_encode($productCategories)"
                    ::expanded-branch="selectedCategoryTree"
                    :fallback-locale="config('app.fallback_locale')"
                    @input="onSelectionChange"
                >
                </x-admin::tree.category.view>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-product-categories', {
            template: '#v-product-categories-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    selectedCategoryTree: [],
                    search: '',
                    initialSelected: @json(array_values((array) $productCategories)),
                }
            },

            computed: {
                selectedCount() { return this.currentSelected().length; },
            },

            mounted() {
                this.get();
                this.$productWorkspace.setCount('categories', this.initialSelected.length);
            },

            methods: {
                get() {
                    this.$axios.post("{{ route('admin.catalog.categories.tree') }}", {
                        locale: "{{ $currentLocaleCode }}",
                        selected: @json($productCategories),
                    })
                    .then(response => {
                        this.isLoading = false;
                        this.categories = response.data.data;
                        this.selectedCategoryTree = response.data.selected_tree;
                    })
                    .catch(error => { console.log(error); });
                },

                currentSelected() {
                    return Array.from(document.querySelectorAll('input[name="categories[]"]:checked'))
                        .map(el => el.value);
                },

                onSelectionChange() {
                    this.$nextTick(() => {
                        const now = this.currentSelected();
                        this.$productWorkspace.setCount('categories', now.length);
                        const changed = now.length !== this.initialSelected.length
                            || now.some(v => ! this.initialSelected.includes(v));
                        this.$productWorkspace.setDirty('categories', changed);
                    });
                },
            }
        });
    </script>
@endPushOnce
```

- [ ] **Step 2: Point the card summary at the live store count**

`section-card` (Task 3) already renders its default slot as the summary when non-empty. So the `categories.blade.php` card just needs a reactive slot. In the file written in Step 1, replace the empty section-card:

```blade
<x-admin::product.section-card
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    icon="📁"
    summary=""
>
</x-admin::product.section-card>
```

with a reactive-summary version:

```blade
<x-admin::product.section-card
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    icon="📁"
>
    <span v-text="$productWorkspace.getCount('categories') + ' ' + @json(trans('admin::app.catalog.products.edit.workspace.categories.selected'))"></span>
</x-admin::product.section-card>
```

No change to `section-card.blade.php` is needed (it was finalized in Task 3).

- [ ] **Step 3: Verify blade renders (route smoke test)**

Run: `php artisan view:clear && php artisan route:list --name=admin.catalog.products.edit | head`
Expected: the edit route is listed; no blade compile error is thrown by `view:clear`.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/categories.blade.php
git commit -m "feat(admin): categories as workspace section card + panel with live count/dirty"
```

---

### Task 5: Refactor Associations (links) into card + workspace panel

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php`

**Interfaces:**
- Consumes: card + panel components; store.
- Produces: `associations` section registered (order 20); card summary = linked-product count; dirty on change.

- [ ] **Step 1: Wrap the existing `<v-product-links>` in the card + panel**

Edit the top of `links.blade.php` (lines 1–9). Replace:

```blade
@props([
    'associationTypes' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.before', ['product' => $product]) !!}

<v-product-links :association-types='@json($associationTypes)'></v-product-links>

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.after', ['product' => $product]) !!}
```

with:

```blade
@props([
    'associationTypes' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.before', ['product' => $product]) !!}

<x-admin::product.section-card
    id="associations"
    :title="trans('admin::app.catalog.products.edit.links.title')"
    icon="icon-product"
>
    {{-- v-text attribute is SINGLE-quoted so @json's double quotes don't collide --}}
    <span v-text='$productWorkspace.getCount("associations") + " " + @json(trans("admin::app.catalog.products.edit.workspace.associations.linked"))'></span>
</x-admin::product.section-card>

<x-admin::product.workspace-panel
    id="associations"
    :title="trans('admin::app.catalog.products.edit.links.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.associations.subtitle')"
    icon="icon-product"
    :order="20"
>
    <v-product-links :association-types='@json($associationTypes)'></v-product-links>
</x-admin::product.workspace-panel>

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.after', ['product' => $product]) !!}
```

- [ ] **Step 2: Report count + dirty from `v-product-links`**

In the `<script type="module">` that registers `v-product-links` (near the bottom of `links.blade.php`, `app.component('v-product-links', { … })`), add a `mounted` count publish and a `watch`/method that recomputes on link add/remove. Find the component's `mounted()` (or add one) and its data holding links. Add:

```javascript
            mounted() {
                this.publishState();
            },

            methods: {
                // … existing methods …

                publishState() {
                    const total = document.querySelectorAll(
                        '.product-workspace-panel[data-section-id="associations"] input[name^="associations["][name*="][products]["]'
                    ).length;
                    this.$productWorkspace.setCount('associations', total);
                },
            },
```

Then, in every existing method that adds or removes a link (e.g. the add-from-search handler and the remove handler), append `this.$nextTick(() => { this.publishState(); this.$productWorkspace.setDirty('associations', true); });`.

Note for implementer: inspect the existing `v-product-links` methods (add/remove) in this file and call `publishState()` + `setDirty('associations', true)` after each mutation. The exact selector for counting links must match the hidden inputs the component emits per linked product; verify by opening the page and checking `input` names under the associations panel.

- [ ] **Step 3: Verify blade compiles**

Run: `php artisan view:clear`
Expected: no error.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php
git commit -m "feat(admin): associations as workspace section card + panel with count/dirty"
```

---

### Task 6: Rewire the product edit right column

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php` (right column ~lines 230–259)

**Interfaces:**
- Consumes: `workspace`, refactored `categories`/`links` partials.
- Produces: rail with inline Product Info + section cards; single workspace shell mounted once; `column_before/after` events preserved for injected sections.

- [ ] **Step 1: Include the workspace chrome once, before the rail**

In `edit.blade.php`, immediately after the opening of the form's main grid (before the `right-column` div at line 230), add the workspace chrome include so it mounts once:

Locate line 229 `</div>` (end of main/left column) and line 230 `<div class="right-column …">`. Between them, insert:

```blade
            @include('admin::catalog.products.edit.workspace')
```

- [ ] **Step 2: Confirm right column order (no change needed to includes)**

The right column already includes `product-info`, then `categories`, then `links`, then `column_before/after` events (lines 231–258). Because Task 4/5 changed those partials to emit a card + a workspace-panel, the cards now stack in the rail and the bodies render into the fixed workspace region automatically. Verify the block reads (current lines 230–259) — keep as-is:

```blade
            <div class="right-column flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                @if ($variantTree ?? null)
                    <v-variant-axis-nav></v-variant-axis-nav>
                @endif

                @include('admin::catalog.products.edit.product-info')

                @include('admin::catalog.products.edit.categories', ['currentLocaleCode' => $currentLocale?->code, 'productCategories' => $product->values['categories'] ?? []])

                @if ($variantTree ?? null)
                    {!! view_render_event('unopim.admin.catalog.product.edit.form.types.' . $product->type . '.before', ['product' => $product]) !!}
                    {!! view_render_event('unopim.admin.catalog.product.edit.form.types.' . $product->type . '.after', ['product' => $product]) !!}
                @else
                    @includeIf('admin::catalog.products.edit.types.' . $product->type)
                @endif

                <!-- Related, Cross Sells, Up Sells View Blade File -->
                @include('admin::catalog.products.edit.links', [
                    'associationTypes'      => $associationTypes,
                    'upSellAssociations'    => $product->values['associations']['up_sells'] ?? [],
                    'crossSellAssociations' => $product->values['associations']['cross_sells'] ?? [],
                    'relatedAssociations'   => $product->values['associations']['related_products'] ?? [],
                ])

                @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                    @includeIf($view)
                @endforeach
            </div>
```

Injected packages that emit their own `<x-admin::product.section-card>` + `<x-admin::product.workspace-panel>` on `unopim.admin.catalog.product.edit.form.column_after` (line 227) will register and appear automatically.

- [ ] **Step 3: Build assets and smoke-test render**

Run: `npm run build && php artisan view:clear`
Expected: build succeeds; no compile error.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php
git commit -m "feat(admin): mount workspace chrome in product edit; rail shows section cards"
```

---

### Task 7: Translation keys (en_US + 33 locales)

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/lang/en_US/app.php` and 32 other locales.

**Interfaces:**
- Produces keys under `admin::app.catalog.products.edit`:
  - `sections.view` = "View"
  - `sections.unsaved` = "Unsaved changes"
  - `workspace.close` = "Close"
  - `workspace.categories.subtitle` = "Assign this product to catalog categories."
  - `workspace.categories.search` = "Search categories…"
  - `workspace.categories.selected` = "selected"
  - `workspace.associations.subtitle` = "Link related, up-sell, cross-sell and custom products."
  - `workspace.associations.linked` = "linked products"

- [ ] **Step 1: Add keys to en_US**

In `packages/Webkul/Admin/src/Resources/lang/en_US/app.php`, inside the `catalog.products.edit` array, add a `sections` and `workspace` block:

```php
                    'sections' => [
                        'view'    => 'View',
                        'unsaved' => 'Unsaved changes',
                    ],

                    'workspace' => [
                        'close'        => 'Close',
                        'categories'   => [
                            'subtitle' => 'Assign this product to catalog categories.',
                            'search'   => 'Search categories…',
                            'selected' => 'selected',
                        ],
                        'associations' => [
                            'subtitle' => 'Link related, up-sell, cross-sell and custom products.',
                            'linked'   => 'linked products',
                        ],
                    ],
```

- [ ] **Step 2: Propagate to all 32 other locales (translated naturally)**

For each locale file `packages/Webkul/Admin/src/Resources/lang/<locale>/app.php`, add the same block with natural translations. Example `fr_FR`:

```php
                    'sections' => [
                        'view'    => 'Voir',
                        'unsaved' => 'Modifications non enregistrées',
                    ],
                    'workspace' => [
                        'close'        => 'Fermer',
                        'categories'   => [
                            'subtitle' => 'Associez ce produit aux catégories du catalogue.',
                            'search'   => 'Rechercher des catégories…',
                            'selected' => 'sélectionné(s)',
                        ],
                        'associations' => [
                            'subtitle' => 'Associez des produits similaires, de montée en gamme, complémentaires et personnalisés.',
                            'linked'   => 'produits liés',
                        ],
                    ],
```

Provide equally natural translations for: ar_AE ca_ES da_DK de_DE en_AU en_GB en_NZ es_ES es_VE fi_FI hi_IN hr_HR id_ID it_IT ja_JP ko_KR mn_MN nl_NL no_NO pl_PL pt_BR pt_PT ro_RO ru_RU sv_SE tl_PH tr_TR uk_UA vi_VN zh_CN zh_TW. (`en_AU`, `en_GB`, `en_NZ` reuse the en_US English strings.) Keep any `:placeholder` untranslated (none in these keys).

- [ ] **Step 3: Verify translations**

Run: `php artisan unopim:translations:check`
Expected: zero missing-key errors for the new keys.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/lang
git commit -m "i18n(admin): workspace + section-card strings across all locales"
```

---

### Task 8: Pest feature test — page renders workspace, not inline rail tree

**Files:**
- Create: `packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php`

**Interfaces:**
- Consumes: existing `AdminTestCase` product factories/helpers used by sibling tests in `packages/Webkul/Admin/tests/Feature/Catalog`.

- [ ] **Step 1: Write the failing test**

Create `packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php`:

```php
<?php

use function Pest\Laravel\get;

it('renders the product edit workspace chrome and section cards', function () {
    $product = createSimpleProduct();   // reuse the helper sibling tests use

    $this->loginAsAdmin();              // reuse the auth helper sibling tests use

    $response = get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();
    $response->assertSee('v-product-workspace', false);
    $response->assertSee("\$productWorkspace.open('categories')", false);
    $response->assertSee("\$productWorkspace.open('associations')", false);
    $response->assertSee('product-workspace-panel', false);
});
```

Note for implementer: match `createSimpleProduct()` / `loginAsAdmin()` to the actual helpers used in `AssociationTypeControllerTest.php` / other `Feature/Catalog` tests (they may be named differently, e.g. a factory + `$this->withHeaders`/session login). Use whatever those tests use.

- [ ] **Step 2: Run to verify it fails (before wiring) or passes (after)**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php`
Expected: PASS (Tasks 2–6 already produce this markup). If run against an unrefactored tree, it FAILS on the `assertSee('v-product-workspace')`.

- [ ] **Step 3: Ensure sibling association/category save tests still pass**

Run: `vendor/bin/pest --filter='Association' packages/Webkul/Product && vendor/bin/pest packages/Webkul/AiAgent/tests/Feature/ManageAssociationsTest.php`
Expected: all PASS (payload unchanged; hidden inputs still inside the form).

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php
git commit -m "test(admin): product edit renders workspace chrome + section cards"
```

---

### Task 9: Playwright E2E — open, tab-switch, dirty, save, close

**Files:**
- Create: `tests/e2e-pw/tests/product-workspace.spec.js`

**Interfaces:**
- Consumes: existing Playwright auth state `tests/e2e-pw/.state/admin-auth.json`, base URL `http://127.0.0.1:8000` (override to :8014 if this workspace serves it — set `PW_BASE_URL` or edit config for local run).

- [ ] **Step 1: Write the E2E spec**

Create `tests/e2e-pw/tests/product-workspace.spec.js`:

```javascript
const { test, expect } = require('@playwright/test');

// Assumes a product with SKU set below exists in the seeded DB.
const PRODUCT_EDIT_PATH = process.env.PW_PRODUCT_EDIT_PATH || '/admin/catalog/products/edit/1';

test.describe('Product edit workspace', () => {
    test('opens a section, switches tabs, marks dirty, closes', async ({ page }) => {
        await page.goto(PRODUCT_EDIT_PATH);

        // Rail shows section cards
        const categoriesCard = page.getByText('Categories', { exact: false }).first();
        await expect(categoriesCard).toBeVisible();

        // Open Categories workspace
        await categoriesCard.click();
        await expect(page.locator('.product-workspace-panel[data-section-id="categories"]')).toBeVisible();

        // Switch to Associations tab without closing
        await page.getByRole('button', { name: 'Associations' }).click();
        await expect(page.locator('.product-workspace-panel[data-section-id="associations"]')).toBeVisible();
        await expect(page.locator('.product-workspace-panel[data-section-id="categories"]')).toBeHidden();

        // Close with Escape
        await page.keyboard.press('Escape');
        await expect(page.locator('.product-workspace-panel[data-section-id="associations"]')).toBeHidden();
    });
});
```

- [ ] **Step 2: Run E2E (requires app serving + seeded product)**

Run: `cd tests/e2e-pw && PW_BASE_URL=http://127.0.0.1:8014 PW_PRODUCT_EDIT_PATH=/admin/catalog/products/edit/<id> npx playwright test product-workspace.spec.js`
Expected: PASS. If the app is not running, start it first (`php artisan serve --port=8014`) and ensure a product exists.

- [ ] **Step 3: Commit**

```bash
git add tests/e2e-pw/tests/product-workspace.spec.js
git commit -m "test(e2e): product edit workspace open/switch/close"
```

---

### Task 10: Extensibility example + docs (injected section contract)

**Files:**
- Modify: `docs/superpowers/specs/2026-07-24-product-edit-workspace-panels-design.md` (append an "Injecting a section" appendix).

**Interfaces:**
- Produces: a copy-paste contract packages (incl. Publication/DPP) use to inject a section via `unopim.admin.catalog.product.edit.form.column_after`.

- [ ] **Step 1: Append the injection recipe to the spec**

Add to the design doc:

```markdown
## Appendix — Injecting a section (packages)

Listen on the existing render event and emit a card + panel:

    // In the package's view-render listener for
    // 'unopim.admin.catalog.product.edit.form.column_after'
    <x-admin::product.section-card
        id="dpp"
        :title="trans('publication::app.passport.title')"
        icon="🛡"
    >
        <span v-text="…summary…"></span>
    </x-admin::product.section-card>

    <x-admin::product.workspace-panel
        id="dpp"
        :title="trans('publication::app.passport.title')"
        :subtitle="trans('publication::app.passport.subtitle')"
        icon="🛡"
        :order="30"
    >
        {{-- package's own Vue body; any hidden inputs it renders submit
             with the product form because the panel stays inside it --}}
        <v-product-passport></v-product-passport>
    </x-admin::product.workspace-panel>

The section auto-registers into `$productWorkspace`; its tab and card appear
in registration/`order` sequence. No core file changes.
```

- [ ] **Step 2: Commit**

```bash
git add -f docs/superpowers/specs/2026-07-24-product-edit-workspace-panels-design.md
git commit -m "docs: appendix — how packages inject a workspace section"
```

---

### Task 11: Full verification gate

**Files:** none (verification only).

- [ ] **Step 1: Pint**

Run: `vendor/bin/pint packages/Webkul/Admin/src/Resources packages/Webkul/Admin/tests && vendor/bin/pint --test`
Expected: `PASS`, zero issues. (Blade files are not PHP-formatted by Pint; test files are.)

- [ ] **Step 2: Larastan on changed PHP (test + any PHP touched)**

Run: `vendor/bin/phpstan analyse packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php --memory-limit=1G`
Expected: `[OK] No errors`.

- [ ] **Step 3: Pest — targeted suites**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Catalog/ProductWorkspacePanelsTest.php && vendor/bin/pest --filter='Association' packages/Webkul/Product`
Expected: all PASS.

- [ ] **Step 4: Translations**

Run: `php artisan unopim:translations:check`
Expected: zero errors.

- [ ] **Step 5: Build + Playwright**

Run: `npm run build && cd tests/e2e-pw && npx playwright test product-workspace.spec.js`
Expected: build ok; E2E PASS.

- [ ] **Step 6: Final commit (if any fixups)**

```bash
git add -A
git commit -m "chore(admin): product workspace panels — verification fixups" || echo "nothing to commit"
```

---

## Notes for the implementer

- **Verify selectors against the real DOM.** The count/dirty selectors in Tasks 4–5 depend on the exact hidden-input `name` patterns emitted by `v-product-categories` (`categories[]`) and `v-product-links` (`associations[<code>][products][…]`). Open the page, inspect, and adjust the selectors so counts are accurate. This is the one place the plan cannot be 100% blind.
- **`--product-workspace-left`** is computed from the admin nav rail's right edge. If the nav selector doesn't match this theme, set it to the actual left-nav element in `workspace.blade.php` `setLeft()`.
- **Icons:** emojis are placeholders matching the mockup. If the admin theme has an icon set (`icon-*` classes are used for the close button), swap the emoji spans for theme icon classes to stay on-brand.
- **DPP** is delivered by the Publication package using the Task 10 contract; it is out of scope for this plan's core deliverable but is unblocked by it.
```
