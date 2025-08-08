@pushOnce('scripts')
<script type="text/x-template" id="v-tree-item-template">
    <div :class="itemClasses">
        <i
            v-if="hasChildren || hasFetchedChildren"
            :class="toggleIconClasses"
            @click="toggleBranch"
        ></i>

        <i :class="folderIconClasses"></i>

        <component
            :is="inputComponent"
            :id="id"
            :label="label"
            :name="name"
            :value="value"
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

    provide() {
        return {
            treeItem: this
        };
    },

    data() {
        return {
            children: this.item[this.categorytree.childrenField] || [],
            hasFetchedChildren: false,
            showChildren: false
        };
    },

    mounted() {
        if (this.children.length > 0) {
            this.showChildren = true;
        }
    },

    computed: {
        id() {
            return this.item['id'];
        },

        label() {
            return this.item[this.categorytree.labelField]
                || (this.item.translations?.find(t => t.locale === this.fallbackLocale)?.[this.categorytree.labelField]
                || `[${this.item.code}]`);
        },

        hasChildren() {
            return (this.item['_rgt'] - this.item['_lft']) > 1;
        },

        hasSelectedValue() {
            if (this.categorytree.has(this.value)) return true;
        },

        itemClasses() {
            return [
                'v-tree-item inline-block w-full [&>.v-tree-item]:ltr:pl-6 [&>.v-tree-item]:rtl:pr-6 [&>.v-tree-item]:hidden [&.active>.v-tree-item]:block',
                this.level === 1 && !this.hasChildren ? 'ltr:!pl-5 rtl:!pr-5'
                : this.level > 1 && !this.hasChildren ? 'ltr:!pl-14 rtl:!pr-14'
                : '',
                this.hasSelectedValue ? 'active' : '',
                this.showChildren ? 'active' : ''
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
            return this.categorytree.inputType === 'radio'
                ? this.$resolveComponent('v-tree-radio')
                : this.$resolveComponent('v-tree-checkbox');
        },

        value() {
           return this.item[this.categorytree.valueField].toString();
        }
    },

    methods: {
        toggleBranch() {
            const categoryId = this.id;
            const url = new URL(this.categorytree.fetchChildrenUrl, window.location.origin);

            if (categoryId) {
                url.searchParams.append('id', categoryId);
                url.searchParams.append('category', this.categorytree.currentCategory);
            }

            this.showChildren = !this.showChildren;

            if (this.showChildren && !this.hasFetchedChildren && this.hasChildren) {
                if (this.categorytree.cache && this.categorytree.cache[this.id]) {
                    this.children = this.categorytree.cache[this.id];
                    this.hasFetchedChildren = true;
                } else {
                    this.$axios
                        .get(url.toString())
                        .then((response) => {
                            const data = response.data;
                            this.children = data;
                            this.categorytree.cache[this.id] = data;
                            this.hasFetchedChildren = true;
                        })
                        .catch((err) => {
                            console.error('Failed to fetch children for node', this.id, err);
                        });
                }
            }
        },

        onInputChange() {
            this.categorytree.handleCheckbox(this.item);
            this.$emit('change-input', this.categorytree.formattedValues);
        },
    }
});
</script>
@endPushOnce
