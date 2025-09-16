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
            <v-tree-item
                v-for="(item, index) in formattedItems"
                :key="index"
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
                    type: Object
                }
            },

            data() {
                return {
                    formattedItems: [],
                    formattedValues: [],
                    formattedExpandedBranch: [],
                    fetchChildrenUrl: "{{ route('admin.catalog.categories.children.tree')}}",
                    cache: []
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
                parseInput(data) {
                    return typeof data === 'string' ? JSON.parse(data) : (data || []);
                },

                mergeExpandedBranches() {
                    const valueField = this.valueField;
                    const childrenField = this.childrenField;

                    const injectChildren = (targetList, sourceBranch) => {
                        for (const item of targetList) {
                            if (item[valueField] === sourceBranch[valueField]) {
                                if (sourceBranch[childrenField]) {
                                    item[childrenField] = sourceBranch[childrenField];
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
