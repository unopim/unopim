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

            <div class="self-end mb-2 text-xs flex gap-1 items-center">
                @php
                    $globaltranslationEnabled = core()->getConfigData('general.magic_ai.translation.enabled');
                @endphp

                @if (($fieldType == 'text' || $fieldType == 'textarea') && ($field->ai_translate != 0 && $globaltranslationEnabled != 0 ))
                    <span>
                        @php
                            $channelValue = core()->getConfigData('general.magic_ai.translation.source_channel');
                            $localeValue = core()->getConfigData('general.magic_ai.translation.source_locale');
                            $targetChannel = core()->getConfigData('general.magic_ai.translation.target_channel');
                            $targetlocales = core()->getConfigData('general.magic_ai.translation.target_locale');
                            $targetlocales = json_encode(explode(',', $targetlocales) ?? []);
                            $model = core()->getConfigData('general.magic_ai.translation.ai_model');
                        @endphp
                        <v-translate-form
                            :channel-value="{{ json_encode($channelValue) }}"
                            :locale-value='@json($localeValue)'
                            :channel-target="{{ json_encode($targetChannel) }}"
                            :target-locales="{{$targetlocales}}"
                            :id="'{{$field->code}}'"
                            :value="'{{ json_encode(e($value)) }}'"
                            :field="'{{$fieldLabel}}'"
                            :field-type="'{{$fieldType}}'"
                            :model="'{{$model}}'"
                            :current-local-code="'{{ $currentLocaleCode }}'"
                            :current-channel="'{{ $currentChannelCode }}'">
                            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                                <div class="flex gap-x-2.5 items-center">
                                    <button
                                        type="button"
                                        class="secondary-button bg-violet-50 text-violet-700 focus:ring-indigo-200 border border-indigo-200 rounded-lg px-2 h-5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="none">
                                            <g clip-path="url(#clip0_3148_2242)">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#6d28d9" />
                                                <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#6d28d9" />
                                                <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#6d28d9" />
                                                <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#6d28d9" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_3148_2242">
                                                    <rect width="24" height="24" fill="white" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                        @lang('admin::app.catalog.products.edit.translate.translate-btn')
                                    </button>
                                </div>
                            </div>
                        </v-translate-form>
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
                        'id' => 0,
                        'url' => Storage::url($value),
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
                        'id' => uniqid(),
                        'url' => Storage::url($image),
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
                            'id' => $option->id,
                            'code' => $option->code,
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

