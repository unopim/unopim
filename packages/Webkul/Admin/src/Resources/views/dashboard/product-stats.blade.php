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
            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4 h-full flex flex-col">
                <template v-if="totalProducts > 0">
                    <!-- Top Row: Total + Status cards -->
                    <div class="flex gap-3 mb-4">
                        <!-- Total Products Card -->
                        <a
                            href="{{ route('admin.catalog.products.index') }}"
                            class="flex-1 rounded-lg p-4 no-underline hover:opacity-90 transition-opacity bg-gradient-to-br from-primary-600 to-primary-700"
                        >
                            <p class="text-xs text-primary-200 mb-1">@lang('admin::app.dashboard.index.total-products')</p>
                            <p class="text-3xl font-bold text-white leading-none">@{{ totalProducts }}</p>
                        </a>

                        <!-- Active Card -->
                        <a
                            href="{{ route('admin.catalog.products.index') }}?filters[status][]=1"
                            class="flex-1 rounded-lg p-4 border no-underline hover:opacity-90 transition-opacity border-emerald-100 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-900/20"
                        >
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <p class="text-xs text-emerald-800 dark:text-emerald-300">@lang('admin::app.dashboard.index.active')</p>
                            </div>
                            <p class="text-2xl font-bold leading-none text-emerald-800 dark:text-emerald-300">@{{ stats.statusBreakdown.active || 0 }}</p>
                        </a>

                        <!-- Inactive Card -->
                        <a
                            href="{{ route('admin.catalog.products.index') }}?filters[status][]=0"
                            class="flex-1 rounded-lg p-4 border no-underline hover:opacity-90 transition-opacity border-amber-100 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-900/20"
                        >
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <p class="text-xs text-amber-800 dark:text-amber-300">@lang('admin::app.dashboard.index.inactive')</p>
                            </div>
                            <p class="text-2xl font-bold leading-none text-amber-800 dark:text-amber-300">@{{ stats.statusBreakdown.inactive || 0 }}</p>
                        </a>
                    </div>

                    <!-- Type Distribution -->
                    <p class="text-xs font-semibold text-zinc-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                        @lang('admin::app.dashboard.index.product-type-dist')
                    </p>

                    <!-- Stacked Bar (each segment deep-links into the grid filtered by that product type) -->
                    <div class="flex rounded-full h-3 overflow-hidden mb-3">
                        <a
                            v-for="(count, type) in stats.typeDistribution"
                            :key="'bar-' + type"
                            :href="typeFilterUrl(type)"
                            class="transition-all duration-700 ease-out first:rounded-l-full last:rounded-r-full cursor-pointer"
                            :style="{ width: Math.max(getPercentage(count), 3) + '%', background: getTypeHex(type) }"
                            :title="type + ': ' + count"
                            :aria-label="type + ': ' + count"
                        ></a>
                    </div>

                    <!-- Type Legend (each chip links to products filtered by type=<type>) -->
                    <div class="flex flex-wrap gap-x-4 gap-y-2 mb-4">
                        <a
                            v-for="(count, type) in stats.typeDistribution"
                            :key="'legend-' + type"
                            :href="typeFilterUrl(type)"
                            class="flex items-center gap-2 no-underline hover:opacity-80 transition-opacity"
                        >
                            <span class="w-3 h-3 rounded-sm flex-shrink-0" :style="{ background: getTypeHex(type) }"></span>
                            <span class="text-xs text-zinc-700 dark:text-slate-300 capitalize">@{{ type }}</span>
                            <span class="text-xs font-bold text-zinc-800 dark:text-slate-200">@{{ count }}</span>
                            <span class="text-[10px] text-zinc-400 dark:text-slate-500">(@{{ getPercentage(count) }}%)</span>
                        </a>
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
                                        :class="stats.enrichedThisWeek >= stats.enrichedLastWeek ? 'text-success' : 'text-danger'"
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
                    <p class="text-sm text-zinc-400 dark:text-slate-500">@lang('admin::app.dashboard.index.no-products')</p>
                </div>
            </div>
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

                            console.error(error);
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

                /**
                 * Read a live CSS custom property off :root so the theme and
                 * dark-mode overrides drive chart colours from one place.
                 * Called at render-time (not cached) so toggling dark-mode
                 * reflects immediately.
                 */
                cssVar(name) {
                    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                },

                getTypeHex(type) {
                    const colors = {
                        'simple': this.cssVar('--chart-1'),
                        'configurable': this.cssVar('--chart-2'),
                        'virtual': this.cssVar('--chart-3'),
                        'bundle': this.cssVar('--chart-4'),
                        'grouped': this.cssVar('--chart-5'),
                        'downloadable': this.cssVar('--chart-6'),
                    };

                    return colors[type] || this.cssVar('--chart-1');
                },

                getCompletenessColor(score) {
                    if (score === null) return this.cssVar('--chart-muted');
                    if (score >= 80) return this.cssVar('--chart-success');
                    if (score >= 50) return this.cssVar('--chart-warning');

                    return this.cssVar('--chart-danger');
                },

                /**
                 * Build a products-index URL that deep-links into the grid
                 * pre-filtered to the given product type. Uses the same
                 * ?filters[col][]=value format as the Active/Inactive card
                 * hrefs so the DataGrid's boot() parseUrlFilters() picks it
                 * up consistently. Internal-678.
                 */
                typeFilterUrl(type) {
                    const base = "{{ route('admin.catalog.products.index') }}";

                    return `${base}?filters[type][]=${encodeURIComponent(type)}`;
                }
            }
        });
    </script>
@endPushOnce
