<x-admin::graphs.radial-progress />

<!-- Percentage Metrics Vue Component -->
<v-dashboard-percentage-cards>
    <!-- Shimmer -->
</v-dashboard-percentage-cards>

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-percentage-cards-template">
        <div v-if="isLoading" class="flex flex-wrap gap-4">
            <div v-for="i in 3" :key="i" class="bg-white dark:bg-cherry-900 box-shadow rounded-lg p-4 flex-1 max-w-[360px] min-w-[300px] animate-pulse">
                <header class="flex gap-2 justify-between mb-5 pb-2 border-b dark:border-gray-600">
                    <div>
                        <div class="shimmer w-[200px] h-6 mb-4"></div>
                        <div class="shimmer w-[200px] h-4 mb-2"></div>
                        <div class="shimmer w-[200px] h-3 mb-2"></div>
                    </div>
                    <div class="mb-5 flex items-center justify-center">
                        <div class="shimmer w-10 h-10 rounded-full border-4 border-gray-200 dark:border-cherry-800 bg-gray-100 dark:bg-cherry-900"></div>
                    </div>
                </header>
                <div class="space-y-4">
                    <div v-for="j in 3" :key="j" class="flex items-center justify-between gap-3">
                        <div class="shimmer w-[200px] h-4"></div>
                        <div class="relative h-10 flex items-center justify-center">
                            <div class="shimmer w-10 h-10 rounded-full border-4 border-gray-200 dark:border-cherry-800 bg-gray-100 dark:bg-cherry-900"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-wrap gap-4">
            <v-percentage-card
                v-for="(channelScores, channel) in data"
                :key="channel"
                :title="channelScores.name"
                :product-count="channelScores.product_count"
                :total-product-count="channelScores.total_products_count"
                :channel-average="channelScores.average"
                :items="formatItems(channelScores?.locales ?? {})"
            />
        </div>
    </script>

    <script type="module">
        app.component('v-dashboard-percentage-cards', {
            template: '#v-dashboard-percentage-cards-template',

            data() {
                return {
                    isLoading: true,
                    data: {},
                };
            },

            mounted() {
                this.fetchCompletenessData();
            },

            methods: {
                fetchCompletenessData() {
                    this.$axios.get("{{ route('admin.completeness.dashboard.data') }}")
                        .then(response => {
                            if (response.data.success) {
                                this.data = response.data.data;
                            }

                            this.isLoading = false;
                        })
                        .catch(error => {
                            console.error("Error fetching completeness data", error);
                            this.isLoading = false;
                        });
                },

                formatItems(locales) {
                    return Object.entries(locales).map(([label, value]) => {
                        return { label, value };
                    });
                }
            }
        });
    </script>

    <script type="text/x-template" id="v-percentage-card-template">
        <section class="bg-white dark:bg-cherry-900 box-shadow rounded-lg p-4 flex-1 max-w-[360px] min-w-[300px]">
            <header class="flex gap-2 items-center justify-between max-w-[360px] mb-3 pb-3.5 border-b dark:border-gray-600">
                <div class="pr-2">
                    <h2
                        class="text-lg font-semibold text-gray-800 dark:text-white"
                        v-text="title"
                    >
                    </h2>
                    <span class="text-xs text-gray-800 dark:text-white max-w-[360px]">
                        <i v-if="productCount != 0">
                            @lang('completeness::app.dashboard.index.completeness.calculated-products'): @{{ productCount }} / @{{ totalProductCount }} <br>
                        </i>

                        @{{ getSuggestionMessage(channelAverage) }}

                    </span>
                </div>

                <div v-if="channelAverage">
                    <v-radial-progress
                        :score="channelAverage"
                        :radius="20"
                        scoreClass="text-md"
                    />
                </div>
            </header>

            <div class="space-y-4">
                <div
                    v-for="(item, index) in items"
                    :key="index"
                    class="flex items-center justify-between gap-3"
                >
                    <span class="text-sm text-gray-700 dark:text-gray-300">@{{ item.label }}</span>

                    <v-radial-progress :score="item.value" />
                </div>
            </div>
        </section>
    </script>

    <script type="module">
        app.component('v-percentage-card', {
            template: '#v-percentage-card-template',
            props: ['title', 'items', 'channelAverage', 'productCount', 'totalProductCount'],

            methods: {
                getSuggestionMessage(channelAverage) {
                    switch (true) {
                        case channelAverage < 30:
                            return `@lang('completeness::app.dashboard.index.completeness.suggestion.low')`;
                        case channelAverage < 70:
                            return `@lang('completeness::app.dashboard.index.completeness.suggestion.medium')`;
                        case channelAverage < 100:
                            return `@lang('completeness::app.dashboard.index.completeness.suggestion.high')`;
                        case channelAverage === 100:
                            return `@lang('completeness::app.dashboard.index.completeness.suggestion.perfect')`;
                        default:
                            return '';
                    }
                }
            }
        });
    </script>
@endPushOnce
