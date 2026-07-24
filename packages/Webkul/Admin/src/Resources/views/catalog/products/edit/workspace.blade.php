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
        .dark .product-workspace-panel { background: #26283D; }

        [v-cloak] { display: none !important; }
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
                    <span class="grid place-items-center w-9 h-9 rounded bg-violet-50 dark:bg-cherry-800 shrink-0">
                        <span :class="activeIcon" class="text-xl text-gray-600 dark:text-gray-300"></span>
                    </span>
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
                        <span class="icon-cancel text-lg"></span>
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
        // `#unopim-sidebar` is the real left nav element in this theme
        // (packages/Webkul/Admin/src/Resources/views/components/layouts/sidebar/index.blade.php);
        // the other selectors are defensive fallbacks in case a theme override renames it.
        const setLeft = () => {
            const nav = document.querySelector('#unopim-sidebar, .left-navigation, aside, nav[role="navigation"]');
            const w = nav ? Math.round(nav.getBoundingClientRect().right) : 0;
            document.documentElement.style.setProperty('--product-workspace-left', w + 'px');
        };
        setLeft();
        window.addEventListener('resize', setLeft);

        // The sidebar collapses/expands via a CSS width transition (no resize event),
        // so re-measure once that transition finishes.
        document.getElementById('unopim-sidebar')?.addEventListener('transitionend', (e) => {
            if (e.propertyName === 'width') setLeft();
        });

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
