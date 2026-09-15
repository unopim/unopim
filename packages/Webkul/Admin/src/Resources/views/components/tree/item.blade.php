@pushOnce('scripts')
<script type="text/x-template" id="v-tree-item-template">
    <div :class="itemClasses">
        <div
            class="group flex items-center gap-0.5 ltr:pr-1 rtl:pl-1 rounded-md"
            :class="rowClasses"
        >
            <i
                :class="toggleIconClasses"
                @click="toggleBranch"
            ></i>

            <i
                :class="folderIconClasses"
                @click="onFolderClick"
            ></i>

            <span
                class="flex-1 ltr:ml-1 rtl:mr-1 py-1.5 text-sm truncate"
                v-if="categorytree.navigateOnSelect"
                :class="[
                    hasSelectedValue ? 'text-primary-700 dark:text-white font-semibold' : 'text-gray-600 dark:text-gray-300',
                    categorytree.allowEdit ? 'cursor-pointer' : '',
                ]"
                :title="label"
                v-text="label"
                @click="onInputChange"
            ></span>

            <component
                :is="inputComponent"
                v-else
                :id="inputId"
                :label="label"
                :name="name"
                :value="value"
                @change="onInputChange(item.value)"
            />

            <a
                class="invisible opacity-0 flex shrink-0 items-center justify-center w-6 h-6 ltr:ml-auto rtl:mr-auto text-lg leading-none text-gray-600 dark:text-gray-300 rounded transition-opacity group-hover:visible group-hover:opacity-100 hover:bg-primary-50 dark:hover:bg-cherry-800"
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
                @select-node="$emit('select-node', $event)"
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
                folderLoading: false,
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
                    /**
                     * A non-partial set is already the complete children
                     * list (e.g. a whole fetched subtree assigned in one
                     * go) — childrenHasMore defaults true, and left alone
                     * it renders the pagination sentinel's spinner forever
                     * with nothing left to ever resolve it.
                     */
                    this.hasFetchedChildren = true;
                    this.childrenHasMore = false;
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

            /**
             * Two trees can sit on one page — the browser beside the panel and the parent
             * picker inside it — and a label bound to a plain category id would activate
             * whichever input the document happened to hold first.
             */
            inputId() {
                return `${this.categorytree.treeUid}-${this.id}`;
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

            rowClasses() {
                if (! this.categorytree.navigateOnSelect) {
                    return '';
                }

                if (this.hasSelectedValue) {
                    return 'bg-primary-50 dark:bg-cherry-800';
                }

                return this.categorytree.allowEdit
                    ? 'hover:bg-primary-50 dark:hover:bg-cherry-800'
                    : '';
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
                    'shrink-0 text-2xl',
                    this.folderLoading ? 'cursor-wait pointer-events-none opacity-50' : 'cursor-pointer'
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
            expandBranch() {
                if (this.showChildren || ! this.hasChildren) {
                    return;
                }

                this.showChildren = true;

                if (this.hasFetchedChildren) {
                    return;
                }

                if (this.paginateChildren) {
                    this.loadMoreChildren();
                } else {
                    this.fetchAllChildren();
                }
            },

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

                return this.$axios
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

            /**
             * Ancestor labels come from the component chain rather than a request: a node
             * is only ever rendered inside the nodes it descends from.
             */
            path() {
                const labels = [this.label];

                for (let parent = this.$parent; parent; parent = parent.$parent) {
                    if (! parent.item) {
                        break;
                    }

                    labels.unshift(parent.label);
                }

                return labels.join(' / ');
            },

            /**
             * Folder click always selects/deselects this branch plus every
             * descendant, independent of selectionType — the checkbox keeps
             * whatever selection mode the tree was configured with.
             */
            async onFolderClick() {
                if (this.categorytree.navigateOnSelect || this.categorytree.inputType !== 'checkbox' || this.folderLoading) {
                    return;
                }

                const willSelect = ! this.hasSelectedValue;

                this.folderLoading = true;

                let tree = [];

                try {
                    if (this.hasChildren || this.hasFetchedChildren) {
                        tree = await this.fetchDescendantTree();
                    }
                } catch {
                    this.folderLoading = false;

                    return;
                }

                this.folderLoading = false;

                this.categorytree.toggle(this.value);

                this.flattenCodes(tree).forEach((code) => {
                    willSelect ? this.categorytree.select(code) : this.categorytree.unSelect(code);
                });

                if (willSelect) {
                    /**
                     * Assigning the whole fetched subtree in one go means every
                     * nested v-tree-item mounts with its own children already
                     * present, so each self-expands in its own mounted() hook —
                     * one render pass, not a fetch-then-reveal per level.
                     */
                    this.item[this.categorytree.childrenField] = tree;
                    this.children = tree;
                    this.hasFetchedChildren = true;
                    this.childrenHasMore = false;
                    this.showChildren = true;

                    /**
                     * A descendant already mounted and visible (e.g. left
                     * expanded from revealing the path to an earlier
                     * selection) initialized its own `children` from the
                     * old, thinner data at mount time — updating the parent's
                     * object here doesn't reach back into that instance, so
                     * it's pushed onto it directly, same as a normal fetch does.
                     */
                    tree.forEach((child) => this.syncMountedDescendant(child));
                } else {
                    this.showChildren = false;
                    this.teardownChildrenObserver();
                }

                if (this.categorytree.unsavedFieldName) {
                    /**
                     * The drawer this tree renders in is teleported away from
                     * the unsaved-changes tracker's own subtree, so a plain
                     * bubbling dispatch from here never reaches its listener —
                     * dispatch straight on the root(s) instead. Touching every
                     * tracker on the page (there's normally exactly one) is
                     * harmless for the others, unlike guessing the wrong one
                     * via querySelector's first-match.
                     */
                    document.querySelectorAll('.unsaved-root').forEach((root) => {
                        root.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                            detail: { name: this.categorytree.unsavedFieldName },
                        }));
                    });
                }

                this.$emit('select-node', {
                    value: this.value,
                    label: this.label,
                    path:  this.path(),
                });

                this.$emit('change-input', this.categorytree.formattedValues);
            },

            /**
             * One nested-set range query on the server returns the whole
             * descendant subtree in a single round trip, so a cascading
             * select doesn't need to walk the branch level by level.
             */
            fetchDescendantTree() {
                const url = new URL(this.categorytree.descendantsUrl, window.location.origin);

                url.searchParams.append('id', this.id);

                return this.$axios
                    .get(url.toString())
                    .then(({ data }) => this.clearPartialFlag(data?.data || []))
                    .catch((err) => {
                        console.error('Failed to fetch descendants for node', this.id, err);

                        this.$emitter.emit('add-flash', {
                            type:    'error',
                            message: err.response?.data?.message || '@lang('admin::app.catalog.categories.browse.children-failed')',
                        });

                        throw err;
                    });
            },

            /**
             * CategoryTreeResource marks any node whose children are loaded
             * as `partial: true` — correct for the lazily-revealed children
             * endpoint, but this fetch already pulled the complete subtree,
             * so a fresh mount must not read that flag and set up an
             * IntersectionObserver to needlessly re-fetch it via the
             * paginated endpoint.
             */
            clearPartialFlag(nodes) {
                nodes.forEach((node) => {
                    delete node.partial;

                    this.clearPartialFlag(node[this.categorytree.childrenField] || []);
                });

                return nodes;
            },

            syncMountedDescendant(nodeData) {
                const children = nodeData[this.categorytree.childrenField] || [];

                const mounted = this.categorytree.nodes.find(
                    (node) => node.value === String(nodeData[this.categorytree.valueField])
                );

                if (mounted) {
                    mounted.children = children;
                    mounted.hasFetchedChildren = true;
                    mounted.childrenHasMore = false;
                    mounted.showChildren = true;
                }

                children.forEach((child) => this.syncMountedDescendant(child));
            },

            flattenCodes(nodes) {
                const codes = [];

                const walk = (list) => {
                    list.forEach((node) => {
                        codes.push(node[this.categorytree.valueField]);
                        walk(node[this.categorytree.childrenField] || []);
                    });
                };

                walk(nodes);

                return codes;
            },

            onInputChange() {
                if (this.categorytree.navigateOnSelect) {
                    this.categorytree.navigateTo(this.id);

                    return;
                }

                if (this.categorytree.inputType === 'checkbox') {
                    this.categorytree.handleCheckbox(this.item);
                }

                this.$emit('select-node', {
                    value: this.value,
                    label: this.label,
                    path:  this.path(),
                });

                this.$emit('change-input', this.categorytree.formattedValues);
            },
        }
    });
</script>
@endPushOnce
