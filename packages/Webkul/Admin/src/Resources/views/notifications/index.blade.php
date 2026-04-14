<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.notifications.title')
    </x-slot>

    {!! view_render_event('unopim.admin.marketing.notifications.create.before') !!}

    <!-- Vue Component -->
    <v-notification-list></v-notification-list>

    {!! view_render_event('unopim.admin.marketing.notifications.create.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-notification-list-template"
        >
            <div class="flex gap-4 justify-between items-center mb-5 max-sm:flex-wrap">
                <div class="grid gap-1.5">
                    <p class="pt-1.5 text-xl text-gray-800 dark:text-slate-50 font-bold leading-6">
                        @lang('admin::app.notifications.title')
                    </p>

                    <p class="text-gray-600 dark:text-gray-300">
                        @lang('admin::app.notifications.description-text')
                    </p>
                </div>

                <div class="flex gap-x-2.5 items-center">
                    <button
                        class="transparent-button"
                        v-if="totalUnread > 0"
                        @click="readAll"
                    >
                        @lang('admin::app.notifications.read-all')
                    </button>
                </div>
            </div>

            <div class="flex flex-col mb-16 bg-white dark:bg-cherry-900 rounded-md box-shadow">
                <!-- Status Tabs (uses x-admin::tabs styling pattern) -->
                <div class="flex gap-4 pt-2 border-b dark:border-gray-800">
                    <div
                        v-for="tab in tabs"
                        :key="tab.value"
                        class="pb-3.5 px-2.5 text-base font-medium cursor-pointer transition-all border-b-2"
                        :class="status === tab.value
                            ? 'border-violet-700 text-violet-700'
                            : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-violet-700'"
                        @click="onTabChange(tab.value)"
                    >
                        <span v-text="tab.title"></span>

                        <span
                            v-if="tab.value === 'unread' && totalUnread > 0"
                            class="ml-1 inline-flex items-center justify-center min-w-5 h-5 px-1.5 bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 rounded-full text-xs font-semibold"
                            v-text="totalUnread"
                        ></span>
                    </div>
                </div>

                <!-- Notification List -->
                <div
                    class="grid"
                    v-if="userNotifications.length"
                >
                    <a
                        class="flex gap-4 p-4 items-start border-b last:border-b-0 dark:border-gray-800 transition-all"
                        v-for="userNotification in userNotifications"
                        :key="userNotification.id"
                        :href="'{{ route('admin.notification.viewed_notification', ':id') }}'.replace(':id', userNotification.notification.id)"
                        :class="isRead(userNotification)
                            ? 'bg-gray-50 dark:bg-cherry-900/50 hover:bg-gray-100 dark:hover:bg-cherry-800'
                            : 'bg-white dark:bg-cherry-900 hover:bg-violet-50 dark:hover:bg-cherry-800'"
                    >
                        <!-- Unread indicator -->
                        <div class="flex-shrink-0 mt-1.5">
                            <span
                                class="block w-2.5 h-2.5 rounded-full"
                                :class="isRead(userNotification) ? 'bg-gray-300 dark:bg-gray-600' : 'bg-violet-500'"
                            ></span>
                        </div>

                        <div class="grid gap-1.5 flex-1 min-w-0">
                            <p
                                class="text-sm truncate"
                                :class="isRead(userNotification)
                                    ? 'font-medium text-gray-500 dark:text-gray-400'
                                    : 'font-bold text-gray-900 dark:text-white'"
                                v-text="userNotification.notification.title"
                            ></p>

                            <p
                                class="text-sm line-clamp-2"
                                :class="isRead(userNotification)
                                    ? 'text-gray-400 dark:text-gray-500'
                                    : 'text-gray-600 dark:text-gray-300'"
                                v-html="userNotification.notification.description"
                            ></p>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                @{{ userNotification.notification.created_at_human }}
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Empty State -->
                <div
                    class="flex flex-col items-center justify-center py-16 px-6 text-center"
                    v-else
                >
                    <span class="icon-notification text-5xl text-gray-300 dark:text-gray-600 mb-4"></span>

                    <p class="text-gray-600 dark:text-gray-300 text-base font-medium">
                        @lang('admin::app.notifications.no-record')
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    class="flex gap-x-2 items-center justify-between p-4 border-t dark:border-gray-800"
                    v-if="userNotifications.length"
                >
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('admin::app.notifications.showing')
                        @{{ pagination.from ?? 0 }}-@{{ pagination.to ?? 0 }}
                        @lang('admin::app.notifications.of')
                        @{{ pagination.total ?? 0 }}
                    </p>

                    <div class="flex gap-1 items-center">
                        <button
                            class="inline-flex items-center justify-center p-1.5 bg-white dark:bg-cherry-800 border dark:border-cherry-800 rounded-md text-gray-600 dark:text-gray-300 cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!pagination.prev_page_url"
                            @click="getResults(pagination.prev_page_url)"
                        >
                            <span class="icon-chevron-left text-2xl"></span>
                        </button>

                        <span class="px-2 text-sm text-gray-600 dark:text-gray-300">
                            @{{ pagination.current_page }} / @{{ pagination.last_page }}
                        </span>

                        <button
                            class="inline-flex items-center justify-center p-1.5 bg-white dark:bg-cherry-800 border dark:border-cherry-800 rounded-md text-gray-600 dark:text-gray-300 cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!pagination.next_page_url"
                            @click="getResults(pagination.next_page_url)"
                        >
                            <span class="icon-chevron-right text-2xl"></span>
                        </button>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-notification-list', {
                template: '#v-notification-list-template',

                data() {
                    return {
                        userNotifications: [],

                        pagination: {},

                        totalUnread: 0,

                        status: 'all',

                        tabs: [
                            { title: '@lang('admin::app.notifications.status.all')', value: 'all' },
                            { title: '@lang('admin::app.notifications.status.unread')', value: 'unread' },
                            { title: '@lang('admin::app.notifications.status.read')', value: 'read' },
                        ],
                    }
                },

                mounted() {
                    this.getNotification();
                },

                methods: {
                    isRead(userNotification) {
                        return Number(userNotification.read) === 1;
                    },

                    onTabChange(value) {
                        this.status = value;

                        this.getNotification();
                    },

                    getNotification() {
                        const params = {};

                        if (this.status === 'unread') {
                            params.read = 0;
                        } else if (this.status === 'read') {
                            params.read = 1;
                        }

                        this.$axios.get("{{ route('admin.notification.get_notification') }}", {
                            params: params
                        })
                        .then((response) => {
                            this.userNotifications = response.data.search_results.data;

                            this.pagination = response.data.search_results;

                            this.totalUnread = response.data.total_unread;
                        })
                        .catch(error => console.log(error));
                    },

                    getResults(url) {
                        if (url) {
                            this.$axios.get(url)
                                .then(response => {
                                    this.userNotifications = response.data.search_results.data;

                                    this.pagination = response.data.search_results;

                                    this.totalUnread = response.data.total_unread;
                                });
                        }
                    },

                    readAll() {
                        this.$axios.post("{{ route('admin.notification.read_all') }}")
                            .then((response) => {
                                this.totalUnread = 0;

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.success_message
                                });

                                this.getNotification();
                            })
                            .catch(error => console.log(error));
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
