<!-- Recent Activity Vue Component -->
<v-dashboard-recent-activity>
    <x-admin::shimmer.dashboard.total-catalogs />
</v-dashboard-recent-activity>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-recent-activity-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.total-catalogs />
        </template>

        <template v-else>
            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow h-full">
                <div v-if="activities.length > 0" class="divide-y divide-zinc-100 dark:divide-cherry-800">
                    <div
                        v-for="(activity, index) in activities"
                        :key="activity.id"
                        class="flex items-start gap-3 p-4 hover:bg-zinc-50 dark:hover:bg-cherry-800/50 transition-colors"
                    >
                        <!-- Event Icon -->
                        <div
                            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mt-0.5"
                            :class="getEventBgColor(activity.event)"
                        >
                            <span
                                class="text-sm"
                                :class="getEventIconClass(activity.event)"
                            ></span>
                        </div>

                        <!-- Activity Details -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-800 dark:text-slate-200">
                                <span class="font-semibold">@{{ activity.user_name || 'System' }}</span>&nbsp;
                                <span class="text-zinc-500 dark:text-slate-400 mx-1">
                                    @{{ getEventLabel(activity.event) }}
                                </span>&nbsp;
                                <span v-if="activity.entity_type" class="font-medium capitalize">@{{ getEntityLabel(activity.entity_type) }}</span>
                                <span v-if="activity.history_id" class="text-zinc-400 dark:text-slate-500 ml-1">
                                    #@{{ activity.history_id }}
                                </span>
                            </p>

                            <p class="text-xs text-zinc-400 dark:text-slate-500 mt-1">
                                @{{ activity.time_ago }}
                            </p>
                        </div>

                        <!-- Entity Type Badge -->
                        <span
                            v-if="activity.entity_type"
                            class="flex-shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full uppercase"
                            :class="getEntityBadgeColor(activity.entity_type)"
                        >
                            @{{ getEntityLabel(activity.entity_type) }}
                        </span>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <span class="icon-calendar text-4xl text-zinc-300 dark:text-cherry-700"></span>

                    <p class="text-sm text-zinc-400 dark:text-slate-500 mt-2">
                        @lang('admin::app.dashboard.index.no-activity')
                    </p>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-recent-activity', {
            template: '#v-dashboard-recent-activity-template',

            data() {
                return {
                    activities: [],
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
                            params: { type: 'recent-activity' }
                        })
                        .then(response => {
                            this.activities = response.data.statistics.activities;
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                        });
                },

                getEventLabel(event) {
                    const labels = {
                        'created': "@lang('admin::app.dashboard.index.created')",
                        'updated': "@lang('admin::app.dashboard.index.updated')",
                        'deleted': "@lang('admin::app.dashboard.index.deleted')",
                    };

                    return labels[event] || event;
                },

                getEventIconClass(event) {
                    const icons = {
                        'created': 'icon-done text-emerald-600',
                        'updated': 'icon-edit text-sky-600',
                        'deleted': 'icon-delete text-red-500',
                    };

                    return icons[event] || 'icon-information text-zinc-500';
                },

                getEventBgColor(event) {
                    const colors = {
                        'created': 'bg-emerald-100 dark:bg-emerald-900/30',
                        'updated': 'bg-sky-100 dark:bg-sky-900/30',
                        'deleted': 'bg-red-100 dark:bg-red-900/30',
                    };

                    return colors[event] || 'bg-zinc-100 dark:bg-cherry-800';
                },

                getEntityLabel(entityType) {
                    const labels = {
                        'product':              "@lang('admin::app.dashboard.index.entity-types.product')",
                        'category':             "@lang('admin::app.dashboard.index.entity-types.category')",
                        'attribute':            "@lang('admin::app.dashboard.index.entity-types.attribute')",
                        'attributeFamily':      "@lang('admin::app.dashboard.index.entity-types.attribute-family')",
                        'attribute_family':     "@lang('admin::app.dashboard.index.entity-types.attribute-family')",
                        'attributeGroup':       "@lang('admin::app.dashboard.index.entity-types.attribute-group')",
                        'attribute_group':      "@lang('admin::app.dashboard.index.entity-types.attribute-group')",
                        'category_field':       "@lang('admin::app.dashboard.index.entity-types.category-field')",
                        'channel':              "@lang('admin::app.dashboard.index.entity-types.channel')",
                        'role':                 "@lang('admin::app.dashboard.index.entity-types.role')",
                        'job_instance':         "@lang('admin::app.dashboard.index.entity-types.job-instance')",
                        'webhook_settings':     "@lang('admin::app.dashboard.index.entity-types.webhook')",
                        'Apikey':               "@lang('admin::app.dashboard.index.entity-types.api-key')",
                    };

                    return labels[entityType] || entityType.replace(/_/g, ' ');
                },

                getEntityBadgeColor(entityType) {
                    const colors = {
                        'product':            'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
                        'category':           'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                        'attribute':          'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'attributeFamily':    'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                        'attribute_family':   'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                        'attributeGroup':     'bg-lime-100 text-lime-700 dark:bg-lime-900/30 dark:text-lime-400',
                        'attribute_group':    'bg-lime-100 text-lime-700 dark:bg-lime-900/30 dark:text-lime-400',
                        'category_field':     'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                        'channel':            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                        'role':               'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                        'job_instance':       'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        'webhook_settings':   'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-400',
                        'Apikey':             'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                        'measurement Family': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    };

                    return colors[entityType] || 'bg-zinc-100 text-zinc-600 dark:bg-cherry-800 dark:text-slate-400';
                }
            }
        });
    </script>
@endPushOnce
