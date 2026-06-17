@props([
    'name'     => 'filters[categories]',
    'value'    => [],
    'pageSize' => 100,
])

@php
    $selectedCategories = $value;

    if (is_string($selectedCategories)) {
        $decoded = json_decode($selectedCategories, true);
        $selectedCategories = is_array($decoded)
            ? $decoded
            : array_filter(array_map('trim', explode(',', $selectedCategories)));
    }

    $selectedCategories = array_values(array_filter((array) $selectedCategories, fn ($code) => $code !== '' && $code !== null));
@endphp

<v-data-transfer-category-tree
    name="{{ $name }}"
    :selected='@json($selectedCategories)'
>
    <x-admin::shimmer.tree />
</v-data-transfer-category-tree>

@pushOnce('scripts')
    <script type="text/x-template" id="v-data-transfer-category-tree-template">
        <div class="max-h-[360px] overflow-y-auto border rounded-md dark:border-gray-700 p-2.5">
            <template v-if="isLoading">
                <x-admin::shimmer.tree />
            </template>

            <template v-else-if="! categories.length">
                <p class="p-2 text-sm text-gray-500 dark:text-gray-300">
                    @lang('admin::app.settings.data-transfer.exports.create.no-categories')
                </p>
            </template>

            <template v-else>
                <x-admin::tree.category.view
                    input-type="checkbox"
                    selection-type="individual"
                    name-field="{{ $name }}"
                    id-field="code"
                    value-field="code"
                    children-page-size="{{ $pageSize }}"
                    ::items="categories"
                    ::value="selectedJson"
                    ::expanded-branch="selectedCategoryTree"
                    :fallback-locale="config('app.fallback_locale')"
                >
                </x-admin::tree.category.view>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-data-transfer-category-tree', {
            template: '#v-data-transfer-category-tree-template',

            props: ['name', 'selected'],

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    selectedCategoryTree: [],
                    selectedJson: JSON.stringify(this.selected ?? []),
                };
            },

            mounted() {
                this.get();
            },

            methods: {
                get() {
                    this.$axios.post("{{ route('admin.catalog.categories.tree') }}", {
                        locale: "{{ core()->getRequestedLocaleCode() }}",
                        selected: this.selected ?? [],
                    })
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data ?? [];
                            this.selectedCategoryTree = response.data.selected_tree ?? [];
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },
            },
        });
    </script>
@endPushOnce
