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

<v-tree-view
    {{ $attributes->except(['input-type', 'selection-type']) }}
    input-type="{{ $inputType }}"
    selection-type="{{ $selectionType }}"
>
    <x-admin::shimmer.tree />
</v-tree-view>

@pushOnce('scripts')
<script type="x-template" id="v-tree-view-template">
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
app.component('v-tree-view', {
    name: 'v-tree-view',
    template: '#v-tree-view-template',
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
        this.formattedItems = typeof this.items === 'string' ? JSON.parse(this.items) : this.items;
        this.formattedExpandedBranch = typeof this.expandedBranch === 'string' ? JSON.parse(this.expandedBranch) : this.expandedBranch;
        this.formattedValues = this.getInitialFormattedValues();
        this.subCategoryValue = this.formattedValues;
    },

    methods: {
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

        hasSelectedValue(key) {
            const valueField = this.valueField;

            for (const branch of this.formattedExpandedBranch) {
                if (branch[valueField] == key) {
                    return branch.children || [];
                }
            }

            return false;
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

        searchInTree(items, value, ancestors = []) {
            for (const item of items) {
                if (item[this.valueField] === value) {
                    return Object.assign(item, { ancestors: [...ancestors].reverse() });
                }

                if (item[this.childrenField]) {
                    const found = this.searchInTree(item[this.childrenField], value, [...ancestors, item]);
                    if (found) return found;
                }
            }
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
