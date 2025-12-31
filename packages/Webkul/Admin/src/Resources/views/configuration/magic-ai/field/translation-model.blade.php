@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey);
@endphp

<v-translation-model
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value='@json($value)'>
</v-translation-model>

@pushOnce('scripts')
    <script type="text/x-template" id="v-translation-model-template">
        <div class="grid gap-2.5 content-start">
            <div>
                <x-admin::form.control-group class="last:!mb-0" v-if="! modelOptions">
                    <x-admin::form.control-group.label ::class="isTranslationEnabled ? 'required' : ''">
                        @{{ label }}
                    </x-admin::form.control-group.label>
                    @php
                        $models = core()->getConfigData('general.magic_ai.settings.api_model');
                        $models = explode(',', $models);
                        $options = [];

                        foreach ($models as $model) {
                            $options[] = [
                                'id'    => $model,
                                'label' => $model,
                            ];
                        }
                    @endphp
                    <x-admin::form.control-group.control
                        type="select"
                        ::id="name"
                        ::name="name"
                        ref="translationModelRef"
                        ::rules="{ 'required': isTranslationEnabled }"
                        ::label="label"
                        :options="json_encode($options)"
                        ::value="value"
                        v-model="selectedValue"
                        track-by="id"
                        label-by="label"
                    />
                    <x-admin::form.control-group.error ::control-name="label" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="mb-4" v-if="modelOptions">
                    <x-admin::form.control-group.label ::class="isTranslationEnabled ? 'required' : ''">
                        @{{ label }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        ::id="name"
                        ::name="name"
                        ref="translationModelRef"
                        ::rules="{ 'required': isTranslationEnabled }"
                        ::label="label"
                        ::value="value"
                        ::options="modelOptions"
                        v-model="selectedModelOption"
                        ::key="componentKey"
                        track-by="id"
                        label-by="label"
                    />
                    <x-admin::form.control-group.error ::control-name="name" />
                </x-admin::form.control-group>
            </div>
        </div>
    </script>
    <script type="module">
        app.component('v-translation-model', {
            template: '#v-translation-model-template',
            props: [
                'label',
                'name',
                'validations',
                'value',
            ],
            data: function() {
                return {
                    modelOptions: null,
                    selectedValue: this.value,
                    selectedModelOption: null,
                    componentKey: 0,
                    isTranslationEnabled: Boolean('{{ core()->getConfigData("general.magic_ai.translation.enabled") == 1 }}')
                };
            },

            mounted() {
                this.$emitter.on('model_value_change', (data) => {
                    this.selectedValue = null;
                    if (!Array.isArray(data)) {
                        let options = JSON.parse(data);
                        options = options.filter(option => option.id != 'dall-e-2' && option.id != 'dall-e-3');
                        this.modelOptions = options;
                        this.selectedModelOption = options[0]?.id || null;
                        this.componentKey++;
                    } else {
                        this.modelOptions = [];
                        this.$refs['translationModelRef'].selectedValue = null;
                    }
                });

                this.$emitter.on('config-value-changed', (data) => {
                    if (data.fieldName == 'general[magic_ai][translation][enabled]') {
                        this.isTranslationEnabled = parseInt(data.value || 0) === 1;
                    }

                    if (data.fieldName === "general[magic_ai][settings][ai_platform]") {
                        this.$refs['translationModelRef'].selectedValue = null;
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
                emitChangeEvent(value) {
                    this.$emitter.emit('config-value-changed', {
                        value: value
                    });
                },
            }
        });
    </script>
@endPushOnce
