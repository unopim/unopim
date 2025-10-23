{!! view_render_event('unopim.admin.catalog.product.edit.more-actions.before', ['product' => $product]) !!}

<v-custom-dropdown></v-custom-dropdown>

{!! view_render_event('unopim.admin.catalog.product.edit.more-actions.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-custom-dropdown-template">
        <div class="relative inline-block text-left">
            <span
                class="text-gray-700 dark:text-slate-50 cursor-pointer flex p-2 w-full items-center"
                @click="toggleDropdown"
                title="@lang('admin::app.catalog.products.edit.more-actions')"
            >
                More 
                <i class="text-2xl icon-chevron-down"></i>
            </span>

            <div
                v-if="isOpen"
                class="absolute right-0.5 top-0.8 w-36 max-sm:left-1/2 bg-white dark:bg-cherry-900 shadow-lg z-[10001] text-gray-700 border-2 border-violet-100 dark:border-cherry-800 min-h[110px] rounded-md"
            >
                <ul class="text-gray-700 rounded">
                    {!! view_render_event('unopim.admin.catalog.product.edit.more-actions.list.before', ['product' => $product]) !!}

                    @if (core()->getConfigData('general.magic_ai.translation.enabled'))
                        @include('admin::catalog.products.edit.more-actions.translate-action')
                    @endif

                    {!! view_render_event('unopim.admin.catalog.product.edit.more-actions.list.after', ['product' => $product]) !!}
                </ul>
            </div>
        </div>
    </script>
    <script type="module">
        app.component('v-custom-dropdown', {
            template: '#v-custom-dropdown-template',
            data() {
                return {
                    isOpen: false,
                };
            },
            methods: {
                toggleDropdown() {
                    this.isOpen = !this.isOpen;
                },

                closeDropdown(event) {
                    if (!this.$el.contains(event.target)) {
                        this.isOpen = false;
                    }
                },

                hideMenu() {
                    this.isOpen = false;
                }
            },
            mounted() {
                document.addEventListener('click', this.closeDropdown);
            },
            beforeUnmount() {
                document.removeEventListener('click', this.closeDropdown);
            },
        });
    </script>
@endPushOnce
