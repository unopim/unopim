{{--
    Async, server-paginated picker for association types, used by the product
    edit Links panel to attach a type on demand. The panel never loads the full
    type set, so this scales regardless of how many types exist -- every query
    hits `admin.catalog.association_types.search` (20 per page).

    Emits `onTypeAdded` with the selected type payloads
    (`{ code, name, fields }`); the parent pushes each as a new panel.
--}}
<v-association-type-search {{ $attributes }}></v-association-type-search>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-association-type-search-template"
    >
        <x-admin::drawer
            ref="searchTypeDrawer"
            @close="searchTerm = ''; searchedTypes = [];"
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

            <x-slot:content class="!p-0">
                <div
                    class="grid"
                    v-if="filteredSearchedTypes.length"
                >
                    <div
                        class="flex gap-2.5 justify-between px-4 py-6 border-b border-slate-300 dark:border-gray-800"
                        v-for="type in filteredSearchedTypes"
                        :key="type.code"
                    >
                        <div class="flex gap-2.5 items-center">
                            <div>
                                <input
                                    type="checkbox"
                                    class="sr-only peer"
                                    :id="'searched-type-' + type.code"
                                    v-model="type.selected"
                                />

                                <label
                                    class="icon-checkbox-normal text-2xl peer-checked:icon-checkbox-check peer-checked:text-primary-700 cursor-pointer"
                                    :for="'searched-type-' + type.code"
                                >
                                </label>
                            </div>

                            <div class="grid gap-1.5 place-content-start">
                                <p class="text-base text-gray-800 dark:text-white font-semibold" v-text="type.name"></p>
                                <p class="text-gray-600 dark:text-gray-300" v-text="type.code"></p>
                            </div>
                        </div>
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
            },

            data() {
                return {
                    searchTerm: '',

                    searchedTypes: [],
                }
            },

            computed: {
                filteredSearchedTypes() {
                    return this.searchedTypes.filter(type => ! this.addedTypeCodes.includes(type.code));
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
                    if (this.searchTerm.length <= 1 && this.searchedTypes.length !== 0) {
                        return;
                    }

                    this.$axios.get("{{ route('admin.catalog.association_types.search') }}", {
                            params: { query: this.searchTerm },
                        })
                        .then(response => {
                            this.searchedTypes = response.data.data;
                        })
                        .catch(() => {});
                },

                addSelected() {
                    const selected = this.searchedTypes.filter(type => type.selected);

                    this.$emit('onTypeAdded', selected);

                    this.$refs.searchTypeDrawer.close();
                },
            },
        });
    </script>
@endPushOnce
