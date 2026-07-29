@props([
    'id'                => '',
    'title'             => '',
    'subtitle'          => '',
    'icon'              => '',
    'formId'            => '',
    'formFields'        => '',
    'searchable'        => false,
    'searchPlaceholder' => '',
    'fullHeight'        => false,
    'offsetEnd'         => 0,
    'dockTo'            => '',
])

<v-product-section-drawer
    id="{{ $id }}"
    title="{{ $title }}"
    subtitle="{{ $subtitle }}"
    icon="{{ $icon }}"
    form-id="{{ $formId }}"
    form-fields="{{ $formFields }}"
    :searchable="{{ $searchable ? 'true' : 'false' }}"
    search-placeholder="{{ $searchPlaceholder }}"
    :full-height="{{ $fullHeight ? 'true' : 'false' }}"
    :offset-end="{{ (int) $offsetEnd }}"
    dock-to="{{ $dockTo }}"
>
    <template #toggle>
        {{ $toggle }}
    </template>

    @isset($headerActions)
        <template #header-actions>
            {{ $headerActions }}
        </template>
    @endisset

    <template #content="{ search }">
        {{ $content }}
    </template>

    @isset($footer)
        <template #footer="{ close }">
            {{ $footer }}
        </template>
    @endisset
</v-product-section-drawer>

