@php
    $platforms = app(\Webkul\MagicAI\Repository\MagicAIPlatformRepository::class)->getActivePlatformOptions();
    $selectedPlatformId = core()->getConfigData("general.magic_ai.{$section}.ai_platform");
    $fieldId = "general_magic_ai_{$section}_ai_platform";
    $fieldLabel = trans("admin::app.configuration.index.general.magic-ai.{$labelKey}.ai-platform");
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
        {{ $fieldLabel }}
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        :id="$fieldId"
        name="general[magic_ai][{{ $section }}][ai_platform]"
        :options="$optionsJson"
        :value="$selectedValue"
        :label="$fieldLabel"
        :placeholder="trans('admin::app.configuration.platform.fields.use-default')"
        track-by="id"
        label-by="label"
        @input="document.dispatchEvent(new CustomEvent('magic-ai-platform-changed:{{ $fieldId }}', { detail: $event }))"
    />

    @if (empty($platforms))
        <p class="mt-1 text-xs text-warning">
            @lang('admin::app.configuration.platform.setup.no-platform-hint')
        </p>
    @else
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            @lang('admin::app.configuration.platform.fields.use-default-hint')
        </p>
    @endif
</x-admin::form.control-group>
