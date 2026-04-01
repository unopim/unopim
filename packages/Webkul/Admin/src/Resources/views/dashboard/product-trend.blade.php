<!-- Product Activity Trend Vue Component -->
<v-dashboard-product-trend>
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-product-trend>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-product-trend-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <template v-else>
            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4 h-full flex flex-col">
                <!-- Legend -->
                <div class="flex items-center gap-4 mb-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm" style="background: #7c3aed;"></span>
                        <span class="text-xs text-zinc-600 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.legend-created')
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm" style="background: #0ea5e9;"></span>
                        <span class="text-xs text-zinc-600 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.legend-updated')
                        </span>
                    </div>
                </div>

                <!-- Bar Chart Area -->
                <div class="flex-1 flex items-end gap-3 min-h-[160px]">
                    <div
                        v-for="(date, index) in dates"
                        :key="date"
                        class="flex-1 flex flex-col items-center h-full justify-end"
                    >
                        <!-- Counts above bars -->
                        <div class="flex items-end gap-0.5 mb-1">
                            <span
                                class="text-[10px] font-bold"
                                :style="{ color: created[date] > 0 ? '#7c3aed' : '#a1a1aa' }"
                            >
                                @{{ created[date] }}
                            </span>

                            <span class="text-[10px] text-zinc-300 dark:text-slate-600">/</span>

                            <span
                                class="text-[10px] font-bold"
                                :style="{ color: updated[date] > 0 ? '#0ea5e9' : '#a1a1aa' }"
                            >
                                @{{ updated[date] }}
                            </span>
                        </div>

                        <!-- Grouped Bars -->
                        <div class="flex items-end gap-[2px] w-full justify-center">
                            <!-- Created Bar -->
                            <div
                                class="flex-1 max-w-[20px] rounded-t-md transition-all duration-700 ease-out"
                                :style="{ height: getBarHeight(created[date]) + 'px', background: created[date] > 0 ? '#7c3aed' : '#f4f4f5' }"
                            ></div>

                            <!-- Updated Bar -->
                            <div
                                class="flex-1 max-w-[20px] rounded-t-md transition-all duration-700 ease-out"
                                :style="{ height: getBarHeight(updated[date]) + 'px', background: updated[date] > 0 ? '#0ea5e9' : '#f4f4f5' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Date Labels -->
                <div class="flex gap-3 mt-2 border-t border-zinc-100 dark:border-cherry-800 pt-2">
                    <div
                        v-for="date in dates"
                        :key="'label-' + date"
                        class="flex-1 text-center"
                    >
                        <p class="text-[10px] text-zinc-400 dark:text-slate-500 leading-none">
                            @{{ formatDay(date) }}
                        </p>
                        <p class="text-xs font-semibold text-zinc-600 dark:text-slate-400 leading-none mt-0.5">
                            @{{ formatDateNum(date) }}
                        </p>
                    </div>
                </div>

                <!-- Summary Footer -->
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-zinc-100 dark:border-cherry-800">
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-zinc-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.legend-created'):
                            <span class="font-bold" style="color: #7c3aed;">@{{ totalCreated }}</span>
                        </span>

                        <span class="text-xs text-zinc-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.legend-updated'):
                            <span class="font-bold" style="color: #0ea5e9;">@{{ totalUpdated }}</span>
                        </span>
                    </div>

                    <span class="text-xs text-zinc-400 dark:text-slate-500">
                        Last 7 days
                    </span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-product-trend', {
            template: '#v-dashboard-product-trend-template',

            data() {
                return {
                    created: {},
                    updated: {},
                    isLoading: true,
                }
            },

            computed: {
                dates() {
                    return Object.keys(this.created);
                },

                maxCount() {
                    const allValues = [
                        ...Object.values(this.created),
                        ...Object.values(this.updated),
                    ];

                    return Math.max(...allValues, 1);
                },

                totalCreated() {
                    return Object.values(this.created).reduce((sum, count) => sum + count, 0);
                },

                totalUpdated() {
                    return Object.values(this.updated).reduce((sum, count) => sum + count, 0);
                }
            },

            mounted() {
                this.$emitter.on('product-stats-loaded', this.handleSharedData);

                // Fallback if emitter data doesn't arrive within 3 seconds
                this._fallbackTimer = setTimeout(() => {
                    if (this.isLoading) {
                        this.fetchStats();
                    }
                }, 3000);
            },

            beforeUnmount() {
                clearTimeout(this._fallbackTimer);
            },

            methods: {
                handleSharedData(statistics) {
                    clearTimeout(this._fallbackTimer);

                    this.created = statistics.creationTrend;
                    this.updated = statistics.updateTrend;
                    this.isLoading = false;
                },

                fetchStats() {
                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: { type: 'product-stats' }
                        })
                        .then(response => {
                            this.created = response.data.statistics.creationTrend;
                            this.updated = response.data.statistics.updateTrend;
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                        });
                },

                getBarHeight(count) {
                    if (this.maxCount === 0) return 6;

                    return Math.max(Math.round((count / this.maxCount) * 120), 6);
                },

                formatDay(dateStr) {
                    const date = new Date(dateStr);
                    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    return days[date.getDay()];
                },

                formatDateNum(dateStr) {
                    const date = new Date(dateStr);
                    return date.getDate();
                }
            }
        });
    </script>
@endPushOnce
