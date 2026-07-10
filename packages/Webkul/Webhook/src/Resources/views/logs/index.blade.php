<x-slot:title>
    @lang('webhook::app.configuration.webhook.logs.index.title')
</x-slot>

<v-webhook-logs>
    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap pt-3">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('webhook::app.configuration.webhook.logs.index.title')
        </p>
    </div>

    <x-admin::shimmer.datagrid />
</v-webhook-logs>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-webhook-logs-template"
    >
        <div>
            <div class="flex gap-4 justify-between items-center max-sm:flex-wrap pt-3">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('webhook::app.configuration.webhook.logs.index.title')
                </p>
            </div>

            <x-admin::datagrid
                src="{{ route('webhook.logs.index') }}"
                ref="datagrid"
            >
                <template #body="{ columns, records, performAction }">
                    <div
                        v-for="record in records"
                        :key="record.id"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(80px, 1fr))`"
                    >
                        <!-- Mass action checkbox (matches header checkbox column) -->
                        <p v-if="$refs.datagrid?.available?.massActions?.length" @click.stop>
                            <label :for="`mass_action_select_record_${record[$refs.datagrid.available.meta.primary_column]}`">
                                <input
                                    type="checkbox"
                                    class="peer hidden"
                                    :name="`mass_action_select_record_${record[$refs.datagrid.available.meta.primary_column]}`"
                                    :value="record[$refs.datagrid.available.meta.primary_column]"
                                    :id="`mass_action_select_record_${record[$refs.datagrid.available.meta.primary_column]}`"
                                    v-model="$refs.datagrid.applied.massActions.indices"
                                >
                                <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-primary-700 cursor-pointer rounded-md text-2xl"></span>
                            </label>
                        </p>

                        <p v-text="record.id"></p>

                        <p v-html="record.created_at"></p>

                        <p v-text="record.sku"></p>

                        <p v-text="record.user || '—'"></p>

                        <p v-html="record.status"></p>

                        <div class="flex justify-end">
                            <span
                                class="icon-view cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                :title="'@lang('webhook::app.configuration.webhook.logs.index.datagrid.view')'"
                                @click="openLog(record.actions.find(a => a.index === 'view')?.url)"
                            ></span>

                            <span
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                :title="'@lang('webhook::app.configuration.webhook.logs.index.datagrid.delete')'"
                                @click="performAction(record.actions.find(a => a.index === 'delete'))"
                            ></span>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            <!-- Log Detail Modal -->
            <Teleport to="body">
                <template v-if="isOpen">
                    <div
                        class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity z-[10001]"
                        @click="closeLog"
                    ></div>

                    <div class="fixed inset-0 z-[10002] transform transition overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 sm:items-center sm:p-0">
                            <div class="w-full max-h-[96%] overflow-y-auto z-[999] absolute ltr:left-1/2 rtl:right-1/2 top-1/2 rounded-lg bg-white dark:bg-gray-900 box-shadow max-md:w-[90%] ltr:-translate-x-1/2 rtl:translate-x-1/2 -translate-y-1/2 max-w-2xl">

                                <div class="flex justify-between items-center gap-2.5 px-4 py-3 border-b dark:border-gray-800 sticky top-0 bg-white dark:bg-gray-900">
                                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                                        @lang('webhook::app.configuration.webhook.logs.index.show-title')
                                    </p>

                                    <span
                                        class="icon-cancel text-3xl cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 hover:rounded-md"
                                        @click="closeLog"
                                    ></span>
                                </div>

                                <div
                                    v-if="isLoading"
                                    class="flex items-center justify-center px-4 py-8"
                                >
                                    <span class="icon-spinner animate-spin text-3xl text-gray-400"></span>
                                </div>

                                <div v-else-if="log" class="px-4 py-3 border-b dark:border-gray-800">
                                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-300 mb-4">
                                        <div>
                                            <span class="font-semibold block">@lang('webhook::app.configuration.webhook.logs.index.datagrid.id')</span>
                                            <span v-text="log.id"></span>
                                        </div>

                                        <div>
                                            <span class="font-semibold block">@lang('webhook::app.configuration.webhook.logs.index.datagrid.sku')</span>
                                            <span v-text="log.sku"></span>
                                        </div>

                                        <div>
                                            <span class="font-semibold block">@lang('webhook::app.configuration.webhook.logs.index.datagrid.user')</span>
                                            <span v-text="log.user || '—'"></span>
                                        </div>

                                        <div>
                                            <span class="font-semibold block">@lang('webhook::app.configuration.webhook.logs.index.datagrid.created_at')</span>
                                            <span v-text="log.created_at"></span>
                                        </div>

                                        <div>
                                            <span class="font-semibold block">@lang('webhook::app.configuration.webhook.logs.index.datagrid.status')</span>
                                            <span
                                                :class="log.status ? 'label-completed' : 'label-canceled'"
                                                v-text="log.status ? '@lang('webhook::app.configuration.webhook.logs.index.datagrid.success')' : '@lang('webhook::app.configuration.webhook.logs.index.datagrid.failed')'"
                                            ></span>
                                        </div>
                                    </div>

                                    <p class="text-base font-semibold text-gray-800 dark:text-slate-50 mb-2">
                                        @lang('webhook::app.configuration.webhook.logs.index.sent-payload')
                                    </p>

                                    <pre
                                        v-if="log.payload"
                                        class="overflow-auto rounded-lg bg-gray-50 dark:bg-cherry-800 text-gray-600 dark:text-gray-300 text-xs px-4 py-3 max-h-[400px] mb-4"
                                        v-text="JSON.stringify(log.payload, null, 2)"
                                    ></pre>

                                    <p
                                        v-else
                                        class="text-sm text-gray-500 dark:text-gray-400 mb-4"
                                    >@lang('webhook::app.configuration.webhook.logs.index.no-payload')</p>

                                    <p class="text-base font-semibold text-gray-800 dark:text-slate-50 mb-2">
                                        @lang('webhook::app.configuration.webhook.logs.index.response')
                                    </p>

                                    <pre
                                        class="overflow-auto rounded-lg bg-gray-50 dark:bg-cherry-800 text-gray-600 dark:text-gray-300 text-xs px-4 py-3 max-h-[400px]"
                                        v-text="JSON.stringify(log.response, null, 2)"
                                    ></pre>
                                </div>

                            </div>
                        </div>
                    </div>
                </template>
            </Teleport>
        </div>
    </script>

    <script type="module">
        app.component('v-webhook-logs', {
            template: '#v-webhook-logs-template',

            data() {
                return {
                    isOpen: false,
                    isLoading: false,
                    log: null,
                };
            },

            computed: {
                gridsCount() {
                    const dg = this.$refs.datagrid;
                    let count = dg?.available?.columns?.length ?? 0;

                    if (dg?.available?.actions?.length) {
                        count++;
                    }

                    if (dg?.available?.massActions?.length) {
                        count++;
                    }

                    return count;
                },
            },

            methods: {
                openLog(url) {
                    if (! url) {
                        return;
                    }

                    this.isOpen = true;
                    this.isLoading = true;
                    this.log = null;

                    document.body.style.overflow = 'hidden';

                    this.$axios.get(url)
                        .then(response => {
                            this.log = response.data;
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message ?? 'Failed to load log details.',
                            });
                            this.closeLog();
                        });
                },

                closeLog() {
                    this.isOpen = false;
                    this.log = null;
                    document.body.style.overflow = 'auto';
                },
            },
        });
    </script>
@endPushOnce
