@pushOnce('scripts')
<script type="text/x-template" id="v-tree-item-template">
    <div :class="itemClasses">
        <i
            v-if="hasChildren || hasFetchedChildren"
            :class="toggleIconClasses"
            @click="toggleBranch"
        ></i>

        <i :class="folderIconClasses"></i>
        <span v-text="item.name"></span>

        <component
            :is="inputComponent"
            :id="id"
            :label="label"
            :name="name"
            :value="item[valueField]"
            @change-input="onInputChange"
        />

        <template v-if="showChildren">
            <v-tree-item
                v-for="(child, index) in children"
                :key="index"
                :item="child"
                :level="level + 1"
                @change-input="$emit('change-input', $event)"
            />
        </template>
    </div>
</script>
<script type="module">
app.component('v-tree-item', {
    name: 'v-tree-item',
    template: '#v-tree-item-template',

    props: {
        item: Object,
        level: {
            type: Number,
            default: 1
        }
    },

    inject: [ 'categorytree' ],

    data() {
        return {
            children: this.item[this.childrenField] || [],
            hasFetchedChildren: false,
            showChildren: false
        };
    },

    computed: {
        id() {
            return this.item[this.idField];
        },

        label() {
            return this.item[this.labelField]
                || (this.item.translations?.find(t => t.locale === this.fallbackLocale)?.[this.labelField]
                || `[${this.item.code}]`);
        },

        hasChildren() {
            return (this.item['_rgt'] - this.item['_lft']) > 0;
        },

        hasSelectedValue() {
            return this.categorytree.formattedValues.includes(this.item[this.valueField])
                || this.categorytree.countSelectedChildren(this.item);
        },

        itemClasses() {
            return [
                'v-tree-item inline-block w-full [&>.v-tree-item]:ltr:pl-6 [&>.v-tree-item]:rtl:pr-6 [&>.v-tree-item]:hidden [&.active>.v-tree-item]:block',
                this.level === 1 && !this.hasChildren ? 'ltr:!pl-5 rtl:!pr-5'
                : this.level > 1 && !this.hasChildren ? 'ltr:!pl-14 rtl:!pr-14'
                : '',
                this.hasChildren && this.hasSelectedValue ? 'active' : ''
            ];
        },

        toggleIconClasses() {
            return [
                this.showChildren ? 'icon-chevron-down' : 'icon-chevron-right',
                'text-xl rounded-md cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800'
            ];
        },

        folderIconClasses() {
            return [
                (this.hasChildren || this.hasFetchedChildren) ? 'icon-folder' : 'icon-attribute',
                'text-2xl cursor-pointer'
            ];
        },

        inputComponent() {
            return this.inputType === 'radio'
                ? this.$resolveComponent('v-tree-radio')
                : this.$resolveComponent('v-tree-checkbox');
        }
    },

    methods: {
        async toggleBranch() {
            this.showChildren = !this.showChildren;

            if (this.showChildren && !this.hasFetchedChildren && this.hasChildren) {
                if (this.categorytree.cache[this.id]) {
                    this.children = this.categorytree.cache[this.id];
                    this.hasFetchedChildren = true;
                } else {
                    const url = `${this.fetchChildrenUrl}?id=${this.id}`;
                    try {
                        const response = await fetch(url);
                        const data = await response.json();
                        this.children = data;
                        this.categorytree.cache[this.id] = data;
                        this.hasFetchedChildren = true;
                    } catch (e) {
                        console.error('Failed to fetch children for node', this.id, e);
                    }
                }
            }
        },

        onInputChange() {
            this.handleCheckbox(this.item[this.valueField]);
            this.$emit('change-input', this.formattedValues);
        },

        handleCheckbox(key) {
            const item = this.searchInTree(this.$parent.formattedItems, key);

            switch (this.selectionType) {
                case 'individual':
                    this.handleIndividualSelectionType(item);
                    break;

                case 'hierarchical':
                default:
                    this.handleHierarchicalSelectionType(item);
                    break;
            }
        }
    }
});
</script>
@endPushOnce
