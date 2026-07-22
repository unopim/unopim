<div
    class="group border-b border-gray-100 last:border-b-0 dark:border-cherry-800"
    v-for="column in getActiveFilterColumns()"
    :key="column.index"
    :data-datagrid-filter="column.index"
    :data-attribute-filter="isAttributeFilter(column) ? column.index : null"
>
    <div class="flex items-center gap-x-1">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-center gap-x-3 rounded-md py-3 text-left ltr:pr-1 rtl:pl-1"
            data-filter-toggle
            :aria-expanded="isFilterExpanded(column.index) ? 'true' : 'false'"
            @click="toggleFilterEditor(column.index)"
        >
            <span
                class="shrink-0 truncate text-sm font-medium text-gray-800 dark:text-white"
                data-filter-name
                v-text="filterLabel(column)"
            >
            </span>

            <span
                v-show="filterHasValue(column) && !isFilterExpanded(column.index)"
                class="min-w-0 flex-1 truncate text-right text-sm text-primary-700 dark:text-primary-400"
                data-filter-summary
                :title="filterSummary(column)"
                v-text="filterSummary(column)"
            >
            </span>

            <span
                class="icon-chevron-down shrink-0 text-2xl text-gray-400 transition-transform dark:text-gray-500 ltr:ml-auto rtl:mr-auto"
                :class="isFilterExpanded(column.index) ? 'rotate-180' : ''"
            ></span>
        </button>

        <span
            v-if="!defaultFilterIndices.includes(column.index)"
            class="icon-cancel cursor-pointer text-lg text-gray-300 opacity-0 transition-all hover:text-gray-600 group-hover:opacity-100 dark:text-gray-600 dark:hover:text-gray-300"
            data-remove-filter
            @click.stop="removeActiveFilter(column.index)"
            title="@lang('admin::app.components.datagrid.filters.remove-filter')"
        ></span>
    </div>

    <div class="pb-3" v-show="isFilterExpanded(column.index)">
        <button
            type="button"
            v-if="filterHasValue(column)"
            class="mb-2 text-xs font-medium text-primary-700 transition-all hover:underline dark:text-primary-400"
            data-clear-filter
            @click="clearFilter(column)"
        >
            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
        </button>

        <div v-if="isAttributeFilter(column)">
            <div class="grid grid-cols-2 gap-2">
                <x-admin::dropdown v-if="column.type === 'price'">
                    <x-slot:toggle>
                        <button
                            type="button"
                            data-filter-currency
                            class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span
                                class="text-sm"
                                :class="attributeCondition(column.index).currency ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-400'"
                                v-text="attributeCurrencyLabel(column) || '@lang('admin::app.components.datagrid.filters.select')'"
                            >
                            </span>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="option in attributeValueOptions(column)"
                            v-text="option.label"
                            @click="setAttributeCurrency(column, option.value)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>

                <x-admin::dropdown ::class="attributeOperatorSpansRow(column) ? 'col-span-2' : ''">
                    <x-slot:toggle>
                        <button
                            type="button"
                            data-filter-operator
                            class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span
                                class="text-sm"
                                :class="attributeCondition(column.index).operator ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-400'"
                                v-text="attributeOperatorLabel(column) || '@lang('admin::app.components.datagrid.filters.select')'"
                            >
                            </span>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="operator in attributeOperators(column)"
                            v-text="operator.label"
                            @click="setAttributeOperator(column, operator.value)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>

                <template v-if="attributeValueControl(column) === 'none'"></template>

                <template v-else-if="attributeValueControl(column) === 'boolean'">
                    <x-admin::dropdown ::class="attributeValueSpansRow(column) ? 'col-span-2' : ''">
                        <x-slot:toggle>
                            <button
                                type="button"
                                data-filter-value
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            >
                                <span
                                    class="text-sm"
                                    :class="hasConditionValue(attributeCondition(column.index).value) ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-400'"
                                    v-text="attributeValueLabel(column) || '@lang('admin::app.components.datagrid.filters.select')'"
                                >
                                </span>

                                <span class="icon-chevron-down text-2xl"></span>
                            </button>
                        </x-slot>

                        <x-slot:menu>
                            <x-admin::dropdown.menu.item
                                v-for="option in attributeValueOptions(column)"
                                v-text="option.label"
                                @click="setAttributeValue(column, option.value)"
                            >
                            </x-admin::dropdown.menu.item>
                        </x-slot>
                    </x-admin::dropdown>
                </template>

                <template v-else-if="attributeValueControl(column) === 'options'">
                    {{-- the multiselect has several root nodes, so the span goes on a wrapper --}}
                    <div class="col-span-2 min-w-0">
                        <v-async-select-handler
                            :key="'condition-value-' + column.index + '-' + attributeCondition(column.index).operator"
                            :name="'condition_' + column.index"
                            multiple="true"
                            :onselect="false"
                            :track-by="'code'"
                            :label-by="'label'"
                            :list-route="column.options.route"
                            :query-params="column.options.params"
                            :value="attributeOptionValue(column)"
                            placeholder="@lang('admin::app.components.datagrid.filters.select')"
                            @input="setAttributeOptionValue(column, $event)"
                        >
                        </v-async-select-handler>
                    </div>
                </template>

                <template v-else-if="attributeValueControl(column) === 'date_range'">
                    <div class="col-span-2 flex items-center gap-2">
                        <x-admin::flat-picker.date ::allow-input="false">
                            <input
                                data-filter-value
                                autocomplete="off"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-900 dark:text-gray-300 dark:hover:border-gray-400"
                                :value="attributeCondition(column.index).value"
                                placeholder="@lang('admin::app.settings.data-transfer.exports.create.range-from')"
                                @change="setAttributeConditionValue(column, 'value', $event.target.value)"
                            />
                        </x-admin::flat-picker.date>

                        <span class="text-gray-400">&ndash;</span>

                        <x-admin::flat-picker.date ::allow-input="false">
                            <input
                                data-filter-value2
                                autocomplete="off"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-900 dark:text-gray-300 dark:hover:border-gray-400"
                                :value="attributeCondition(column.index).value2"
                                placeholder="@lang('admin::app.settings.data-transfer.exports.create.range-to')"
                                @change="setAttributeConditionValue(column, 'value2', $event.target.value)"
                            />
                        </x-admin::flat-picker.date>
                    </div>
                </template>

                <template v-else-if="attributeValueControl(column) === 'number_range'">
                    <div class="col-span-2 flex items-center gap-2">
                        <input
                            type="number"
                            data-filter-value
                            class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            v-model="attributeCondition(column.index).value"
                            placeholder="@lang('admin::app.settings.data-transfer.exports.create.range-from')"
                            @input="applyAttributeCondition(column)"
                        />

                        <span class="text-gray-400">&ndash;</span>

                        <input
                            type="number"
                            data-filter-value2
                            class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            v-model="attributeCondition(column.index).value2"
                            placeholder="@lang('admin::app.settings.data-transfer.exports.create.range-to')"
                            @input="applyAttributeCondition(column)"
                        />
                    </div>
                </template>

                <template v-else-if="attributeValueControl(column) === 'date'">
                    <x-admin::flat-picker.date ::allow-input="false">
                        <input
                            data-filter-value
                            autocomplete="off"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-900 dark:text-gray-300 dark:hover:border-gray-400"
                            :value="attributeCondition(column.index).value"
                            :placeholder="filterLabel(column)"
                            @change="setAttributeConditionValue(column, 'value', $event.target.value)"
                        />
                    </x-admin::flat-picker.date>
                </template>

                <template v-else>
                    <input
                        :type="attributeValueControl(column)"
                        data-filter-value
                        :class="attributeValueSpansRow(column) ? 'col-span-2' : ''"
                        class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        v-model="attributeCondition(column.index).value"
                        :placeholder="filterLabel(column)"
                        @input="applyAttributeCondition(column)"
                    />
                </template>
            </div>
        </div>

        <!-- Boolean -->
        <div v-else-if="column.type === 'boolean'">
            <div>
                <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <div
                            class="flex min-h-[38px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-md border bg-white px-2.5 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                        >
                            <template v-if="hasAnyAppliedColumnValues(column.index)">
                                <span
                                    class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700"
                                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                >
                                    <span v-text="column.options.find((option => option.value == appliedColumnValue))?.label"></span>

                                    <span
                                        class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-700"
                                        @click.stop="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                    ></span>
                                </span>
                            </template>

                            <span
                                v-else
                                class="text-sm text-gray-400 dark:text-gray-400"
                                v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                            >
                            </span>

                            <span class="icon-chevron-down text-2xl ltr:ml-auto rtl:mr-auto"></span>
                        </div>
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
        </div>

        <!-- Dropdown -->
        <div v-else-if="column.type === 'dropdown'">
            <!-- Basic -->
            <div v-if="column.options.type === 'basic'">
                <div>
                    <x-admin::dropdown>
                        <!-- Dropdown Toggler -->
                        <x-slot:toggle>
                            <div
                                class="flex min-h-[38px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-md border bg-white px-2.5 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                            >
                                <template v-if="hasAnyAppliedColumnValues(column.index)">
                                    <span
                                        class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700"
                                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                    >
                                        <span v-text="column.options.params.options.find((option => option.value == appliedColumnValue))?.label"></span>

                                        <span
                                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-700"
                                            @click.stop="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                        ></span>
                                    </span>
                                </template>

                                <span
                                    v-else
                                    class="text-sm text-gray-400 dark:text-gray-400"
                                    v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                                >
                                </span>

                                <span class="icon-chevron-down text-2xl ltr:ml-auto rtl:mr-auto"></span>
                            </div>
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
            </div>

            <!-- Searchable -->
            <div v-else-if="column.options.type === 'searchable'">
            <v-datagrid-searchable-dropdown
                    :datagrid-id="available.id"
                    :column="column"
                    :applied-values="getAppliedColumnValues(column.index)"
                    @set-values="setAppliedColumnValues(column, $event)"
                >
                </v-datagrid-searchable-dropdown>
            </div>

            <!-- sync -->
            <div v-else-if="column.options.type === 'sync'">
            <v-datagrid-sync-dropdown
                    :datagrid-id="available.id"
                    :column="column"
                    :applied-values="getAppliedColumnValues(column.index)"
                    @set-values="setAppliedColumnValues(column, $event)"
                >
                </v-datagrid-sync-dropdown>
            </div>
        </div>

        <!-- Date Range -->
        <div v-else-if="column.type === 'date_range'">
            <div class="grid grid-cols-2 gap-1.5">
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
                        :placeholder="filterLabel(column)"
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
                        :placeholder="filterLabel(column)"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.date>

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-primary-100 px-2 py-1 font-semibold text-primary-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' to ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-primary-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Price -->
        <div v-else-if="column.type === 'price'">
            <div class="grid grid-cols-2 gap-2">
                    <input
                        type="text"
                        class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        :name="column.index"
                        :placeholder="filterLabel(column)"
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
                                class="text-sm"
                                :class="selectedCurrency ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-400'"
                                v-text="selectedCurrency ? column.options.find(o => o.value === selectedCurrency)?.label || selectedCurrency : '@lang('admin::app.components.datagrid.filters.select')'"
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
                            @click="selectCurrency(option.value, column)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>
                </div>

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-primary-100 px-2 py-1 font-semibold text-primary-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' - ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-primary-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
            </div>
        </div>

        <!-- Date Time Range -->
        <div v-else-if="column.type === 'datetime_range'">
            <div class="grid grid-cols-2 gap-1.5">
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
                        :placeholder="filterLabel(column)"
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
                        :placeholder="filterLabel(column)"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.datetime>

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-primary-100 px-2 py-1 font-semibold text-primary-700"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' to ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-primary-700"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Integer/Number -->
        <div v-else-if="column.type === 'integer'">
            <div class="grid">
                <v-form-field
                    :field="filterFields[column.index]"
                    context="filter"
                    model-value=""
                    @update:model-value="filterPage($event, column)"
                />
            </div>

            <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                <p
                    class="flex items-center rounded bg-primary-100 px-2 py-1 font-semibold text-primary-700"
                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                >
                    <span v-text="appliedColumnValue"></span>

                    <span
                        class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-primary-700"
                        @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                    >
                    </span>
                </p>
            </div>
        </div>

        <!-- Rest -->
        <div v-else>
            <div class="grid">
                <v-form-field
                    :field="filterFields[column.index]"
                    context="filter"
                    model-value=""
                    @update:model-value="filterPage($event, column)"
                />
            </div>

            <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                <p
                    class="flex items-center rounded bg-primary-100 px-2 py-1 font-semibold text-primary-700"
                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                >
                    <span v-text="appliedColumnValue"></span>

                    <span
                        class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-primary-700"
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
        <div>
            <v-async-select-handler
                :name="'filter_' + column.index"
                multiple="true"
                :onselect="false"
                :track-by="'id'"
                :label-by="'label'"
                :list-route="column.options.route"
                :query-params="column.options.params"
                :value="valueString"
                @input="onInput"
            >
            </v-async-select-handler>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid-searchable-dropdown', {
            template: '#v-datagrid-searchable-dropdown-template',

            props: ['column', 'datagridId', 'appliedValues'],

            computed: {
                valueString() {
                    return (this.appliedValues ?? []).join(',');
                },
            },

            methods: {
                onInput(event) {
                    let parsed = [];

                    try {
                        parsed = event ? JSON.parse(event) : [];
                    } catch (error) {
                        parsed = [];
                    }

                    const values = Array.isArray(parsed)
                        ? parsed.map(option => option?.id ?? option).filter(value => value !== undefined && value !== null && value !== '')
                        : [];

                    this.$emit('set-values', values);
                },
            },
        });
    </script>

    <script type="text/x-template" id="v-datagrid-sync-dropdown-template">
        <div>
            <v-async-select-handler
                :name="'filter_' + column.index"
                multiple="true"
                :onselect="false"
                :track-by="'code'"
                :label-by="'label'"
                :list-route="column.options.route"
                :query-params="column.options.params"
                :value="valueString"
                @input="onInput"
            >
            </v-async-select-handler>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid-sync-dropdown', {
            template: '#v-datagrid-sync-dropdown-template',

            props: ['datagridId', 'column', 'appliedValues'],

            computed: {
                valueString() {
                    return (this.appliedValues ?? []).join(',');
                },
            },

            methods: {
                onInput(event) {
                    let parsed = [];

                    try {
                        parsed = event ? JSON.parse(event) : [];
                    } catch (error) {
                        parsed = [];
                    }

                    const values = Array.isArray(parsed)
                        ? parsed.map(option => option?.code ?? option).filter(value => value !== undefined && value !== null && value !== '')
                        : [];

                    this.$emit('set-values', values);
                },
            },
        });
    </script>
@endpushOnce
