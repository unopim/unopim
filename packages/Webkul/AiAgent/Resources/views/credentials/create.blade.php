<x-admin::layouts>
    <x-slot:title>
        @lang('ai-agent::app.credentials.create-title')
    </x-slot:title>

    <v-credential-create />

    @pushOnce('scripts')
        <script type="text/x-template" id="v-credential-create-template">
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form @submit="handleSubmit($event, store)" ref="credentialForm">
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                            @lang('ai-agent::app.credentials.create-title')
                        </p>

                        <div class="flex items-center gap-x-2.5">
                            <a
                                href="{{ route('ai-agent.credentials.index') }}"
                                class="transparent-button"
                            >
                                @lang('ai-agent::app.common.back')
                            </a>

                            <button
                                type="submit"
                                class="primary-button"
                                :disabled="isLoading"
                            >
                                <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                @lang('ai-agent::app.common.save')
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <!-- Left -->
                        <div class="flex flex-col flex-1 gap-2 overflow-auto">
                            <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('ai-agent::app.credentials.general')
                                </p>

                                <!-- Label -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('ai-agent::app.credentials.fields.label')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="label"
                                        rules="required"
                                        :label="trans('ai-agent::app.credentials.fields.label')"
                                        :placeholder="trans('ai-agent::app.credentials.fields.label-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="label" />
                                </x-admin::form.control-group>

                                <!-- Provider -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('ai-agent::app.credentials.fields.provider')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $providerOptions = json_encode([
                                            ['id' => 'openai',    'label' => 'OpenAI'],
                                            ['id' => 'anthropic', 'label' => 'Anthropic'],
                                            ['id' => 'azure',     'label' => 'Azure OpenAI'],
                                            ['id' => 'custom',    'label' => 'Custom'],
                                        ]);
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="provider"
                                        rules="required"
                                        :label="trans('ai-agent::app.credentials.fields.provider')"
                                        :placeholder="trans('ai-agent::app.credentials.fields.provider-placeholder')"
                                        :options="$providerOptions"
                                        track-by="id"
                                        label-by="label"
                                    />

                                    <x-admin::form.control-group.error control-name="provider" />
                                </x-admin::form.control-group>

                                <!-- API URL -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('ai-agent::app.credentials.fields.api-url')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="apiUrl"
                                        rules="required|url"
                                        :label="trans('ai-agent::app.credentials.fields.api-url')"
                                        :placeholder="trans('ai-agent::app.credentials.fields.api-url-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="apiUrl" />
                                </x-admin::form.control-group>

                                <!-- API Key -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('ai-agent::app.credentials.fields.api-key')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="password"
                                        name="apiKey"
                                        rules="required"
                                        :label="trans('ai-agent::app.credentials.fields.api-key')"
                                        :placeholder="trans('ai-agent::app.credentials.fields.api-key-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="apiKey" />
                                </x-admin::form.control-group>

                                <!-- Model -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('ai-agent::app.credentials.fields.model')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="model"
                                        rules="required"
                                        :label="trans('ai-agent::app.credentials.fields.model')"
                                        :placeholder="trans('ai-agent::app.credentials.fields.model-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="model" />
                                </x-admin::form.control-group>
                            </div>
                        </div>

                        <!-- Right sidebar -->
                        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                            <x-admin::accordion>
                                <x-slot:header>
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('ai-agent::app.credentials.settings')
                                    </p>
                                </x-slot:header>

                                <x-slot:content>
                                    <!-- Status -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label>
                                            @lang('ai-agent::app.credentials.fields.status')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="switch"
                                            name="status"
                                            value="1"
                                            :label="trans('ai-agent::app.credentials.fields.status')"
                                            :checked="true"
                                        />

                                        <x-admin::form.control-group.error control-name="status" />
                                    </x-admin::form.control-group>
                                </x-slot:content>
                            </x-admin::accordion>
                        </div>
                    </div>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-credential-create', {
                template: '#v-credential-create-template',

                data() {
                    return {
                        isLoading: false,
                    };
                },

                methods: {
                    parseSelectValue(value) {
                        try {
                            let parsed = JSON.parse(value);
                            return parsed.id ?? value;
                        } catch (e) {
                            return value;
                        }
                    },

                    store(params, { resetForm, setErrors }) {
                        this.isLoading = true;

                        params.provider = this.parseSelectValue(params.provider);

                        this.$axios.post("{{ route('ai-agent.credentials.store') }}", params)
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                if (response.data.redirect_url) {
                                    window.location.href = response.data.redirect_url;
                                }
                            })
                            .catch((error) => {
                                if (error.response.status === 422) {
                                    setErrors(error.response.data.errors);
                                }

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message || "{{ trans('ai-agent::app.common.error-generic') }}" });
                            })
                            .finally(() => {
                                this.isLoading = false;
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
