@props([
    'inputType' => 'checkbox',
    'selectionType' => 'hierarchical',
])

<x-admin::tree.item />

@if ($inputType == 'checkbox')
    <!-- Tree Checkbox Component -->
    <x-admin::tree.checkbox />
@else
    <!-- Tree Radio Component -->
    <x-admin::tree.radio />
@endif

<v-category-tree-view
    {{ $attributes->except(['input-type', 'selection-type']) }}
    input-type="{{ $inputType }}"
    selection-type="{{ $selectionType }}"
>
    <x-admin::shimmer.tree />
</v-category-tree-view>

@pushOnce('scripts')
    <script type="x-template" id="v-category-tree-view-template">
        <div class="v-tree-container v-tree-item-wrapper">
            <a
                class="flex items-center gap-1 mb-2 text-sm text-primary-600 cursor-pointer"
                v-if="allowRootCreate"
                :href="createCategoryUrl()"
            >
                <span class="text-base leading-none">+</span>

                @lang('admin::app.catalog.categories.browse.add-root')
            </a>

            <div class="mb-2" v-if="showSearch">
                <x-admin::search
                    name="category_tree_search"
                    :placeholder="trans('admin::app.catalog.categories.browse.search-placeholder')"
                    v-model="searchTerm"
                    @input="onSearchInput"
                    @keydown.esc="clearSearch"
                />
            </div>

            <template v-if="isSearching">
                <p
                    class="py-6 text-center text-sm text-gray-400 dark:text-gray-300"
                    v-if="searchLoading && ! searchResults.length"
                >
                    @lang('admin::app.catalog.categories.browse.searching')
                </p>

                <p
                    class="py-6 text-center text-sm text-gray-400 dark:text-gray-300"
                    v-else-if="! searchResults.length"
                >
                    @lang('admin::app.catalog.categories.browse.no-results')
                </p>

                <div
                    class="flex flex-col gap-0.5 px-2 py-1.5 rounded-md cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                    v-for="result in searchResults"
                    :key="result.id"
                    @click="chooseResult(result)"
                >
                    <span class="text-sm text-gray-800 dark:text-white truncate" v-text="result.label"></span>

                    <span class="text-xs text-gray-400 dark:text-gray-300 truncate" v-text="result.path" v-if="result.path"></span>
                </div>

                <p
                    class="py-2 text-center text-xs text-primary-600 cursor-pointer"
                    v-if="searchPage < searchLastPage"
                    @click="runSearch(searchPage + 1)"
                >
                    @lang('admin::app.catalog.categories.browse.load-more')
                </p>
            </template>

            <template v-else>
            <div
                class="flex items-center justify-end gap-1 pb-1.5 mb-1.5 border-b dark:border-cherry-800"
                v-if="showToolbar"
            >
                <span
                    class="px-1.5 py-0.5 text-xs text-gray-600 dark:text-gray-300 rounded cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                    title="@lang('admin::app.catalog.categories.browse.expand-all-hint')"
                    @click="expandAll"
                >
                    @lang('admin::app.catalog.categories.browse.expand-all')
                </span>

                <span
                    class="px-1.5 py-0.5 text-xs text-gray-600 dark:text-gray-300 rounded cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                    @click="collapseAll"
                >
                    @lang('admin::app.catalog.categories.browse.collapse-all')
                </span>
            </div>

            <v-tree-item
                v-for="item in formattedItems"
                :key="item[valueField]"
                :item="item"
                :level="1"
                @change-input="$emit('change-input', $event)"
                @select-node="$emit('select-node', $event)"
            />
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-category-tree-view', {
            name: 'v-category-tree-view',
            template: '#v-category-tree-view-template',
            inheritAttrs: false,

            props: {
                inputType: {
                    type: String,
                    default: 'checkbox'
                },
                selectionType: {
                    type: String,
                    default: 'hierarchical'
                },
                nameField: {
                    type: String,
                    default: 'permissions'
                },
                valueField: {
                    type: String,
                    default: 'value'
                },
                idField: {
                    type: String,
                    default: 'id'
                },
                labelField: {
                    type: String,
                    default: 'name'
                },
                childrenField: {
                    type: String,
                    default: 'children'
                },
                items: {
                    type: [Array, String, Object],
                    default: () => ([])
                },
                value: {
                    type: [Array, String, Object],
                    default: () => ([])
                },
                fallbackLocale: {
                    type: String,
                    default: 'en_US',
                },
                expandedBranch: {
                    type: [Array, Object],
                    default: () => ([])
                },
                currentCategory: {
                    type: [Number, String],
                    default: null
                },
                childrenPageSize: {
                    type: [Number, String],
                    default: 0
                },
                showToolbar: {
                    type: Boolean,
                    default: false
                },
                allowCreate: {
                    type: Boolean,
                    default: false
                },
                showSearch: {
                    type: Boolean,
                    default: false
                },
                navigateOnSelect: {
                    type: Boolean,
                    default: false
                },
                allowDelete: {
                    type: Boolean,
                    default: false
                },
                allowRootCreate: {
                    type: Boolean,
                    default: false
                }
            },

            data() {
                return {
                    formattedItems: [],
                    formattedValues: [],
                    formattedExpandedBranch: [],
                    fetchChildrenUrl: "{{ route('admin.catalog.categories.children.tree')}}",
                    createUrl: "{{ route('admin.catalog.categories.index') }}",
                    searchUrl: "{{ route('admin.catalog.categories.search') }}",
                    deleteUrl: "{{ route('admin.catalog.categories.delete', 'nodeId') }}",
                    cache: [],
                    nodes: [],
                    searchTerm: '',
                    searchResults: [],
                    searchPage: 1,
                    searchLastPage: 1,
                    searchLoading: false,
                    searchTimer: null,

                    labels: {}
                };
            },

            provide() {
                return {
                    categorytree: this
                };
            },

            created() {
                this.formattedItems = this.parseInput(this.items);
                this.formattedExpandedBranch = this.parseInput(this.expandedBranch);
                this.formattedValues = this.getInitialFormattedValues();
                this.mergeExpandedBranches();
            },


            computed: {
                isSearching() {
                    return this.searchTerm.trim().length > 0;
                },
            },

            methods: {
                navigateTo(categoryId) {
                    const url = new URL(this.createUrl, window.location.origin);

                    url.searchParams.set('category', categoryId);

                    window.location.href = url.toString();
                },

                onSearchInput() {
                    clearTimeout(this.searchTimer);

                    if (! this.isSearching) {
                        this.searchResults = [];

                        return;
                    }

                    this.searchTimer = setTimeout(() => this.runSearch(1), 300);
                },

                clearSearch() {
                    clearTimeout(this.searchTimer);

                    this.searchTerm = '';
                    this.searchResults = [];
                },

                runSearch(page) {
                    const term = this.searchTerm.trim();

                    if (! term) {
                        return;
                    }

                    this.searchLoading = true;

                    const url = new URL(this.searchUrl, window.location.origin);

                    url.searchParams.set('query', term);
                    url.searchParams.set('page', page);

                    this.$axios.get(url.toString())
                        .then(({ data }) => {
                            this.searchResults = page === 1 ? data.data : this.searchResults.concat(data.data);
                            this.searchPage = data.page;
                            this.searchLastPage = data.lastPage;
                        })
                        .finally(() => this.searchLoading = false);
                },

                /**
                 * A hit is flat, so it carries no branch to expand into. Browsing jumps
                 * straight to the category; picking records the value and lets the tree
                 * reveal it on the next render.
                 */
                chooseResult(result) {
                    if (this.navigateOnSelect) {
                        this.navigateTo(result.id);

                        return;
                    }

                    this.formattedValues = [result.id.toString()];
                    this.registerLabel(result.id.toString(), result.label);

                    this.clearSearch();

                    this.$emit('select-node', {
                        value: result.id.toString(),
                        label: result.label,
                        path:  result.path || result.label,
                    });

                    this.$emit('change-input', this.formattedValues);
                },

                registerNode(node) {
                    this.nodes.push(node);
                },

                unregisterNode(node) {
                    this.nodes = this.nodes.filter(registered => registered !== node);
                },

                /**
                 * One level per press rather than the whole tree: opening everything would
                 * fire a request per node, and catalogues here run to tens of thousands of
                 * them. The list grows as children mount, so it is walked from a snapshot.
                 */
                expandAll() {
                    this.nodes.slice().forEach(node => node.expandBranch());
                },

                collapseAll() {
                    this.nodes.forEach(node => node.showChildren = false);
                },

                createCategoryUrl(parentId = null) {
                    const url = new URL(this.createUrl, window.location.origin);

                    url.searchParams.set('panel', 'create');

                    if (parentId) {
                        url.searchParams.set('parent_id', parentId);
                    }

                    return url.toString();
                },

                subCategoryUrl(parentId) {
                    return this.createCategoryUrl(parentId);
                },

                /**
                 * Whether the page is built around this category, and so has nothing left
                 * to reload onto once it is gone.
                 */
                isOnScreen(id) {
                    if (String(id) === String(this.currentCategory)) {
                        return true;
                    }

                    return new URL(window.location.href).searchParams.get('category') === String(id);
                },

                /**
                 * The tree holds no state worth preserving after a delete, and the branch
                 * the node sat in may have been revealed rather than fetched, so the page
                 * is reloaded instead of pruned in place.
                 */
                destroyCategory(item) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            const id = item[this.idField];

                            this.$axios.delete(this.deleteUrl.replace('nodeId', id))
                                .then(({ data }) => {
                                    this.$emitter.emit('add-flash', { type: 'success', message: data.message });

                                    window.location.href = this.isOnScreen(id) ? this.createUrl : window.location.href;
                                })
                                .catch(({ response }) => {
                                    this.$emitter.emit('add-flash', {
                                        type:    'error',
                                        message: response?.data?.message,
                                    });
                                });
                        },
                    });
                },

                parseInput(data) {
                    const parsed = typeof data === 'string' ? JSON.parse(data) : (data || []);

                    return Array.isArray(parsed) ? parsed : (parsed ? Object.values(parsed) : []);
                },

                mergeExpandedBranches() {
                    const valueField = this.valueField;
                    const childrenField = this.childrenField;

                    const injectChildren = (targetList, sourceBranch) => {
                        for (const item of targetList) {
                            if (item[valueField] === sourceBranch[valueField]) {
                                if (sourceBranch[childrenField]) {
                                    item[childrenField] = sourceBranch[childrenField];
                                    item.partial = true;
                                }
                                return true;
                            }

                            if (item[childrenField]) {
                                const found = injectChildren(item[childrenField], sourceBranch);
                                if (found) return true;
                            }
                        }
                        return false;
                    };

                    for (const branch of this.formattedExpandedBranch) {
                        injectChildren(this.formattedItems, branch);
                    }
                },

                getInitialFormattedValues() {
                    if (this.inputType === 'radio') {
                        return Array.isArray(this.value) ? this.value : [this.value];
                    }

                    let val = typeof this.value === 'string' ? JSON.parse(this.value) : this.value;
                    return Array.isArray(val) ? val : [];
                },

                registerLabel(value, label) {
                    this.labels[value] = label;
                },

                has(key) {
                    return this.formattedValues.includes(key);
                },

                select(key) {
                    if (!this.has(key)) this.formattedValues.push(key);
                },

                unSelect(key) {
                    this.formattedValues = this.formattedValues.filter(v => v !== key);
                },

                toggle(key) {
                    this.has(key) ? this.unSelect(key) : this.select(key);
                },

                countSelectedChildren(item) {
                    let count = 0;
                    const children = item[this.childrenField] || [];
                    for (const child of children) {
                        if (this.has(child[this.valueField])) count++;
                        count += this.countSelectedChildren(child);
                    }
                    return count;
                },

                selectAllChildren(item) {
                    const children = item[this.childrenField] || [];
                    for (const child of children) {
                        this.select(child[this.valueField]);
                        this.selectAllChildren(child);
                    }
                },

                unSelectAllChildren(item) {
                    const children = item[this.childrenField] || [];
                    for (const child of children) {
                        this.unSelect(child[this.valueField]);
                        this.unSelectAllChildren(child);
                    }
                },

                handleAncestors(item) {
                    if (item.ancestors?.length) {
                        item.ancestors.forEach(ancestor => this.select(ancestor[this.valueField]));
                    }
                },

                handleCurrent(item) {

                    this.toggle(item[this.valueField]);
                },

                handleChildren(item) {
                    const selected = this.countSelectedChildren(item);
                    selected ? this.unSelectAllChildren(item) : this.selectAllChildren(item);
                },

                handleCheckbox(item) {
                    switch (this.selectionType) {
                        case 'individual':
                            this.handleIndividualSelectionType(item);
                            break;

                        case 'hierarchical':
                        default:
                            this.handleHierarchicalSelectionType(item);
                            break;
                    }
                },

                handleIndividualSelectionType(item) {
                    this.handleCurrent(item);
                },

                handleHierarchicalSelectionType(item) {
                    this.handleAncestors(item);
                    this.handleCurrent(item);
                    this.handleChildren(item);

                    if (!this.has(item[this.valueField])) {
                        this.unSelectAllChildren(item);
                    }
                }
            }
        });
    </script>
@endPushOnce
