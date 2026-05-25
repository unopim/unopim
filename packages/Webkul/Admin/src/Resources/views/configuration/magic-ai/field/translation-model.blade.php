@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey);

    $platformId = core()->getConfigData('general.magic_ai.translation.ai_platform');
    $platform = null;

    if ($platformId) {
        $platform = app(\Webkul\MagicAI\Repository\MagicAIPlatformRepository::class)->find($platformId);
    }

    if (!$platform) {
        $platform = app(\Webkul\MagicAI\Repository\MagicAIPlatformRepository::class)->getDefault();
    }

    $modelOptions = [];
    if ($platform) {
        foreach ($platform->model_list as $model) {
            if ($model) {
                $modelOptions[] = ['id' => $model, 'label' => $model];
            }
        }
    }
@endphp

<v-section-model
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value='@json($value)'
    :initial-options='@json($modelOptions)'
    platform-select-id="general_magic_ai_translation_ai_platform"
>
</v-section-model>

@pushOnce('scripts')
    <script type="text/x-template" id="v-section-model-template">
        <x-admin::form.control-group class="last:!mb-0">
            <x-admin::form.control-group.label>
                @{{ label }}
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                ::id="name"
                ::name="name"
                ::options="JSON.stringify(currentOptions)"
                ::value="selectedValue"
                ::label="label"
                :placeholder="trans('admin::app.configuration.prompt.create.select-model')"
                track-by="id"
                label-by="label"
                @input="handleModelChange"
            />

            <p v-if="loading" class="mt-1 text-xs text-violet-600">
                @lang('admin::app.configuration.prompt.create.loading-models')
            </p>
            <p v-if="!currentOptions.length && !loading" class="mt-1 text-xs text-gray-400">
                @lang('admin::app.configuration.prompt.create.no-models-available')
            </p>
        </x-admin::form.control-group>
    </script>

    <script type="module">
        app.component('v-section-model', {
            template: '#v-section-model-template',
            props: ['label', 'name', 'value', 'initialOptions', 'platformSelectId', 'modelType'],
            data() {
                return {
                    selectedValue: this.value,
                    currentOptions: this.initialOptions || [],
                    loading: false,
                };
            },

            mounted() {
                this.translationPlatformHandler = (event) => {
                    try {
                        const selected = event.detail ? JSON.parse(event.detail) : null;
                        this.loadModelsForPlatform(selected?.id || '');
                    } catch (error) {
                        this.loadModelsForPlatform('');
                    }
                };

                document.addEventListener('magic-ai-translation-platform-changed', this.translationPlatformHandler);
            },

            beforeUnmount() {
                if (this.translationPlatformHandler) {
                    document.removeEventListener('magic-ai-translation-platform-changed', this.translationPlatformHandler);
                }
            },

            methods: {
                handleModelChange(value) {
                    try {
                        this.selectedValue = value ? JSON.parse(value).id : '';
                    } catch (error) {
                        this.selectedValue = '';
                    }
                },

                loadModelsForPlatform(platformId) {
                    this.loading = true;

                    let params = {};
                    if (platformId) {
                        params.platform_id = platformId;
                    }
                    if (this.modelType) {
                        params.type = this.modelType;
                    }

                    this.$axios.get("{{ route('admin.magic_ai.model') }}", { params })
                        .then((response) => {
                            this.loading = false;
                            this.currentOptions = response.data.models || [];
                            this.selectedValue = this.currentOptions[0]?.id || '';
                        })
                        .catch(() => { this.loading = false; this.currentOptions = []; });
                },
            },
        });
    </script>
@endPushOnce
