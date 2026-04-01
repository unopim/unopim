<!-- Product Stats Vue Component -->
<v-dashboard-product-stats>
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-product-stats>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-product-stats-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <template v-else>
            <a
                href="{{ route('admin.catalog.products.index') }}"
                class="bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4 h-full flex flex-col no-underline cursor-pointer hover:shadow-md transition-shadow"
            >
                <template v-if="totalProducts > 0">
                    <!-- Top Row: Total + Status cards -->
                    <div class="flex gap-3 mb-4">
                        <!-- Total Products Card -->
                        <div class="flex-1 rounded-lg p-4" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                            <p class="text-xs text-violet-200 mb-1">@lang('admin::app.dashboard.index.total-products')</p>
                            <p class="text-3xl font-bold text-white leading-none">@{{ totalProducts }}</p>
                        </div>

                        <!-- Active Card -->
                        <div class="flex-1 rounded-lg p-4 border" style="border-color: #d1fae5; background: #ecfdf5;">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full" style="background: #10b981;"></span>
                                <p class="text-xs" style="color: #065f46;">@lang('admin::app.dashboard.index.active')</p>
                            </div>
                            <p class="text-2xl font-bold leading-none" style="color: #065f46;">@{{ stats.statusBreakdown.active || 0 }}</p>
                        </div>

                        <!-- Inactive Card -->
                        <div class="flex-1 rounded-lg p-4 border" style="border-color: #fef3c7; background: #fffbeb;">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full" style="background: #f59e0b;"></span>
                                <p class="text-xs" style="color: #92400e;">@lang('admin::app.dashboard.index.inactive')</p>
                            </div>
                            <p class="text-2xl font-bold leading-none" style="color: #92400e;">@{{ stats.statusBreakdown.inactive || 0 }}</p>
                        </div>
                    </div>

                    <!-- Type Distribution -->
                    <p class="text-xs font-semibold text-zinc-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                        @lang('admin::app.dashboard.index.product-type-dist')
                    </p>

                    <!-- Stacked Bar -->
                    <div class="flex rounded-full h-3 overflow-hidden mb-3">
                        <div
                            v-for="(count, type) in stats.typeDistribution"
                            :key="'bar-' + type"
                            class="transition-all duration-700 ease-out first:rounded-l-full last:rounded-r-full"
                            :style="{ width: Math.max(getPercentage(count), 3) + '%', background: getTypeHex(type) }"
                        ></div>
                    </div>

                    <!-- Type Legend -->
                    <div class="flex flex-wrap gap-x-4 gap-y-2 mb-4">
                        <div
                            v-for="(count, type) in stats.typeDistribution"
                            :key="'legend-' + type"
                            class="flex items-center gap-2"
                        >
                            <span class="w-3 h-3 rounded-sm flex-shrink-0" :style="{ background: getTypeHex(type) }"></span>
                            <span class="text-xs text-zinc-700 dark:text-slate-300 capitalize">@{{ type }}</span>
                            <span class="text-xs font-bold text-zinc-800 dark:text-slate-200">@{{ count }}</span>
                            <span class="text-[10px] text-zinc-400 dark:text-slate-500">(@{{ getPercentage(count) }}%)</span>
                        </div>
                    </div>

                    <!-- Quick Insights -->
                    <div class="mt-auto pt-3 border-t border-zinc-100 dark:border-cherry-800">
                        <div class="grid grid-cols-4 gap-3">
                            <!-- New This Week -->
                            <div class="text-center">
                                <p class="text-lg font-bold text-zinc-800 dark:text-slate-50 leading-none mb-1">
                                    @{{ stats.newThisWeek || 0 }}
                                </p>
                                <p class="text-[10px] text-zinc-400 dark:text-slate-500 uppercase tracking-wide">
                                    @lang('admin::app.dashboard.index.new-this-week')
                                </p>
                            </div>

                            <!-- With Variants -->
                            <div class="text-center border-x border-zinc-100 dark:border-cherry-800">
                                <p class="text-lg font-bold text-zinc-800 dark:text-slate-50 leading-none mb-1">
                                    @{{ stats.withVariants || 0 }}
                                </p>
                                <p class="text-[10px] text-zinc-400 dark:text-slate-500 uppercase tracking-wide">
                                    @lang('admin::app.dashboard.index.with-variants')
                                </p>
                            </div>

                            <!-- Avg Completeness -->
                            <div class="text-center border-r border-zinc-100 dark:border-cherry-800">
                                <p class="text-lg font-bold leading-none mb-1" :style="{ color: getCompletenessColor(stats.avgCompleteness) }">
                                    @{{ stats.avgCompleteness !== null ? stats.avgCompleteness + '%' : 'N/A' }}
                                </p>
                                <p class="text-[10px] text-zinc-400 dark:text-slate-500 uppercase tracking-wide">
                                    @lang('admin::app.dashboard.index.avg-completeness')
                                </p>
                            </div>

                            <!-- Enriched This Week -->
                            <div class="text-center">
                                <p class="text-lg font-bold text-zinc-800 dark:text-slate-50 leading-none mb-1">
                                    @{{ stats.enrichedThisWeek || 0 }}
                                    <span
                                        v-if="stats.enrichedThisWeek !== stats.enrichedLastWeek"
                                        class="text-[10px] ml-0.5"
                                        :style="{ color: stats.enrichedThisWeek >= stats.enrichedLastWeek ? '#10b981' : '#ef4444' }"
                                    >
                                        @{{ stats.enrichedThisWeek >= stats.enrichedLastWeek ? '▲' : '▼' }}
                                    </span>
                                </p>
                                <p class="text-[10px] text-zinc-400 dark:text-slate-500 uppercase tracking-wide">
                                    @lang('admin::app.dashboard.index.enriched')
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center py-8">
                    <img src="{{ unopim_asset('images/icon-products.svg')}}" class="w-12 h-12 opacity-30 mb-3">
                    <p class="text-sm text-zinc-400 dark:text-slate-500">No products yet.</p>
                </div>
            </a>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-product-stats', {
            template: '#v-dashboard-product-stats-template',

            data() {
                return {
                    stats: {
                        typeDistribution: {},
                        statusBreakdown: {},
                        totalProducts: 0,
                        newThisWeek: 0,
                        withVariants: 0,
                        avgCompleteness: null,
                    },
                    isLoading: true,
                }
            },

            computed: {
                totalProducts() {
                    return this.stats.totalProducts || 0;
                }
            },

            mounted() {
                this.getStats();
            },

            methods: {
                getStats() {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: { type: 'product-stats' }
                        })
                        .then(response => {
                            this.stats = response.data.statistics;
                            this.isLoading = false;

                            this.$emitter.emit('product-stats-loaded', response.data.statistics);
                        })
                        .catch(error => {
                            this.isLoading = false;
                        });
                },

                getPercentage(count) {
                    if (this.totalProducts === 0) return 0;

                    return Math.round((count / this.totalProducts) * 100);
                },

                getStatusPercent(status) {
                    if (this.totalProducts === 0) return 0;

                    return Math.round(((this.stats.statusBreakdown[status] || 0) / this.totalProducts) * 100);
                },

                getTypeHex(type) {
                    const colors = {
                        'simple': '#7c3aed',
                        'configurable': '#0ea5e9',
                        'virtual': '#14b8a6',
                        'bundle': '#f97316',
                        'grouped': '#ec4899',
                        'downloadable': '#6366f1',
                    };

                    return colors[type] || '#7c3aed';
                },

                getCompletenessColor(score) {
                    if (score === null) return '#a1a1aa';
                    if (score >= 80) return '#10b981';
                    if (score >= 50) return '#f59e0b';

                    return '#ef4444';
                }
            }
        });
    </script>
@endPushOnce
