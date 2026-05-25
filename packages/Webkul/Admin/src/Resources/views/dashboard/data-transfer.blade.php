<!-- Data Transfer Status Vue Component -->
<v-dashboard-data-transfer>
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-data-transfer>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-data-transfer-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <template v-else>
            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow h-full">
                <!-- Job Summary Bar -->
                <div v-if="Object.keys(jobSummary).length > 0" class="flex flex-wrap gap-2.5 p-4 border-b border-zinc-100 dark:border-cherry-800">
                    <div
                        v-for="(count, state) in jobSummary"
                        :key="state"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                        :class="getStateBadgeColor(state)"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="getStateDotColor(state)"></span>
                        @{{ getStateLabel(state) }}: @{{ count }}
                    </div>
                </div>

                <!-- Recent Jobs List -->
                <div v-if="recentJobs.length > 0" class="divide-y divide-zinc-100 dark:divide-cherry-800">
                    <a
                        v-for="job in recentJobs"
                        :key="job.id"
                        :href="'{{ route('admin.settings.data_transfer.tracker.view', ':id') }}'.replace(':id', job.id)"
                        class="flex items-center gap-3 p-4 hover:bg-zinc-50 dark:hover:bg-cherry-800/50 transition-colors"
                    >
                        <!-- Type Icon -->
                        <div
                            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                            :class="job.type === 'import' ? 'bg-sky-100 dark:bg-sky-900/30' : 'bg-violet-100 dark:bg-violet-900/30'"
                        >
                            <span
                                class="text-sm"
                                :class="job.type === 'import' ? 'icon-import text-sky-600' : 'icon-export text-violet-600'"
                            ></span>
                        </div>

                        <!-- Job Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-zinc-800 dark:text-slate-200">
                                    @{{ job.job_code || job.type }}
                                </span>

                                <span
                                    class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full"
                                    :class="getStateBadgeColor(job.state)"
                                >
                                    @{{ getStateLabel(job.state) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs text-zinc-500 dark:text-slate-400 capitalize">
                                    @{{ job.entity_type }} &middot; @{{ job.type }}
                                </span>

                                <span class="text-xs text-zinc-500 dark:text-slate-400">
                                    @lang('admin::app.dashboard.index.rows-processed'):
                                    <span class="font-medium text-zinc-700 dark:text-slate-300">@{{ job.processed_rows_count || 0 }}</span>
                                </span>

                                <span v-if="job.errors_count > 0" class="text-xs text-red-500">
                                    @lang('admin::app.dashboard.index.errors'):
                                    <span class="font-medium">@{{ job.errors_count }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Time & User -->
                        <div class="flex-shrink-0 text-right">
                            <p class="text-xs text-zinc-400 dark:text-slate-500">
                                @{{ job.time_ago }}
                            </p>

                            <p v-if="job.user_name" class="text-[10px] text-zinc-400 dark:text-slate-500 mt-0.5">
                                @{{ job.user_name }}
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <span class="icon-export text-4xl text-zinc-300 dark:text-cherry-700"></span>

                    <p class="text-sm text-zinc-400 dark:text-slate-500 mt-2">
                        @lang('admin::app.dashboard.index.no-jobs')
                    </p>
                </div>

                <!-- Footer Link -->
                @if (bouncer()->hasPermission('data_transfer.job_tracker'))
                    <div v-if="recentJobs.length > 0" class="p-3 border-t border-zinc-100 dark:border-cherry-800 text-center">
                        <a
                            href="{{ route('admin.settings.data_transfer.tracker.index') }}"
                            class="text-xs font-medium text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                        >
                            @lang('admin::app.dashboard.index.view-all-jobs') &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-data-transfer', {
            template: '#v-dashboard-data-transfer-template',

            data() {
                return {
                    recentJobs: [],
                    jobSummary: {},
                    isLoading: true,
                }
            },

            mounted() {
                this.getStats();
            },

            methods: {
                getStats() {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: { type: 'data-transfer-status' }
                        })
                        .then(response => {
                            this.recentJobs = response.data.statistics.recentJobs;
                            this.jobSummary = response.data.statistics.jobSummary;
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                        });
                },

                getStateLabel(state) {
                    const labels = {
                        'completed': "@lang('admin::app.dashboard.index.job-state-completed')",
                        'failed': "@lang('admin::app.dashboard.index.job-state-failed')",
                        'processing': "@lang('admin::app.dashboard.index.job-state-processing')",
                        'pending': "@lang('admin::app.dashboard.index.job-state-pending')",
                        'validated': "@lang('admin::app.dashboard.index.job-state-validated')",
                    };

                    return labels[state] || state;
                },

                getStateBadgeColor(state) {
                    const colors = {
                        'completed': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                        'failed': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        'processing': 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                        'pending': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'validated': 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                    };

                    return colors[state] || 'bg-zinc-100 text-zinc-600 dark:bg-cherry-800 dark:text-slate-400';
                },

                getStateDotColor(state) {
                    const colors = {
                        'completed': 'bg-emerald-500',
                        'failed': 'bg-red-500',
                        'processing': 'bg-sky-500',
                        'pending': 'bg-amber-500',
                        'validated': 'bg-teal-500',
                    };

                    return colors[state] || 'bg-zinc-400';
                }
            }
        });
    </script>
@endPushOnce
