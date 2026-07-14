@props(['isMultiRow' => false])

<x-admin::form.fields.load :types="['text', 'number']" />

<v-datagrid {{ $attributes }}>
    <x-admin::shimmer.datagrid :isMultiRow="$isMultiRow" />

    {{ $slot }}
</v-datagrid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-template"
    >
        <div>
            <x-admin::datagrid.toolbar />

            <div class="flex mt-4">
                <x-admin::datagrid.table :isMultiRow="$isMultiRow">
                    <template #header>
                        <slot
                            name="header"
                            :columns="available.columns"
                            :actions="available.actions"
                            :mass-actions="available.massActions"
                            :records="available.records"
                            :meta="available.meta"
                            :sort-page="sortPage"
                            :selectAllRecords="selectAllRecords"
                            :available="available"
                            :applied="applied"
                            :is-loading="isLoading"
                        >
                        </slot>
                    </template>

                    <template #body>
                        <slot
                            name="body"
                            :columns="available.columns"
                            :actions="available.actions"
                            :mass-actions="available.massActions"
                            :records="available.records"
                            :meta="available.meta"
                            :setCurrentSelectionMode="setCurrentSelectionMode"
                            :performAction="performAction"
                            :handleRowClick="handleRowClick"
                            :available="available"
                            :applied="applied"
                            :is-loading="isLoading"
                        >
                        </slot>
                    </template>
                </x-admin::datagrid.table>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid', {
            template: '#v-datagrid-template',

            props: ['src', 'filterAttributesSrc'],

            data() {
                return {
                    isLoading: false,

                    priceValue: '',

                    previousPriceValue: '',

                    selectedCurrency: null,

                    urlFilterIndices: [],

                    defaultFilterIndices: [],

                    activeFilterIndices: [],

                    showFilterPicker: false,

                    filterPickerSearch: '',

                    pickerOptions: [],

                    addedFilterColumns: {},

                    attributeConditions: {},

                    filterPickerPage: 1,

                    filterPickerLastPage: 1,

                    filterPickerLoading: false,

                    filterPickerSearchTimer: null,

                    available: {
                        id: null,

                        columns: [],

                        actions: [],

                        massActions: [],

                        records: [],

                        meta: {},
                    },

                    applied: {
                        massActions: {
                            meta: {
                                mode: 'none',

                                action: null,
                            },

                            indices: [],

                            value: null,
                        },

                        pagination: {
                            page: 1,

                            perPage: 10,
                        },

                        sort: {
                            column: null,

                            order: null,
                        },

                        filters: {
                            columns: [
                                {
                                    index: 'all',
                                    value: [],
                                },
                            ],
                        },
                    },
                };
            },

            mounted() {
                this.boot();

                this._onShareLinkChanged = () => this.get();
                this.$emitter.on('share-link-changed', this._onShareLinkChanged);
            },

            beforeUnmount() {
                if (this._onShareLinkChanged) {
                    this.$emitter.off('share-link-changed', this._onShareLinkChanged);
                }
            },

            computed: {
                filterFields() {
                    const types = {
                        string:  'text',
                        integer: 'number',
                    };

                    return (this.available.columns ?? []).reduce((fields, column) => {
                        fields[column.index] = {
                            name:        column.index,
                            type:        types[column.type] ?? 'text',
                            label:       column.label,
                            placeholder: column.label,
                            options:     [],
                            async:       false,
                        };

                        return fields;
                    }, {});
                },
            },

            watch: {
                'applied.massActions.indices': {
                    handler() {
                        this.setCurrentSelectionMode();
                    },

                    deep: true,
                },

                filterPickerSearch() {
                    if (! this.filterAttributesSrc) {
                        return;
                    }

                    clearTimeout(this.filterPickerSearchTimer);

                    this.filterPickerSearchTimer = setTimeout(() => this.loadFilterAttributes(true), 300);
                },
            },

            methods: {
                /**
                 * Initialization: This function checks for any previously saved filters in local storage and applies them as needed.
                 *
                 * @returns {void}
                 */
                boot() {
                    let datagrids = this.getDatagrids();

                    const urlParams = new URLSearchParams(window.location.search);
                    const urlFilters = this.parseUrlFilters();
                    const hasUrlFilters = Object.keys(urlFilters).length > 0;

                    if (urlParams.has('search')) {
                        let searchAppliedColumn = this.findAppliedColumn('all');

                        searchAppliedColumn.value = [urlParams.get('search')];
                    }

                    if (datagrids?.length) {
                        const currentDatagrid = datagrids.find(({ src }) => src === this.src);

                        if (currentDatagrid) {
                            this.applied.pagination = currentDatagrid.applied.pagination;

                            this.applied.sort = currentDatagrid.applied.sort;

                            this.applied.filters = currentDatagrid.applied.filters;

                            this.available.meta = currentDatagrid.available.meta;

                            if (currentDatagrid.activeFilterIndices?.length) {
                                this.activeFilterIndices = currentDatagrid.activeFilterIndices;
                            }

                            if (currentDatagrid.defaultFilterIndices?.length) {
                                this.defaultFilterIndices = currentDatagrid.defaultFilterIndices;
                            }

                            if (urlParams.has('search')) {
                                let searchAppliedColumn = this.findAppliedColumn('all');

                                searchAppliedColumn.value = [urlParams.get('search')];
                            }
                        }
                    }

                    if (hasUrlFilters) {
                        this.applyUrlFilters(urlFilters);
                    }

                    this.get();
                },

                /**
                 * Get. This will prepare params from the `applied` props and fetch the data from the backend.
                 *
                 * @returns {void}
                 */
                get(extraParams = {}) {
                    let params = {
                        pagination: {
                            page: this.applied.pagination.page,
                            per_page: this.applied.pagination.perPage,
                        },

                        sort: {},

                        filters: {},
                    };

                    if (
                        this.applied.sort.column &&
                        this.applied.sort.order
                    ) {
                        params.sort = this.applied.sort;
                    }

                    this.applied.filters.columns.forEach(column => {
                        params.filters[column.index] = column.value;
                    });

                    params.managedColumns = this.available.meta?.managedColumn?.columns;
                    params.manageableColumn = this.available.meta?.managedColumn?.columns;

                    this.isLoading = true;

                    this.$refs['filterDrawer'].close();

                    this.$axios
                        .get(this.src, {
                            params: { ...params, ...extraParams }
                        })
                        .then((response) => {
                            /**
                             * Precisely taking all the keys to the data prop to avoid adding any extra keys from the response.
                             */
                            const {
                                id,
                                columns,
                                actions,
                                mass_actions,
                                search_placeholder,
                                records,
                                meta,
                                manageableColumn,
                                managedColumns
                            } = response.data;

                            /**
                             * Guard against malformed responses (e.g. an auth redirect returning HTML instead of
                             * the datagrid JSON). Without a valid columns array the filter-initialisation below
                             * throws `Cannot read properties of undefined (reading 'filter')` and breaks the page.
                             */
                            if (! Array.isArray(columns)) {
                                this.isLoading = false;

                                return;
                            }

                            this.available.id = id;

                            this.available.columns = columns;

                            if (this.filterAttributesSrc) {
                                Object.values(this.addedFilterColumns).forEach(col => {
                                    if (this.activeFilterIndices.includes(col.index) && ! this.available.columns.some(c => c.index === col.index)) {
                                        this.available.columns.push(col);
                                    }
                                });
                            }

                            this.available.actions = actions;

                            this.available.massActions = mass_actions;

                            this.available.records = records;

                            this.available.meta = meta;

                            this.available.searchPlaceholder = search_placeholder;

                            // Initialize active filters on first load
                            if (this.activeFilterIndices.length === 0) {
                                this.activeFilterIndices = this.available.columns
                                    .filter(col => col.filterable && col.visible !== false)
                                    .map(col => col.index);
                            }

                            // Track default filter indices so they cannot be removed
                            if (this.defaultFilterIndices.length === 0) {
                                this.defaultFilterIndices = this.available.columns
                                    .filter(col => col.filterable && col.visible !== false)
                                    .map(col => col.index);
                            }

                            // Remove filters for attribute columns which have been disabled
                            if (this.available?.meta?.managedColumn?.enabled && this.available?.columns?.length) {
                                let filterableColumns = [];

                                this.available.columns.forEach(column => {
                                    if (column?.filterable) {
                                        filterableColumns.push(column.index);
                                    }
                                });

                                this.applied.filters.columns = this.applied.filters.columns.filter(column => column.index === 'all' || (filterableColumns.includes(column.index)));
                            }

                            this.syncAttributeConditions();

                            this.setCurrentSelectionMode();

                            this.updateDatagrids();

                           /**
                            * This event should be fired at the end, but only in the GET method. This allows the export feature to listen to it
                            * and update its properties accordingly.
                            */
                            this.$emitter.emit('change-datagrid', {
                                available: this.available,
                                applied: this.applied
                            });

                            this.isLoading = false;
                        });
                },

                /**
                 * Change Page.
                 *
                 * The reason for choosing the numeric approach over the URL approach is to prevent any conflicts with our existing
                 * URLs. If we were to use the URL approach, it would introduce additional arguments in the `get` method, necessitating
                 * the addition of a `url` prop. Instead, by using the numeric approach, we can let Axios handle all the query parameters
                 * using the `applied` prop. This allows for a cleaner and more straightforward implementation.
                 *
                 * @param {string|integer} directionOrPageNumber
                 * @returns {void}
                 */
                changePage(directionOrPageNumber) {
                    let newPage;

                    if (typeof directionOrPageNumber === 'string') {
                        if (directionOrPageNumber === 'previous') {
                            newPage = this.available.meta.current_page - 1;
                        } else if (directionOrPageNumber === 'next') {
                            newPage = this.available.meta.current_page + 1;
                        } else if (directionOrPageNumber === 'first') {
                            newPage = 1;
                        } else if (directionOrPageNumber === 'last') {
                            newPage = this.available.meta.last_page;
                        } else {
                            console.warn('Invalid Direction Provided : ' + directionOrPageNumber);

                            return;
                        }
                    }  else if (typeof directionOrPageNumber === 'number') {
                        newPage = directionOrPageNumber;
                    } else {
                        console.warn('Invalid Input Provided: ' + directionOrPageNumber);

                        return;
                    }

                    if (this.applied.pagination.page == newPage) {
                        return;
                    }

                    /**
                     * Check if the `newPage` is within the valid range.
                     */
                    if (newPage >= 1 && newPage <= this.available.meta.last_page) {
                        this.applied.pagination.page = newPage;

                        this.get();
                    } else {
                        console.warn('Invalid Page Provided: ' + newPage);
                    }
                },

                /**
                 * Change per page option.
                 *
                 * @param {integer} option
                 * @returns {void}
                 */
                changePerPageOption(option) {
                    this.applied.pagination.perPage = option;

                    /**
                     * When the total records are less than the number of data per page, we need to reset the page.
                     */
                    if (this.available.meta.last_page >= this.applied.pagination.page) {
                        this.applied.pagination.page = 1;
                    }

                    this.get();
                },

                /**
                 * Sort Page.
                 *
                 * @param {object} column
                 * @returns {void}
                 */
                sortPage(column) {
                    if (column.sortable) {
                        this.applied.sort = {
                            column: column.index,
                            order: this.applied.sort.order === 'asc' ? 'desc' : 'asc',
                        };

                        /**
                         * When the sorting changes, we need to reset the page.
                         */
                        this.applied.pagination.page = 1;

                        this.get();
                    }
                },

                /**
                 * Filter Page.
                 *
                 * @param {object} $event
                 * @param {object} column
                 * @param {object} additional
                 * @returns {void}
                 */
                filterPage($event, column = null, additional = {}) {
                    let quickFilter = additional?.quickFilter;

                    if (quickFilter?.isActive) {
                        let options = quickFilter.selectedFilter;

                        switch (column.type) {
                            case 'date_range':
                            case 'datetime_range':
                                this.applyFilter(column, options.from, {
                                    range: {
                                        name: 'from'
                                    }
                                });

                                this.applyFilter(column, options.to, {
                                    range: {
                                        name: 'to'
                                    }
                                });

                                break;

                            default:
                                break;
                        }
                    } else {
                        /**
                         * Here, either a real event will come or a string value. If a string value is present, then
                         * we create a similar event-like structure to avoid any breakage and make it easy to use.
                         */
                        if ($event?.target?.value === undefined) {
                            $event = {
                                target: {
                                    value: $event,
                                }
                            };
                        }

                        this.applyFilter(column, $event.target.value, additional);

                        if (column) {
                            $event.target.value = '';
                        }
                    }

                    /**
                     * We need to reset the page on filtering.
                     */
                    this.applied.pagination.page = 1;
                    if ('search' == $event.srcElement?.name ) {
                        this.get();
                    }
                },

                runFilters() {
                    this.get();
                },

                applyFilter(column, requestedValue, additional = {}) {
                    let appliedColumn = this.findAppliedColumn(column?.index);

                    /**
                     * If no column is found, it means that search from the toolbar have been
                     * activated. In this case, we will search for `all` indices and update the
                     * value accordingly.
                     */
                    if (! column) {
                        let appliedColumn = this.findAppliedColumn('all');

                        if (! requestedValue) {
                            appliedColumn.value = [];

                            return;
                        }

                        if (appliedColumn) {
                            appliedColumn.value = [requestedValue];
                        } else {
                            this.applied.filters.columns.push({
                                index: 'all',
                                value: [requestedValue]
                            });
                        }

                        /**
                         * Else, we will look into the sidebar filters and update the value accordingly.
                         */
                    } else {
                        /**
                         * Here if value already exists, we will not do anything.
                         */
                        if (
                            requestedValue === undefined ||
                            requestedValue === '' ||
                            appliedColumn?.value.includes(requestedValue)
                        ) {
                            return;
                        }

                        switch (column.type) {
                            case 'date_range':
                            case 'datetime_range':
                                let {
                                    range
                                } = additional;

                                if (appliedColumn) {
                                    let appliedRanges = appliedColumn.value[0];

                                    if (range.name == 'from') {
                                        appliedRanges[0] = requestedValue;
                                    }

                                    if (range.name == 'to') {
                                        appliedRanges[1] = requestedValue;
                                    }

                                    appliedColumn.value = [appliedRanges];
                                } else {
                                    let appliedRanges = ['', ''];

                                    if (range.name == 'from') {
                                        appliedRanges[0] = requestedValue;
                                    }

                                    if (range.name == 'to') {
                                        appliedRanges[1] = requestedValue;
                                    }

                                    this.applied.filters.columns.push({
                                        ...column,
                                        value: [appliedRanges]
                                    });
                                }

                                break;
                            case 'price':
                                let {
                                    field
                                } = additional;

                                if (appliedColumn) {
                                    let appliedValue = appliedColumn.value[0];

                                    if (field.name == 'currency') {
                                        appliedValue[0] = this.selectedCurrency;
                                    }

                                    if (field.name == 'amount') {
                                        appliedValue[0] = this.selectedCurrency;
                                        appliedValue[1] = requestedValue;
                                    }

                                    appliedColumn.value = [appliedValue];
                                } else {
                                    let appliedValue = [this.selectedCurrency, ''];

                                    if (field.name == 'currency') {
                                        appliedValue[0] = requestedValue;
                                    }

                                    if (field.name == 'amount') {
                                        appliedValue[1] = requestedValue;
                                    }

                                    this.applied.filters.columns.push({
                                        index: column.index,
                                        value: [appliedValue]
                                    });
                                }

                                break;
                            default:
                                if (appliedColumn) {
                                    appliedColumn.value.push(requestedValue);
                                } else {
                                    this.applied.filters.columns.push({
                                        ...column,
                                        value: [requestedValue]
                                    });
                                }

                                break;
                        }
                    }
                },

                managedColumns(columns) {
                    this.available.meta.managedColumn.columns = columns;
                    this.get();
                },

                //================================================================
                // Filters logic, will move it from here once completed.
                //================================================================

                /**
                 * Parse filter parameters from URL query string.
                 * Supports format: ?filters[column][]=value
                 *
                 * @returns {object}
                 */
                parseUrlFilters() {
                    const filters = {};
                    const params = new URLSearchParams(window.location.search);

                    for (const [key, value] of params.entries()) {
                        const match = key.match(/^filters\[([^\]]+)\]\[\]$/);

                        if (match) {
                            const filterIndex = match[1];

                            if (! filters[filterIndex]) {
                                filters[filterIndex] = [];
                            }

                            filters[filterIndex].push(value);
                        }
                    }

                    return filters;
                },

                /**
                 * Apply URL filter params, replacing any localStorage filters entirely.
                 * URL-sourced filters are tracked so they are excluded from localStorage persistence.
                 *
                 * @param {object} urlFilters
                 * @returns {void}
                 */
                applyUrlFilters(urlFilters) {
                    /**
                     * Clear all existing column filters (except global search) so URL filters
                     * are the sole source of truth — no stale localStorage filters leak through.
                     */
                    this.applied.filters.columns = this.applied.filters.columns.filter(
                        col => col.index === 'all'
                    );

                    for (const [index, values] of Object.entries(urlFilters)) {
                        this.applied.filters.columns.push({
                            index: index,
                            value: values,
                        });

                        this.urlFilterIndices.push(index);

                        if (! this.activeFilterIndices.includes(index)) {
                            this.activeFilterIndices.push(index);
                        }
                    }

                    /**
                     * Reset pagination when applying URL filters.
                     */
                    this.applied.pagination.page = 1;
                },

                findAppliedColumn(columnIndex) {
                    return this.applied.filters.columns.find(column => column.index === columnIndex);
                },

                hasAnyAppliedColumnValues(columnIndex) {
                    let appliedColumn = this.findAppliedColumn(columnIndex);

                    return appliedColumn?.value.length > 0;
                },

                getAppliedColumnValues(columnIndex) {
                    let appliedColumn = this.findAppliedColumn(columnIndex);

                    return appliedColumn?.value ?? [];
                },

                removeAppliedColumnValue(columnIndex, appliedColumnValue) {
                    let appliedColumn = this.findAppliedColumn(columnIndex);

                    appliedColumn.value = appliedColumn?.value.filter(value => value !== appliedColumnValue);

                    /**
                     * Clean up is done here. If there are no applied values present, there is no point in including the applied column as well.
                     */
                    if (!appliedColumn.value.length) {
                        this.applied.filters.columns = this.applied.filters.columns.filter(column => column.index !== columnIndex);
                    }
                },

                removeAppliedColumnAllValues(columnIndex) {
                    this.applied.filters.columns = this.applied.filters.columns.filter(column => column.index !== columnIndex);

                    this.get();
                },

                //================================================================
                // Mass actions logic, will move it from here once completed.
                //================================================================

                setCurrentSelectionMode() {
                    this.applied.massActions.meta.mode = 'none';

                    if (! this.available.records.length) {
                        return;
                    }

                    let selectionCount = 0;

                    this.available.records.forEach(record => {
                        const id = record[this.available.meta.primary_column];

                        if (this.applied.massActions.indices.includes(id)) {
                            this.applied.massActions.meta.mode = 'partial';

                            ++selectionCount;
                        }
                    });

                    if (this.available.records.length === selectionCount) {
                        this.applied.massActions.meta.mode = 'all';
                    }
                },

                selectAllRecords() {
                    this.setCurrentSelectionMode();

                    if (['all', 'partial'].includes(this.applied.massActions.meta.mode)) {
                        this.available.records.forEach(record => {
                            const id = record[this.available.meta.primary_column];

                            this.applied.massActions.indices = this.applied.massActions.indices.filter(selectedId => selectedId !== id);
                        });

                        this.applied.massActions.meta.mode = 'none';
                    } else {
                        this.available.records.forEach(record => {
                            const id = record[this.available.meta.primary_column];

                            let found = this.applied.massActions.indices.find(selectedId => selectedId === id);

                            if (!found) {
                                this.applied.massActions.indices.push(id);
                            }
                        });

                        this.applied.massActions.meta.mode = 'all';
                    }
                },

                validateMassAction() {
                    if (! this.applied.massActions.indices.length) {
                        this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.components.datagrid.index.no-records-selected')" });

                        return false;
                    }

                    if (! this.applied.massActions.meta.action) {
                        this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.components.datagrid.index.must-select-a-mass-action')" });

                        return false;
                    }

                    if (
                        this.applied.massActions.meta.action?.options?.length &&
                        this.applied.massActions.value === null
                    ) {
                        this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.components.datagrid.index.must-select-a-mass-action-option')" });

                        return false;
                    }

                    return true;
                },

                performMassAction(currentAction, currentOption = null) {
                    this.applied.massActions.meta.action = currentAction;

                    if (currentOption) {
                        this.applied.massActions.value = currentOption.value;
                    }

                    if (! this.validateMassAction()) {
                        return;
                    }

                    const {
                        action
                    } = this.applied.massActions.meta;

                    const method = action.method.toLowerCase();
                    const actionType = action?.options?.actionType?.toLowerCase() ?? '';
                    const modal = action?.options?.modal ?? null;

                    let modalEvent = 'open-confirm-modal';

                    if (actionType === 'delete') {
                        modalEvent = 'open-delete-modal';
                    } else if (modal) {
                        modalEvent = modal;
                    }

                    this.$emitter.emit(modalEvent, {
                        agree: (data) => {
                            switch (method) {
                                case 'post':
                                case 'put':
                                case 'patch':
                                    this.$axios[method](action.url, {
                                            indices: this.applied.massActions.indices,
                                            value: this.applied.massActions.value,
                                            filter: data
                                        })
                                        .then(response => {
                                            if (response.data.redirect && actionType === 'redirect') {
                                                this.$navigate(response.data.redirect);
                                                return;
                                            }

                                            this.$emitter.emit('add-flash', {
                                                type: 'success',
                                                message: response.data.message
                                            });

                                            this.get();
                                        })
                                        .catch((error) => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'error',
                                                message: error.response.data.message
                                            });
                                        });

                                    break;

                                case 'delete':
                                    this.$axios[method](action.url, {
                                            indices: this.applied.massActions.indices
                                        })
                                        .then(response => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'success',
                                                message: response.data.message
                                            });

                                            this.get();
                                        })
                                        .catch((error) => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'error',
                                                message: error.response.data.message
                                            });
                                        });

                                    break;

                                default:
                                    console.error('Method not supported.');

                                    break;
                            }

                            this.applied.massActions.indices = [];
                        }
                    });
                },

                //=======================================================================================
                // Support for previous applied values in datagrids. All code is based on local storage.
                //=======================================================================================

                updateDatagrids() {
                    let datagrids = this.getDatagrids();

                    /**
                     * Strip URL-sourced filters before persisting to localStorage so they
                     * do not leak into future visits without URL params.
                     */
                    let appliedForStorage = this.applied;

                    if (this.urlFilterIndices.length) {
                        appliedForStorage = JSON.parse(JSON.stringify(this.applied));

                        appliedForStorage.filters.columns = appliedForStorage.filters.columns.filter(
                            col => ! this.urlFilterIndices.includes(col.index)
                        );
                    }

                    if (datagrids?.length) {
                        const currentDatagrid = datagrids.find(({ src }) => src === this.src);

                        if (currentDatagrid) {
                            datagrids = datagrids.map(datagrid => {
                                if (datagrid.src === this.src) {
                                    return {
                                        ...datagrid,
                                        requestCount: ++datagrid.requestCount,
                                        available: this.available,
                                        applied: appliedForStorage,
                                        activeFilterIndices: this.activeFilterIndices,
                                        defaultFilterIndices: this.defaultFilterIndices,
                                    };
                                }

                                return datagrid;
                            });
                        } else {
                            datagrids.push(this.getDatagridInitialProperties());
                        }
                    } else {
                        datagrids = [this.getDatagridInitialProperties()];
                    }

                    this.setDatagrids(datagrids);
                },

                getDatagridInitialProperties() {
                    let appliedForStorage = this.applied;

                    if (this.urlFilterIndices.length) {
                        appliedForStorage = JSON.parse(JSON.stringify(this.applied));

                        appliedForStorage.filters.columns = appliedForStorage.filters.columns.filter(
                            col => ! this.urlFilterIndices.includes(col.index)
                        );
                    }

                    return {
                        src: this.src,
                        requestCount: 0,
                        available: this.available,
                        applied: appliedForStorage,
                        activeFilterIndices: this.activeFilterIndices,
                        defaultFilterIndices: this.defaultFilterIndices,
                    };
                },

                getDatagridsStorageKey() {
                    return 'datagrids';
                },

                getDatagrids() {
                    let datagrids = localStorage.getItem(
                        this.getDatagridsStorageKey()
                    );

                    return JSON.parse(datagrids) ?? [];
                },

                setDatagrids(datagrids) {
                    localStorage.setItem(
                        this.getDatagridsStorageKey(),
                        JSON.stringify(datagrids)
                    );
                },

                //================================================================
                // Remaining logic, will check.
                //================================================================

                handleRowClick($event, record) {
                    const selection = $event.view.getSelection();

                    if (selection && selection.toString().length > 0) {
                        return;
                    }

                    this.performAction(record.actions.find(action => action.index === 'edit'), record);
                },

                performAction(action, record) {
                    if (!action) {
                        return;
                    }

                    const method = action.method.toLowerCase();

                    switch (method) {
                        case 'get':
                            if (window.unopim && typeof window.unopim.visit === 'function') {
                                window.unopim.visit(action.url);
                            } else {
                                window.location.href = action.url;
                            }

                            break;

                        case 'copy':
                            this.copyToClipboard(action.url);

                            break;

                        case 'edit-share':
                            this.$emitter.emit('open-share-edit-modal', {
                                url:    action.url,
                                record: record,
                            });

                            break;

                        case 'post':
                        case 'put':
                        case 'patch':
                        case 'delete':
                            this.$emitter.emit('delete' === method ? 'open-delete-modal' : 'open-confirm-modal', {
                                agree: () => {
                                    this.$axios[method](action.url)
                                        .then(response => {
                                            if (response.data.redirect_url) {
                                                this.$navigate(response.data.redirect_url);

                                                return;
                                            }

                                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                            this.get();
                                        })
                                        .catch((error) => {
                                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                                        });
                                }
                            });

                            break;

                        default:
                            console.error('Method not supported.');

                            break;
                    }
                },

                copyToClipboard(text) {
                    const success = () => this.$emitter.emit('add-flash', { type: 'success', message: "@lang('admin::app.components.datagrid.index.link-copied')" });
                    const failure = () => this.$emitter.emit('add-flash', { type: 'error', message: "@lang('admin::app.components.datagrid.index.copy-failed')" });

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(success).catch(failure);

                        return;
                    }

                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();

                    try {
                        document.execCommand('copy') ? success() : failure();
                    } catch (e) {
                        failure();
                    }

                    document.body.removeChild(textarea);
                },

                getActiveFilterColumns() {
                    return this.available.columns.filter(
                        col => col.filterable && this.activeFilterIndices.includes(col.index)
                    );
                },

                getInactiveFilterColumns() {
                    return this.available.columns.filter(
                        col => col.filterable && !this.activeFilterIndices.includes(col.index)
                    );
                },

                addActiveFilter(columnIndex) {
                    if (!this.activeFilterIndices.includes(columnIndex)) {
                        this.activeFilterIndices.push(columnIndex);
                    }

                    this.updateDatagrids();
                },

                filterPickerList() {
                    if (this.filterAttributesSrc) {
                        return this.pickerOptions.filter(col => ! this.activeFilterIndices.includes(col.index));
                    }

                    const search = this.filterPickerSearch.toLowerCase();

                    return this.getInactiveFilterColumns().filter(col => ! search || col.label.toLowerCase().includes(search));
                },

                toggleFilterPicker() {
                    this.showFilterPicker = ! this.showFilterPicker;
                    this.filterPickerSearch = '';

                    if (this.showFilterPicker && this.filterAttributesSrc) {
                        this.loadFilterAttributes(true);
                    }
                },

                onFilterPickerScroll(event) {
                    if (! this.filterAttributesSrc) {
                        return;
                    }

                    const el = event.target;

                    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 48) {
                        this.loadFilterAttributes(false);
                    }
                },

                selectFilterAttribute(column) {
                    if (! this.available.columns.some(c => c.index === column.index)) {
                        this.available.columns.push(column);
                    }

                    this.addedFilterColumns[column.index] = column;

                    this.showFilterPicker = false;

                    this.addActiveFilter(column.index);

                    this.syncAttributeConditions();
                },

                //================================================================
                // Attribute filters (operator + value), added via "Add Filter".
                //================================================================

                /**
                 * An attribute filter renders operator + value inputs instead of the
                 * plain type-based input the default columns use.
                 */
                isAttributeFilter(column) {
                    return !! column.attribute_type
                        && ! this.defaultFilterIndices.includes(column.index);
                },

                attributeCondition(columnIndex) {
                    if (! this.attributeConditions[columnIndex]) {
                        this.attributeConditions[columnIndex] = {
                            operator: '',
                            value:    '',
                            value2:   '',
                            currency: '',
                        };
                    }

                    return this.attributeConditions[columnIndex];
                },

                attributeOperators(column) {
                    return column.operators ?? [];
                },

                /**
                 * Which value input the selected operator needs — 'none' for the empty
                 * checks, a pair of inputs for ranges, and so on.
                 */
                attributeValueControl(column) {
                    const condition = this.attributeCondition(column.index);

                    const operator = this.attributeOperators(column)
                        .find(operator => operator.value === condition.operator);

                    return operator ? operator.control : 'text';
                },

                /**
                 * Boolean columns carry their options inline; option-type attributes fetch
                 * theirs from column.options.route, so they never reach here.
                 */
                attributeValueOptions(column) {
                    return Array.isArray(column.options) ? column.options : (column.options?.params?.options ?? []);
                },

                setAttributeOptionValue(column, event) {
                    const option = event?.target?.value ?? event;

                    this.attributeCondition(column.index).value = option?.code ?? option ?? '';

                    this.applyAttributeCondition(column);
                },

                /**
                 * The dropdowns show a label but store a value, so each needs its selected
                 * label resolved back from the option list.
                 */
                attributeOperatorLabel(column) {
                    const condition = this.attributeCondition(column.index);

                    return this.attributeOperators(column)
                        .find(operator => operator.value === condition.operator)?.label ?? '';
                },

                attributeCurrencyLabel(column) {
                    const condition = this.attributeCondition(column.index);

                    return this.attributeValueOptions(column)
                        .find(option => option.value === condition.currency)?.label ?? '';
                },

                attributeValueLabel(column) {
                    const condition = this.attributeCondition(column.index);

                    return this.attributeValueOptions(column)
                        .find(option => `${option.value}` === `${condition.value}`)?.label ?? '';
                },

                setAttributeCurrency(column, currency) {
                    this.attributeCondition(column.index).currency = currency;

                    this.applyAttributeCondition(column);
                },

                setAttributeValue(column, value) {
                    this.attributeCondition(column.index).value = value;

                    this.applyAttributeCondition(column);
                },

                /**
                 * Rebuild the operator/value inputs from whatever is already applied, so
                 * filters survive a reload or a trip through the URL/localStorage.
                 */
                syncAttributeConditions() {
                    (this.available.columns ?? []).forEach(column => {
                        if (! this.isAttributeFilter(column)) {
                            return;
                        }

                        const condition = this.attributeCondition(column.index);
                        const applied = this.findAppliedColumn(column.index)?.value?.[0];

                        if (applied && typeof applied === 'object') {
                            condition.operator = applied.operator ?? '';
                            condition.value    = applied.value ?? '';
                            condition.value2   = applied.value2 ?? '';
                            condition.currency = applied.currency ?? '';
                        }

                        if (! condition.operator) {
                            condition.operator = this.attributeOperators(column)[0]?.value ?? '';
                        }
                    });
                },

                /**
                 * Reset the value when the operator switches to a different input, so a
                 * range's second value cannot leak into a single-value operator.
                 */
                setAttributeOperator(column, operator) {
                    const condition = this.attributeCondition(column.index);
                    const previous = this.attributeValueControl(column);

                    condition.operator = operator;

                    if (this.attributeValueControl(column) !== previous) {
                        condition.value = '';
                        condition.value2 = '';
                    }

                    this.applyAttributeCondition(column);
                },

                hasConditionValue(value) {
                    return Array.isArray(value) ? value.length > 0 : `${value ?? ''}`.length > 0;
                },

                /**
                 * An incomplete condition is dropped rather than sent, otherwise the grid
                 * would filter on a half-filled row.
                 */
                isConditionComplete(column, condition, control) {
                    if (! condition.operator) {
                        return false;
                    }

                    if (column.type === 'price' && ! condition.currency) {
                        return false;
                    }

                    if (control === 'none') {
                        return true;
                    }

                    if (control === 'number_range' || control === 'date_range') {
                        return this.hasConditionValue(condition.value) && this.hasConditionValue(condition.value2);
                    }

                    return this.hasConditionValue(condition.value);
                },

                applyAttributeCondition(column) {
                    const condition = this.attributeCondition(column.index);
                    const control = this.attributeValueControl(column);

                    this.applied.filters.columns = this.applied.filters.columns.filter(
                        appliedColumn => appliedColumn.index !== column.index
                    );

                    if (! this.isConditionComplete(column, condition, control)) {
                        return;
                    }

                    const payload = {
                        operator: condition.operator,
                        value:    control === 'none' ? '' : condition.value,
                    };

                    if (control === 'number_range' || control === 'date_range') {
                        payload.value2 = condition.value2;
                    }

                    if (column.type === 'price') {
                        payload.currency = condition.currency;
                    }

                    this.applied.filters.columns.push({
                        index: column.index,
                        value: [payload],
                    });
                },

                loadFilterAttributes(reset = false) {
                    if (! this.filterAttributesSrc || this.filterPickerLoading) {
                        return;
                    }

                    if (reset) {
                        this.filterPickerPage = 1;
                    } else {
                        if (this.filterPickerPage >= this.filterPickerLastPage) {
                            return;
                        }

                        this.filterPickerPage += 1;
                    }

                    this.filterPickerLoading = true;

                    this.$axios.get(this.filterAttributesSrc, {
                        params: {
                            query: this.filterPickerSearch,
                            page: this.filterPickerPage,
                        },
                    })
                        .then(response => {
                            const options = response.data.options || [];

                            if (reset) {
                                this.pickerOptions = options;
                            } else {
                                const seen = new Set(this.pickerOptions.map(c => c.index));

                                options.forEach(col => {
                                    if (! seen.has(col.index)) {
                                        this.pickerOptions.push(col);
                                    }
                                });
                            }

                            this.filterPickerLastPage = response.data.lastPage || 1;
                            this.filterPickerLoading = false;
                        })
                        .catch(() => {
                            this.filterPickerLoading = false;
                        });
                },

                removeActiveFilter(columnIndex) {
                    if (this.defaultFilterIndices.includes(columnIndex)) {
                        return;
                    }

                    this.activeFilterIndices = this.activeFilterIndices.filter(i => i !== columnIndex);

                    this.applied.filters.columns = this.applied.filters.columns.filter(
                        col => col.index !== columnIndex
                    );

                    this.updateDatagrids();
                },

                checkAndFilter(column) {
                    this.previousPriceValue = this.priceValue;
                    if (this.priceValue && this.selectedCurrency) {
                        this.filterPage(this.priceValue, column, {
                            field: { name: 'amount', currency: this.selectedCurrency },
                            quickFilter: { isActive: false }
                        });
                        this.priceValue = '';
                    }
                },

                selectCurrency(value,column) {
                    this.selectedCurrency = value;
                    this.priceValue = this.previousPriceValue;
                    this.checkAndFilter(column);
                },
            },
        });
    </script>
@endPushOnce
