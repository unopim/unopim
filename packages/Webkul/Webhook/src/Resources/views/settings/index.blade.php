<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('webhook::app.configuration.webhook.settings.index.title')
    </x-slot>

    <v-webhook-settings></v-webhook-settings>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-webhook-settings-template">
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="storeConfigurations"
            >
                <form
                    @submit="handleSubmit($event, storeConfigurations)"
                    ref="storeConfigurationsForm"
                >
                    <div class="flex justify-between items-center">
                        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                            @lang('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.title')
                        </p>
                        @if (bouncer()->hasPermission('configuration.webhook.settings.update'))
                            <div class="flex gap-x-2.5 items-center">
                                <button 
                                    type="submit" 
                                    class="primary-button"
                                    :disabled="isLoading"
                                >
                                    @lang('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.save')
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                            <div class="bg-white dark:bg-cherry-900 rounded box-shadow" style="height:-webkit-fill-available">
                                <div class="flex items-center justify-between p-1.5">
                                    <div class="flex items-center justify-between">
                                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">  @lang('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.general')  </p>
                                    </div>
                                    <span class="text-2xl p-1.5 rounded-md cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 icon-arrow-up"></span>
                                </div>
                                <div class="px-4 pb-4">
                                    <div class="mb-4 !mb-0">
                                        <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-600 dark:text-gray-300 font-medium">
                                            @lang('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.active.label') 
                                        </label>
                                        <x-admin::form.control-group class="!mb-0">
                                            <x-admin::form.control-group.control
                                                type="switch"
                                                name="webhook_active"
                                                value="1"
                                                :label="trans('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.active.label')"
                                                ::checked="formData?.webhook_active"
                                            />
                                            <x-admin::form.control-group.error control-name="webhook_active" />
                                        </x-admin::form.control-group>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto text-gray-600 dark:text-gray-300">
                            <template v-if="isLoading">
                                <div class="shimmer h-[120px] p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                </div>
                            </template>
                            <template v-else>
                                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                    <p class="text-base font-semibold mb-4"> 
                                        @lang('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.name') 
                                    </p> 

                                    <div class="mb-4">
                                        <div class="flex flex-col gap-2 mt-2">
                                            <label class="text-sm break-words required">@lang('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.webhook_url.label')</label>
                                            <x-admin::form.control-group class="!mb-0">
                                                <x-admin::form.control-group.control
                                                    type="text"
                                                    name="webhook_url"
                                                    id="webhook_url"
                                                    rules="required"
                                                    :label="trans('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.webhook_url.label')"
                                                    ::value="formData?.webhook_url"
                                                />
                                                <x-admin::form.control-group.error control-name="webhook_url" />
                                            </x-admin::form.control-group>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-webhook-settings', {
                template: '#v-webhook-settings-template',
                props: {
                    initialChecked: {
                        type: [Boolean, String],
                        required: true
                    },
                    configurations: {
                        type: Object,
                        required: true
                    }
                },
                data() {
                    return {
                        isLoading: false,
                        isEnabled: 0,
                        formData: null,
                    };
                    
                },

                mounted() {
                    this.loadData();
                },

                methods: {
                    loadData() {
                        this.isLoading = true;

                        this.$axios.get("{{ route('webhook.settings.get') }}")
                        .then((response) => {
                            this.formData = response.data.data;
                            this.isLoading = false;
                        })
                        .catch((error) => {
                            console.error('Error fetching webhook settings:', error);
                        });
                    },

                    storeConfigurations(params, {
                            resetForm,
                            setErrors
                    }) {
                        this.isLoading = true;
                        
                        let formData = new FormData(this.$refs.storeConfigurationsForm);

                        this.$axios.post("{{ route('webhook.settings.store') }}", formData)
                            .then((response) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                this.formData.webhook_url = response?.data?.data?.field === 'webhook_url' ? response?.data?.data?.value : this.formData.webhook_url;
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });

                                if (error.status == 400) {
                                    setErrors(error.response.data.errors);
                                }
                            }).finally(() => this.isLoading = false);
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