@pushOnce('scripts')
    <style>
        .v-drawer-search::-webkit-search-cancel-button,
        .v-drawer-search::-webkit-search-decoration {
            -webkit-appearance: none;
            appearance: none;
        }
    </style>

    <script type="text/x-template" id="v-product-section-drawer-template">
        <div>
            <div
                @click="open"
                @keydown.enter="open"
                @keydown.space.prevent="open"
            >
                <slot name="toggle"></slot>
            </div>

            <teleport
                to="body"
                v-if="mounted"
            >
                <transition
                    enter-active-class="transition-opacity ease-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition-opacity ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-show="isOpen"
                        ref="overlay"
                        class="fixed bg-gray-500/30 dark:bg-black/50"
                        :style="overlayStyle"
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
                        ref="panel"
                        :data-section-id="id"
                        :style="panelStyle"
                        class="fixed flex flex-col bg-unopim-primary-page dark:bg-cherry-800 shadow-2xl"
                    >
                        <div class="shrink-0 flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="grid place-items-center w-9 h-9 rounded bg-unopim-primary-soft dark:bg-cherry-800 shrink-0">
                                    <span
                                        :class="icon"
                                        class="text-xl text-gray-600 dark:text-gray-300"
                                    ></span>
                                </span>

                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-gray-800 dark:text-white truncate">@{{ title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ subtitle }}</p>
                                </div>
                            </div>

                            <div class="flex flex-1 items-center justify-end gap-3 min-w-0">
                                <div
                                    v-if="searchable"
                                    class="relative flex-1 basis-56 min-w-0 max-w-md"
                                >
                                    <input
                                        type="search"
                                        ref="search"
                                        v-model="search"
                                        :placeholder="searchPlaceholder"
                                        :aria-label="searchPlaceholder"
                                        class="v-drawer-search w-full py-2 ltr:pl-3 rtl:pr-3 ltr:pr-9 rtl:pl-9 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                                    />

                                    <button
                                        v-if="search"
                                        type="button"
                                        @click="clearSearch"
                                        :aria-label="clearLabel"
                                        :title="clearLabel"
                                        class="icon-cancel text-xl absolute inset-y-0 ltr:right-2 rtl:left-2 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-white"
                                    ></button>

                                    <span
                                        v-else
                                        class="icon-search text-xl absolute inset-y-0 ltr:right-2 rtl:left-2 flex items-center text-gray-400 pointer-events-none"
                                    ></span>
                                </div>

                                <slot name="header-actions"></slot>

                                <button
                                    type="button"
                                    @click="close"
                                    :aria-label="closeLabel"
                                    class="icon-cancel text-3xl shrink-0 cursor-pointer p-1 hover:bg-primary-50 dark:hover:bg-cherry-800 hover:rounded-md"
                                ></button>
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 overflow-auto p-6">
                            <slot
                                name="content"
                                :search="search"
                            ></slot>
                        </div>

                        <div
                            class="flex justify-end gap-2.5 px-6 py-4 border-t border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900"
                            v-if="$slots.footer"
                        >
                            <slot
                                name="footer"
                                :close="close"
                            ></slot>
                        </div>
                    </div>
                </transition>
            </teleport>
        </div>
    </script>

    <script type="module">
        app.component('v-product-section-drawer', {
            template: '#v-product-section-drawer-template',

            props: {
                id: String,
                title: String,
                subtitle: String,
                icon: String,
                formId: String,
                formFields: String,
                searchable: {
                    type: Boolean,
                    default: false,
                },
                searchPlaceholder: {
                    type: String,
                    default: '',
                },
                fullHeight: {
                    type: Boolean,
                    default: false,
                },
                offsetEnd: {
                    type: Number,
                    default: 0,
                },
                dockTo: {
                    type: String,
                    default: '',
                },
            },

            data: () => ({
                isOpen: false,
                isTouched: false,
                mounted: false,
                search: '',
                overlayStyle: {},
                panelStyle: {},
                closeLabel: "@lang('admin::app.catalog.products.edit.workspace.close')",
                clearLabel: "@lang('admin::app.catalog.products.edit.workspace.clear-search')",
            }),

            computed: {
                offClass() {
                    return document.dir === 'rtl' ? '-translate-x-full' : 'translate-x-full';
                },
            },

            mounted() {
                this.mounted = true;

                this._esc = (e) => {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.close();
                    }
                };

                this._reflow = () => {
                    if (this.isOpen) {
                        this.reposition();
                    }
                };

                window.addEventListener('keydown', this._esc);
                window.addEventListener('resize', this._reflow);

                /**
                 * Panel content that mutates the form without a `change` event
                 * escapes the panel listener below and signals the drawer here.
                 */
                this._onSectionTouch = (id) => {
                    if (id !== this.id) {
                        return;
                    }

                    this.isTouched = true;

                    this.claimFormFields();

                    this.touchTracker();
                };

                this.$emitter.on('section-drawer:touch', this._onSectionTouch);

                /**
                 * Collapsing the sidebar resizes main-content without resizing the
                 * window, so the panel has to track the element itself or it keeps
                 * the geometry it was opened with. The observer also fires through
                 * the collapse transition, so the panel follows it rather than
                 * jumping once it ends.
                 */
                if (window.ResizeObserver && this.main()) {
                    this._observer = new ResizeObserver(this._reflow);
                    this._observer.observe(this.main());
                }
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this._esc);
                window.removeEventListener('resize', this._reflow);

                this.$emitter.off('section-drawer:touch', this._onSectionTouch);

                this._observer?.disconnect();
                this._fieldObserver?.disconnect();

                if (this._onPanelChange) {
                    this.$refs.panel?.removeEventListener('change', this._onPanelChange, true);
                }
            },

            methods: {
                main() {
                    return document.getElementById('main-content');
                },

                /**
                 * The panel is teleported to `<body>`, and a control's owning form is
                 * decided by DOM ancestry — so without an explicit `form` attribute
                 * every field in here is dropped from the product's submission and is
                 * invisible to the unsaved-changes tracker. `formFields` scopes this to
                 * the caller's own controls so unrelated inputs that merely live in the
                 * panel (a picker's search box, a datagrid filter) stay out of the form.
                 */
                claimFormFields() {
                    if (! this.formId || ! this.formFields || ! this.$refs.panel) {
                        return;
                    }

                    let claimed = false;

                    this.$refs.panel.querySelectorAll(this.formFields).forEach((el) => {
                        if (el.getAttribute('form') !== this.formId) {
                            el.setAttribute('form', this.formId);

                            claimed = true;
                        }
                    });

                    // Syncing after an edit would re-baseline the rows it just added and the save bar would never open.
                    if (claimed && ! this.isTouched) {
                        this.$el?.dispatchEvent(new CustomEvent('unsaved-changes:sync', { bubbles: true }));
                    }
                },

                /**
                 * Teleported controls never bubble into the tracker's root, so the bar
                 * is signalled from the in-place toggle instead. The group name is the
                 * field prefix the tracker matches submitted keys against.
                 */
                touchTracker() {
                    const name = (this.formFields.match(/name\^?=["']?([a-zA-Z0-9_-]+)/) ?? [])[1];

                    if (! name) {
                        return;
                    }

                    this.$el?.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                        detail: { name },
                        bubbles: true,
                    }));
                },

                /**
                 * Width the drawer occupies as a fraction of the main-content area:
                 * 90% from tablet up, full-width below so it stays usable on phones.
                 */
                ratio() {
                    return window.innerWidth >= 768 ? 0.90 : 1;
                },

                /**
                 * Positions the fixed overlay/panel over the visible main-content
                 * region using its live bounding rect, so the drawer tracks the
                 * left sidebar and app header without covering them and stays in the
                 * viewport regardless of page scroll.
                 */
                /**
                 * Width of whatever is docked against the same edge, so the panel
                 * stops at their border rather than a fixed guess -- a second docked
                 * panel opening after this one would otherwise be overlapped.
                 */
                dockEdge() {
                    if (! this.dockTo) {
                        return null;
                    }

                    const rtl = document.dir === 'rtl';

                    const edges = [...document.querySelectorAll(this.dockTo)]
                        .filter(el => el.getClientRects().length)
                        .map(el => {
                            const box = el.getBoundingClientRect();

                            return rtl ? box.right : box.left;
                        });

                    if (! edges.length) {
                        return null;
                    }

                    return rtl ? Math.max(...edges) : Math.min(...edges);
                },

                reposition() {
                    const main = this.main();

                    if (! main) {
                        return;
                    }

                    const rect = main.getBoundingClientRect();
                    const rtl = document.dir === 'rtl';

                    // getBoundingClientRect and a docked panel's `right: 0` are both
                    // measured against the client area; window.innerWidth counts the
                    // scrollbar too and would leave the panel short of the dock edge.
                    const viewport = document.documentElement.clientWidth;

                    const edge = this.dockEdge();

                    const width = edge === null
                        ? Math.round(rect.width * this.ratio())
                        : Math.max(320, Math.round(rtl ? rect.right - edge : edge - rect.left));

                    const top = this.fullHeight ? '0px' : Math.round(rect.top) + 'px';
                    const bottom = this.fullHeight ? '0px' : Math.round(window.innerHeight - rect.bottom) + 'px';

                    // z-index set inline (not via Tailwind's z-[..] classes, which
                    // aren't compiled from this x-template's markup) so the panel
                    // sits above page content -- incl. the rich-text toolbars -- yet
                    // below the app's own drawers/modals (z-index 10001), which is
                    // what lets a docked panel slide out from beneath one.
                    this.overlayStyle = {
                        top,
                        bottom,
                        left: edge === null ? Math.round(rect.left) + 'px' : '0px',
                        width: edge === null
                            ? Math.round(rect.width) + 'px'
                            : Math.round(rtl ? viewport - edge : edge) + 'px',
                        zIndex: 9998,
                    };

                    this.panelStyle = {
                        top,
                        bottom,
                        width: width + 'px',
                        zIndex: 9999,
                        ...(edge === null
                            ? (rtl
                                ? { left: Math.round(rect.left) + 'px' }
                                : { right: Math.round(viewport - rect.right) + 'px' })
                            : { left: Math.round(rtl ? edge : rect.left) + 'px' }),
                    };
                },

                open() {
                    if (! this.main()) {
                        return;
                    }

                    this.reposition();
                    this.isOpen = true;

                    this.$nextTick(() => {
                        this.claimFormFields();
                        this.watchPanelFields();

                        /**
                         * Opening locks page scroll, and losing the scrollbar moves
                         * every edge this panel docks against, so the geometry taken
                         * a frame ago is a scrollbar out.
                         */
                        requestAnimationFrame(() => this.reposition());
                    });
                },

                /**
                 * Panel content arrives asynchronously (the category tree, association
                 * rows), so newly rendered controls have to be claimed as they appear.
                 */
                watchPanelFields() {
                    if (this._fieldObserver || ! this.formFields || ! this.$refs.panel) {
                        return;
                    }

                    this._onPanelChange = (e) => {
                        if (e.target !== this.$refs.search) {
                            this.touchTracker();
                        }
                    };

                    this.$refs.panel.addEventListener('change', this._onPanelChange, true);

                    this._fieldObserver = new MutationObserver(() => this.claimFormFields());

                    this._fieldObserver.observe(this.$refs.panel, { childList: true, subtree: true });
                },

                clearSearch() {
                    this.search = '';

                    this.$refs.search?.focus();
                },

                close() {
                    this.isOpen = false;
                    this.search = '';

                    this.$productWorkspace?.setView(this.id, 'browse');
                },
            },
        });
    </script>
@endPushOnce
