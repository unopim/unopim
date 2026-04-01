<!-- Channel Readiness Vue Component -->
<v-dashboard-channel-readiness>
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-channel-readiness>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-channel-readiness-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <template v-else>
            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4">
                <template v-if="channels.length > 0">
                    <div class="space-y-4">
                        <div
                            v-for="ch in channels"
                            :key="ch.channel"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-semibold text-zinc-700 dark:text-slate-300 capitalize">
                                    @{{ ch.channel }}
                                </span>

                                <span class="text-xs text-zinc-500 dark:text-slate-400">
                                    <span class="font-bold" :style="{ color: getColor(ch.percentage) }">@{{ ch.ready }}</span>
                                    @lang('admin::app.dashboard.index.of') @{{ ch.total }}
                                    @lang('admin::app.dashboard.index.products-ready')
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex-1 rounded-full h-2.5 overflow-hidden" style="background: #f4f4f5;">
                                    <div
                                        class="h-full rounded-full transition-all duration-700 ease-out"
                                        :style="{ width: Math.max(ch.percentage, 1) + '%', background: getColor(ch.percentage) }"
                                    ></div>
                                </div>

                                <span
                                    class="text-xs font-bold w-10 text-right"
                                    :style="{ color: getColor(ch.percentage) }"
                                >
                                    @{{ ch.percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="py-6 text-center">
                    <p class="text-sm text-zinc-400 dark:text-slate-500">
                        @lang('admin::app.dashboard.index.no-readiness-data')
                    </p>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-channel-readiness', {
            template: '#v-dashboard-channel-readiness-template',

            data() {
                return {
                    channels: [],
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
                            params: { type: 'channel-readiness' }
                        })
                        .then(response => {
                            this.channels = response.data.statistics;
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                        });
                },

                getColor(percentage) {
                    if (percentage >= 80) return '#10b981';
                    if (percentage >= 50) return '#f59e0b';

                    return '#ef4444';
                }
            }
        });
    </script>
@endPushOnce
