<!-- Total configurations Vue Component -->
<v-dashboard-total-configurations>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-total-configurations>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-total-configurations-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <!-- Total Configuration Section -->
        <template v-else>
            <div class="flex gap-4 flex-wrap">
                
                <!-- Total Attributes -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-attribute.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-attributes')" 
                    label="@lang('admin::app.dashboard.index.total-attributes')" 
                    :total-count="report.statistics.totalAttributes">
                </v-entity-count-display>

                <!-- Total Groups -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-groups.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-groups')" 
                    label="@lang('admin::app.dashboard.index.total-groups')" 
                    :total-count="report.statistics.totalAttributeGroups">
                </v-entity-count-display>

                <!-- Total Attribute Families -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-families.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-families')" 
                    label="@lang('admin::app.dashboard.index.total-families')" 
                    :total-count="report.statistics.totalAttributeFamilies">
                </v-entity-count-display>

                <!-- Total Locales -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-locale.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-locales')" 
                    label="@lang('admin::app.dashboard.index.total-locales')" 
                    :total-count="report.statistics.totalLocales">
                </v-entity-count-display>

                <!-- Total Currencies -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-products.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-currencies')" 
                    label="@lang('admin::app.dashboard.index.total-currencies')" 
                    :total-count="report.statistics.totalCurrencies">
                </v-entity-count-display>

                <!-- Total Channels -->
                <v-entity-count-display
                    img-src="{{ unopim_asset('images/icon-channel.svg')}}" 
                    img-title="@lang('admin::app.dashboard.index.total-channels')" 
                    label="@lang('admin::app.dashboard.index.total-channels')" 
                    :total-count="report.statistics.totalChannels">
                </v-entity-count-display>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-configurations', {
            template: '#v-dashboard-total-configurations-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});
                
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filtets) {
                    this.isLoading = true;

                    var filtets = Object.assign({}, filtets);

                    filtets.type = 'total-configurations';

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
@endPushOnce