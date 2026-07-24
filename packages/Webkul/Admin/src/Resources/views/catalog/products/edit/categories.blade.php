@props([
    'currentLocaleCode' => core()->getRequestedLocaleCode(),
    'productCategories' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.before', ['product' => $product]) !!}

<x-admin::product.section-card
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    icon="icon-folder"
>
    <span v-text='($productWorkspace?.getCount("categories") ?? 0) + " " + @json(trans("admin::app.catalog.products.edit.workspace.categories.selected"))'></span>
</x-admin::product.section-card>

<x-admin::product.workspace-panel
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.categories.subtitle')"
    icon="icon-folder"
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
                    @{{ $productWorkspace.getCount('categories') }} @lang('admin::app.catalog.products.edit.workspace.categories.selected')
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
                    @change-input="onSelectionChange"
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
