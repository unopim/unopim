@pushOnce('scripts')
<script type="text/x-template" id="v-tree-item-template">
    <div :class="itemClasses">
        <div class="group flex items-center">
            <i
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

            <a
                class="invisible opacity-0 flex shrink-0 items-center justify-center w-6 h-6 ltr:ml-1 rtl:mr-1 text-lg leading-none text-gray-600 dark:text-gray-300 rounded transition-opacity group-hover:visible group-hover:opacity-100 hover:bg-primary-50 dark:hover:bg-cherry-800"
                v-if="categorytree.allowCreate"
                :href="categorytree.subCategoryUrl(id)"
                title="@lang('admin::app.catalog.categories.browse.add-child')"
            >+</a>

            <span
                class="icon-delete invisible opacity-0 flex shrink-0 items-center justify-center w-6 h-6 text-xl text-gray-600 dark:text-gray-300 rounded cursor-pointer transition-opacity group-hover:visible group-hover:opacity-100 hover:bg-primary-50 dark:hover:bg-cherry-800"
                v-if="categorytree.allowDelete"
                title="@lang('admin::app.catalog.categories.index.datagrid.delete')"
                @click.stop="categorytree.destroyCategory(item)"
            ></span>
        </div>

        <template v-if="showChildren">
            <v-tree-item
                v-for="child in children"
                :key="child[categorytree.valueField]"
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
                isPartial: !! this.item.partial,
                showChildren: false,
                name: this.categorytree.nameField,
                childrenPage: 0,
                childrenHasMore: true,
                revealedChildren: null,
                childrenLoading: false,
                childrenObserver: null,
            };
        },

        mounted() {
            this.categorytree.registerLabel?.(this.value, this.label);

            this.categorytree.registerNode?.(this);

            if (this.children.length > 0) {
                this.showChildren = true;

                if (! this.paginateChildren) {
                    return;
                }

                if (this.isPartial) {
                    this.setupChildrenObserver();
                } else {
                    this.hasFetchedChildren = true;
                }
            }
        },

        beforeUnmount() {
            this.teardownChildrenObserver();

            this.categorytree.unregisterNode?.(this);
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
                    this.hasSelectedValue ? 'active' : '',
                    this.showChildren ? 'active' : ''
                ];
            },

            toggleIconClasses() {
                const isExpandable = this.hasChildren || this.hasFetchedChildren;

                return [
                    isExpandable ? (this.showChildren ? 'icon-chevron-down' : 'icon-chevron-right') : '',
                    'flex shrink-0 items-center justify-center w-6 text-xl rounded-md transition-all',
                    isExpandable ? 'cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800' : 'pointer-events-none'
                ];
            },

            folderIconClasses() {
                return [
                    (this.hasChildren || this.hasFetchedChildren) ? 'icon-folder' : 'icon-attribute',
                    'shrink-0 text-2xl cursor-pointer'
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

                if (this.isPartial && this.childrenPage === 0) {
                    this.revealedChildren = new Map(
                        this.children.map(child => [this.childKey(child), child])
                    );
                }

                return this.$axios
                    .get(this.buildChildrenUrl({ page: nextPage, limit: this.pageSize }))
                    .then((response) => {
                        const payload = response.data || {};
                        const batch = Array.isArray(payload.data) ? payload.data : [];

                        this.children = (this.childrenPage === 0 && this.revealedChildren ? [] : this.children)
                            .concat(this.takeRevealed(batch));

                        this.childrenPage = payload.page || nextPage;
                        this.childrenHasMore = !! payload.has_more;
                        this.hasFetchedChildren = true;

                        if (! this.childrenHasMore) {
                            this.flushRevealedChildren();
                        }
                    })
                    .catch((err) => {
                        console.error('Failed to fetch children for node', this.id, err);
                        this.childrenHasMore = false;
                        this.flushRevealedChildren();
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

            childKey(node) {
                return String(node[this.categorytree.valueField]);
            },

            /**
             * A partially revealed branch already holds the nodes on the path to a
             * selection, each carrying its own expanded subtree. Prefer those over the
             * freshly fetched copy so expanding the level neither duplicates them nor
             * collapses what was revealed underneath.
             */
            takeRevealed(batch) {
                if (! this.revealedChildren) {
                    return batch;
                }

                return batch.map((node) => {
                    const key = this.childKey(node);
                    const revealed = this.revealedChildren.get(key);

                    if (! revealed) {
                        return node;
                    }

                    this.revealedChildren.delete(key);

                    return revealed;
                });
            },

            flushRevealedChildren() {
                if (! this.revealedChildren) {
                    return;
                }

                if (this.revealedChildren.size) {
                    this.children = this.children.concat([...this.revealedChildren.values()]);
                }

                this.revealedChildren = null;
                this.isPartial = false;
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
                if (this.categorytree.navigateOnSelect) {
                    this.categorytree.navigateTo(this.id);

                    return;
                }

                if (this.categorytree.inputType === 'checkbox') {
                    this.categorytree.handleCheckbox(this.item);
                }
                this.$emit('change-input', this.categorytree.formattedValues);
            },
        }
    });
</script>
@endPushOnce
