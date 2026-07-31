@props([
    'currentLocaleCode' => core()->getRequestedLocaleCode(),
    'productCategories' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.before', ['product' => $product]) !!}

<x-admin::product.section-drawer
    id="categories"
    :title="trans('admin::app.catalog.products.edit.categories.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.categories.subtitle')"
    icon="icon-folder"
    form-id="product-edit-form"
    form-fields='input[name="categories[]"]'
    :searchable="true"
    :search-placeholder="trans('admin::app.catalog.products.edit.workspace.categories.search')"
>
    <x-slot:toggle>
        <x-admin::product.section-card
            id="categories"
            :title="trans('admin::app.catalog.products.edit.categories.title')"
            icon="icon-folder"
        >
            <span
                :title='$productWorkspace?.getSummary("categories")'
                v-text='$productWorkspace?.getSummary("categories") || (($productWorkspace?.getCount("categories") ?? 0) + " " + @json(trans("admin::app.catalog.products.edit.workspace.categories.selected")))'
            ></span>
        </x-admin::product.section-card>
    </x-slot:toggle>

    <x-slot:headerActions>
        <button
            type="button"
            @click="$productWorkspace.toggleView('categories', 'selected')"
            :title="'@lang('admin::app.catalog.products.edit.workspace.categories.review-selected')'"
            :aria-pressed="$productWorkspace.getView('categories') === 'selected'"
            :class="[
                'shrink-0 text-xs font-medium px-3 py-2 rounded cursor-pointer transition-all',
                $productWorkspace.getView('categories') === 'selected'
                    ? 'bg-primary-100 text-primary-700 dark:bg-cherry-700 dark:text-white'
                    : 'bg-gray-100 text-gray-600 dark:bg-cherry-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-cherry-700'
            ]"
        >
            @{{ $productWorkspace.getCount('categories') }} @lang('admin::app.catalog.products.edit.workspace.categories.selected')
        </button>
    </x-slot:headerActions>

    <x-slot:content>
        {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.before', ['product' => $product]) !!}

        <v-product-categories :search="search">
            <x-admin::shimmer.tree />
        </v-product-categories>

        {!! view_render_event('unopim.admin.catalog.product.edit.form.categories.controls.after', ['product' => $product]) !!}
    </x-slot:content>

    <x-slot:footer>
        <button
            type="button"
            class="primary-button"
            @click="close"
        >
            @lang('admin::app.catalog.products.edit.workspace.add-selected')
        </button>
    </x-slot:footer>
</x-admin::product.section-drawer>

{!! view_render_event('unopim.admin.catalog.product.edit.form.categories.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-categories-template"
    >
        <div>
            <input
                v-for="code in selectedCodes"
                :key="'selected-' + code"
                type="hidden"
                name="categories[]"
                form="product-edit-form"
                :value="code"
            />

            <template v-if="isLoading">
                <x-admin::shimmer.tree />
            </template>

            <template v-else-if="isListMode">
                <p
                    v-if="isFetchingList && ! list.rows.length"
                    class="p-2 text-sm text-gray-500 dark:text-gray-300"
                >
                    @lang('admin::app.catalog.products.edit.workspace.categories.searching')
                </p>

                <p
                    v-else-if="! list.rows.length"
                    class="p-2 text-sm text-gray-500 dark:text-gray-300"
                    v-text="emptyListMessage"
                ></p>

                <template v-else>
                    <label
                        v-for="row in list.rows"
                        :key="'row-' + row.code"
                        class="flex gap-2.5 w-full p-1.5 items-center cursor-pointer select-none group"
                    >
                        <input
                            type="checkbox"
                            class="hidden peer"
                            :checked="isSelected(row.code)"
                            @change="toggle(row.code)"
                        />

                        <span class="icon-checkbox-normal shrink-0 rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-primary-700"></span>

                        <span class="min-w-0">
                            <span
                                class="block text-sm text-gray-600 dark:text-gray-300 group-hover:text-gray-800 dark:group-hover:text-white truncate"
                                v-text="row.label"
                            ></span>

                            <span
                                v-if="row.path && row.path !== row.label"
                                class="block text-xs text-gray-400 truncate"
                                v-text="row.path"
                            ></span>
                        </span>
                    </label>

                    <button
                        v-if="list.page < list.lastPage"
                        type="button"
                        class="secondary-button mt-2"
                        :disabled="isFetchingList"
                        @click="fetchList(list.page + 1)"
                    >
                        @lang('admin::app.catalog.products.edit.workspace.categories.load-more')
                    </button>
                </template>
            </template>

            <template v-else>
                <x-admin::tree.category.view
                    ref="tree"
                    input-type="checkbox"
                    selection-type="individual"
                    name-field="__categories_tree"
                    id-field="code"
                    value-field="code"
                    children-page-size="100"
                    ::items="categories"
                    ::value="selectedJson"
                    ::baseline-value="baselineJson"
                    ::expanded-branch="selectedCategoryTree"
                    :fallback-locale="config('app.fallback_locale')"
                    @change-input="onTreeSelection"
                >
                </x-admin::tree.category.view>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-product-categories', {
            template: '#v-product-categories-template',

            props: {
                search: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    selectedCategoryTree: [],
                    list: { rows: [], page: 0, lastPage: 0, term: '' },
                    isFetchingList: false,
                    searchTimer: null,
                    emptyResultsMessage: "@lang('admin::app.catalog.products.edit.workspace.categories.no-results')",
                    emptySelectionMessage: "@lang('admin::app.catalog.products.edit.workspace.categories.none-selected')",
                    andMoreMessage: "@lang('admin::app.catalog.products.edit.workspace.categories.and-more', ['count' => ':count'])",
                    selectedMessage: "@lang('admin::app.catalog.products.edit.workspace.categories.selected')",
                    labelByCode: {},
                    initialSelected: @json(array_values((array) $productCategories)),
                    selectedCodes: @json(array_values((array) $productCategories)),
                }
            },

            computed: {
                selectedJson() {
                    return JSON.stringify(this.selectedCodes);
                },

                baselineJson() {
                    return JSON.stringify(this.initialSelected);
                },

                isSearchMode() {
                    return this.search.trim().length > 0;
                },

                isSelectedMode() {
                    return this.$productWorkspace.getView('categories') === 'selected';
                },

                isListMode() {
                    return this.isSelectedMode || this.isSearchMode;
                },

                emptyListMessage() {
                    return this.isSelectedMode ? this.emptySelectionMessage : this.emptyResultsMessage;
                },
            },

            watch: {
                search() {
                    if (this.isSearchMode) {
                        this.$productWorkspace.setView('categories', 'browse');
                    }

                    this.onSearchInput();
                },

                isSelectedMode(active) {
                    if (active) {
                        this.fetchList(1);
                    }
                },

                selectedCodes: {
                    deep: true,
                    handler(codes) {
                        this.$productWorkspace.setCount('categories', codes.length);

                        this.$productWorkspace.setDirty('categories', codes.length !== this.initialSelected.length
                            || codes.some(code => ! this.initialSelected.includes(code)));

                        this.publishSummary();
                    },
                },
            },

            mounted() {
                this.get();
                this.$productWorkspace.setCount('categories', this.selectedCodes.length);

                this.$emitter.on('unsaved-changes:reset', this.restoreSelection);
                this.$emitter.on('form-saved', this.commitSelection);
            },

            beforeUnmount() {
                clearTimeout(this.searchTimer);

                this.$emitter.off('unsaved-changes:reset', this.restoreSelection);
                this.$emitter.off('form-saved', this.commitSelection);
            },

            methods: {
                get() {
                    this.$axios.post("{{ route('admin.catalog.categories.tree') }}", {
                        locale: "{{ $currentLocaleCode }}",
                        selected: @json($productCategories),
                    })
                    .then(response => {
                        this.isLoading = false;
                        this.categories = response.data.data ?? [];
                        this.selectedCategoryTree = response.data.selected_tree ?? [];

                        this.learnLabelsFromNodes(this.selectedCategoryTree);
                        this.publishSummary();
                    })
                    .catch(() => {
                        this.isLoading = false;
                    });
                },

                restoreSelection() {
                    this.selectedCodes = [...this.initialSelected];
                },

                commitSelection() {
                    this.initialSelected = [...this.selectedCodes];
                },

                onSearchInput() {
                    clearTimeout(this.searchTimer);

                    if (! this.isSearchMode) {
                        this.resetList();

                        return;
                    }

                    this.isFetchingList = true;
                    this.searchTimer = setTimeout(() => this.fetchList(1), 300);
                },

                resetList() {
                    this.list = { rows: [], page: 0, lastPage: 0, term: '' };
                    this.isFetchingList = false;
                },

                fetchList(page) {
                    const selectedMode = this.isSelectedMode;
                    const term = selectedMode ? '' : this.search.trim();

                    if (selectedMode && ! this.selectedCodes.length) {
                        this.resetList();

                        return;
                    }

                    this.isFetchingList = true;

                    this.$axios.get("{{ route('admin.catalog.categories.search') }}", {
                        params: {
                            query: term,
                            codes: selectedMode ? this.selectedCodes : undefined,
                            page,
                            locale: "{{ $currentLocaleCode }}",
                        },
                    })
                    .then(response => {
                        if (selectedMode !== this.isSelectedMode || term !== (selectedMode ? '' : this.search.trim())) {
                            return;
                        }

                        const hits = response.data.data ?? [];

                        hits.forEach(hit => this.labelByCode[hit.code] = hit.label);

                        this.publishSummary();

                        this.list = {
                            rows:     page > 1 ? this.list.rows.concat(hits) : hits,
                            page:     response.data.page ?? page,
                            lastPage: response.data.lastPage ?? page,
                            term,
                        };
                    })
                    .catch(() => {
                        this.list = { rows: [], page: 0, lastPage: 0, term };
                    })
                    .finally(() => {
                        this.isFetchingList = false;
                    });
                },

                onTreeSelection(codes) {
                    this.learnLabels(this.$refs.tree?.labels);

                    this.selectedCodes = Array.isArray(codes) ? [...codes] : [];
                },

                learnLabels(source) {
                    if (! source) {
                        return;
                    }

                    Object.entries(source).forEach(([code, label]) => {
                        if (label) {
                            this.labelByCode[code] = label;
                        }
                    });
                },

                learnLabelsFromNodes(nodes) {
                    (nodes ?? []).forEach(node => {
                        this.labelByCode[node.code] = node.name;

                        this.learnLabelsFromNodes(node.children);
                    });
                },

                publishSummary() {
                    const names = this.selectedCodes.map(code => this.labelByCode[code]).filter(Boolean);

                    if (! names.length) {
                        this.$productWorkspace.setSummary('categories', this.selectedCodes.length
                            ? `${this.selectedCodes.length} ${this.selectedMessage}`
                            : '');

                        return;
                    }

                    const shown = names.slice(0, 2);
                    const rest = this.selectedCodes.length - shown.length;

                    this.$productWorkspace.setSummary('categories', rest > 0
                        ? `${shown.join(', ')} ${this.andMoreMessage.replace(':count', rest)}`
                        : shown.join(', '));
                },

                isSelected(code) {
                    return this.selectedCodes.includes(code);
                },

                toggle(code) {
                    const index = this.selectedCodes.indexOf(code);

                    if (index === -1) {
                        this.selectedCodes.push(code);
                    } else {
                        this.selectedCodes.splice(index, 1);
                    }
                },
            }
        });
    </script>
@endPushOnce
