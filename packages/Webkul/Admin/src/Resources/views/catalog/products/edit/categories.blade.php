@props([
    'currentLocaleCode' => core()->getRequestedLocaleCode(),
    'productCategories' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.before', ['product' => $product]) !!}

<!-- Panel -->
<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <!-- Panel Header -->
    <p class="flex justify-between text-base text-gray-800 dark:text-white font-semibold mb-4">
        @lang('admin::app.catalog.products.edit.categories.title')
    </p>

    {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.before', ['product' => $product]) !!}

    <!-- Panel Content -->
    <div class="mb-5 text-sm text-gray-600 dark:text-gray-300 max-h-[400px] overflow-y-auto">

        <v-product-categories>
            <x-admin::shimmer.tree />
        </v-product-categories>

    </div>

    {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.after', ['product' => $product]) !!}
</div>

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-categories-template"
    >
        <div>
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
                }
            },

            mounted() {
                this.get();
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
                    .catch(error => {
                        console.log(error);
                    });
                }

            }
        });
    </script>
@endpushOnce
