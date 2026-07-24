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
                to="#main-content"
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
                        :data-section-id="id"
                        class="absolute inset-y-0 ltr:right-0 rtl:left-0 z-[31] flex flex-col w-full md:w-[90%] lg:w-[80%] xl:w-[70%] max-sm:!w-full bg-unopim-primary-page dark:bg-cherry-800 shadow-2xl"
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

                window.addEventListener('keydown', this._esc);
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this._esc);
                this.unlock();
            },

            methods: {
                main() {
                    return document.getElementById('main-content');
                },

                /**
                 * Anchors the teleported overlay/panel to main-content and locks its
                 * scroll via inline style (overriding the element's overflow-y-auto class)
                 * so the absolute inset-0 panel matches the visible box instead of the
                 * full scroll height.
                 */
                open() {
                    const main = this.main();

                    if (! main) {
                        return;
                    }

                    main.classList.add('relative');
                    main.style.overflow = 'hidden';

                    this.isOpen = true;
                },

                close() {
                    this.isOpen = false;
                    this.unlock();
                },

                unlock() {
                    const main = this.main();

                    if (main) {
                        main.style.overflow = '';
                    }
                },
            },
        });
    </script>
@endPushOnce
