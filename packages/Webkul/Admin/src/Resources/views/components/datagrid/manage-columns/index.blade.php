
<v-datagrid-manage-columns
    :datagrid-id="available.id"
    {{ $attributes }}
>
    <div class="transparent-button ">
        @lang('Manage Columns')
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
                                    <span>
                                        @lang('Manage Columns')
                                    </span>
                                </div>

                                <div class="z-10 hidden w-full divide-y divide-gray-100 rounded bg-white dark:bg-cherry-800 shadow">
                                </div>
                            </div>
                        </x-slot>

                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('Manage Columns')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                                <div class="grid grid-cols-2 gap-4 mb-2.5 p-4">
                                    <!-- Left Side -->
                                    <div class="flex flex-col gap-y-2">
                                        <div class="flex items-center justify-between">
                                            <p class="text-base text-gray-800 dark:text-white font-bold">
                                                @lang('Available Columns')
                                            </p>
                                        </div>
                                            <draggable
                                                class="h-[calc(100vh-285px)] pb-[16px] overflow-auto ltr:border-r rtl:border-l border-gray-200"
                                                ghost-class="draggable-ghost"
                                                handle=".icon-drag"
                                                v-bind="{animation: 200}"
                                                :list="availableColumns"
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
                                                                    
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>  
                                                </template>
                                            </draggable>
                                    </div>
                                    <!-- Right Side -->
                                    <div class="flex flex-col gap-y-2">
                                        <div class="flex items-center justify-between mt-2.5">
                                            <p class="text-base text-gray-800 dark:text-white font-bold">
                                                @lang('Selected Columns')
                                            </p>
                                        </div>

                                        <draggable
                                            class="h-[calc(100vh-285px)] pb-[16px] overflow-auto border-gray-200"
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
                                @lang('Apply')
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

            props: ['src', 'datagridId'],

            data() {
                return {
                    format: 'xls',

                    available: null,

                    applied: null,
                    selectedColumns: [],
                    columnList: [
                        {
                            code: "name",
                            label: "Name",
                        },
                        {
                            code: "email",
                            label: "Email",
                        },
                        {
                            code: "phone",
                            label: "Phone",
                        },
                        {
                            code: "status",
                            label: "Status",
                        },
                        {
                            code: "created_at",
                            label: "Created At",
                        },
                        {
                            code: "updated_at",
                            label: "Updated At",
                        },
                    ],
                    viewedColumns: [],
                };
            },

            computed: {
                availableColumns: function() {
                    return this.columnList.filter((column) => {
                        return !this.selectedColumns.find((selectedColumn) => {
                            return selectedColumn.code === column.code;
                        });
                    });
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
                        entityName: 'attributes',
                        page: 1,
                    };

                    this.$axios
                        .get('{{ route('admin.vue_js_select.select.options') }}', {
                            params
                        })
                        .then(({
                            data
                        }) => {
                            console.log(data);
                            this.columnList = data.options;
                        });
                },
            },
        });
    </script>
@endPushOnce
