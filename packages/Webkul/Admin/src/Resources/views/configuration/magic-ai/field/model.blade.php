@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $selectedOptions = core()->getConfigData($nameKey);
    $selectedOptions = json_encode(explode(',', $selectedOptions) ?? []);
@endphp

<v-ai-model
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value="{{ $selectedOptions }}">
</v-ai-model>

@pushOnce('scripts')
    <script type="text/x-template" id="v-ai-model-template">
        <div class="grid gap-2.5 content-start">
            <div class="flex gap-x-2.5 items-center" v-if="!isValid">
                <button type="button" class="primary-button" @click="validated"> Validate Credentials </button>
            </div>
            <div>
                <!-- Groq -->
                <x-admin::form.control-group class="mb-4" v-if="aiCredentials.api_platform === 'groq'">
                    <x-admin::form.control-group.label>
                        @{{ label }}
                        @php
                            $modelOptions = [ 
                                "deepseek-r1-distill-llama-70b",
                                "llama-3.1-8b-instant",
                                "openai/gpt-oss-120b",
                                "openai/gpt-oss-20b",
                                "groq/compound",
                                "qwen/qwen3-32b",
                                "moonshotai/kimi-k2-instruct-0905"
                            ];
                            $options = [];
                            foreach($modelOptions as $option) {
                                $options[] = [
                                    'id'    => $option,
                                    'label' => $option,
                                ];
                            }
                            $optionsInJson = json_encode($options);
                        @endphp
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="multiselect"
                        ref="aiModelRef"
                        ::id="name"
                        ::name="name"
                        :options="$optionsInJson"
                        ::value="value"
                        ::label="label"
                        ::placeholder="label"
                        track-by="id"
                        label-by="label"
                        @input="getOptionValue"
                    />
                    <x-admin::form.control-group.error ::control-name="name" />
                </x-admin::form.control-group>

                <!-- Open AI -->
                <x-admin::form.control-group class="mb-4" v-if="aiCredentials.api_platform === 'openai'">
                    <x-admin::form.control-group.label>
                        @{{ label }}
                        @php
                            $modelOptions = [
                                "gpt-5.2",
                                "gpt-5",
                                "gpt-5.1",
                                "gpt-5-mini",
                                "gpt-5-nano",
                                "gpt-4o", 
                                "gpt-4o-mini", 
                                "gpt-3.5-turbo", 
                                "dall-e-2", 
                                "dall-e-3",
                                "gpt-image-1.5",
                                "gpt-image-1",
                                "gpt-image-1-mini",
                            ];
                            $options = [];
                            foreach($modelOptions as $option) {
                                $options[] = [
                                    'id'    => $option,
                                    'label' => $option,
                                ];
                            }

                            $optionsInJson = json_encode($options);
                        @endphp
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="multiselect"
                        ref="aiModelRef"
                        ::id="name"
                        ::name="name"
                        :options="$optionsInJson"
                        ::value="value"
                        ::label="label"
                        ::placeholder="label"
                        track-by="id"
                        label-by="label"
                        @input="getOptionValue"
                    />
                    <x-admin::form.control-group.error ::control-name="name" />
                </x-admin::form.control-group>

                <!-- Ollama -->
                <x-admin::form.control-group class="mb-4" v-if="aiCredentials.api_platform === 'ollama'">
                    <x-admin::form.control-group.label>
                        @{{ label }}
                        @php
                            $modelOptions = [
                                "llama2", 
                                "llama3",  
                                "mistral", 
                                "qwen", 
                                "deepseek-coder", 
                                "phi", 
                                "llava"];
                            $options = [];
                            foreach($modelOptions as $option) {
                                $options[] = [
                                    'id'    => $option,
                                    'label' => $option,
                                ];
                            }
                            $optionsInJson = json_encode($options);
                        @endphp
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="multiselect"
                        ref="aiModelRef"
                        ::id="name"
                        ::name="name"
                        :options="$optionsInJson"
                        ::value="value"
                        ::label="label"
                        ::placeholder="label"
                        track-by="id"
                        label-by="label"
                        @input="getOptionValue"
                    />
                    <x-admin::form.control-group.error ::control-name="name" />
                </x-admin::form.control-group>

                <!-- Gemini -->
                <x-admin::form.control-group class="mb-4" v-if="aiCredentials.api_platform === 'gemini'">
                    <x-admin::form.control-group.label>
                        @{{ label }}
                        @php
                            $modelOptions = [
                                "gemini-3-pro-preview",
                                "gemini-3-flash-preview",
                                "gemini-2.5-pro",
                                "gemini-2.5-flash",
                                "gemini-2.0-flash",
                                "gemini-1.5-flash-latest",
                                "gemini-1.5-pro",
                                "gemini-2.5-flash-image",
                                "gemini-3-pro-image-preview",
                            ];
                            $options = array_map(fn($option) => ['id' => $option, 'label' => $option], $modelOptions);
                            $optionsInJson = json_encode($options);
                        @endphp
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="multiselect"
                        ref="aiModelRef"
                        ::id="name"
                        ::name="name"
                        :options="$optionsInJson"
                        ::value="value"
                        ::label="label"
                        ::placeholder="label"
                        track-by="id"
                        label-by="label"
                        @input="getOptionValue"
                    />
                    <x-admin::form.control-group.error ::control-name="name" />
                </x-admin::form.control-group>
            </div>
        </div>
    </script>
    <script type="module">
        app.component('v-ai-model', {
            template: '#v-ai-model-template',
            props: [
                'label',
                'name',
                'validations',
                'value',
            ],
            data: function() {
                return {
                    modelsOptions: null,
                    errorMessage: null,
                    aiCredentials: {
                        api_key: "{{ core()->getConfigData('general.magic_ai.settings.api_key') }}",
                        api_domain: "{{ core()->getConfigData('general.magic_ai.settings.api_domain') }}",
                        api_platform: "{{ core()->getConfigData('general.magic_ai.settings.ai_platform') }}",
                        enabled: "{{ core()->getConfigData('general.magic_ai.settings.enabled') }}",
                    },

                    value: this.value,

                    isValid: true,
                }
            },
            mounted() {
                this.$emitter.on('config-value-changed', (data) => {
                    if (data.fieldName == 'general[magic_ai][settings][enabled]') {
                        this.aiCredentials.enabled = data.value;
                        this.isValid = false;
                    }

                    if (data.fieldName == 'general[magic_ai][settings][ai_platform]') {
                        this.aiCredentials.api_platform = this.parseJson(data.value)?.value;
                        this.isValid = false;
                    }

                    if (data.fieldName == 'general[magic_ai][settings][api_key]') {
                        this.aiCredentials.api_key = data.value;
                        this.isValid = false;
                    }

                    if (data.fieldName == 'general[magic_ai][settings][api_domain]') {
                        this.aiCredentials.api_domain = data.value;
                        this.isValid = false;
                    }
                });
            },

            methods: {
                parseJson(value) {
                    try {
                        return JSON.parse(value);
                    } catch (e) {
                        return null;
                    }
                },
                async validated() {
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.validate_credential') }}", {
                            params: this.aiCredentials
                        });
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                        this.isValid = true;
                    } catch (error) {
                        console.error("Failed to fetch AI models:", error);
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response.data.message
                        });
                    }
                },

                getOptionValue(event) {
                    this.$emitter.emit('model_value_change', event);
                }
            }
        });
    </script>
@endPushOnce
