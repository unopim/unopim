@props([
    'items'            => '[]',
    'expandedBranch'   => '[]',
    'lockedIds'        => '[]',
    'selected'         => null,
    'locale'           => null,
    'fallbackLocale'   => config('app.fallback_locale'),
    'childrenPageSize' => 100,
    'canCreate'        => false,
    'canDelete'        => false,
])

<v-category-browser
    :items='@json(json_decode($items, true))'
    :expanded-branch='@json(json_decode($expandedBranch, true))'
    :locked-ids='@json(json_decode($lockedIds, true))'
    selected="{{ $selected }}"
    locale="{{ $locale ?? core()->getRequestedLocaleCode() }}"
    fallback-locale="{{ $fallbackLocale }}"
    children-page-size="{{ $childrenPageSize }}"
    :can-create="{{ $canCreate ? 'true' : 'false' }}"
    :can-delete="{{ $canDelete ? 'true' : 'false' }}"
>
    <x-admin::shimmer.tree />
</v-category-browser>

@pushOnce('scripts')
    <script type="text/x-template" id="v-category-browser-template">
        <div class="flex flex-col h-full">
            <div class="flex flex-col gap-2 pb-2.5 border-b dark:border-cherry-800">
                <x-admin::search
                    name="category_search"
                    :placeholder="trans('admin::app.catalog.categories.browse.search-placeholder')"
                    v-model="searchTerm"
                    @input="onSearchInput"
                    @keydown.esc="clearSearch"
                />

                <div class="flex items-center justify-between" v-if="! isSearching">
                    <span class="text-xs text-gray-400 dark:text-gray-300" v-text="rootsCountLabel"></span>

                    <div class="flex items-center gap-1">
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
                </div>
            </div>

            <div class="flex-1 pt-2.5 overflow-y-auto" ref="scroller">
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

                    <a
                        class="flex flex-col gap-0.5 px-2 py-1.5 rounded-md cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                        v-for="result in searchResults"
                        :key="result.id"
                        :href="categoryUrl(result.id)"
                        :class="isSelected(result.id) ? 'bg-primary-50 dark:bg-cherry-800' : ''"
                    >
                        <span class="text-sm text-gray-800 dark:text-white truncate" v-text="result.label"></span>

                        <span class="text-xs text-gray-400 dark:text-gray-300 truncate" v-text="result.path" v-if="result.path"></span>
                    </a>

                    <p
                        class="py-2 text-center text-xs text-primary-600 cursor-pointer"
                        v-if="searchHasMore"
                        @click="loadMoreResults"
                    >
                        @lang('admin::app.catalog.categories.browse.load-more')
                    </p>
                </template>

                <template v-else>
                    <v-category-browser-node
                        v-for="item in formattedItems"
                        :key="item.id"
                        :item="item"
                        :level="1"
                    />

                    <p
                        class="py-6 text-center text-sm text-gray-400 dark:text-gray-300"
                        v-if="! formattedItems.length"
                    >
                        @lang('admin::app.catalog.categories.browse.empty')
                    </p>
                </template>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="v-category-browser-node-template">
        <div class="v-category-node">
            <div
                class="group flex items-center gap-1 pr-1 rounded-md cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                :class="rowClasses"
                :style="{ paddingInlineStart: indent }"
                @click="select"
            >
                <i
                    class="flex shrink-0 items-center justify-center w-5 text-xl transition-all"
                    :class="toggleClasses"
                    @click.stop.prevent="toggle"
                ></i>

                <i
                    class="shrink-0 text-2xl"
                    :class="isLocked ? 'icon-folder-block' : (hasChildren ? 'icon-folder' : 'icon-attribute')"
                ></i>

                <span
                    class="flex-1 py-1.5 text-sm truncate"
                    :class="isCurrent ? 'text-primary-700 dark:text-white font-semibold' : 'text-gray-800 dark:text-gray-300'"
                    :title="label"
                    v-text="label"
                ></span>

                <span
                    class="shrink-0 px-1.5 text-xs text-gray-400 dark:text-gray-300"
                    v-if="descendantCount"
                    v-text="descendantCount"
                ></span>

                <span
                    class="invisible opacity-0 flex shrink-0 items-center gap-0.5 transition-opacity group-hover:visible group-hover:opacity-100"
                    @click.stop
                >
                    <span
                        class="flex items-center justify-center w-6 h-6 text-lg leading-none text-gray-600 dark:text-gray-300 rounded hover:bg-white dark:hover:bg-cherry-900"
                        v-if="browser.canCreate"
                        title="@lang('admin::app.catalog.categories.browse.add-child')"
                        @click="addChild"
                    >+</span>

                    <span
                        class="icon-delete flex items-center justify-center w-6 h-6 text-xl text-gray-600 dark:text-gray-300 rounded hover:bg-white dark:hover:bg-cherry-900"
                        v-if="browser.canDelete && ! isLocked"
                        title="@lang('admin::app.catalog.categories.index.datagrid.delete')"
                        @click="destroy"
                    ></span>
                </span>
            </div>

            <template v-if="expanded">
                <v-category-browser-node
                    v-for="child in children"
                    :key="child.id"
                    :item="child"
                    :level="level + 1"
                />

                <p
                    class="py-1.5 text-xs text-gray-400 dark:text-gray-300"
                    :style="{ paddingInlineStart: childIndent }"
                    v-if="loading"
                >
                    @lang('admin::app.catalog.categories.browse.loading')
                </p>

                <p
                    class="py-1.5 text-xs text-primary-600 cursor-pointer"
                    :style="{ paddingInlineStart: childIndent }"
                    v-else-if="hasMore"
                    @click="loadChildren"
                >
                    @lang('admin::app.catalog.categories.browse.load-more')
                </p>

                <a
                    class="flex items-center gap-1 py-1.5 text-xs text-gray-400 dark:text-gray-300 rounded-md hover:text-primary-600"
                    :style="{ paddingInlineStart: childIndent }"
                    v-if="browser.canCreate && ! loading && ! hasMore"
                    :href="browser.createUrl(item.id)"
                >
                    <span class="text-base leading-none">+</span>

                    @lang('admin::app.catalog.categories.browse.add-child')
                </a>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-category-browser', {
            template: '#v-category-browser-template',

            props: {
                items: {
                    type: Array,
                    default: () => ([]),
                },

                expandedBranch: {
                    type: Array,
                    default: () => ([]),
                },

                lockedIds: {
                    type: Array,
                    default: () => ([]),
                },

                selected: {
                    type: [Number, String],
                    default: null,
                },

                locale: {
                    type: String,
                    default: '',
                },

                fallbackLocale: {
                    type: String,
                    default: 'en_US',
                },

                childrenPageSize: {
                    type: [Number, String],
                    default: 100,
                },

                canCreate: {
                    type: Boolean,
                    default: false,
                },

                canDelete: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    formattedItems: [],
                    searchTerm: '',
                    searchResults: [],
                    searchPage: 1,
                    searchLastPage: 1,
                    searchLoading: false,
                    searchTimer: null,
                    nodes: [],
                    rootsCountText: "@lang('admin::app.catalog.categories.browse.roots-count')",
                    urls: {
                        children: "{{ route('admin.catalog.categories.children.tree') }}",
                        search: "{{ route('admin.catalog.categories.search') }}",
                        index: "{{ route('admin.catalog.categories.index') }}",
                        delete: "{{ route('admin.catalog.categories.delete', 'nodeId') }}",
                    },
                };
            },

            provide() {
                return {
                    browser: this,
                };
            },

            created() {
                this.formattedItems = this.mergeBranch(this.items);
            },

            computed: {
                isSearching() {
                    return this.searchTerm.trim().length > 0;
                },

                searchHasMore() {
                    return this.searchPage < this.searchLastPage;
                },

                pageSize() {
                    return parseInt(this.childrenPageSize) || 100;
                },

                rootsCountLabel() {
                    return this.rootsCountText.replace(':count', this.formattedItems.length);
                },
            },

            methods: {
                /**
                 * The revealed path to the selected node arrives as its own tree, so it is
                 * grafted onto the matching roots. Without it the branch would collapse to
                 * the roots and the selection would sit behind several manual expansions.
                 */
                mergeBranch(roots) {
                    const branch = new Map((this.expandedBranch || []).map(node => [String(node.id), node]));

                    return (roots || []).map(root => branch.get(String(root.id)) || root);
                },

                registerNode(node) {
                    this.nodes.push(node);
                },

                unregisterNode(node) {
                    this.nodes = this.nodes.filter(registered => registered !== node);
                },

                isLocked(id) {
                    return (this.lockedIds || []).map(String).includes(String(id));
                },

                isSelected(id) {
                    return this.selected && String(this.selected) === String(id);
                },

                categoryUrl(id) {
                    return this.buildUrl({ category: id });
                },

                createUrl(parentId) {
                    return this.buildUrl(parentId ? { panel: 'create', parent_id: parentId } : { panel: 'create' });
                },

                buildUrl(params) {
                    const url = new URL(this.urls.index, window.location.origin);

                    if (this.locale) {
                        url.searchParams.set('locale', this.locale);
                    }

                    Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));

                    return url.toString();
                },

                childrenUrl(parentId, page) {
                    const url = new URL(this.urls.children, window.location.origin);

                    url.searchParams.set('id', parentId);
                    url.searchParams.set('page', page);
                    url.searchParams.set('limit', this.pageSize);

                    if (this.locale) {
                        url.searchParams.set('locale', this.locale);
                    }

                    return url.toString();
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

                loadMoreResults() {
                    this.runSearch(this.searchPage + 1);
                },

                runSearch(page) {
                    const term = this.searchTerm.trim();

                    if (! term) {
                        return;
                    }

                    this.searchLoading = true;

                    const url = new URL(this.urls.search, window.location.origin);

                    url.searchParams.set('query', term);
                    url.searchParams.set('page', page);

                    if (this.locale) {
                        url.searchParams.set('locale', this.locale);
                    }

                    this.$axios.get(url.toString())
                        .then(({ data }) => {
                            this.searchResults = page === 1 ? data.data : this.searchResults.concat(data.data);
                            this.searchPage = data.page;
                            this.searchLastPage = data.lastPage;
                        })
                        .catch(() => {
                            this.$emitter.emit('add-flash', {
                                type:    'error',
                                message: "@lang('admin::app.catalog.categories.browse.search-failed')",
                            });
                        })
                        .finally(() => this.searchLoading = false);
                },

                /**
                 * Bounded on purpose: only branches already fetched are opened. Walking the
                 * whole tree would fire a request per node, and catalogues here run to tens
                 * of thousands of them.
                 */
                expandAll() {
                    this.nodes.forEach(node => {
                        if (node.loaded || node.children.length) {
                            node.expanded = true;
                        }
                    });
                },

                collapseAll() {
                    this.nodes.forEach(node => node.expanded = false);
                },

                destroy(id, label) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            this.$axios.delete(this.urls.delete.replace('nodeId', id))
                                .then(({ data }) => {
                                    this.$emitter.emit('add-flash', { type: 'success', message: data.message });

                                    window.location.href = this.isSelected(id) ? this.urls.index : window.location.href;
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
            },
        });

        app.component('v-category-browser-node', {
            name: 'v-category-browser-node',

            template: '#v-category-browser-node-template',

            props: {
                item: {
                    type: Object,
                    required: true,
                },

                level: {
                    type: Number,
                    default: 1,
                },
            },

            inject: ['browser'],

            data() {
                return {
                    children: this.item.children || [],
                    expanded: !! (this.item.children || []).length,
                    loaded: !! (this.item.children || []).length && ! this.item.partial,
                    loading: false,
                    page: 0,
                    hasMore: false,
                    revealed: this.item.partial ? (this.item.children || []) : [],
                };
            },

            mounted() {
                this.browser.registerNode(this);
            },

            beforeUnmount() {
                this.browser.unregisterNode(this);
            },

            computed: {
                label() {
                    return this.item.name || `[${this.item.code}]`;
                },

                /**
                 * Nested set bounds already encode the size of the branch, so the badge
                 * costs nothing — no count query per row.
                 */
                descendantCount() {
                    return Math.max(0, (this.item._rgt - this.item._lft - 1) / 2);
                },

                hasChildren() {
                    return (this.item._rgt - this.item._lft) > 1;
                },

                isCurrent() {
                    return this.browser.isSelected(this.item.id);
                },

                isLocked() {
                    return this.browser.isLocked(this.item.id);
                },

                indent() {
                    return `${(this.level - 1) * 16}px`;
                },

                childIndent() {
                    return `${this.level * 16 + 24}px`;
                },

                rowClasses() {
                    return this.isCurrent ? 'bg-primary-50 dark:bg-cherry-800' : '';
                },

                toggleClasses() {
                    if (! this.hasChildren) {
                        return 'pointer-events-none';
                    }

                    return [
                        this.expanded ? 'icon-chevron-down' : 'icon-chevron-right',
                        'cursor-pointer hover:bg-primary-100 dark:hover:bg-cherry-900 rounded',
                    ];
                },
            },

            methods: {
                toggle() {
                    if (! this.hasChildren) {
                        return;
                    }

                    this.expanded = ! this.expanded;

                    if (this.expanded && ! this.loaded) {
                        this.loadChildren();
                    }
                },

                loadChildren() {
                    if (this.loading) {
                        return;
                    }

                    this.loading = true;

                    this.$axios.get(this.browser.childrenUrl(this.item.id, this.page + 1))
                        .then(({ data }) => {
                            const batch = Array.isArray(data.data) ? data.data : [];

                            this.children = (this.page === 0 ? [] : this.children).concat(this.takeRevealed(batch));
                            this.page = data.page;
                            this.hasMore = !! data.has_more;
                            this.loaded = true;
                        })
                        .catch(() => {
                            this.hasMore = false;

                            this.$emitter.emit('add-flash', {
                                type:    'error',
                                message: "@lang('admin::app.catalog.categories.browse.children-failed')",
                            });
                        })
                        .finally(() => this.loading = false);
                },

                /**
                 * A revealed child already carries its own expanded subtree, so it wins over
                 * the freshly fetched copy — otherwise expanding the level would collapse
                 * everything that was revealed underneath it.
                 */
                takeRevealed(batch) {
                    if (! this.revealed.length) {
                        return batch;
                    }

                    return batch.map(node => this.revealed.find(child => String(child.id) === String(node.id)) || node);
                },

                select() {
                    window.location.href = this.browser.categoryUrl(this.item.id);
                },

                addChild() {
                    window.location.href = this.browser.createUrl(this.item.id);
                },

                destroy() {
                    this.browser.destroy(this.item.id, this.label);
                },
            },
        });
    </script>
@endPushOnce
