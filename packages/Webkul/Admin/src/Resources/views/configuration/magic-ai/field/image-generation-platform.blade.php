@php
    $platforms = app(\Webkul\MagicAI\Repository\MagicAIPlatformRepository::class)->getActivePlatformOptions();
    $selectedPlatformId = core()->getConfigData('general.magic_ai.image_generation.ai_platform');
    $options = [
        [
            'id'    => '0',
            'label' => trans('admin::app.configuration.platform.fields.use-default'),
        ],
    ];

    foreach ($platforms as $platform) {
        $options[] = [
            'id'    => (string) $platform['id'],
            'label' => $platform['label'] . ($platform['is_default'] ? ' *' : ''),
        ];
    }

    $optionsJson = json_encode($options);
    $selectedValue = json_encode((string) ($selectedPlatformId ?: 0));
@endphp

<x-admin::form.control-group>
    <x-admin::form.control-group.label>
        @lang('admin::app.configuration.index.general.magic-ai.image-generation.ai-platform')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        id="general_magic_ai_image_generation_ai_platform"
        name="general[magic_ai][image_generation][ai_platform]"
        :options="$optionsJson"
        :value="$selectedValue"
        :label="trans('admin::app.configuration.index.general.magic-ai.image-generation.ai-platform')"
        :placeholder="trans('admin::app.configuration.platform.fields.use-default')"
        track-by="id"
        label-by="label"
    />

    @if(empty($platforms))
        <p class="mt-1 text-xs text-amber-600">
            @lang('admin::app.configuration.platform.setup.no-platform-hint')
        </p>
    @else
        <p class="mt-1 text-xs text-gray-500">
            @lang('admin::app.configuration.platform.fields.use-default-hint')
        </p>
    @endif
</x-admin::form.control-group>
