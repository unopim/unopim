@php
    $activeTab = match (true) {
        request()->has('logs') && bouncer()->hasPermission('configuration.webhook.logs') => 'logs',
        request()->has('history')                                                        => 'history',
        default                                                                          => 'general',
    };

    $tabItems = [
        [
            'key'   => 'general',
            'url'   => route('webhook.settings.index'),
            'label' => 'admin::app.components.layouts.sidebar.general',
        ],
    ];

    if (bouncer()->hasPermission('configuration.webhook.logs')) {
        $tabItems[] = [
            'key'   => 'logs',
            'url'   => route('webhook.settings.index', ['logs' => 1]),
            'label' => 'webhook::app.configuration.webhook.settings.index.logs-title',
        ];
    }
@endphp

<x-admin::layouts.with-history
    :activeTab="$activeTab"
    entity-name="webhook_settings"
    history-id="1"
    :tab-items="$tabItems"
>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('webhook::app.configuration.webhook.settings.index.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('webhook::app.configuration.webhook.settings.index.title')"
            :sticky="false"
        />
    </x-slot>

    <x-slot:tabContents>
        @if ($activeTab === 'general')
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
                            <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                                <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto text-gray-600 dark:text-gray-300">
                                    <template v-if="isLoading">
                                        <div class="shimmer h-[120px] p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                            <p class="text-base font-semibold mb-4">
                                                @lang('webhook::app.configuration.webhook.settings.index.name')
                                            </p>

                                            <div class="mb-4">
                                                <div class="flex flex-col gap-2 mt-2">
                                                    <label class="text-sm break-words text-gray-800 dark:text-white">@lang('webhook::app.configuration.webhook.settings.index.webhook_url.label')</label>
                                                    <x-admin::form.control-group class="!mb-0">
                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            name="webhook_url"
                                                            id="webhook_url"
                                                            :label="trans('webhook::app.configuration.webhook.settings.index.webhook_url.label')"
                                                            ::value="formData?.webhook_url"
                                                        />
                                                        <x-admin::form.control-group.error control-name="webhook_url" />
                                                    </x-admin::form.control-group>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                                    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                        <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                            @lang('webhook::app.configuration.webhook.settings.index.general')
                                        </p>

                                        <x-admin::form.control-group class="!mb-0">
                                            <x-admin::form.control-group.label>
                                                @lang('webhook::app.configuration.webhook.settings.index.active.label')
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="switch"
                                                name="webhook_active"
                                                value="1"
                                                :label="trans('webhook::app.configuration.webhook.settings.index.active.label')"
                                                ::checked="formData?.webhook_active"
                                            />

                                            <x-admin::form.control-group.error control-name="webhook_active" />
                                        </x-admin::form.control-group>
                                    </div>

                                    @if (bouncer()->hasPermission('configuration.webhook.settings.update'))
                                        <div class="flex justify-end">
                                            <button
                                                type="submit"
                                                class="primary-button"
                                                :disabled="isLoading"
                                            >
                                                @lang('webhook::app.configuration.webhook.settings.index.save')
                                            </button>
                                        </div>
                                    @endif
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

                                        if (error.response?.status === 400 || error.response?.status === 422) {
                                            setErrors(error.response.data.errors || {});
                                        }
                                    }).finally(() => this.isLoading = false);
                            },
                        },
                    });
                </script>
            @endPushOnce
        @endif

        @if ($activeTab === 'logs' && bouncer()->hasPermission('configuration.webhook.logs'))
            @include('webhook::logs.index')
        @endif
    </x-slot>
</x-admin::layouts.with-history>
