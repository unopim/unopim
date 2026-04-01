<!-- Needs Attention Vue Component -->
<v-dashboard-needs-attention>
</v-dashboard-needs-attention>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-needs-attention-template"
    >
        <template v-if="! isLoading && hasIssues">
            <div class="mt-4 rounded-lg box-shadow bg-white dark:bg-cherry-900 p-3">
                <div class="flex items-center flex-wrap gap-3">
                    <!-- Label -->
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" style="background: #ef4444;"></span>
                        <span class="text-xs font-semibold text-zinc-700 dark:text-slate-300">
                            @lang('admin::app.dashboard.index.needs-attention')
                        </span>
                    </div>

                    <!-- Unenriched Products -->
                    <a
                        v-if="stats.unenriched > 0"
                        href="{{ route('admin.catalog.products.index') }}"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium no-underline transition-shadow hover:shadow-md"
                        style="background: #fef2f2; color: #991b1b;"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" style="background: #ef4444;"></span>
                        @{{ stats.unenriched }} @lang('admin::app.dashboard.index.unenriched-products')
                    </a>

                    <!-- Failed Jobs -->
                    <a
                        v-if="stats.failedJobs > 0"
                        href="{{ route('admin.settings.data_transfer.tracker.index') }}"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium no-underline transition-shadow hover:shadow-md"
                        style="background: #fff7ed; color: #9a3412;"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" style="background: #f97316;"></span>
                        @{{ stats.failedJobs }} @lang('admin::app.dashboard.index.failed-jobs-24h')
                    </a>

                    <!-- Low Completeness -->
                    <a
                        v-if="stats.lowCompleteness > 0"
                        href="{{ route('admin.catalog.products.index') }}"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium no-underline transition-shadow hover:shadow-md"
                        style="background: #fffbeb; color: #92400e;"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" style="background: #f59e0b;"></span>
                        @{{ stats.lowCompleteness }} @lang('admin::app.dashboard.index.low-completeness')
                    </a>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-needs-attention', {
            template: '#v-dashboard-needs-attention-template',

            data() {
                return {
                    stats: {
                        unenriched: 0,
                        failedJobs: 0,
                        lowCompleteness: 0,
                    },
                    isLoading: true,
                }
            },

            computed: {
                hasIssues() {
                    return this.stats.unenriched > 0
                        || this.stats.failedJobs > 0
                        || this.stats.lowCompleteness > 0;
                }
            },

            mounted() {
                this.getStats();
            },

            methods: {
                getStats() {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: { type: 'needs-attention' }
                        })
                        .then(response => {
                            this.stats = response.data.statistics;
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                        });
                }
            }
        });
    </script>
@endPushOnce
