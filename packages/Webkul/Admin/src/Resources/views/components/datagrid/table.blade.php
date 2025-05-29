@props(['isMultiRow' => false])

<v-datagrid-table>
    {{ $slot }}
</v-datagrid-table>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-table-template"
    >
        <div class="w-full">
            <div class="table-responsive grid w-full box-shadow rounded bg-white dark:bg-cherry-900 overflow-x-auto">
                <slot name="header">
                    <template v-if="$parent.isLoading">
                        <x-admin::shimmer.datagrid.table.head :isMultiRow="$isMultiRow" />
                    </template>

                    <template v-else>
                        <div
                            class="row grid gap-2.5 min-h-[47px] px-4 py-2.5 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 bg-violet-50 dark:bg-cherry-900 font-semibold items-center"
                            :style="`grid-template-columns: repeat(${gridsCount}, minmax(80px, 1fr))`"
                        >
                            <!-- Mass Actions -->
                            <p v-if="$parent.available.massActions.length">
                                <label for="mass_action_select_all_records">
                                    <input
                                        type="checkbox"
                                        name="mass_action_select_all_records"
                                        id="mass_action_select_all_records"
                                        class="peer hidden"
                                        :checked="['all', 'partial'].includes($parent.applied.massActions.meta.mode)"
                                        @change="$parent.selectAllRecords"
                                    >

                                    <span
                                        class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                                        :class="[
                                            $parent.applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checkbox-check peer-checked:text-violet-700 ' : (
                                                $parent.applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-violet-700' : ''
                                            ),
                                        ]"
                                    >
                                    </span>
                                </label>
                            </p>

                            <!-- Columns -->
                            <p
                                v-for="column in $parent.available.columns"
                                class="flex gap-1.5 items-center break-words"
                                :class="{'cursor-pointer select-none hover:text-gray-800 dark:hover:text-white': column.sortable}"
                                @click="$parent.sortPage(column)"
                            >
                                @{{ column.label }}

                                <i
                                    class="text-base  text-gray-600 dark:text-gray-300 align-text-bottom"
                                    :class="[$parent.applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                    v-if="column.index == $parent.applied.sort.column"
                                ></i>
                            </p>

                            <!-- Actions -->
                            <div
                                class="flex gap-2.5 items-center justify-end select-none"
                            >
                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-if="$parent.available.actions.length"
                                >
                                    @lang('admin::app.components.datagrid.table.actions')
                                </p>
                            </div>
                        </div>
                    </template>
                </slot>

                <slot name="body">
                    <template v-if="$parent.isLoading">
                        <x-admin::shimmer.datagrid.table.body :isMultiRow="$isMultiRow" />
                    </template>

                    <template v-else>
                        <template v-if="$parent.available.records.length">
                            <div
                                class="row grid gap-2.5 items-center px-4 py-4 cursor-pointer border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                v-for="record in $parent.available.records"
                                :style="`grid-template-columns: repeat(${gridsCount}, minmax(80px, 1fr))`"
                                @click="handleRowClick($event, record)"
                            >
                                <!-- Mass Actions -->
                                <p v-if="$parent.available.massActions.length" @click.stop>
                                    <label :for="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`">
                                        <input
                                            type="checkbox"
                                            class="peer hidden"
                                            :name="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                            :value="record[$parent.available.meta.primary_column]"
                                            :id="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                            v-model="$parent.applied.massActions.indices"
                                            @change="$parent.setCurrentSelectionMode"
                                        >

                                        <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer rounded-md text-2xl">
                                        </span>
                                    </label>
                                </p>

                                <!-- Columns -->
                                 <template
                                    v-if="record.is_closure"
                                    v-for="column in $parent.available.columns"
                                 >
                                    <template v-if="column.type === 'image'">
                                        <img
                                        :src="record[column.index] ? record[column.index] : '{{ unopim_asset('images/placeholder.svg') }}'"
                                        alt="Thumbnail"
                                        width="74"
                                        height="74"
                                        class="h-[120px] max-w-[60px] min-w-[60px] max-h-[60px] min-h-[60px] rounded-lg border border-gray-300 shadow-sm object-cover"
                                    />
                                    </template>

                                    <template v-else-if="typeof record[column.index] === 'string' && record[column.index].length > 25">
                                        <p
                                            class="break-words text-nowrap overflow-hidden text-ellipsis hover:text-wrap"
                                            v-html="record[column.index]"
                                        >
                                        </p>
                                    </template>

                                    <p
                                        v-else
                                        class="break-words"
                                        v-html="record[column.index]"
                                    >
                                    </p>
                                 </template>

                                <template
                                    v-else
                                    v-for="column in $parent.available.columns"
                                >
                                    <p
                                        class="break-words"
                                        v-html="record[column.index]"
                                    >
                                    </p>
                                </template>

                                <!-- Actions -->
                                <div
                                    class="flex gap-2.5 items-center justify-end select-none"
                                    @click.stop
                                >
                                    <p
                                        class="text-gray-600 dark:text-gray-300"
                                        v-if="$parent.available.actions.length"
                                    >
                                        <span
                                            class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                            :class="action.icon"
                                            v-text="!action.icon ? action.title : ''"
                                            v-for="action in record.actions"
                                            :title="action.title ?? ''"
                                            @click="$parent.performAction(action)"
                                        >
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="row grid px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center">
                                <p>
                                    @lang('admin::app.components.datagrid.table.no-records-available')
                                </p>
                            </div>
                        </template>
                    </template>
                </slot>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid-table', {
            template: '#v-datagrid-table-template',

            computed: {
                gridsCount() {
                    let count = this.$parent.available.columns.length;

                    if (this.$parent.available.actions.length) {
                        ++count;
                    }

                    if (this.$parent.available.massActions.length) {
                        ++count;
                    }

                    return count;
                },
            },

            methods: {
                handleRowClick(event, record) {
                    // Ensure the click event only fires if the clicked element is the row itself
                    if (event.target === event.currentTarget) {
                        this.$parent.performAction(record.actions.find(action => action.index === 'edit'));
                    }
                }
            }
        });
    </script>
@endpushOnce