@pushOnce('scripts')
    <script type="text/x-template" id="v-translate-form-template">
        <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
            <div class="flex gap-x-2.5 items-center">
                <!-- translate Button -->
                <button
                    type="button"
                    class="secondary-button bg-violet-50 text-violet-700 focus:ring-indigo-200 border border-indigo-200 rounded-lg px-2 h-5"
                    @click="resetForm();fetchSourceLocales();fetchTargetLocales();$refs.translationModal.toggle();"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="none">
                        <g clip-path="url(#clip0_3148_2242)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#6d28d9"/>
                            <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#6d28d9"/>
                            <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#6d28d9"/>
                            <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#6d28d9"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_3148_2242">
                                <rect width="24" height="24" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                    @lang('admin::app.catalog.products.edit.translate.translate-btn')
                </button>
            </div>
        </div>
        <div>
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="translationForm"
            >
                <form @submit="handleSubmit($event, translate)" ref="translationForm">
                    <x-admin::modal ref="translationModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="flex  items-center text-lg text-gray-800 dark:text-white font-bold">
                                <span class="icon-magic text-2xl text-gray-800"></span>
                                @lang('admin::app.catalog.products.edit.translate.title')
                            </p>
                        </x-slot>
                        <!-- Modal Content -->

                        <x-slot:content>
                            <div class="grid grid-cols-2 gap-4" v-show="! translatedData && ! nothingToTranslate">
                                <x-admin::form.control-group  >
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.products.edit.translate.source-channel')
                                    </x-admin::form.control-group.label>
                                    @php
                                        $channels = core()->getAllChannels();
                                        $options = [];
                                        foreach($channels as $channel)
                                            {
                                                $options[] = [
                                                    'id' => $channel->code,
                                                    'label' => $channel->name,
                                                    ];
                                            }
                                        $optionsInJson = json_encode($options);
                                    @endphp
                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="channel"
                                        rules="required"
                                        ::value="sourceChannel"
                                        :options="json_encode($options)"
                                        @input="getSourceLocale"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="channel"></x-admin::form.control-group.error>
                                </x-admin::form.control-group >

                                <x-admin::form.control-group  >
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.products.edit.translate.target-channel')
                                    </x-admin::form.control-group.label>
                                        
                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="targetChannel"
                                        rules="required"
                                        ::value="targetChannel"
                                        :options="json_encode($options)"
                                        @input="getTargetLocale"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="targetChannel"></x-admin::form.control-group.error>
                                </x-admin::form.control-group >

                                <x-admin::form.control-group v-if="localeOption">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.products.edit.translate.locale')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="locale"
                                        rules="required"
                                        ref="localelRef"
                                        ::value="sourceLocale"
                                        ::options="localeOption"
                                        @input="resetTargetLocales"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="locale"></x-admin::form.control-group.error>
                                </x-admin::form.control-group >

                                <x-admin::form.control-group v-if="targetLocOptions">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.products.edit.translate.target-locales')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="multiselect"
                                        id="section"
                                        ref="targetLocOptionsRef"
                                        name="targetLocale"
                                        rules="required"
                                        ::value="targetLocales"
                                        ::options="targetLocOptions"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="targetLocale"></x-admin::form.control-group.error>
                                </x-admin::form.control-group >
                            </div>

                            <!-- Generated Content -->
                            <x-admin::form.control-group class="mt-5 flex flex-row gap-5" v-if="translatedData">
                                <div class="w-full">
                                    <x-admin::form.control-group.label class="flex items-center justify-center mb-4">
                                        <p class="text-sm text-gray-800 dark:text-white font-bold">@lang('admin::app.catalog.products.edit.translate.source-content')</p>
                                    </x-admin::form.control-group.label>

                                    <div class="inline-flex justify-between w-full">
                                        <x-admin::form.control-group.label class="text-left pr-2">
                                            @{{field}}
                                        </x-admin::form.control-group.label>
                                            <div class="self-end mb-2 text-xs flex gap-1 items-center">
                                                <span class="icon-channel uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border text- border-gray-200  text-gray-600 dark:!text-gray-600">
                                                    @{{sourceChannel}}
                                                </span>
                                                <span class="icon-language uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200  text-gray-600 dark:!text-gray-600">
                                                    @{{sourceLocale}}
                                                </span>
                                            </div>
                                    </div>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        class="h-[30px]"
                                        name="content"
                                        v-model="sourceData"
                                        readOnly
                                        disabled
                                        v-if="fieldType === 'text'"
                                    />
                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        class="h-[75px]"
                                        name="content"
                                        v-model="sourceData"
                                        readOnly
                                        disabled
                                        v-if="fieldType === 'textarea'"
                                    />
                                </div>
                                <div class="w-full">
                                    <x-admin::form.control-group.label class="flex items-center justify-center mb-4">
                                        <p class="text-sm text-gray-800 dark:text-white font-bold">@lang('admin::app.catalog.products.edit.translate.translated-content')</p>
                                    </x-admin::form.control-group.label>
                                    <div class="inline-flex justify-between w-full">
                                        <x-admin::form.control-group.label class="text-left">
                                            @{{field}}
                                        </x-admin::form.control-group.label>
                                        <div class="self-end mb-2 text-xs flex gap-1 items-center">
                                            <span class="icon-channel uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border text- border-gray-200 text-gray-600 dark:!text-gray-600">
                                                @{{targetChannel}}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="overflow-y-auto h-40 space-y-4 border rounded-lg p-2">
                                        <div v-for="(item, index) in translatedData"
                                            :key="index"
                                            class="flex flex-col space-y-0"
                                        >
                                            <x-admin::form.control-group.label class="flex justify-end text-right mb-0">
                                                <span class="icon-language uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200 text-gray-600 dark:!text-gray-600">
                                                    @{{ item.locale }}
                                                </span>
                                            </x-admin::form.control-group.label>
                                            <x-admin::form.control-group.control
                                                type="text"
                                                class="h-[30px] w-full border-gray-300 rounded mt-0"
                                                ::name="item.locale"
                                                v-model="item.content"
                                                v-if="fieldType === 'text'"
                                            />
                                            <x-admin::form.control-group.control
                                                type="textarea"
                                                class="h-[75px] w-full border-gray-300 rounded mt-0"
                                                ::name="item.locale"
                                                v-model="item.content"
                                                v-if="fieldType === 'textarea'"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="mt-5" v-if="nothingToTranslate">
                                <x-admin::form.control-group.label class="text-left">
                                    @lang('admin::app.catalog.products.edit.translate.translated-content')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    class="h-[75px]"
                                    name="content"
                                    v-model="nothingToTranslate"
                                />
                            </x-admin::form.control-group>

                        </x-slot>

                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <template v-if="! translatedData && ! nothingToTranslate">
                                    <button
                                        type="submit"
                                        class="secondary-button"
                                    >
                                        <!-- Spinner -->
                                        <template v-if="isLoading">
                                            <img
                                                class="animate-spin h-5 w-5 text-violet-700"
                                                src="{{ unopim_asset('images/spinner.svg') }}"
                                            />
                                            @lang('admin::app.catalog.products.edit.translate.translating')
                                        </template>

                                        <template v-else>
                                            <span class="icon-magic text-2xl text-violet-700"></span>
                                            @lang('admin::app.catalog.products.edit.translate.translate-btn')
                                        </template>
                                    </button>
                                </template>

                                <template v-else>
                                    <button
                                        v-if="translatedData"
                                        type="button"
                                        class="primary-button"
                                        :disabled="!translatedData"
                                        @click="apply"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.apply')
                                    </button>

                                    <button
                                        v-else-if="nothingToTranslate"
                                        type="button"
                                        class="secondary-button"
                                        @click="cancel"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.cancel')
                                    </button>
                                </template>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-translate-form', {
            template: '#v-translate-form-template',
            props: [
                'currentChannel',
                'currentLocalCode',
                'field',
                'fieldType',
                'selector',
                'value',
                'id',
                'channelValue',
                'localeValue',
                'model',
                'channelTarget',
                'targetLocales'

            ],
            data() {
                return {
                    targetLocOptions: null,
                    localeOption: null,
                    resourceId: "{{ request()->id }}",
                    sourceData: null,
                    translatedData: null,
                    isLoading: false,
                    nothingToTranslate: '',
                    sourceLocale: this.localeValue,
                    sourceChannel: this.channelValue,
                    targetChannel: this.channelTarget,
                    targetLocales: this.targetLocales,
                    fieldType: this.fieldType,
                };
            },

            methods: {
                fetchSourceLocales() {
                    this.getLocale(this.sourceChannel)
                        .then((options) => {
                            this.localeOption = JSON.stringify(options);
                        })
                        .catch((error) => {
                            console.error('Error fetching source locales:', error);
                        });
                },

                fetchTargetLocales() {
                    this.getLocale(this.targetChannel)
                        .then((options) => {
                            if (this.targetChannel === this.sourceChannel) {
                                options = options.filter(option => option.id != this.sourceLocale);
                            }
                            this.targetLocOptions = JSON.stringify(options);
                        })
                        .catch((error) => {
                            console.error('Error fetching target locales:', error);
                        });
                },

                getSourceLocale(event) {
                    if (event) {
                        this.sourceChannel = JSON.parse(event).id;
                        this.getLocale(this.sourceChannel)
                            .then((options) => {
                                if (this.$refs['localelRef']) {
                                    this.$refs['localelRef'].selectedValue = null;
                                }
                                this.localeOption = JSON.stringify(options);
                                if (options.length == 1) {
                                    this.sourceLocale = options[0].id;
                                    if (this.$refs['localelRef']) {
                                        this.$refs['localelRef'].selectedValue = options[0];
                                    }
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });
                    }
                },

                getTargetLocale(event) {
                    if (event) {
                        this.targetChannel = JSON.parse(event).id;
                        this.getLocale(this.targetChannel)
                            .then((options) => {
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = null;
                                }
                                if (this.targetChannel === this.sourceChannel) {
                                    options = options.filter(option => option.id != this.sourceLocale);
                                }
                                this.targetLocOptions = JSON.stringify(options);
                                this.targetLocales = options;
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = options;
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });

                    }

                },

                resetTargetLocales(event) {
                    if (event) {
                        this.sourceLocale = JSON.parse(event).id;
                        this.getLocale(this.targetChannel)
                            .then((options) => {
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = null;
                                }
                                if (this.targetChannel === this.sourceChannel) {
                                    options = options.filter(option => option.id != this.sourceLocale);
                                }
                                this.targetLocOptions = JSON.stringify(options);
                                this.targetLocales = options;
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = options;
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });

                    }
                },

                getLocale(channel) {
                    return this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                            params: {
                                channel: channel,
                            },
                        })
                        .then((response) => {
                            return response.data?.locales || [];
                        })
                        .catch((error) => {
                            console.error('Error fetching locales:', error);
                            throw error;
                        });
                },


                translate(params, {
                    resetForm,
                    resetField,
                    setErrors
                }) {
                    this.isLoading = true;
                    if (!this.$refs.translationForm) {
                        console.error("translationForm reference is missing.");
                        return;
                    }

                    const formData = new FormData(this.$refs.translationForm);
                    let locale = params['locale'];
                    formData.append('model', this.model);
                    formData.append('resource_id', this.resourceId);
                    formData.append('resource_type', 'product');
                    formData.append('field', this.id);

                    this.$axios.post("{{route('admin.magic_ai.check.is_translatable')}}", formData)
                        .then((response) => {
                            if (response.data.isTranslatable) {
                                this.sourceData = response.data.sourceData;
                                this.$axios.post("{{ route('admin.magic_ai.translate') }}", formData)
                                    .then((response) => {
                                        this.isLoading = false;
                                        this.translatedData = response.data.translatedData;
                                    })
                                    .catch((error) => {
                                        console.error("Error in translation request:", error);
                                        if (setErrors) {
                                            setErrors(error.response?.data?.errors || {});
                                        }
                                    });
                            } else {
                                this.nothingToTranslate = 'Data not available for translate on the basis of source channel and locale';
                                this.isLoading = false;
                            }
                        })
                        .catch((error) => {
                            console.error("Error in translation check request:", error);
                        });
                },

                apply() {
                    const translatedData = this.translatedData.map(item => ({
                        locale: item.locale,
                        content: item.content,
                    }));

                    const formData = new FormData(this.$refs.translationForm);
                    formData.append('resource_id', this.resourceId);
                    formData.append('resource_type', 'product');
                    formData.append('field', this.id);
                    formData.append('translatedData', JSON.stringify(translatedData));
                    this.$axios.post("{{ route('admin.magic_ai.store.translated') }}", formData)
                        .then((response) => {
                            this.$refs.translationModal.close();
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        })
                        .catch((error) => {

                        });

                },

                cancel() {
                    this.$refs.translationModal.close();
                    this.resetForm();
                },

                resetForm() {
                    this.translatedData = null;
                    this.localeOption = null;
                    this.nothingToTranslate = null;
                    this.targetLocOptions = null;
                }
            }
        });
    </script>
@endPushOnce
