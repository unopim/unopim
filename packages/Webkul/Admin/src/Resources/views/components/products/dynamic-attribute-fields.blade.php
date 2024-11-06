@props([
    'fields'             => [],
    'currentLocaleCode'  => core()->getRequestedLocaleCode(),
    'currentChannelCode' => core()->getRequestedChannelCode(),
    'fieldsWrapper'      => 'values',
    'fieldValues'        => [],
    'channelCurrencies'  => [],
    'variantFields'      => [],
])

@foreach($fields as $field)
    @php
        $isLocalizable = $field->isLocaleBasedAttribute();
        $isChannelBased = $field->isChannelBasedAttribute();

        $isConfigurableAttribute = in_array($field->code, $variantFields);

        /** This only changes the value in the current page as we are not saving this attribute */
        if ($isConfigurableAttribute) {
            $field->is_required = true;
        }

        $value = '';

        $formattedoptions = [];

        $fieldName = $fieldsWrapper . $field->getAttributeInputFieldName($currentChannelCode, $currentLocaleCode);

        $flatFieldName = $fieldsWrapper . $field->getFlatAttributeName($currentChannelCode, $currentLocaleCode);

        if ($fieldValues) {
            $value = $field->getValueFromProductValues($fieldValues, $currentChannelCode, $currentLocaleCode);
        }

        $value = old($flatFieldName) ?? $value;

        $fieldLabel = $field->translate($currentLocaleCode)['name'] ?? '';

        $fieldLabel = empty($fieldLabel) ? '['.$field->code.']' : $fieldLabel;

        $fieldType = $field->type;
    @endphp

    {!! view_render_event('unopim.admin.products.dynamic-attribute-fields.field.before', ['field' => $field]) !!}

    <x-admin::form.control-group>
        <div class="inline-flex justify-between w-full">
            <x-admin::form.control-group.label :for="$fieldName">
                {{ $fieldLabel }} 

                @if ($field->is_required || $isConfigurableAttribute)
                    <span class="required"></span>
                @endif
            </x-admin::form.control-group.label>

            <div class="self-end mb-2 text-xs flex gap-1">
                @if ($isChannelBased)
                    <span class="icon-channel uppercase box-shadow p-1 rounded-full bg-gray-100 border border-gray-200 rounded text-gray-600 dark:!text-gray-600">
                        {{ "{$currentChannelCode}" }}
                    </span>
                @endif
                @if ($isLocalizable)
                    <span class="icon-language uppercase box-shadow p-1 rounded-full bg-gray-100 border border-gray-200 rounded text-gray-600 dark:!text-gray-600">
                        {{ "{$currentLocaleCode}" }}
                    </span>
                @endif
            </div>
        </div>

        {!! view_render_event('unopim.admin.products.dynamic-attribute-fields.control.'.$fieldType.'.before', ['field' => $field, 'value' => $value, 'fieldName' => $fieldName]) !!}

        @switch ($fieldType)
            @case ('checkbox')
                @if (! empty($value))
                    <input type="hidden" name="{{ $fieldName }}" value="">
                @endIf

                @php
                    $fieldName = $fieldName.'[]';

                    $selectedValue = ! empty($value) && is_string($value) ? explode(',', $value) : $value;

                    $selectedValue = empty($selectedValue) || ! is_array($selectedValue) ? [] : $selectedValue;
                @endphp

                @foreach ($field->options as $option)
                    <div class="flex py-2 items-center gap-2">
                        <x-admin::form.control-group.control
                            type="checkbox"
                            :id="$field->code . '_' . $option->id"
                            :name="$fieldName"
                            :value="$option->code"
                            ::rules="{{ $field->getValidationsField() }}"
                            :label="$fieldLabel"
                            :for="$field->code . '_' . $option->id"
                            :checked="(bool) false !== array_search($option->code, $selectedValue)"
                        />
    
                        <label
                            class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer select-none"
                            for="{{ $field->code . '_' . $option->id }}"
                        >
                            {{ $option->translate($currentLocaleCode)['label'] }}
                        </label>
                    </div>
                @endforeach

                @break
            @case ('boolean')
                <input type="hidden" name="{{ $fieldName }}" value="false" />

                <x-admin::form.control-group.control
                    type="switch"
                    :id="$field->code"
                    :name="$fieldName"
                    :label="$fieldLabel"
                    :checked="(bool) ('true' == strtolower($value))"
                    value="true"
                />

                @break
            @case('image')
                @php
                    if (is_array($value)) {
                        $value = current($value);
                    }

                    $savedImage = ! empty($value) ? [
                        'id'    => 0,
                        'url'   => Storage::url($value),
                        'value' => $value,
                    ] : [];
                @endphp

                @if (! empty($value))
                    <!-- Emoty value sent when value is deleted need to send empty value for this field -->
                    <input type="hidden" name="{{ $fieldName }}" value="">
                @endIf

                <x-admin::media.images
                    name="{{ $fieldName }}"
                    ::class="[errors && errors['{{ $fieldName }}'] ? 'border !border-red-600 hover:border-red-600' : '']"
                    :id="$field->code"
                    ::rules="{{ $field->getValidationsField() }}"
                    :uploaded-images="! empty($value) ? [$savedImage] : []"
                    width='210px'
                />
                @break
            @case('gallery')
                @php
                    $savedImages = ! empty($value) ? array_map(function ($image, $index) {
                        return [
                            'id'    => uniqid(),
                            'url'   => Storage::url($image),
                            'value' => $image,
                        ];
                    }, (array)$value, array_keys((array)$value)) : [];
                @endphp

                @if (! empty($value))
                    <!-- Empty value sent when value is deleted need to send empty value for this field -->
                    <input type="hidden" name="{{ $fieldName }}" value="">
                @endIf

                <x-admin::media.images
                    name="{{ $fieldName }}"
                    ::class="[errors && errors['{{ $fieldName }}'] ? 'border !border-red-600 hover:border-red-600' : '']"
                    :id="$field->code"
                    ::rules="{{ $field->getValidationsField() }}"
                    :uploaded-images="! empty($value) ? $savedImages : []"
                    :allow-multiple=true
                    width='210px'
                />
                @break
            @case('file')
                @php
                    $fileName = last(explode('/', $value));
                    $fileName = strlen($fileName) > 20 ? substr($fileName, 0, 20) . '...' : $fileName;

                    $savedFile = ! empty($value) ? [
                        'id'       => 0,
                        'url'      => Storage::url($value),
                        'value'    => $value,
                        'fileName' => $fileName,
                    ] : [];
                @endphp

                @if (! empty($value))
                    <!--  Emoty value sent when value is deleted need to send empty value for this field -->
                    <input type="hidden" name="{{ $fieldName }}" value="">
                @endIf

                <x-admin::media.files
                    type="video"
                    :id="$field->code"
                    :name="$fieldName"
                    ::rules="{{ $field->getValidationsField() }}"
                    :label="$fieldLabel"
                    :uploaded-files="! empty($value) ? [$savedFile] : []"
                    value="{{$value}}"
                    class="mt-3"
                />
                @break
            @case('price')
                @php
                    $value = ! is_array($value) && ! empty($value) ? json_decode($value, true) : $value;
                @endphp
                <div class="flex gap-4">
                    @foreach ($channelCurrencies as $currency)
                        @php $currencyValue = $value[$currency->code] ?? ''; @endphp
                        <div class="grid w-full">
                            <x-admin::form.control-group.control
                                type="price"
                                :id="$field->code"
                                :name="$fieldName . '[' . $currency->code . ']'"
                                ::rules="{{ $field->getValidationsField() }}"
                                :value="$currencyValue"
                                :label="$fieldLabel"
                            >
                                <x-slot:currency class="dark:text-gray-300">
                                    {{ core()->currencySymbol($currency->code) }}
                                </x-slot>
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error :control-name="$fieldName . '[' . $currency->code . ']'" />
                        </div>
                    @endForeach
                </div>
            @break
            @case('multiselect')
                <!-- NO BREAK -->
                @php
                    $value = str_contains($value, ',')
                        ? explode(',', $value)
                        : (empty($value) ? '' : [$value]);
                @endphp
            @case('select')
                <!-- NO BREAK -->
                @php
                    $selectedValue = [];
                    foreach ($field->options->whereIn('code', $value) as $option) {
                        $translatedOptionLabel = $option->translate($currentLocaleCode)?->label;

                        $selectedValue[] = [
                            'id'    => $option->id,
                            'code'  => $option->code,
                            'label' => ! empty($translatedOptionLabel) ? $translatedOptionLabel : "[{$option->code}]",
                        ];
                    }

                    if ('select' == $fieldType) {
                        $selectedValue = ! empty($selectedValue[0]) ? $selectedValue[0] : $selectedValue;
                    }

                    $value = ! empty($selectedValue) ? json_encode($selectedValue) : '';
                @endphp
            @default
                <x-admin::form.control-group.control
                    :type="$fieldType"
                    :id="$field->code"
                    :name="$fieldName"
                    ::rules="{{ $field->getValidationsField() }}"
                    :tinymce="(bool) $field->enable_wysiwyg"
                    :options="json_encode([])"
                    :label="$fieldLabel"
                    :value="$value"
                    track-by="code"
                    async="true"
                    entity-name="attribute"
                    :attribute-id="$field->id"
                />
        @endswitch

        @php 
            if ($isConfigurableAttribute) {
                $field->is_required = $field->getOriginal('is_required');
            }
        @endphp

        @if ($field->is_unique)
            <x-admin::form.control-group.control
                type="hidden"
                name="uniqueFields[{{ $flatFieldName }}]"
                :value="$fieldName"
                :label="$fieldLabel"
                id="uniqueFields[{{ $flatFieldName }}]"
            />
        @endIf

        {!! view_render_event('unopim.admin.products.dynamic-attribute-fields.control.'.$fieldType.'.after', ['field' => $field, 'value' => $value, 'fieldName' => $fieldName]) !!}

        <x-admin::form.control-group.error :control-name="$fieldName" />
    </x-admin::form.control-group>

    {!! view_render_event('unopim.admin.products.dynamic-attribute-fields.field.after', ['fieldType' => $fieldType]) !!}
@endforeach
