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
            />
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
                }
            },

            data() {
                return {
                    formattedItems: [],
                    formattedValues: [],
                    formattedExpandedBranch: [],
                    fetchChildrenUrl: "{{ route('admin.catalog.categories.children.tree')}}",
                    createUrl: "{{ route('admin.catalog.categories.index') }}",
                    cache: [],
                    nodes: [],

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


            methods: {
                registerNode(node) {
                    this.nodes.push(node);
                },

                unregisterNode(node) {
                    this.nodes = this.nodes.filter(registered => registered !== node);
                },

                /**
                 * Bounded on purpose: only branches already fetched are opened. Walking the
                 * whole tree would fire a request per node, and catalogues here run to tens
                 * of thousands of them.
                 */
                expandAll() {
                    this.nodes.forEach(node => {
                        if (node.hasFetchedChildren || node.children.length) {
                            node.showChildren = true;
                        }
                    });
                },

                collapseAll() {
                    this.nodes.forEach(node => node.showChildren = false);
                },

                subCategoryUrl(parentId) {
                    const url = new URL(this.createUrl, window.location.origin);

                    url.searchParams.set('panel', 'create');
                    url.searchParams.set('parent_id', parentId);

                    return url.toString();
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
