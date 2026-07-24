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
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900">
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

                            <button
                                type="button"
                                @click="close"
                                :aria-label="closeLabel"
                                class="grid place-items-center w-9 h-9 rounded border border-gray-200 dark:border-cherry-800 text-gray-500 hover:text-gray-800 dark:hover:text-white shrink-0"
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
                overlayStyle: {},
                panelStyle: {},
                closeLabel: "@lang('admin::app.catalog.products.edit.workspace.close')",
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
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this._esc);
                window.removeEventListener('resize', this._reflow);
            },

            methods: {
                main() {
                    return document.getElementById('main-content');
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
                reposition() {
                    const main = this.main();

                    if (! main) {
                        return;
                    }

                    const rect = main.getBoundingClientRect();
                    const rtl = document.dir === 'rtl';
                    const width = Math.round(rect.width * this.ratio());

                    const top = Math.round(rect.top) + 'px';
                    const bottom = Math.round(window.innerHeight - rect.bottom) + 'px';

                    // z-index set inline (not via Tailwind's z-[..] classes, which
                    // aren't compiled from this x-template's markup) so the panel
                    // sits above page content -- incl. the rich-text toolbars -- yet
                    // below the app's own drawers/modals (z-index 10001).
                    this.overlayStyle = {
                        top,
                        bottom,
                        left: Math.round(rect.left) + 'px',
                        width: Math.round(rect.width) + 'px',
                        zIndex: 9998,
                    };

                    this.panelStyle = {
                        top,
                        bottom,
                        width: width + 'px',
                        zIndex: 9999,
                        ...(rtl
                            ? { left: Math.round(rect.left) + 'px' }
                            : { right: Math.round(window.innerWidth - rect.right) + 'px' }),
                    };
                },

                open() {
                    if (! this.main()) {
                        return;
                    }

                    this.reposition();
                    this.isOpen = true;
                },

                close() {
                    this.isOpen = false;
                },
            },
        });
    </script>
@endPushOnce
