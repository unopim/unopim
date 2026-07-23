<template v-if="isLoading">
    <x-admin::shimmer.datagrid.toolbar />
</template>

<template v-else>
    <div class="datagrid-toolbar mt-7 flex items-center justify-between gap-4 max-md:flex-wrap">
        <!-- Left Toolbar -->
        <div class="flex gap-x-1">
            <div
                class="flex w-full items-center gap-x-1"
                v-if="applied.massActions.indices.length"
            >
                <x-admin::dropdown>
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-black"
                        >
                            <span>
                                @lang('admin::app.components.datagrid.toolbar.mass-actions.select-action')
                            </span>

                            <span class="icon-chevron-down text-2xl" aria-hidden="true"></span>
                        </button>
                    </x-slot>

                    <x-slot:menu class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                        <template v-for="(massAction, massActionIndex) in available.massActions">
                            <li
                                class="group/item relative overflow-visible"
                                :key="massActionIndex"
                                v-if="massAction?.options?.length"
                            >
                                <a
                                    class="flex gap-1.5 justify-between whitespace-no-wrap cursor-not-allowed rounded-t px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-cherry-800"
                                    href="javascript:void(0);"
                                >
                                    <i
                                        class="text-2xl"
                                        :class="massAction.icon"
                                        v-if="massAction?.icon"
                                    >
                                    </i>

                                    <span>
                                        @{{ massAction.title }}
                                    </span>

                                    <i class="icon-arrow-left text-xl -mt-px" aria-hidden="true"></i>
                                </a>

                                <ul class="absolute ltr:left-full rtl:right-full top-0 z-10 hidden w-max min-w-[150px] border dark:border-cherry-800 rounded bg-white dark:bg-cherry-800 shadow-[0_5px_20px_rgba(0,0,0,0.15)] group-hover/item:block">
                                    <li
                                        v-for="(option, optionIndex) in massAction.options"
                                        :key="optionIndex"
                                    >
                                        <a
                                            class="whitespace-no-wrap block rounded-t px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-cherry-800"
                                            href="javascript:void(0);"
                                            v-text="option.label"
                                            @click="performMassAction(massAction, option)"
                                        >
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li
                                v-else
                                :key="massActionIndex"
                            >
                                <a
                                    class="flex gap-1.5 whitespace-no-wrap rounded-b px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-cherry-800"
                                    href="javascript:void(0);"
                                    @click="performMassAction(massAction)"
                                >
                                    <i
                                        class="text-2xl"
                                        :class="massAction.icon"
                                        v-if="massAction?.icon"
                                    >
                                    </i>

                                    @{{ massAction.title }}
                                </a>
                            </li>
                        </template>
                    </x-slot>
                </x-admin::dropdown>

                <div class="ltr:pl-2.5 rtl:pr-2.5 flex items-center gap-2.5">
                    <p class="text-sm font-light text-gray-800 dark:text-white">
                        @{{ @json(trans('admin::app.components.datagrid.toolbar.length-of')).replace(':length', applied.massActions.indices.length) }}

                        @{{ @json(trans('admin::app.components.datagrid.toolbar.selected')).replace(':total', available.meta.total) }}
                    </p>

                    <span
                        class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                        v-if="isAllMatchingSelected && available.meta.last_page > 1"
                    >
                        @lang('admin::app.components.datagrid.toolbar.all-selected')
                    </span>
                </div>
            </div>

            <div
                class="flex w-full items-center gap-x-1"
                v-else
            >
                <div class="flex max-w-[445px] items-center max-sm:w-full max-sm:max-w-full">
                    <x-admin::search
                        name="search"
                        ::value="getAppliedColumnValues('all')"
                        ::placeholder="available.searchPlaceholder"
                        @keydown.enter.prevent="filterPage"
                    />
                </div>

                <div class="ltr:pl-2.5 rtl:pr-2.5">
                    <p class="text-sm font-light text-gray-800 dark:text-white">
                        @{{ @json(trans('admin::app.components.datagrid.toolbar.results')).replace(':total', available.meta.total) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-x-4">
             <template v-if="available.meta.managedColumn?.enabled">
                <x-admin::datagrid.manage-columns />
             </template>
            
            <x-admin::dropdown v-if="viewsSrc" ref="savedFilters" ::close-on-click="false">
                <x-slot:toggle>
                    <button
                        type="button"
                        data-grid-views
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400"
                    >
                        <span class="icon-star text-2xl" aria-hidden="true"></span>

                        <span class="max-w-[10rem] truncate" v-text="appliedViewName()"></span>

                        <span class="icon-chevron-down text-2xl"></span>
                    </button>
                </x-slot>

                <x-slot:menu class="!p-0 w-72 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border dark:border-cherry-800">
                    <div class="border-b border-gray-200 p-2 dark:border-cherry-800">
                        <div class="relative">
                            <input
                                type="text"
                                data-view-search
                                class="w-full rounded-md border bg-white py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400 ltr:pl-3 ltr:pr-8 rtl:pl-8 rtl:pr-3"
                                placeholder="@lang('admin::app.components.datagrid.filters.search-filter')"
                                v-model="viewSearch"
                            />

                            <span class="icon-search pointer-events-none absolute top-1.5 text-xl text-gray-400 ltr:right-2 rtl:left-2"></span>
                        </div>
                    </div>

                    <div
                        class="flex cursor-pointer items-center gap-x-2 border-b border-gray-200 px-4 py-2 text-sm text-gray-600 transition-all hover:bg-primary-50 dark:border-cherry-800 dark:text-gray-300 dark:hover:bg-cherry-800"
                        data-clear-filters
                        @click="clearAllFilters()"
                    >
                        <span class="icon-cancel text-xl"></span>

                        @lang('admin::app.components.datagrid.filters.saved-filters.clear')
                    </div>

                    <div class="max-h-64 overflow-auto py-1">
                        <p
                            v-if="viewsLoading"
                            class="flex justify-center px-4 py-3"
                        >
                            <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-gray-300 border-t-transparent dark:border-gray-500"></span>
                        </p>

                        <p
                            v-else-if="! savedViews.length"
                            class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500"
                        >
                            @lang('admin::app.components.datagrid.filters.saved-filters.none')
                        </p>

                        <div
                            v-for="view in savedViews"
                            :key="view.id"
                            class="flex cursor-pointer items-center gap-x-2 px-4 py-2 text-sm transition-all hover:bg-primary-50 dark:hover:bg-cherry-800"
                            :class="view.id === activeViewId()
                                ? 'bg-primary-50 font-medium text-primary-700 dark:bg-cherry-800 dark:text-primary-400'
                                : 'text-gray-600 dark:text-gray-300'"
                            data-grid-view
                            @click="applyView(view)"
                        >
                            <span
                                class="text-lg text-primary-700 dark:text-primary-400"
                                :class="view.id === activeViewId() ? 'icon-done' : 'opacity-0 icon-done'"
                            ></span>

                            <span class="min-w-0 flex-1 truncate" v-text="view.name"></span>

                            <span
                                v-if="view.is_shared"
                                class="rounded bg-primary-100 px-1.5 py-0.5 text-xs font-medium text-primary-700"
                            >
                                @lang('admin::app.components.datagrid.filters.saved-filters.shared')
                            </span>

                            <span
                                v-if="view.is_owner"
                                class="icon-delete text-xl text-gray-400 transition-all hover:text-red-600 dark:text-gray-500"
                                data-delete-view
                                :title="'@lang('admin::app.components.datagrid.filters.saved-filters.delete')'"
                                @click.stop="deleteView(view)"
                            ></span>
                        </div>
                    </div>

                    <div
                        class="grid gap-2 border-t border-gray-200 p-3 dark:border-cherry-800"
                        data-save-filter-form
                        v-if="hasUnsavedFilters()"
                    >
                        <p class="text-xs font-medium text-gray-800 dark:text-white">
                            @lang('admin::app.components.datagrid.filters.saved-filters.unsaved')
                        </p>

                        <input
                            type="text"
                            data-view-name
                            class="w-full rounded-md border bg-white px-3 py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                            placeholder="@lang('admin::app.components.datagrid.filters.saved-filters.name')"
                            v-model="viewName"
                            @keyup.enter="saveView()"
                        />

                        <x-admin::form.control-group class="!mb-0 flex items-center gap-2">
                            <x-admin::form.control-group.control
                                type="checkbox"
                                name="share_saved_filter"
                                id="share_saved_filter"
                                for="share_saved_filter"
                                value="1"
                                ::checked="viewShared"
                                @change="viewShared = $event.target.checked"
                            />

                            <label
                                class="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300"
                                for="share_saved_filter"
                            >
                                @lang('admin::app.components.datagrid.filters.saved-filters.share')
                            </label>
                        </x-admin::form.control-group>

                        <button
                            type="button"
                            data-save-view
                            class="secondary-button block w-full text-center"
                            :class="! viewName.trim() ? 'cursor-not-allowed opacity-50' : ''"
                            :disabled="! viewName.trim()"
                            @click="saveView()"
                        >
                            @lang('admin::app.components.datagrid.filters.saved-filters.save')
                        </button>
                    </div>
                </x-slot>
            </x-admin::dropdown>

            <x-admin::drawer width="350px" ref="filterDrawer">
                <x-slot:toggle>
                    <div>
                        <div
                            class="relative inline-flex w-full max-w-max ltr:pl-3 rtl:pr-3 ltr:pr-5 rtl:pl-5 cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-1 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-2"
                            :class="{'[&>*]:text-primary-700 [&>*]:dark:text-white': applied.filters.columns.length > 1}"
                            v-if="available?.columns?.filter(col => col?.filterable == true)?.length"
                        >
                            <span class="icon-filter text-2xl" aria-hidden="true"></span>

                            <span>
                                @lang('admin::app.components.datagrid.toolbar.filter.title')
                            </span>

                            <span
                                class="icon-dot absolute top-0.5 right-1 text-2xl font-bold"
                                v-if="applied.filters.columns.length > 1"
                            ></span>
                        </div>

                        <div class="z-10 hidden w-full divide-y divide-gray-100 rounded bg-white dark:bg-cherry-800 shadow">
                        </div>
                    </div>
                </x-slot>

                <x-slot:header>
                    <div class="flex justify-between items-center p-3">
                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.components.datagrid.filters.title')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!p-5">
                    <x-admin::datagrid.filters />

                    <div class="mb-4" v-if="getInactiveFilterColumns().length || filterAttributesSrc">
                        <button
                            type="button"
                            class="flex items-center gap-1.5 w-full justify-center rounded-md border border-dashed border-gray-300 dark:border-cherry-800 px-3 py-2 text-sm font-medium text-primary-700 dark:text-primary-400 transition-all hover:border-primary-400 hover:bg-primary-50 dark:hover:border-primary-400 dark:hover:bg-cherry-800"
                            @click="toggleFilterPicker()"
                        >
                            <span class="icon-add text-lg"></span>

                            @lang('admin::app.components.datagrid.filters.add-filter')
                        </button>

                        <div
                            v-if="showFilterPicker"
                            class="mt-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 overflow-hidden"
                        >
                            <div class="sticky top-0 p-2 bg-white dark:bg-cherry-800 border-b dark:border-cherry-900">
                                <div class="relative">
                                    <input
                                        type="text"
                                        class="w-full rounded-md border dark:border-cherry-900 bg-white dark:bg-cherry-900 ltr:pl-3 rtl:pr-3 ltr:pr-8 rtl:pl-8 py-1.5 text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400"
                                        placeholder="@lang('admin::app.components.datagrid.filters.search-filter')"
                                        v-model="filterPickerSearch"
                                        ref="filterPickerSearchInput"
                                    />

                                    <span class="icon-search text-xl absolute ltr:right-2 rtl:left-2 top-1.5 text-gray-400 pointer-events-none"></span>
                                </div>
                            </div>

                            <div class="max-h-48 overflow-auto" @scroll="onFilterPickerScroll">
                                <p
                                    v-for="column in filterPickerList()"
                                    :key="column.index"
                                    class="cursor-pointer px-3 py-2 text-sm text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 dark:hover:bg-cherry-900"
                                    v-text="filterLabel(column)"
                                    @click="selectFilterAttribute(column)"
                                ></p>

                                <p
                                    v-if="filterPickerLoading"
                                    class="flex justify-center px-3 py-2"
                                >
                                    <span class="inline-block w-3 h-3 border-2 border-gray-300 dark:border-gray-500 border-t-transparent rounded-full animate-spin"></span>
                                </p>

                                <p
                                    v-if="! filterPickerLoading && filterPickerList().length === 0"
                                    class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500 text-center"
                                >
                                    @lang('admin::app.components.datagrid.filters.dropdown.searchable.no-results')
                                </p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="primary-button block w-full text-center"
                        @click="runFilters()"
                    >
                        @lang('admin::app.components.datagrid.filters.save')
                    </button>
                </x-slot>
            </x-admin::drawer>

            <div class="flex items-center gap-x-2">
                <x-admin::dropdown>
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            aria-label="@lang('admin::app.components.datagrid.toolbar.per-page')"
                        >
                            <span v-text="applied.pagination.perPage"></span>

                            <span class="icon-chevron-down text-2xl" aria-hidden="true"></span>
                        </button>
                    </x-slot>

                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="perPageOption in available.meta.per_page_options"
                            ::key="perPageOption"
                            v-text="perPageOption"
                            @click="changePerPageOption(perPageOption)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>

                <p class="whitespace-nowrap text-gray-600 dark:text-gray-300 max-sm:hidden">
                    @lang('admin::app.components.datagrid.toolbar.per-page')
                </p>

                <input
                    type="text"
                    class="inline-flex min-h-[38px] max-w-[60px] appearance-none items-center justify-center gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-3 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                    :value="available.meta.current_page"
                    @change="changePage(parseInt($event.target.value))"
                    aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.page-number')"
                >

                <div class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                    <span> @lang('admin::app.components.datagrid.toolbar.of') </span>

                    <span v-text="available.meta.last_page"></span>
                </div>

                <div class="flex items-center gap-1" role="navigation" aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.page-number')">
                    <button
                        type="button"
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-primary-100 dark:hover:bg-gray-800 active:border-gray-300"
                        @click="changePage('first')"
                        title="@lang('admin::app.components.datagrid.toolbar.pagination.first-page')"
                        aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.first-page')"
                    >
                        <span class="text-2xl" aria-hidden="true">&#171;</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-primary-100 dark:hover:bg-gray-800 active:border-gray-300"
                        @click="changePage('previous')"
                        title="@lang('admin::app.components.datagrid.toolbar.pagination.previous-page')"
                        aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.previous-page')"
                    >
                        <span class="text-2xl" aria-hidden="true">&#8249;</span>
                    </button>

                    <button
                        type="button"
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-primary-100 dark:hover:bg-gray-800 active:border-gray-300"
                        @click="changePage('next')"
                        title="@lang('admin::app.components.datagrid.toolbar.pagination.next-page')"
                        aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.next-page')"
                    >
                        <span class="text-2xl" aria-hidden="true">&#8250;</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-primary-100 dark:hover:bg-gray-800 active:border-gray-300"
                        @click="changePage('last')"
                        title="@lang('admin::app.components.datagrid.toolbar.pagination.last-page')"
                        aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.last-page')"
                    >
                        <span class="text-2xl" aria-hidden="true">&#187;</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
