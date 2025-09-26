<!-- Total catalogs Vue Component -->
<v-dashboard-total-catalogs>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-total-catalogs>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-total-catalogs-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <!-- Total catalogs Section -->
        <template v-else>
            <div class="flex gap-4 flex-wrap">

                <!-- Total Products -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-products.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-products')" 
                    label="@lang('admin::app.dashboard.index.total-products')" 
                    :total-count="report.statistics.totalProducts">
                </v-entity-count-display>

                <!-- Total Categories -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-categories.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-categories')" 
                    label="@lang('admin::app.dashboard.index.total-categories')" 
                    :total-count="report.statistics.totalCategories">
                </v-entity-count-display>
            </div>
             
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-catalogs', {
            template: '#v-dashboard-total-catalogs-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});
            },

            methods: {
                getStats(filtets) {
                    this.isLoading = true;

                    var filtets = Object.assign({}, filtets);

                    filtets.type = 'total-catalogs';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filtets
                        })
                        .then(response => {
                            this.report = response.data;
                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>


    <script type="text/x-template" id="v-entity-count-display-template">
        <div class="flex gap-3 p-4 bg-white dark:bg-cherry-900 box-shadow rounded-lg flex-1 min-w-[200px] ">
            <div class="w-full h-[60px] max-w-[60px] max-h-[60px]">
                <img :src="imgSrc" :title="imgTitle">
            </div>
        
            <div class="grid gap-2.5 place-content-start">
                <p class="text-sm text-zinc-600 dark:text-slate-300">
                    @{{label}}
                </p>
                <p class="text-3xl text-zinc-800 leading-none dark:text-slate-50 font-semibold">
                    @{{ totalCount }}
                </p>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-entity-count-display', {
            template: '#v-entity-count-display-template',
            props: ['imgSrc', 'imgTitle', 'label', 'totalCount']
        });
    </script>

@endPushOnce