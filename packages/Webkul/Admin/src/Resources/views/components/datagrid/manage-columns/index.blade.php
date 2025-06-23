
<v-datagrid-manage-columns
    :datagrid-id="available.id"
    {{ $attributes }}
>
    <div class="transparent-button">
        <div
            class="relative inline-flex w-full max-w-max ltr:pl-3 rtl:pr-3 ltr:pr-5 rtl:pl-5 cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-1 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-2"
        >
            <span class="icon-manage-column text-2xl"></span>

            <span>
                @lang('admin::app.components.datagrid.toolbar.manage-columns.title')
            </span>
        </div>
    </div>
</v-datagrid-manage-columns>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-manage-columns-template"
    >
        <div>
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, applyColumns)"
                    ref="manageColumnsForm"
                >
                <!-- Modal Component -->
                    <x-admin::modal
                        ref="manageColumnsModal"
                        type="large"
                        @toggle="getColumnsList"
                    >
                        <!-- Modal Toggle -->
                        <x-slot:toggle>
                            <div>
                                <div
                                    class="relative inline-flex w-full max-w-max ltr:pl-3 rtl:pr-3 ltr:pr-5 rtl:pl-5 cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-1 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-2"
                                >
                                    <span class="icon-manage-column text-2xl"></span>

                                    <span>
                                        @lang('admin::app.components.datagrid.toolbar.manage-columns.title')
                                    </span>
                                </div>

                                <div class="z-10 hidden w-full divide-y divide-gray-100 rounded bg-white dark:bg-cherry-800 shadow">
                                </div>
                            </div>
                        </x-slot>

                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold p-2">
                                @lang('admin::app.components.datagrid.manage-columns.title')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <div class="grid grid-cols-2 gap-4 p-2">
                                <!-- Left Side -->
                                <div class="flex flex-col gap-y-2">
                                    <div class="flex items-center justify-between">
                                        <p class="text-base text-gray-800 dark:text-white font-bold">
                                            @lang('admin::app.components.datagrid.manage-columns.available-columns')
                                        </p>
                                    </div>
                                    <template v-if="loading">
                                        <div class="shimmer h-10 rounded-md mb-1"></div>

                                        <div class="grid gap-y-2.5 pt-3 !h-[calc(100vh-367px)] pb-1 pt-1 overflow-auto">
                                            <div v-for="n in 20" :key="n" class="shimmer w-[302px] h-[38px] rounded-md"></div>
                                        </div>
                                        <div class="flex gap-x-2.5 pt-3">
                                            <div class="shimmer w-[38px] h-[38px] rounded-md"></div>
                                            <div class="shimmer w-[38px] h-[38px] rounded-md"></div>
                                            <div class="shimmer w-[60px] h-[38px] rounded-md"></div>
                                            <div class="shimmer w-[38px] h-[38px] rounded-md"></div>
                                        </div>
                                    </template>

                                    <template v-else>
                                        <div class="relative w-full flex items-center justify-center mb-1 mt-2">
                                            <input
                                                type="text"
                                                class="bg-white dark:bg-cherry-800 border dark:border-cherry-900 rounded-lg block w-full ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 py-1.5 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400"
                                                placeholder="Search"
                                                v-model.lazy="searchTerm"
                                                v-debounce="500"
                                                @keydown.enter.prevent="search($event.target.value)"
                                            />

                                            <span class="icon-search text-2xl absolute ltr:right-5 rtl:left-3 top-1.5 flex items-center pointer-events-none"></span>
                                        </div>
                                        <draggable
                                            class="!h-[calc(100vh-367px)] pb-[16px] pt-1 overflow-auto ltr:border-r rtl:border-l border-gray-200"
                                            ghost-class="draggable-ghost"
                                            handle=".icon-drag"
                                            v-bind="{animation: 200}"
                                            :list="availableColumns"
                                            item-key="code"
                                            group="groups"
                                            v-if="!loading"
                                        >
                                            <template #item="{ element, index }">
                                                <div class="">
                                                    <!-- Group Container -->
                                                    <div class="flex items-center group">
                                                        <div
                                                            class="text-[20px] rounded-[6px] cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 group-hover:text-gray-800"
                                                        >
                                                            <div
                                                                class="flex gap-[6px] max-w-max py-[6px] ltr:pr-[6px] rtl:pl-[6px] rounded transition-all text-gray-600 dark:text-gray-300 group cursor-pointer"
                                                            >
                                                                <i class="icon-drag text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>

                                                                <span
                                                                    class="text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
                                                                    v-text="element.label"
                                                                >
                                                                </span>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>

                                        <!-- Pagination -->
                                        <div class="flex gap-1 items-left justify-right mt-2.5">
                                            <a @click="changePage(currentPage - 1)">
                                                <div class="inline-flex gap-x-1 items-center justify-between w-full max-w-max ltr:ml-2 rtl:mr-2 p-1.5 bg-white dark:bg-cherry-800 border rounded-md dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center cursor-pointer transition-all hover:border hover:bg-violet-50 dark:hover:bg-cherry-800 marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black">
                                                    <span class="icon-chevron-left text-2xl"></span>
                                                </div>
                                            </a>

                                            <a @click="changePage(currentPage + 1)">
                                                <div
                                                    class="inline-flex gap-x-1 items-center justify-between w-full max-w-max ltr:ml-2 rtl:mr-2 p-1.5 bg-white dark:bg-cherry-800 border rounded-md dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center cursor-pointer transition-all hover:border hover:bg-violet-50 dark:hover:bg-cherry-800 marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black">
                                                    <span class="icon-chevron-right text-2xl"></span>
                                                </div>
                                            </a>

                                            <div class="flex items-center justify-center gap-2.5">
                                                <input
                                                    type="text"
                                                    class="inline-flex min-h-[38px] max-w-[60px] appearance-none items-center justify-center gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-3 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                                                    :value="currentPage"
                                                    @keydown.enter.prevent="changePage($event.target.value)"
                                                >
                    
                                                <div class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                                                    <span> @lang('admin::app.components.datagrid.toolbar.of') </span>
                    
                                                    <span v-text="totalPages"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <!-- Right Side -->
                                <div class="flex flex-col gap-y-2">
                                    <div class="flex items-center justify-between mt-2.5">
                                        <p class="text-base text-gray-800 dark:text-white font-bold">
                                            @lang('admin::app.components.datagrid.manage-columns.selected-columns')
                                        </p>
                                    </div>

                                    <draggable
                                        class="h-[calc(100vh-285px)] pb-[16px] pt-3 overflow-auto border-gray-200"
                                        ghost-class="draggable-ghost"
                                        handle=".icon-drag"
                                        v-bind="{animation: 200}"
                                        :list="selectedColumns"
                                        item-key="code"
                                        group="groups"
                                    >
                                        <template #item="{ element, index }">
                                            <div class="">
                                                <!-- Group Container -->
                                                <div class="flex items-center group">
                                                    <div
                                                        class="text-[20px] rounded-[6px] cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 group-hover:text-gray-800"
                                                    >
                                                        <div
                                                            class="flex gap-[6px] max-w-max py-[6px] ltr:pr-[6px] rtl:pl-[6px] rounded transition-all text-gray-600 dark:text-gray-300 group cursor-pointer"
                                                        >
                                                            <i class="icon-drag text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>

                                                            <span
                                                                class="text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
                                                                v-text="element.label"
                                                            >
                                                            </span>

                                                            <input
                                                                type="hidden"
                                                                :name="'selected_columns[]'"
                                                                :value="element.code"
                                                            />

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                            </div>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.components.datagrid.manage-columns.apply-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid-manage-columns', {
            template: '#v-datagrid-manage-columns-template',

            data() {
                return {
                    loading: false,
                    selectedColumns: [],
                    columnList: [],
                    viewedColumns: [],
                    currentPage: 1,
                    limit: 25,
                    totalPages: 1,
                    searchQuery: "",
                    debounceTimeout: null,
                };
            },

            computed: {
                availableColumns: function() {
                    return this.columnList;
                },
            },

            mounted() {
                this.normalizeSelectedColumns();
            },

            watch: {
                selectedColumns: {
                    handler(newVal) {
                        this.viewedColumns = newVal.map(el => el.code);
                    },
                    deep: true,
                    immediate: true,
                }
            },

            methods: {
                applyColumns() {
                    this.$parent.managedColumns(this.viewedColumns)
                },

                normalizeSelectedColumns() {
                    this.selectedColumns = this.$parent.available.columns.map((el) => {
                        return {
                            code: el.index,
                            label: el.label,
                        };
                    });
                },

                getColumnsList() {
                    const params = {
                        entityName: this.$parent.available.meta.managedColumn.entityName || 'attributes',
                        source: this.$parent.available.meta.managedColumn.source || 'product',
                        page: this.currentPage,
                        limit: this.limit,
                        query: this.searchQuery,
                        exclude:this.selectedColumns,
                    };

                    this.loading = true;

                    if (!this.$parent.available.meta.managedColumn.route) {
                        this.columnList = this.$parent.available.columns;
                        this.loading = false;
                    }

                    this.$axios
                        .get(this.$parent.available.meta.managedColumn.route, {
                            params
                        })
                        .then(({
                            data
                        }) => {
                            this.columnList = data.options.filter((column) => {
                                const columnKey = column.code || column.index;
                                return !this.selectedColumns.some((selectedColumn) => {
                                    return selectedColumn.code == columnKey;
                                });
                            });
                            this.totalPages = data.lastPage;
                            this.loading = false;
                        });
                },

                changePage(page) {
                    if (page > 0 && page <= this.totalPages) {
                        this.currentPage = page;
                        this.getColumnsList();
                    }
                },

                search(value) {
                    this.searchQuery = value;
                    this.currentPage = 1;

                    this.getColumnsList();
                }
            },
        });
    </script>
@endPushOnce
