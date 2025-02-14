<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.categories.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.catalog.categories.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            {!! view_render_event('unopim.admin.catalog.categories.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.categories.create'))
                <a href="{{ route('admin.catalog.categories.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.categories.index.add-btn')
                    </div>
                </a>
            @endif

            {!! view_render_event('unopim.admin.catalog.categories.index.create-button.after') !!}
        </div>        
    </div>

    {!! view_render_event('unopim.admin.catalog.categories.list.before') !!}

    <x-admin::datagrid
        src="{{ route('admin.catalog.categories.index') }}" 
    >
        @php
            $hasPermission = bouncer()->hasPermission('catalog.categories.edit') || bouncer()->hasPermission('catalog.categories.delete');
        @endphp
        
        <template #header="{ columns, records, sortPage, selectAllRecords, applied, isLoading, actions}">
            <template v-if="! isLoading">
                <div
                    class="row grid grid-rows-1 gap-2.5 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold"
                    :style="'grid-template-columns: 2fr repeat(' + (actions.length ? columns.length : (columns.length -1 )) + ', 1fr)'"
                >
                    <div
                        class="flex items-center select-none"
                        v-for="(columnGroup, index) in ['display_name', 'category_name', 'code']"
                    >
                        @if ($hasPermission)
                            <label
                                class="flex mr-2 gap-1 items-center w-max cursor-pointer select-none"
                                for="mass_action_select_all_records"
                                v-if="! index"
                            >
                                <input
                                    type="checkbox"
                                    name="mass_action_select_all_records"
                                    id="mass_action_select_all_records"
                                    class="hidden peer"
                                    :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                    @change="selectAllRecords"
                                >

                                <span
                                    class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                                    :class="[
                                        applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checkbox-check peer-checked:text-violet-700' : (
                                            applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-violet-700' : ''
                                        ),
                                    ]"
                                >
                                </span>
                            </label>
                        @endif

                        <p class="text-gray-600 dark:text-gray-300">
                            <span class="[&>*]:after:content-['_/_']">
                                <span
                                    class="after:content-['/'] last:after:content-['']"
                                    :class="{
                                        'text-gray-800 dark:text-white font-medium': applied.sort.column == columnGroup,
                                        'cursor-pointer hover:text-gray-800 dark:hover:text-white': columns.find(columnTemp => columnTemp.index === columnGroup)?.sortable,
                                    }"
                                    @click="
                                        columns.find(columnTemp => columnTemp.index === columnGroup)?.sortable ? sortPage(columns.find(columnTemp => columnTemp.index === columnGroup)): {}
                                    "
                                >
                                    @{{ columns.find(columnTemp => columnTemp.index === columnGroup)?.label }}
                                </span>
                            </span>

                            <!-- Filter Arrow Icon -->
                            <i
                                class="ltr:ml-1.5 rtl:mr-1.5 text-base  text-gray-800 dark:text-white align-text-bottom"
                                :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                v-if="columnGroup.includes(applied.sort.column)"
                            ></i>
                        </p>
                    </div>

                    @if ($hasPermission)
                        <!-- Actions -->
                        <div
                            class="flex gap-2.5 items-center justify-end select-none"
                        >
                            <p
                                class="text-gray-600 dark:text-gray-300"
                                v-if="actions?.length"
                            >
                                @lang('admin::app.components.datagrid.table.actions')
                            </p>
                        </div>
                    @endif
                </div>
            </template>

            <!-- Datagrid Head Shimmer -->
            <template v-else>
                <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
            </template>
        </template>

        <!-- DataGrid Body -->
        <template #body="{ columns, records, performAction, applied, actions, isLoading }">
            <template v-if="! isLoading">
                <div
                    v-for="record in records"
                    class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                    :style="'grid-template-columns: 2fr repeat(' + (actions.length ? columns.length : (columns.length -1 )) + ', 1fr)'"
                >
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        @if ($hasPermission)
                            <div class="mass-action-input">
                                <input
                                    type="checkbox"
                                    :name="`mass_action_select_record_${record.category_id}`"
                                    :id="`mass_action_select_record_${record.category_id}`"
                                    :value="record.category_id"
                                    class="hidden peer"
                                    v-model="applied.massActions.indices"
                                    @change="setCurrentSelectionMode"
                                >
                                <label
                                    class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"
                                    :for="`mass_action_select_record_${record.category_id}`"
                                >
                                </label>
                            </div>
                        @endif

                        <p v-text="record.display_name" class="text-nowrap overflow-hidden text-ellipsis hover:text-wrap"></p>
                    </div>

                    <p v-text="record.category_name" class="text-nowrap overflow-hidden text-ellipsis hover:text-wrap"></p>

                    <p v-text="record.code"></p>

                    <!-- Actions -->
                    <div class="flex justify-end">
                        <span
                            class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                            :class="action.icon"
                            v-text="!action.icon ? action.title : ''"
                            v-for="action in record.actions"
                            :title="action.title ?? ''"
                            @click="performAction(action)"
                        >
                        </span>
                    </div>
                </div>
            </template>

            <!-- Datagrid Shimmer for body when loading data  -->
            <template v-else>
                <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
            </template>
        </template>
    </x-admin::datagrid>

    {!! view_render_event('unopim.admin.catalog.categories.list.after') !!}

</x-admin::layouts>
