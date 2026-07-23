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
            @change="onInputChange(item.value)"
        />

        <template v-if="showChildren">
            <v-tree-item
                v-for="(child, index) in children"
                :key="index"
                :item="child"
                :level="level + 1"
                @change-input="$emit('change-input', $event)"
            />

            <div
                v-if="paginateChildren && childrenHasMore"
                ref="sentinel"
                class="v-tree-children-sentinel flex items-center gap-2 ltr:pl-12 rtl:pr-12 py-1.5 text-xs text-gray-400 dark:text-gray-300"
            >
                <span class="inline-block w-3 h-3 border-2 border-gray-300 dark:border-gray-500 border-t-transparent rounded-full animate-spin"></span>
            </div>
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
                showChildren: false,
                name: this.categorytree.nameField,
                childrenPage: 0,
                childrenHasMore: true,
                childrenLoading: false,
                childrenObserver: null,
            };
        },

        mounted() {
            if (this.children.length > 0) {
                this.showChildren = true;

                if (this.paginateChildren) {
                    this.hasFetchedChildren = true;
                }
            }
        },

        beforeUnmount() {
            this.teardownChildrenObserver();
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

            pageSize() {
                return parseInt(this.categorytree.childrenPageSize) || 0;
            },

            paginateChildren() {
                return this.pageSize > 0;
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
                    'text-xl rounded-md cursor-pointer transition-all hover:bg-primary-50 dark:hover:bg-cherry-800'
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
                this.showChildren = !this.showChildren;

                if (! this.showChildren) {
                    this.teardownChildrenObserver();

                    return;
                }

                if (this.hasFetchedChildren || ! this.hasChildren) {
                    return;
                }

                if (this.paginateChildren) {
                    this.loadMoreChildren();
                } else {
                    this.fetchAllChildren();
                }
            },

            buildChildrenUrl(extra = {}) {
                const url = new URL(this.categorytree.fetchChildrenUrl, window.location.origin);

                if (this.id) {
                    url.searchParams.append('id', this.id);
                }

                if (this.categorytree.currentCategory) {
                    url.searchParams.append('category', this.categorytree.currentCategory);
                }

                Object.entries(extra).forEach(([key, val]) => url.searchParams.append(key, val));

                return url.toString();
            },

            fetchAllChildren() {
                if (this.categorytree.cache && this.categorytree.cache[this.id]) {
                    this.children = this.categorytree.cache[this.id];
                    this.hasFetchedChildren = true;

                    return;
                }

                this.$axios
                    .get(this.buildChildrenUrl())
                    .then((response) => {
                        this.children = response.data;
                        this.categorytree.cache[this.id] = response.data;
                        this.hasFetchedChildren = true;
                    })
                    .catch((err) => {
                        console.error('Failed to fetch children for node', this.id, err);
                    });
            },

            loadMoreChildren() {
                if (this.childrenLoading || ! this.childrenHasMore) {
                    return Promise.resolve();
                }

                this.childrenLoading = true;

                const nextPage = this.childrenPage + 1;

                return this.$axios
                    .get(this.buildChildrenUrl({ page: nextPage, limit: this.pageSize }))
                    .then((response) => {
                        const payload = response.data || {};
                        const batch = Array.isArray(payload.data) ? payload.data : [];

                        this.children = this.children.concat(batch);
                        this.childrenPage = payload.page || nextPage;
                        this.childrenHasMore = !! payload.has_more;
                        this.hasFetchedChildren = true;
                    })
                    .catch((err) => {
                        console.error('Failed to fetch children for node', this.id, err);
                        this.childrenHasMore = false;
                    })
                    .finally(() => {
                        this.childrenLoading = false;

                        if (! this.childrenHasMore) {
                            this.teardownChildrenObserver();
                        } else {
                            this.$nextTick(() => this.rearmChildrenObserver());
                        }
                    });
            },

            setupChildrenObserver() {
                if (! this.paginateChildren || this.childrenObserver) {
                    return;
                }

                this.childrenObserver = new IntersectionObserver((entries) => {
                    if (entries.some(entry => entry.isIntersecting)) {
                        this.loadMoreChildren();
                    }
                }, {
                    root: this.getScrollParent(this.$el),
                    rootMargin: '120px',
                    threshold: 0,
                });

                this.$nextTick(() => {
                    if (this.$refs.sentinel && this.childrenObserver) {
                        this.childrenObserver.observe(this.$refs.sentinel);
                    }
                });
            },

            rearmChildrenObserver() {
                const sentinel = this.$refs.sentinel;

                if (this.childrenObserver && sentinel) {
                    this.childrenObserver.unobserve(sentinel);
                    this.childrenObserver.observe(sentinel);
                } else {
                    this.setupChildrenObserver();
                }
            },

            teardownChildrenObserver() {
                if (this.childrenObserver) {
                    this.childrenObserver.disconnect();
                    this.childrenObserver = null;
                }
            },

            getScrollParent(el) {
                let node = el ? el.parentElement : null;

                while (node) {
                    const overflowY = window.getComputedStyle(node).overflowY;

                    if ((overflowY === 'auto' || overflowY === 'scroll') && node.scrollHeight > node.clientHeight) {
                        return node;
                    }

                    node = node.parentElement;
                }

                return null;
            },

            has(value) {
                return this.categorytree.has(value);
            },

            onInputChange() {
                if (this.categorytree.inputType === 'checkbox') {
                    this.categorytree.handleCheckbox(this.item);
                }
                this.$emit('change-input', this.categorytree.formattedValues);
            },
        }
    });
</script>
@endPushOnce
