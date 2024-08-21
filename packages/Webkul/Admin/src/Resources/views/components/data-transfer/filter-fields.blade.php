@props([
    'fields'            => [],
    'currentLocaleCode' => core()->getRequestedLocaleCode(),
    'fieldsWrapper'     => 'filters',
    'fieldValues'       => [],
])

@foreach($fields as $field)
    @php 
        $fieldName = $field['name'];
        $fieldLabel = trans($field['title']);
        $validation = $field['validation'] ?? '';
        $value = $fieldValues[$fieldName] ?? null
    @endphp
    <x-admin::form.control-group>
        <x-admin::form.control-group.label>
            {{ $field['title'] }}

            @if ($field['required'])
                <span class="required"></span>
            @endif
        </x-admin::form.control-group.label>
        @switch($field['type'])
            @case('boolean')
                <input type="hidden" name="filters[{{$fieldName}}]" value="0" />
                <x-admin::form.control-group.control
                    type="switch"
                    name="filters[{{$fieldName}}]"
                    ::rules="$validation"
                    :label="$fieldLabel"
                    :checked="(bool) ! empty($value)"
                    value="1"
                />
                @break

            @case('select')
                @php
                    $optionsInJson = json_encode($field['options']);
                @endphp
                @if ($fieldName == 'file_format')
                    <x-admin::form.control-group.control
                        type="select"
                        id="file_format"
                        ::rules="$validation"
                        name="filters[{{$fieldName}}]"
                        value="{{old($fieldName) ?? $value}}"
                        v-model="fileFormat"
                        :options="$optionsInJson"
                        track-by="value"
                        label-by="label" 
                    />
                @else
                    <x-admin::form.control-group.control
                        type="select"
                        id="$fieldName"
                        ::rules="$validation"
                        name="filters[{{$fieldName}}]"
                        value="{{old($fieldName) ?? $value}}"
                        v-model="$fieldName"
                        :options="$optionsInJson"
                        track-by="value"
                        label-by="label" 
                    />
                @endif
                @break

            @default
                <x-admin::form.control-group.control
                    type="$field['type']"
                    id="$fieldName"
                    name="filters['$fieldName']"
                    ::rules="$validation"
                    :label="$fieldLabel"
                    :value="$value"
                    async="true"
                    entity-name="export_filter_field"
                />
        @endswitch
        <x-admin::form.control-group.error :control-name="$fieldName" />
    </x-admin::form.control-group>
@endforeach
