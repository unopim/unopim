<div v-for="column in available.columns">
    <div v-if="column.filterable && activeFilterIndices.includes(column.index)" class="mb-6">
        <div
            v-if="isAttributeFilter(column)"
            :data-attribute-filter="column.index"
        >
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                    v-text="column.label"
                >
                </p>

                <span
                    class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    @click="removeActiveFilter(column.index)"
                    title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                ></span>
            </div>

            <div class="mt-1.5 grid gap-2">
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

                <x-admin::dropdown>
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
                    <x-admin::dropdown>
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
                </template>

                <template v-else-if="attributeValueControl(column) === 'number_range' || attributeValueControl(column) === 'date_range'">
                    <div class="flex items-center gap-2">
                        <input
                            :type="attributeValueControl(column) === 'date_range' ? 'date' : 'number'"
                            data-filter-value
                            class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            v-model="attributeCondition(column.index).value"
                            placeholder="@lang('admin::app.settings.data-transfer.exports.create.range-from')"
                            @change="applyAttributeCondition(column)"
                        />

                        <span class="text-gray-400">&ndash;</span>

                        <input
                            :type="attributeValueControl(column) === 'date_range' ? 'date' : 'number'"
                            data-filter-value2
                            class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            v-model="attributeCondition(column.index).value2"
                            placeholder="@lang('admin::app.settings.data-transfer.exports.create.range-to')"
                            @change="applyAttributeCondition(column)"
                        />
                    </div>
                </template>

                <template v-else>
                    <input
                        :type="attributeValueControl(column)"
                        data-filter-value
                        class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        v-model="attributeCondition(column.index).value"
                        :placeholder="column.label"
                        @change="applyAttributeCondition(column)"
                    />
                </template>
            </div>
        </div>

        <!-- Boolean -->
        <div v-else-if="column.type === 'boolean'">
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                    v-text="column.label"
                >
                </p>

                <div class="flex items-center gap-x-1.5">
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>

                    <span
                        v-if="!defaultFilterIndices.includes(column.index)"
                        class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        @click="removeActiveFilter(column.index)"
                        title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                    ></span>
                </div>
            </div>

            <div class="mt-1.5">
                <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <div
                            class="flex min-h-[38px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-md border bg-white px-2.5 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                        >
                            <template v-if="hasAnyAppliedColumnValues(column.index)">
                                <span
                                    class="flex items-center rounded bg-violet-100 px-2 py-0.5 text-sm font-semibold text-violet-700"
                                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                >
                                    <span v-text="column.options.find((option => option.value == appliedColumnValue))?.label"></span>

                                    <span
                                        class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1 rtl:mr-1 dark:!text-violet-700"
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
                <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                        v-text="column.label"
                    >
                    </p>

                    <div class="flex items-center gap-x-1.5">
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                            @click="removeAppliedColumnAllValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>

                        <span
                            v-if="!defaultFilterIndices.includes(column.index)"
                            class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            @click="removeActiveFilter(column.index)"
                            title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                        ></span>
                    </div>
                </div>

                <div class="mt-1.5">
                    <x-admin::dropdown>
                        <!-- Dropdown Toggler -->
                        <x-slot:toggle>
                            <div
                                class="flex min-h-[38px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-md border bg-white px-2.5 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                            >
                                <template v-if="hasAnyAppliedColumnValues(column.index)">
                                    <span
                                        class="flex items-center rounded bg-violet-100 px-2 py-0.5 text-sm font-semibold text-violet-700"
                                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                    >
                                        <span v-text="column.options.params.options.find((option => option.value == appliedColumnValue))?.label"></span>

                                        <span
                                            class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1 rtl:mr-1 dark:!text-violet-700"
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
                <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                        v-text="column.label"
                    >
                    </p>

                    <div class="flex items-center gap-x-1.5">
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                            @click="removeAppliedColumnAllValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>

                        <span
                            v-if="!defaultFilterIndices.includes(column.index)"
                            class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            @click="removeActiveFilter(column.index)"
                            title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                        ></span>
                    </div>
                </div>

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
                <div class="flex items-center justify-between">
                    <p
                        class="text-sm font-medium leading-6 dark:text-white text-gray-800"
                        v-text="column.label"
                    >
                    </p>

                    <div class="flex items-center gap-x-1.5">
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                            @click="removeAppliedColumnAllValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>

                        <span
                            v-if="!defaultFilterIndices.includes(column.index)"
                            class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            @click="removeActiveFilter(column.index)"
                            title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                        ></span>
                    </div>
                </div>

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
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white"
                    v-text="column.label"
                >
                </p>

                <div class="flex items-center gap-x-1.5">
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>

                    <span
                        v-if="!defaultFilterIndices.includes(column.index)"
                        class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        @click="removeActiveFilter(column.index)"
                        title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                    ></span>
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

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
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

                    <div class="flex items-center gap-x-1.5">
                        <p
                            class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                            v-if="hasAnyAppliedColumnValues(column.index)"
                            @click="removeAppliedColumnAllValues(column.index)"
                        >
                            @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                        </p>

                        <span
                            v-if="!defaultFilterIndices.includes(column.index)"
                            class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            @click="removeActiveFilter(column.index)"
                            title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                        ></span>
                    </div>
                </div>

                <div class="mt-1.5 grid grid-cols-2 gap-2">
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

                <div class="flex items-center gap-x-1.5">
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>

                    <span
                        v-if="!defaultFilterIndices.includes(column.index)"
                        class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        @click="removeActiveFilter(column.index)"
                        title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                    ></span>
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

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
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

        <!-- Integer/Number -->
        <div v-else-if="column.type === 'integer'">
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white"
                    v-text="column.label"
                >
                </p>

                <div class="flex items-center gap-x-1.5">
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>

                    <span
                        v-if="!defaultFilterIndices.includes(column.index)"
                        class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        @click="removeActiveFilter(column.index)"
                        title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                    ></span>
                </div>
            </div>

            <div class="mt-1.5 grid">
                <v-form-field
                    :field="filterFields[column.index]"
                    context="filter"
                    model-value=""
                    @update:model-value="filterPage($event, column)"
                />
            </div>

            <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
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

        <!-- Rest -->
        <div v-else>
            <div class="flex items-center justify-between">
                <p
                    class="text-sm font-medium leading-6 dark:text-white"
                    v-text="column.label"
                >
                </p>

                <div class="flex items-center gap-x-1.5">
                    <p
                        class="cursor-pointer text-xs font-medium leading-6 text-violet-700"
                        v-if="hasAnyAppliedColumnValues(column.index)"
                        @click="removeAppliedColumnAllValues(column.index)"
                    >
                        @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                    </p>

                    <span
                        v-if="!defaultFilterIndices.includes(column.index)"
                        class="icon-cancel cursor-pointer text-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        @click="removeActiveFilter(column.index)"
                        title="@lang('admin::app.components.datagrid.filters.remove-filter')"
                    ></span>
                </div>
            </div>

            <div class="mt-1.5 grid">
                <v-form-field
                    :field="filterFields[column.index]"
                    context="filter"
                    model-value=""
                    @update:model-value="filterPage($event, column)"
                />
            </div>

            <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
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
        <div class="mt-1.5">
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
        <div class="mt-1.5">
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
