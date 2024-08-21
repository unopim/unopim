<v-modal-history ref="historyModal"></v-modal-history>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-modal-history-template"
    >
        <div >
            <transition
                tag="div"
                name="modal-overlay"
                enter-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity z-[10002]"
                    v-show="isOpen"
                ></div>
            </transition>

            <transition
                tag="div"
                name="modal-content"
                enter-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-x-full"
                enter-to-class="opacity-100 translate-x-0"
                leave-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-full"
            >
                <div
                    class="fixed inset-0 z-[10002] transform  left-20  right-20 top-24 bottom-4"
                    v-if="isOpen"
                >
                    <!-- Modal Overlay -->
                    <div class="fixed inset-0 z-[9999] flex items-center justify-center outline-none">
                        <!-- Modal Container -->
                        <div class="w-full max-w-[568px] z-[999] absolute ltr:left-1/2 rtl:right-1/2 top-1/2 rounded-lg bg-white dark:bg-gray-900 box-shadow max-md:w-[90%] ltr:-translate-x-1/2 rtl:translate-x-1/2 -translate-y-1/2">
                            <!-- Modal Header -->
                            <div class="flex justify-between items-center p-4 border-b dark:border-cherry-800 text-lg text-gray-800 dark:text-white font-bold">
                                <div>
                                    <h2 class="text-xl">@{{ title }}</h2>
                                    <p class="text-sm font-normal">@{{ subtitle }}</p>
                                </div>
                                
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="icon-cancel text-3xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md"
                                >
                                </button>
                            </div>
                            <!-- Modal Body -->
                            <div class="p-4">
                                <div class="p-4 text-gray-600 dark:text-gray-300">
                                    
                                    <div class="flex  gap-2.5">
                                        <span class="font-bold">@{{ versionLabel }} : </span>
                                        <span>@{{ version }}</span>
                                    </div>
                                    
                                    <div class="flex  gap-2.5">
                                        <span class="font-bold">@{{ dateTimeLabel }} : </span>
                                        <span>@{{ dateTime }}</span>
                                    </div>

                                    <div class="flex  gap-2.5">
                                        <span class="font-bold">@{{ userLabel }} : </span>
                                        <span>@{{ user }}</span>
                                    </div>

                                </div>
                                <div class="p-4 overflow-y-auto max-h-[50vh]" >
                                    <div class="w-full bg-white dark:bg-cherry-800 dark:text-white rounded-lg overflow-hidden shadow-md">
                                        <table class="w-full">
                                            <!-- Table Header -->
                                            <thead>
                                                <tr class="bg-gray-100 dark:bg-cherry-800">
                                                    <th class="py-2 px-4 text-left">
                                                        <span>@{{ nameLabel }}</span>
                                                    </th>
                                                    <th class="py-2 px-4 text-left">
                                                        <span class="text-red-500">@{{ oldValueLabel }}</span>
                                                    </th>
                                                    <th class="py-2 px-4 text-left">
                                                        <span class="text-violet-700">@{{ newValueLabel }}</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <!-- Table Body -->
                                            <tbody>
                                                <template v-if="versionHistory.length === 0">
                                                    <tr>
                                                        <td colspan="3">
                                                            <div class="flex items-center justify-center h-32">
                                                                <span class="text-gray-400 text-2xl">
                                                                    @lang('admin::app.components.modal.history.no-history')
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                            
                                                <template v-else-if="versionHistory">
                                                    <tr v-for="history in versionHistory" :key="history.id" class="border-t dark:border-gray-800">
                                                        <td class="py-2 px-4">@{{ history.name }}</td>
                                                        <td class="py-2 px-4 text-red-500  word-break">@{{ history.old }}</td>
                                                        <td class="py-2 px-4 text-violet-700 word-break">@{{ history.new }}</td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <!-- Add more child elements as needed -->
                            </div>
                        </div>
                    </div>

                </div>
            </transition>
        </div>
    </script>

    <script type="module">
        app.component('v-modal-history', {
            template: '#v-modal-history-template',

            data() {
                return {
                    isOpen: false,

                    title: '',

                    subtitle: '',

                    closeBtn: '',

                    versionLabel: '',

                    dateTimeLabel: '',

                    userLabel: '',

                    nameLabel: '',

                    oldValueLabel: '',

                    newValueLabel: '',

                    closeModalCallback: null,

                    version: '',

                    dateTime: '',

                    user: '',

                    versionHistory: [],

                    url: '',
                };
            },

            created() {
                this.registerGlobalEvents();
            },

            watch: {
                isOpen(newValue) {
                    if (newValue === true) {
                        this.fetchData();
                    }
                }
            },
    
            methods: {
                open({
                    title = "@lang('admin::app.components.modal.history.title')",

                    subtitle= "@lang('admin::app.components.modal.history.subtitle')",

                    closeBtn = "@lang('admin::app.components.modal.history.close-btn')",

                    versionLabel = "@lang('admin::app.components.modal.history.version-label')",

                    dateTimeLabel = "@lang('admin::app.components.modal.history.date-time-label')",

                    userLabel = "@lang('admin::app.components.modal.history.user-label')",

                    nameLabel = "@lang('admin::app.components.modal.history.name-label')",

                    oldValueLabel = "@lang('admin::app.components.modal.history.old-value-label')",

                    newValueLabel = "@lang('admin::app.components.modal.history.new-value-label')",

                    url = '',

                    closeModal = () => {},
                }) {
                    this.isOpen = true;

                    document.body.style.overflow = 'hidden';

                    this.title = title;

                    this.subtitle = subtitle;

                    this.closeBtn = closeBtn;

                    this.versionLabel = versionLabel;

                    this.dateTimeLabel = dateTimeLabel;

                    this.userLabel = userLabel;

                    this.nameLabel = nameLabel;

                    this.oldValueLabel = oldValueLabel;

                    this.newValueLabel = newValueLabel;

                    this.closeModalCallback = closeModal;

                    this.url = url;
                },

                closeModal() {
                    this.isOpen = false;

                    document.body.style.overflow = 'auto';

                    this.closeModalCallback();
                },
 
                registerGlobalEvents() {
                    this.$emitter.on('open-v-confirm-modal', (data) => {
                        this.open(data);
                    });
                },

                fetchData() {
                    this.$axios.get(this.url)
                        .then(response => {
                            this.version = response.data?.version;
                            this.dateTime = response.data?.dateTime;
                            this.user = response.data?.user;
                            this.versionHistory = response.data?.versionHistory;
                        })
                        .catch(error => {
                            console.error(error);
                        });
                },
            }
        });
    </script>
@endPushOnce

