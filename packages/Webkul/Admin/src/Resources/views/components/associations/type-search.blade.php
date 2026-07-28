{{--
    Async, server-paginated picker for association types, used by the product
    edit Links panel to attach a type on demand. The panel never loads the full
    type set, so this scales regardless of how many types exist -- every query
    hits `admin.catalog.association_types.search` (20 per page, next page
    fetched when the list is scrolled near its end).

    Types already added to the product stay in the list, checked and locked,
    so the picker reflects the full catalogue instead of a shrinking remainder.

    Emits `onTypeAdded` with `{ added, removed }` -- newly ticked type payloads
    (`{ code, name, fields }`) the parent pushes as panels, and the codes of
    link-less panels the user unticked, which the parent drops.
--}}
<v-association-type-search {{ $attributes }}></v-association-type-search>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-association-type-search-template"
    >
        <x-admin::drawer
            ref="searchTypeDrawer"
            @close="searchTerm = ''; searchedTypes = []; currentPage = 1; lastPage = 1;"
        >
            <x-slot:header>
                <div class="grid gap-3">
                    <div class="flex justify-between items-center">
                        <p class="text-xl font-medium dark:text-white">
                            @lang('admin::app.components.associations.type-search.title')
                        </p>

                        <div
                            class="ltr:mr-11 rtl:ml-11 primary-button"
                            @click="addSelected"
                        >
                            @lang('admin::app.components.associations.type-search.add-btn')
                        </div>
                    </div>

                    <div class="relative w-full">
                        <input
                            type="text"
                            class="bg-white dark:bg-cherry-800 border dark:border-cherry-900 rounded-lg block w-full ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 py-1.5 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400"
                            :placeholder="@json(trans('admin::app.components.associations.type-search.search-placeholder'))"
                            v-model.lazy="searchTerm"
                            v-debounce="500"
                        />

                        <span class="icon-search text-2xl absolute ltr:right-3 rtl:left-3 top-1.5 flex items-center pointer-events-none"></span>
                    </div>
                </div>
            </x-slot>

            <x-slot:content class="!p-0" @scroll="onScroll">
                <div
                    class="grid"
                    v-if="searchedTypes.length"
                >
                    <div
                        class="flex gap-2.5 items-center px-4 py-4 bg-gray-50 dark:bg-cherry-800 border-b border-slate-300 dark:border-gray-800 cursor-pointer select-none"
                        role="checkbox"
                        tabindex="0"
                        :aria-checked="allSelected ? 'true' : (someSelected ? 'mixed' : 'false')"
                        @click="toggleAll"
                        @keydown.enter.prevent="toggleAll"
                        @keydown.space.prevent="toggleAll"
                    >
                        <span
                            class="text-2xl"
                            :class="allSelected ? 'icon-checkbox-check text-primary-700' : (someSelected ? 'icon-checkbox-partial text-primary-700' : 'icon-checkbox-normal')"
                        ></span>

                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.components.associations.type-search.select-all')
                        </p>
                    </div>

                    <div
                        class="flex gap-2.5 justify-between px-4 py-6 border-b border-slate-300 dark:border-gray-800 select-none"
                        :class="isLocked(type)
                            ? 'cursor-default opacity-60'
                            : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-cherry-800'"
                        v-for="type in searchedTypes"
                        :key="type.code"
                        role="checkbox"
                        :tabindex="isLocked(type) ? -1 : 0"
                        :aria-checked="type.selected ? 'true' : 'false'"
                        :aria-disabled="isLocked(type) ? 'true' : 'false'"
                        @click="toggle(type)"
                        @keydown.enter.prevent="toggle(type)"
                        @keydown.space.prevent="toggle(type)"
                    >
                        <div class="flex gap-2.5 items-center">
                            <div>
                                <span
                                    class="text-2xl"
                                    :class="type.selected ? 'icon-checkbox-check text-primary-700' : 'icon-checkbox-normal'"
                                ></span>
                            </div>

                            <div class="grid gap-1.5 place-content-start">
                                <p class="text-base text-gray-800 dark:text-white font-semibold" v-text="type.name"></p>
                                <p class="text-gray-600 dark:text-gray-300" v-text="type.code"></p>
                            </div>
                        </div>

                        <span
                            class="label-active h-fit"
                            v-if="isLocked(type)"
                        >
                            @lang('admin::app.components.associations.type-search.already-added')
                        </span>
                    </div>

                    <div
                        class="flex justify-center py-4"
                        v-if="isLoading"
                    >
                        <span class="icon-loader text-2xl animate-spin"></span>
                    </div>
                </div>

                <div
                    class="grid gap-3.5 justify-center justify-items-center py-10 px-2.5"
                    v-else
                >
                    <img
                        src="{{ unopim_asset('images/icon-add-product.svg') }}"
                        class="w-20 h-20 dark:invert dark:mix-blend-exclusion"
                    />

                    <div class="flex flex-col gap-1.5 items-center">
                        <p class="text-base text-gray-400 font-semibold">
                            @lang('admin::app.components.associations.type-search.empty-title')
                        </p>

                        <p class="text-gray-400">
                            @lang('admin::app.components.associations.type-search.empty-info')
                        </p>
                    </div>
                </div>
            </x-slot>
        </x-admin::drawer>
    </script>

    <script type="module">
        app.component('v-association-type-search', {
            template: '#v-association-type-search-template',

            props: {
                addedTypeCodes: {
                    type: Array,
                    default: () => [],
                },

                /**
                 * Types the product already links products under: they stay ticked
                 * and untouchable here, since dropping them would silently discard
                 * their links -- only empty types can be unticked to remove them.
                 */
                lockedTypeCodes: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    searchTerm: '',

                    searchedTypes: [],

                    currentPage: 1,

                    lastPage: 1,

                    isLoading: false,
                }
            },

            computed: {
                selectableTypes() {
                    return this.searchedTypes.filter(type => ! this.isLocked(type));
                },

                selectedCount() {
                    return this.selectableTypes.filter(type => type.selected).length;
                },

                allSelected() {
                    return this.selectableTypes.length > 0
                        && this.selectedCount === this.selectableTypes.length;
                },

                someSelected() {
                    return this.selectedCount > 0 && ! this.allSelected;
                },
            },

            watch: {
                searchTerm() {
                    this.search();
                },
            },

            methods: {
                open() {
                    this.$refs.searchTypeDrawer.open();
                    this.search();
                },

                search() {
                    this.fetch(1);
                },

                loadMore() {
                    if (this.isLoading || this.currentPage >= this.lastPage) {
                        return;
                    }

                    this.fetch(this.currentPage + 1);
                },

                fetch(page) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.catalog.association_types.search') }}", {
                            params: {
                                query: this.searchTerm,
                                page,
                            },
                        })
                        .then(response => {
                            const types = response.data.data.map(type => ({
                                ...type,
                                selected: this.addedTypeCodes.includes(type.code),
                            }));

                            this.searchedTypes = page === 1
                                ? types
                                : this.searchedTypes.concat(types);

                            this.currentPage = response.data.meta?.current_page ?? page;
                            this.lastPage = response.data.meta?.last_page ?? page;
                        })
                        .catch(() => {})
                        .finally(() => this.isLoading = false);
                },

                onScroll(event) {
                    const target = event.target;

                    if (target.scrollHeight - target.scrollTop - target.clientHeight <= 100) {
                        this.loadMore();
                    }
                },

                isLocked(type) {
                    return this.lockedTypeCodes.includes(type.code);
                },

                toggle(type) {
                    if (this.isLocked(type)) {
                        return;
                    }

                    type.selected = ! type.selected;
                },

                toggleAll() {
                    const selected = ! this.allSelected;

                    this.selectableTypes.forEach(type => type.selected = selected);
                },

                addSelected() {
                    this.$emit('onTypeAdded', {
                        added: this.selectableTypes.filter(
                            type => type.selected && ! this.addedTypeCodes.includes(type.code)
                        ),
                        removed: this.selectableTypes
                            .filter(type => ! type.selected && this.addedTypeCodes.includes(type.code))
                            .map(type => type.code),
                    });

                    this.$refs.searchTypeDrawer.close();
                },
            },
        });
    </script>
@endPushOnce
