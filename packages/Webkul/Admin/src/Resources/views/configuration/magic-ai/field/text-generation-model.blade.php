@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey);

    $platformId = core()->getConfigData('general.magic_ai.settings.ai_platform');
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
    platform-select-id="general_magic_ai_settings_ai_platform"
>
</v-section-model>
