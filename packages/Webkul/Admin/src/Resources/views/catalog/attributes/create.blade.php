<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.attributes.create.title')
    </x-slot>

    <v-create-attributes :locales="{{ $locales->toJson() }}"></v-create-attributes>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-attributes-template"
        >

            {!! view_render_event('unopim.admin.catalog.attributes.create.before') !!}

            <x-admin::form
                ajax
                :action="route('admin.catalog.attributes.store')"
                enctype="multipart/form-data"
            >

                {!! view_render_event('unopim.admin.catalog.attributes.create.create_form_controls.before') !!}

                <x-admin::page-header :title="trans('admin::app.catalog.attributes.create.title')">
                    <x-slot:actions>
                        <a
                            href="{{ route('admin.catalog.attributes.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.catalog.attributes.create.back-btn')
                        </a>

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.attributes.create.save-btn')
                        </button>
                    </x-slot>
                </x-admin::page-header>

                <div class="flex gap-2.5 mt-3.5">

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.label.before') !!}

                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.attributes.create.general')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.create.code')
                                </x-admin::form.control-group.label>

                                <v-field
                                    type="text"
                                    name="code"
                                    rules="required"
                                    value="{{ old('code') }}"
                                    v-slot="{ field }"
                                    label="{{ trans('admin::app.catalog.attributes.create.code') }}"
                                >
                                    <input
                                        type="text"
                                        id="code"
                                        :class="[errors['{{ 'code' }}'] ? 'border border-red-600 hover:border-red-600' : '']"
                                        class="flex w-full min-h-[39px] py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 dark:focus:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-800"
                                        name="slug"
                                        v-bind="field"
                                        placeholder="{{ trans('admin::app.catalog.attributes.create.code') }}"
                                        v-code
                                    >
                                </v-field>

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.create.type')
                                </x-admin::form.control-group.label>

                                @php
                                    $supportedTypes = config('attribute_types');

                                    $attributeTypes = [];

                                    foreach($supportedTypes as $type) {
                                        $attributeTypes[] = [
                                            'id'    => $type['key'],
                                            'label' => trans($type['name'])
                                        ];
                                    }

                                    $attributeTypesJson = json_encode($attributeTypes);

                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="type"
                                    class="cursor-pointer"
                                    name="type"
                                    rules="required"
                                    :value="old('type')"
                                    v-model="attributeType"
                                    :label="trans('admin::app.catalog.attributes.create.type')"
                                    :options="$attributeTypesJson"
                                    track-by="id"
                                    label-by="label"
                                >
                                    
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group v-if="(selectedAttributeType == 'select' || selectedAttributeType == 'multiselect')">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.create.swatch')
                                </x-admin::form.control-group.label>

                                @php
                                    $options = [];

                                    foreach($swatchTypes as $type) {
                                        $options[] = [
                                            'id'    => $type,
                                            'label' => trans('admin::app.catalog.attributes.edit.option.' . $type),
                                        ];
                                    }

                                    $optionsInJson = json_encode($options);

                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="swatch_type"
                                    class="cursor-pointer"
                                    name="swatch_type"
                                    rules="required"
                                    :value="old('swatch_type', 'text')"
                                    :label="trans('admin::app.catalog.attributes.create.swatch')"
                                    :options="$optionsInJson"
                                    track-by="id"
                                    label-by="label"
                                >
                                    
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="swatch_type" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group v-show=" (selectedAttributeType == 'textarea')">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.catalog.attributes.create.enable-wysiwyg')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="enable_wysiwyg"
                                    value="1"
                                    :label="trans('admin::app.catalog.attributes.create.enable-wysiwyg')"
                                />
                            </x-admin::form.control-group>
                        </div>

                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">                                    
                                    @lang('admin::app.catalog.attributes.create.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group class="last:!mb-0">
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            :name="$locale->code . '[name]'"
                                            :value="old($locale->code)['name'] ?? ''"
                                        />

                                        <x-admin::form.control-group.label class="mt-2">
                                            @lang('admin::app.catalog.attributes.create.instructions')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="textarea"
                                            :name="$locale->code . '[instructions]'"
                                            :value="old($locale->code)['instructions'] ?? ''"
                                            :placeholder="trans('admin::app.catalog.attributes.create.instructions-placeholder')"
                                        />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.label.after') !!}

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.general.before') !!}

                    <div class="flex flex-col gap-2 w-[360px] max-w-full">
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.create.validations')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group v-if="selectedAttributeType == 'text'">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.create.input-validation')
                                    </x-admin::form.control-group.label>

                                 
                                    @php
                                        $supportedValidationTypes = ['number', 'email', 'decimal', 'url', 'regex'];

                                        $validationTypes = [];

                                        foreach($supportedValidationTypes as $type) {
                                            $validationTypes[] = [
                                                'id'    => $type,
                                                'label' => trans('admin::app.catalog.attributes.create.'. $type)
                                            ];
                                        }

                                        $validationTypesJson = json_encode($validationTypes);

                                    @endphp
                                        
                                    <x-admin::form.control-group.control
                                        type="select"
                                        class="cursor-pointer"
                                        id="validation"
                                        name="validation"
                                        :value="old('validation')"
                                        v-model="validationType"
                                        :label="trans('admin::app.catalog.attributes.create.input-validation')"
                                        refs="validation"
                                        :options="$validationTypesJson"
                                        track-by="id"
                                        label-by="label"
                                    >
                                       
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="validation" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group v-show="selectedValidationType == 'regex'">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.create.regex')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="regex_pattern"
                                        :value="old('regex_pattern')"
                                        :placeholder="trans('admin::app.catalog.attributes.create.regex')"
                                    />

                                    <x-admin::form.control-group.error control-name="regex_pattern" />
                                </x-admin::form.control-group>

                                 <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="is_required"
                                        name="is_required"
                                        value="1"
                                        for="is_required"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_required"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.is-required')
                                    </label>
                                </x-admin::form.control-group>

                                <x-admin::form.control-group
                                    class="flex gap-2.5 items-center !mb-0 select-none"
                                    v-if="selectedAttributeType == 'text'"
                                >
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="is_unique"
                                        name="is_unique"
                                        value="1"
                                        for="is_unique"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_unique"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.is-unique')
                                    </label>
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.create.configuration')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="value_per_locale"
                                        name="value_per_locale"
                                        value="1"
                                        for="value_per_locale"
                                        @input="checkValuePerLocale"
                                    />
                
                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="value_per_locale"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.value-per-locale')
                                    </label>
                                </x-admin::form.control-group>
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none" v-show=" ((selectedAttributeType == 'textarea') || (selectedAttributeType == 'text')) && (valuePerLocale == 1)">

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="ai_translate"
                                        name="ai_translate"
                                        value="1"
                                        for="ai_translate"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="ai_translate"
                                    >
                                        @lang('admin::app.catalog.attributes.create.ai-translate')
                                    </label>
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="value_per_channel"
                                        name="value_per_channel"
                                        value="1"
                                        for="value_per_channel"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="value_per_channel"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.value-per-channel')
                                    </label>
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        name="is_filterable"
                                        id="is_filterable"
                                        for="is_filterable"
                                        value="1"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_filterable"
                                    >
                                        @lang('admin::app.catalog.attributes.create.is-filterable')
                                    </label>
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.general.after') !!}

                </div>

                {!! view_render_event('unopim.admin.catalog.attributes.create_form_controls.after') !!}
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.attributes.create.after') !!}

        </script>

        <script type="module">
            app.component('v-create-attributes', {
                template: '#v-create-attributes-template',

                props: ['locales'],

                data() {
                    return {
                        optionRowCount: 1,

                        attributeType: @json(old('type')) ?? '',

                        selectedAttributeType: @json(old('type')) ?? '',

                        validationType: '',

                        selectedSwatchType: '',

                        swatchType: '',

                        optionIsNew: true,

                        selectedValidationType: '',

                        options: [],

                        swatchValue: [
                            {
                            image: [],
                        }
                    ],
                        valuePerLocale: 0,
                    }
                },
                watch: {
                    attributeType(value) {
                        this.selectedAttributeType = this.parseValue(value)?.id;
                    },
                    validationType(value) {
                        this.selectedValidationType = this.parseValue(value)?.id;
                    },
                    swatchType(value) {
                        this.selectedSwatchType = this.parseValue(value)?.id;
                    }
                },
                methods: {
                    parseValue(value) {  
                        try {
                            return value ? JSON.parse(value) : null;
                        } catch (error) {
                            return value;
                        }
                    },

                    checkValuePerLocale() {
                        this.valuePerLocale = this.valuePerLocale === 0 ? 1 : 0;
                    }
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
