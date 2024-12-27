@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
    $nameKey = $item['key'] . '.' . $field['name'];

    $name = $coreConfigRepository->getNameField($nameKey);

    $selectedOptions = core()->getConfigData($nameKey);
    $selectedOptions = json_encode(explode(',', $selectedOptions) ?? []);
    $options = json_encode($magicAI->getModelList());
@endphp

<x-admin::form.control-group class="mb-4">
    <x-admin::form.control-group.label>
        {{ $field['title'] }}
    </x-admin::form.control-group.label>
    <x-admin::form.control-group.control
        type="multiselect"
        id="{{ $name }}"
        name="{{ $name }}"
        rules="required"
        :options="$options"
        :value="$selectedOptions"
        :label="trans('admin::app.settings.channels.edit.locales')"
        :placeholder="trans('admin::app.settings.channels.edit.select-locales')"
        track-by="value"
        label-by="title"
    />
    <x-admin::form.control-group.error control-name="{{ $name }}" />
</x-admin::form.control-group>