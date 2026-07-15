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
    search-route="{{ route('admin.catalog.categories.search') }}"
    page-size="{{ $pageSize }}"
>
    <x-admin::shimmer.tree />
</v-data-transfer-category-tree>

@pushOnce('scripts')
    <script type="text/x-template" id="v-data-transfer-category-tree-template">
        <div class="flex flex-col gap-2">
            <input
                v-for="code in selectedCodes"
                :key="'selected-' + code"
                type="hidden"
                :name="name + '[]'"
                :value="code"
            />

            <div class="relative">
                <input
                    type="text"
                    v-model="searchTerm"
                    @input="onSearchInput"
                    placeholder="{{ trans('admin::app.settings.data-transfer.exports.create.search-categories') }}"
                    class="w-full rounded-md border dark:border-cherry-900 bg-white dark:bg-cherry-900 ltr:pl-3 rtl:pr-3 ltr:pr-8 rtl:pl-8 py-1.5 text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400"
                />

                <span class="icon-search text-xl absolute ltr:right-2 rtl:left-2 top-1.5 text-gray-400 pointer-events-none"></span>
            </div>

            <div class="overflow-y-auto border rounded-md dark:border-gray-700 p-2.5" style="max-height: 360px;">
                <template v-if="isLoading">
                    <x-admin::shimmer.tree />
                </template>

                <template v-else-if="isSearchMode">
                    <template v-if="isSearching">
                        <p class="p-2 text-sm text-gray-500 dark:text-gray-300">
                            @lang('admin::app.settings.data-transfer.exports.create.no-categories')
                        </p>
                    </template>

                    <template v-else-if="! searchResults.length">
                        <p class="p-2 text-sm text-gray-500 dark:text-gray-300">
                            @lang('admin::app.settings.data-transfer.exports.create.no-categories')
                        </p>
                    </template>

                    <template v-else>
                        <label
                            v-for="result in searchResults"
                            :key="'result-' + result.code"
                            class="inline-flex gap-2.5 w-full p-1.5 items-center cursor-pointer select-none group"
                        >
                            <input
                                type="checkbox"
                                class="hidden peer"
                                :checked="isSelected(result.code)"
                                @change="toggleCategory(result.code)"
                            />

                            <span class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"></span>

                            <span
                                class="text-sm text-gray-600 dark:text-gray-300 group-hover:text-gray-800 dark:group-hover:text-white"
                                v-text="result.label"
                            ></span>
                        </label>
                    </template>
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
                        name-field="category_browse_ui"
                        id-field="code"
                        value-field="code"
                        children-page-size="{{ $pageSize }}"
                        ::items="categories"
                        ::value="selectedJson"
                        ::expanded-branch="selectedCategoryTree"
                        :fallback-locale="config('app.fallback_locale')"
                        @change-input="onTreeSelection"
                    >
                    </x-admin::tree.category.view>
                </template>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-data-transfer-category-tree', {
            template: '#v-data-transfer-category-tree-template',

            props: ['name', 'selected', 'searchRoute', 'pageSize'],

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    selectedCategoryTree: [],
                    selectedCodes: Array.isArray(this.selected) ? [...this.selected] : [],
                    searchTerm: '',
                    searchResults: [],
                    isSearching: false,
                    searchTimer: null,
                };
            },

            computed: {
                selectedJson() {
                    return JSON.stringify(this.selectedCodes);
                },

                isSearchMode() {
                    return this.searchTerm.trim().length > 0;
                },
            },

            mounted() {
                this.getRoots();
            },

            beforeUnmount() {
                clearTimeout(this.searchTimer);
            },

            methods: {
                getRoots() {
                    this.$axios.post("{{ route('admin.catalog.categories.tree') }}", {
                        locale: "{{ core()->getRequestedLocaleCode() }}",
                        selected: this.selectedCodes,
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

                onSearchInput() {
                    clearTimeout(this.searchTimer);

                    if (! this.isSearchMode) {
                        this.searchResults = [];
                        this.isSearching = false;

                        return;
                    }

                    this.isSearching = true;
                    this.searchTimer = setTimeout(() => this.runSearch(this.searchTerm.trim()), 300);
                },

                runSearch(term) {
                    this.$axios.get(this.searchRoute, {
                        params: {
                            query: term,
                            locale: "{{ core()->getRequestedLocaleCode() }}",
                        },
                    })
                        .then(response => {
                            this.searchResults = response.data.data ?? [];
                            this.isSearching = false;
                        })
                        .catch(() => {
                            this.searchResults = [];
                            this.isSearching = false;
                        });
                },

                onTreeSelection(codes) {
                    this.selectedCodes = Array.isArray(codes) ? [...codes] : [];
                },

                toggleCategory(code) {
                    const index = this.selectedCodes.indexOf(code);

                    if (index === -1) {
                        this.selectedCodes.push(code);
                    } else {
                        this.selectedCodes.splice(index, 1);
                    }
                },

                isSelected(code) {
                    return this.selectedCodes.includes(code);
                },
            },
        });
    </script>
@endPushOnce
