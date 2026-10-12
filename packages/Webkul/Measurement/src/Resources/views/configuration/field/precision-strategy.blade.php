@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'].'.'.$field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey) ?: ($field['default_value'] ?? 'round');

    $options = [
        [
            'value' => 'round',
            'title' => trans('measurement::app.config.catalog.measurement.precision.strategy-round'),
        ],
        [
            'value' => 'trim',
            'title' => trans('measurement::app.config.catalog.measurement.precision.strategy-trim'),
        ],
    ];
@endphp

<x-admin::form.control-group class="last:!mb-0">
    <x-admin::form.control-group.label>
        @lang($field['title'])

        @if (! empty($field['info']))
            <span
                class="icon tooltip-icon cursor-pointer ltr:ml-1.5 rtl:mr-1.5"
                title="@lang($field['info'])"
            >
                &#9432;
            </span>
        @endif
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        :name="$name"
        :value="$value"
        :label="trans($field['title'])"
        :options="json_encode($options)"
        track-by="value"
        label-by="title"
    >
    </x-admin::form.control-group.control>
</x-admin::form.control-group>
