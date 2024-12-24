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
            </div>

            <div class="flex flex-col justify-between max-w-max bg-white dark:bg-cherry-800 rounded-md box-shadow h-[calc(100vh-179px)]">
                <div class="">
                    <div class="flex border-b dark:border-cherry-800 overflow-auto journal-scroll">
                           

                    </div>

                    <div
                        class="grid max-h-[calc(100vh-330px)] overflow-auto journal-scroll"
                        v-if="userNotifications.length"
                    >
                        <a
                            class="flex gap-1.5 p-4 items-start hover:bg-violet-50 dark:hover:bg-cherry-800"
                            v-for="userNotification in userNotifications"
                            :href="'{{ route('admin.notification.viewed_notification', ':id') }}'.replace(':id', userNotification.notification.id)"
                        >
                            <span
                                class="h-fit text-2xl rounded-full"
                            >
                            </span>

                            <div class="grid gap-2">
                                <p 
                                    class="text-sm text-base text-gray-800 dark:text-white"
                                    v-text="userNotification.notification.title"
                                ></p>

                                <p 
                                    class="text-xs text-gray-600 dark:text-gray-300"
                                    v-html="userNotification.notification.description"
                                ></p>
                                
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @{{ userNotification.notification.created_at_human }}
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- For Empty Data -->
                    <div
                        class="px-6 py-3 text-gray-600 dark:text-gray-300 max-h-[calc(100vh-330px)]"
                        v-else
                    >
                        @lang('admin::app.notifications.no-record')
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex gap-x-2 items-center p-4 border-t dark:border-gray-800">
                    <div
                        class="inline-flex gap-x-1 items-center justify-between w-full max-w-max py-1.5 px-2 ltr:ml-2 rtl:mr-2 bg-white dark:bg-cherry-800 border dark:border-cherry-800 rounded-md text-gray-600 dark:text-gray-300 leading-6 text-center marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black max-sm:hidden"
                        v-text="pagination.per_page"
                    >
                    </div>

                    <span class="text-gray-600 dark:text-gray-300 whitespace-nowrap">per Page</span>

                    <p
                        class="text-gray-600 dark:text-gray-300 whitespace-nowrap"
                        v-text="pagination.current_page"
                    >
                    </p>

                    <span class="text-gray-600 dark:text-gray-300 whitespace-nowrap">of</span>

                    <p
                        class="text-gray-600 dark:text-gray-300 whitespace-nowrap"
                        v-text="pagination.last_page"
                    >
                    </p>

                    <!-- Prev & Next Page Button -->
                    <div class="flex gap-1 items-center">
                        <a @click="getResults(pagination.prev_page_url)">
                            <div class="inline-flex gap-x-1 items-center justify-between w-full max-w-max ltr:ml-2 rtl:mr-2 p-1.5 bg-white dark:bg-cherry-800 border rounded-md dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center cursor-pointer transition-all hover:border hover:bg-violet-50 dark:hover:bg-cherry-800 marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black">
                                <span class="icon-chevron-left text-2xl"></span>
                            </div>
                        </a>

                        <a @click="getResults(pagination.next_page_url)">
                            <div
                                class="inline-flex gap-x-1 items-center justify-between w-full max-w-max ltr:ml-2 rtl:mr-2 p-1.5 bg-white dark:bg-cherry-800 border rounded-md dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center cursor-pointer transition-all hover:border hover:bg-violet-50 dark:hover:bg-cherry-800 marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black">
                                <span class="icon-chevron-right text-2xl"></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-notification-list',{
                template: '#v-notification-list-template',

                data() {
                    return {
                        userNotifications: [],

                        pagination: {},

                        status: 'all',
                    }
                },

                mounted() {
                    this.getNotification();
                },

                methods: {
                    getNotification($event) {
                        const params = {};

                        if (this.status != 'all') {
                            params.status = this.status
                        }

                        this.$axios.get("{{ route('admin.notification.get_notification') }}", {
                            params: params
                        })
                        .then((response) => {
                            this.userNotifications = response.data.search_results.data;

                            this.pagination = response.data.search_results;
                        })
                        .catch(error => console.log(error));
                    },

                    getResults(url) {
                        if (url) {
                            axios.get(url)
                                .then(response => {
                                    this.userNotifications = [];
    
                                    this.userNotifications = response.data.search_results.data;
    
                                    this.pagination = response.data.search_results;
                                });
                        }
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>