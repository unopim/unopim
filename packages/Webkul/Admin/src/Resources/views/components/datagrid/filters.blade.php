<div v-for="column in available.columns">
    <div v-if="column.filterable">
        <!-- Boolean -->
        <div v-if="column.type === 'boolean'">
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                    v-text="column.label"
                >
                </p>

                <div
                    class="flex items-center gap-x-1.5"
                    @click="removeAppliedColumnAllValues(column.index)"
                >
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>
                </div>
            </div>

            <div class="mb-2 mt-1.5">
                <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span
                                class="text-sm text-gray-400 dark:text-gray-400"
                                v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                            >
                            </span>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="option in column.options"
                            v-text="option.label"
                            @click="filterPage(option.value, column)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>
            </div>

            <div class="mb-4 flex gap-2 flex-wrap">
                <p
                    class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                >
                    <!-- Retrieving the label from the options based on the applied column value. -->
                    <span v-text="column.options.find((option => option.value == appliedColumnValue)).label"></span>

                    <span
                        class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                        @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                    >
                    </span>
                </p>
            </div>
        </div>

        <!-- Dropdown -->
        <div v-else-if="column.type === 'dropdown'">
            <!-- Basic -->
            <div v-if="column.options.type === 'basic'">
                <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                        v-text="column.label"
                    >
                    </p>

                    <div
                        class="flex items-center gap-x-1.5"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>
                    </div>
                </div>

                <div class="mb-2 mt-1.5">
                    <x-admin::dropdown>
                        <!-- Dropdown Toggler -->
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 "
                            >
                                <span
                                    class="text-sm text-gray-400 dark:text-gray-400"
                                    v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                                >
                                </span>

                                <span class="icon-chevron-down text-2xl"></span>
                            </button>
                        </x-slot>

                        <!-- Dropdown Content -->
                        <x-slot:menu>
                            <x-admin::dropdown.menu.item
                                v-for="option in column.options.params.options"
                                v-text="option.label"
                                @click="filterPage(option.value, column)"
                            >
                            </x-admin::dropdown.menu.item>
                        </x-slot>
                    </x-admin::dropdown>
                </div>

                <div class="mb-4 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <!-- Retrieving the label from the options based on the applied column value. -->
                        <span v-text="column.options.params.options.find((option => option.value == appliedColumnValue))?.label"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>

            <!-- Searchable -->
            <div v-else-if="column.options.type === 'searchable'">
                <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                        v-text="column.label"
                    >
                    </p>

                    <div
                        class="flex items-center gap-x-1.5"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>
                    </div>
                </div>

                <v-datagrid-searchable-dropdown
                    :datagrid-id="available.id"
                    :column="column"
                    @select-option="filterPage($event, column)"
                >
                </v-datagrid-searchable-dropdown>
            </div>

            <!-- sync -->
            <div v-else-if="column.options.type === 'sync'">
                <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                        v-text="column.label"
                    >
                    </p>

                    <div
                        class="flex items-center gap-x-1.5"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>
                    </div>
                </div>

                <div class="mb-2 mt-1.5">
                    <v-datagrid-sync-dropdown
                        :datagrid-id="available.id"
                        :column="column"
                        @select-option="filterPage($event, column)"
                    >
                    </v-datagrid-sync-dropdown>
                </div>

                <div class="mb-4 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Date Range -->
        <div v-else-if="column.type === 'date_range'">
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white"
                    v-text="column.label"
                >
                </p>

                <div
                    class="flex items-center gap-x-1.5"
                    @click="removeAppliedColumnAllValues(column.index)"
                >
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>
                </div>
            </div>

            <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                <p
                    class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:text-gray-300"
                    v-for="option in column.options"
                    v-text="option.label"
                    @click="filterPage(
                        $event,
                        column,
                        { quickFilter: { isActive: true, selectedFilter: option } }
                    )"
                >
                </p>

                <x-admin::flat-picker.date ::allow-input="false">
                    <input
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :type="column.input_type"
                        :name="`${column.index}[from]`"
                        :placeholder="column.label"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'from' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.date>

                <x-admin::flat-picker.date ::allow-input="false">
                    <input
                        type="column.input_type"
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :name="`${column.index}[to]`"
                        :placeholder="column.label"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.date>

                <div class="mb-4 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' to ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Price -->
        <div v-else-if="column.type === 'price'">
            <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white"
                        v-text="column.label"
                    >
                    </p>

                    <div
                        class="flex items-center gap-x-1.5"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>
                    </div>
                </div>

                <div class="mb-2 mt-1.5 grid grid-cols-2 gap-2">
                    <input
                        type="text"
                        class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        :name="column.index"
                        :placeholder="column.label"
                        v-model="priceValue"
                        @change="checkAndFilter(column)"
                    />

                    <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span
                                class="text-sm text-gray-400 dark:text-gray-400"
                                v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                            >
                            </span>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="option in column.options"
                            v-text="option.label"
                            @click="selectCurrency(option.value,column)"

                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>
                </div>

                <div class="mb-4 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' - ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
            </div>
        </div>

        <!-- Date Time Range -->
        <div v-else-if="column.type === 'datetime_range'">
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white"
                    v-text="column.label"
                >
                </p>

                <div
                    class="flex items-center gap-x-1.5"
                    @click="removeAppliedColumnAllValues(column.index)"
                >
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>
                </div>
            </div>

            <div class="my-4 grid grid-cols-2 gap-1.5">
                <p
                    class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:text-gray-300"
                    v-for="option in column.options"
                    v-text="option.label"
                    @click="filterPage(
                        $event,
                        column,
                        { quickFilter: { isActive: true, selectedFilter: option } }
                    )"
                >
                </p>

                <x-admin::flat-picker.datetime ::allow-input="false">
                    <input
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :type="column.input_type"
                        :name="`${column.index}[from]`"
                        :placeholder="column.label"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'from' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.datetime>

                <x-admin::flat-picker.datetime ::allow-input="false">
                    <input
                        type="column.input_type"
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :name="`${column.index}[to]`"
                        :placeholder="column.label"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.datetime>

                <div class="mb-4 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' to ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Rest -->
        <div v-else>
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white"
                    v-text="column.label"
                >
                </p>

                <div
                    class="flex items-center gap-x-1.5"
                    @click="removeAppliedColumnAllValues(column.index)"
                >
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>
                </div>
            </div>

            <div class="mb-2 mt-1.5 grid">
                <input
                    type="text"
                    class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                    :name="column.index"
                    :placeholder="column.label"
                    @change="filterPage($event, column)"
                />
            </div>

            <div class="mb-4 flex gap-2 flex-wrap">
                <p
                    class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                >
                    <span v-text="appliedColumnValue"></span>

                    <span
                        class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                        @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                    >
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-datagrid-searchable-dropdown-template">
        <template v-if="isLoading">
    
        </template>
        <template v-else>
            <div class="mb-2 mt-1.5">
                <x-admin::form.control-group.control
                    type="select"
                    ::ref="column.index"
                    ::name="column?.label"
                    ::label="column?.label || column.index"
                    track-by="id"
                    label-by="label"
                    async="true"
                    ::list-route="column.options.route"
                    ::query-params="column.options.params"
                    @select-option="selectOption($event, column.index)"
                />
            </div>

            <div class="mb-4 flex gap-2 flex-wrap">
                <p
                    class="flex items-center rounded bg-violet-100 px-2 py-1 font-semibold text-violet-700"
                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                >
                    <span v-text="getLabel(appliedColumnValue)"></span>

                    <span
                        class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                        @click="this.$parent.$parent.$parent.removeAppliedColumnValue(column.index, appliedColumnValue)"
                    >
                    </span>
                </p>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-datagrid-searchable-dropdown', {
            template: '#v-datagrid-searchable-dropdown-template',

            props: ['column', 'datagridId'],

            data() {
                return {
                    isMinimumCharacters: false,

                    selectedOptions: [],

                    selectedValues: [],

                    params: {
                        entityName: this.column.options.params.entityName,
                        page: 1,
                        locale: "{{ core()->getRequestedLocaleCode() }}",
                    },
                };
            },

            mounted() {
                this.selectedValues = this.getAppliedColumnValues(this.column.index);

                this.initializeValue();
            },

            methods: {
                selectOption(option, index) {
                    this.$emit('select-option', {
                        target: {
                            value: option.target.value[this.column.options?.track_by ?? 'id']
                        }
                    });

                    if (option.target.value.id) {
                        this.selectedOptions.push(option.target.value);
                    }

                    this.$refs[index].selectedValue = null;
                },

                initializeValue() {
                    this.isLoading = true;

                    this.params.identifiers = {
                        columnName: 'id',
                        values: this.selectedValues
                    };

                    this.$axios.get(this.column.options.route, {params: this.params})
                        .then(result => {
                            this.params.identifiers = {};

                            this.selectedOptions = result.data.options;

                            this.isLoading = false;
                        })
                },

                getLabel(value) {
                    let option = this.selectedOptions.filter(option => option.id == value)[0] ?? null;

                    return option?.label ?? value;
                },
                getAppliedColumnValues(index) {
                    return this.$parent.$parent.$parent.getAppliedColumnValues(index);
                }
            }
        });
    </script>

    <script type="text/x-template" id="v-datagrid-sync-dropdown-template">
        <x-admin::form.control-group.control
            type="select"
            ::ref="'filter_' + column.index"
            name="'filter_' + column.index"
            ::label="column.label || column.index"
            track-by="code"
            label-by="label"
            async="true"
            ::list-route="column.options.route"
            ::query-params="column.options.params"
            @select-option="selectOption($event, column.index)"
        />
    </script>

    <script type="module">
        app.component('v-datagrid-sync-dropdown', {
            template: '#v-datagrid-sync-dropdown-template',

            props: ['datagridId', 'column'],
            methods: {
                selectOption(option, index) {
                    this.searchedOptions = [];

                    this.$emit('select-option', {
                        target: {
                            value: option.target.value.code
                        }
                    });
                    this.$refs[`filter_${index}`].selectedValue = null;
                },
            }
        });
    </script>
@endpushOnce
