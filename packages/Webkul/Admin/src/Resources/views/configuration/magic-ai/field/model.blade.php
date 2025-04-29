@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
    $nameKey = $item['key'] . '.' . $field['name'];

    $name = $coreConfigRepository->getNameField($nameKey);

    $selectedOptions = core()->getConfigData($nameKey);
    $selectedOptions = json_encode(explode(',', $selectedOptions) ?? []);
@endphp

<v-ai-model
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value="{{ $selectedOptions }}"
></v-ai-model>

@pushOnce('scripts')
<script type="text/x-template" id="v-ai-model-template">
    <div class="grid gap-2.5 content-start">
        <div class="flex gap-x-2.5 items-center" v-if="!isValid">
            <button type="button" class="primary-button" @click="validated"> Validate Credentials </button>
        </div>
        <div>
            <x-admin::form.control-group class="mb-4" v-if="modelsOptions">
                <x-admin::form.control-group.label>
                    @{{ label }}
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="multiselect"
                    ref="aiModelRef"
                    ::id="name"
                    ::name="name"
                    rules="required"
                    ::options="modelsOptions"
                    ::value="value"
                    ::label="label"
                    ::placeholder="label"
                    track-by="id"
                    label-by="label"
                />
                <x-admin::form.control-group.error ::control-name="name" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="mb-4" v-else>
                <x-admin::form.control-group.label>
                    @{{ label }}
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="multiselect"
                    ref="aiModelRef"
                    ::id="name"
                    ::name="name"
                />
                <p
                    v-if="errorMessage"
                    class="mt-1 text-xs italic text-red-600"
                    v-text="errorMessage"
                >
                </p>
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

            this.fetchModels();
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

                    this.modelsOptions = JSON.stringify(response.data.models); // Populate the models array with the 
                    
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: response.data.message
                    });

                    this.$refs['aiModelRef'].selectedValue = null;

                    this.isValid = true;
                } catch (error) {
                    console.error("Failed to fetch AI models:", error);
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: error.response.data.message
                    });

                }
            },
            async fetchModels() {
                try {
                    const response = await axios.get("{{ route('admin.magic_ai.model') }}");
                    this.modelsOptions = JSON.stringify(response.data.models); // Populate the models array with the response data
                } catch (error) {
                    this.modelsOptions = null;
                    this.errorMessage = error.response.data.message;
                    console.error("Failed to fetch AI models:", error);
                }
            },
        }
    });
</script>
@endPushOnce
