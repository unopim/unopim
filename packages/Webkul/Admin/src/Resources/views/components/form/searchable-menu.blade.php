@props([
    'items' => null,
])

<v-searchable-menu
    @if (! is_null($items))
        :items='@json($items)'
    @endif
    {{ $attributes }}
>
    @isset($toggle)
        <template v-slot:toggle>
            {{ $toggle }}
        </template>
    @endisset
</v-searchable-menu>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-searchable-menu-template"
    >
        <div class="inline-flex">
            <div
                ref="toggle"
                @click.stop="toggle"
            >
                <slot name="toggle"></slot>
            </div>

            {{--
                Teleported and fixed-positioned: these menus open from inside cards,
                drawers and modals that scroll or clip, where an absolutely
                positioned panel is cut off by the first such ancestor.
            --}}
            <teleport to="body">
                <div
                    class="fixed w-72 rounded-md border bg-white box-shadow dark:border-cherry-800 dark:bg-cherry-900"
                    v-if="isOpen"
                    :style="panelStyle"
                    @click.stop
                >
                    <div
                        class="p-2 border-b dark:border-cherry-800"
                        v-if="searchable"
                    >
                        <x-admin::search.field
                            icon-position="left"
                            ::placeholder="searchPlaceholder"
                            v-model.trim="search"
                            clear-when="search"
                            clear-action="search = ''"
                        />
                    </div>

                    <div class="max-h-72 overflow-auto">
                        <button
                            type="button"
                            class="flex gap-2.5 justify-between items-center w-full px-4 py-2 ltr:text-left rtl:text-right text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                            v-for="item in filteredItems"
                            :key="'searchable-menu-' + item.id"
                            :class="{'bg-gray-100 dark:bg-cherry-800': item.id === modelValue}"
                            @click="select(item.id)"
                        >
                            <span
                                class="min-w-0"
                                v-text="item.label"
                            ></span>

                            <span
                                class="shrink-0 px-1.5 rounded-full bg-gray-100 dark:bg-cherry-800 text-xs"
                                v-if="item.badge"
                                v-text="item.badge"
                            ></span>

                            <span
                                class="shrink-0 icon-done text-lg"
                                v-else-if="item.checked"
                            ></span>
                        </button>

                        <p
                            class="px-4 py-3 text-sm text-gray-400"
                            v-if="! filteredItems.length"
                            v-text="emptyText"
                        ></p>
                    </div>
                </div>
            </teleport>
        </div>
    </script>

    <script type="module">
        app.component('v-searchable-menu', {
            template: '#v-searchable-menu-template',

            props: {
                items: {
                    type: Array,
                    default: () => [],
                },

                modelValue: {
                    type: String,
                    default: '',
                },

                searchable: {
                    type: Boolean,
                    default: true,
                },

                searchPlaceholder: {
                    type: String,
                    default: @json(trans('admin::app.components.form.searchable-menu.search')),
                },

                emptyText: {
                    type: String,
                    default: @json(trans('admin::app.components.form.searchable-menu.empty')),
                },
            },

            emits: ['update:modelValue'],

            data() {
                return {
                    isOpen: false,
                    search: '',
                    panelStyle: {},
                };
            },

            computed: {
                filteredItems() {
                    const term = this.search.trim().toLowerCase();

                    if (! term) {
                        return this.items;
                    }

                    return this.items.filter(item => String(item.label).toLowerCase().includes(term));
                },
            },

            mounted() {
                this.onDocumentClick = () => this.close();
                this.onViewportChange = () => this.isOpen && this.position();
                this.onKeydown = (event) => event.key === 'Escape' && this.close();

                window.addEventListener('click', this.onDocumentClick);
                window.addEventListener('resize', this.onViewportChange);
                window.addEventListener('scroll', this.onViewportChange, true);
                window.addEventListener('keydown', this.onKeydown);
            },

            beforeUnmount() {
                window.removeEventListener('click', this.onDocumentClick);
                window.removeEventListener('resize', this.onViewportChange);
                window.removeEventListener('scroll', this.onViewportChange, true);
                window.removeEventListener('keydown', this.onKeydown);
            },

            methods: {
                toggle() {
                    this.isOpen = ! this.isOpen;

                    if (this.isOpen) {
                        this.$nextTick(this.position);
                    }
                },

                close() {
                    this.isOpen = false;
                    this.search = '';
                },

                position() {
                    const rect = this.$refs.toggle.getBoundingClientRect();
                    const width = 288;
                    const margin = 8;

                    this.panelStyle = {
                        top: (rect.bottom + 4) + 'px',
                        left: Math.max(margin, Math.min(rect.right - width, window.innerWidth - width - margin)) + 'px',
                        zIndex: 10003,
                    };
                },

                select(id) {
                    this.$emit('update:modelValue', id);

                    this.close();
                },
            },
        });
    </script>
@endPushOnce
