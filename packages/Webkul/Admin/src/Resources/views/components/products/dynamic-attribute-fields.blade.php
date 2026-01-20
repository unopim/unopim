@props([
    'fields'                 => [],
    'currentLocaleCode'      => core()->getRequestedLocaleCode(),
    'currentChannelCode'     => core()->getRequestedChannelCode(),
    'fieldsWrapper'          => 'values',
    'fieldValues'            => [],
    'channelCurrencies'      => [],
    'variantFields'          => [],
    'completenessAttributes' => []
])

@php
    $globaltranslationEnabled = core()->getConfigData('general.magic_ai.translation.enabled');

    if ($globaltranslationEnabled == 1) {
        $channelValue = core()->getConfigData('general.magic_ai.translation.source_channel');
        $localeValue = core()->getConfigData('general.magic_ai.translation.source_locale');
        $targetChannel = core()->getConfigData('general.magic_ai.translation.target_channel');
        $targetlocales = core()->getConfigData('general.magic_ai.translation.target_locale');
        $targetlocales = json_encode(explode(',', $targetlocales) ?? []);
        $model = core()->getConfigData('general.magic_ai.translation.ai_model');
    }
@endphp

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

        $fieldName = $fieldsWrapper.$field->getAttributeInputFieldName($currentChannelCode, $currentLocaleCode);

        $flatFieldName = $fieldsWrapper.$field->getFlatAttributeName($currentChannelCode, $currentLocaleCode);

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

                @if (isset($completenessAttributes[$field->id]) && ! isset($value))
                    <span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span>
                @endif
            </x-admin::form.control-group.label>

            <div class="self-end mb-2 text-xs flex gap-1 items-center">
                @if (
                    $globaltranslationEnabled == 1
                    && ($fieldType == 'text' || $fieldType == 'textarea')
                    && $field->ai_translate == 1
                )
                    <span>
                        @include('admin::catalog.products.edit.fields.translate-button', [
                            'globaltranslationEnabled' => $globaltranslationEnabled,
                            'channelValue'             => $channelValue,
                            'localeValue'              => $localeValue,
                            'targetChannel'            => $targetChannel,
                            'targetlocales'            => $targetlocales,
                            'model'                    => $model,
                        ])
                    </span>
                @endif

                @if ($isChannelBased)
                    <span class="icon-channel uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200  text-gray-600 dark:!text-gray-600">
                        {{ "{$currentChannelCode}" }}
                    </span>
                @endif
                @if ($isLocalizable)
                    <span class="icon-language uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200  text-gray-600 dark:!text-gray-600">
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
                            {{ $option->translate($currentLocaleCode)?->label ?? "[{$option->code}]" }}
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
                    $savedData = ! empty($value) ? array_map(function ($media, $index) {
                        $mimeType = Storage::exists($media) ? Storage::mimeType($media) : null;
                        $fileName = basename($media);

                        return [
                            'id'    => uniqid(),
                            'url'   => Storage::url($media),
                            'value' => $media,
                            'type'  => $mimeType,
                            'name'  => $fileName,
                        ];
                    }, (array)$value, array_keys((array)$value)) : [];
                @endphp

                @if (! empty($value))
                    <!-- Empty value sent when value is deleted need to send empty value for this field -->
                    <input type="hidden" name="{{ $fieldName }}" value="">
                @endIf

                <x-admin::media.gallery
                    name="{{ $fieldName }}"
                    ::class="[errors && errors['{{ $fieldName }}'] ? 'border !border-red-600 hover:border-red-600' : '']"
                    :id="$field->code"
                    ::rules="{{ $field->getValidationsField() }}"
                    :uploaded-images="! empty($value) ? $savedData : []"
                    :allow-multiple=true
                    width='210px'
                />
                @break
            @case('file')
                @php
                    $fileName = last(explode('/', $value));
                    $fileName = strlen($fileName) > 20 ? substr($fileName, 0, 20) . '...' : $fileName;

                    $savedFile = ! empty($value) ? [
                        'id' => 0,
                        'url' => Storage::url($value),
                        'value' => $value,
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
                    $value = !is_array($value) && !empty($value) ? json_decode($value, true) : $value;
                @endphp
                <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,_minmax(200px,_1fr))]">
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
                            'id'                => $option->id,
                            'code'              => $option->code,
                            'swatch_value'      => $option->swatch_value,
                            'swatch_value_url'  => $option->swatch_value_url,
                            'attribute'         => ['swatch_type' => $field->swatch_type],
                            'label'             => ! empty($translatedOptionLabel) ? $translatedOptionLabel : "[{$option->code}]",
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
                >

                <x-slot:option>
                    <div class="flex items-center space-x-2">
                        <!-- Image swatch -->
                        <div
                            v-if="option.attribute.swatch_type == 'image'"
                            class="justify-items-center border rounded relative overflow-hidden group w-12 h-12"
                        >
                            <img :src="option.swatch_value_url || '{{ unopim_asset('images/product-placeholders/front.svg') }}'"
                             class="w-full h-full object-contain object-top rounded border" ref="optionImage" />

                            <div class="flex items-center justify-center invisible w-full bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 group-hover:visible">
                                <div class="flex justify-between">
                                    <span
                                        class="icon-view text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                        @click.stop.prevent="previewImage(option)"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Color swatch -->
                        <div v-if="option.swatch_value && option.attribute.swatch_type == 'color'"
                            :style="{ backgroundColor: option.swatch_value }"
                            class="w-6 h-6 rounded border"></div>

                        <!-- Label -->
                        <span>@{{ option[labelBy] }}</span>
                    </div>
                </x-slot:option>

                <x-slot:singleLabel>
                    <div class="flex items-center space-x-2">
                        <div
                            v-if="option.swatch_value_url && option.attribute.swatch_type == 'image'"
                            class="justify-items-center border rounded relative overflow-hidden group w-12 h-12"
                        >
                            <img :src="option.swatch_value_url || '{{ unopim_asset('images/product-placeholders/front.svg') }}'"
                             class="w-full h-full object-contain object-top rounded border" ref="optionImage" />

                            <div class="flex items-center justify-center invisible w-full bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 group-hover:visible">
                                <div class="flex justify-between">
                                    <span
                                        class="icon-view text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                        @mousedown.stop.prevent="previewImage(option)"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <div v-if="option.swatch_value && option.attribute.swatch_type == 'color'"
                            :style="{ backgroundColor: option.swatch_value }"
                            class="w-4 h-4 rounded border"></div>

                        <span>@{{ option[labelBy] }}</span>
                    </div>
                </x-slot:singleLabel>

                <x-slot:tag>
                        <div class="multiselect__tag space-x-2 items-center justify-center" style="display:inline-flex">
                            <div
                                v-if="option.attribute.swatch_type == 'image'"
                                class="justify-items-center border rounded relative overflow-hidden group w-12 h-12"
                            >
                                <img :src="option.swatch_value_url || '{{ unopim_asset('images/product-placeholders/front.svg') }}'"
                                 class="w-full h-full object-contain object-top rounded border" ref="optionImage" />
    
                                <div class="flex items-center justify-center invisible w-full bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 group-hover:visible">
                                    <div class="flex justify-between">
                                        <span
                                            class="icon-view text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                            @mousedown.stop.prevent="previewImage(option)"
                                        ></span>
                                    </div>
                                </div>
                            </div>
    
                            <div v-if="option.swatch_value && option.attribute.swatch_type == 'color'"
                                :style="{ backgroundColor: option.swatch_value }"
                                class="w-4 h-4 rounded border"></div>
    
                            <span>@{{ option[labelBy] }}</span>
                            <i tabindex="1" @click="remove(option)" class="multiselect__tag-icon"></i>
                        </div>
                   
                </x-slot:tag>

                <x-slot name="modal">
                    <x-admin::modal ref="imagePreviewModal">
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold"></p>
                        </x-slot:header>
                        <x-slot:content>
                            <div class="max-w-full h-[260px]">
                                <img :src="fileUrl" class="w-full h-full object-contain object-top" />
                            </div>
                        </x-slot:content>
                    </x-admin::modal>
                </x-slot>
                </x-admin::form.control-group.control>

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
