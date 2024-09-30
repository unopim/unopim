<x-admin::layouts.with-history>
    <x-slot:entityName>
        attribute
    </x-slot>

    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.attributes.edit.title')
    </x-slot>

    <!-- Edit Attributes Vue Components -->
    <v-edit-attributes :locales="{{ $locales->toJson() }}"></v-edit-attributes>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-attributes-template"
        >
            {!! view_render_event('unopim.admin.catalog.attributes.edit.before') !!}

            <!-- Input Form -->
            <x-admin::form
                :action="route('admin.catalog.attributes.update', $attribute->id)"
                enctype="multipart/form-data"
                method="PUT"
            >
                
                {!! view_render_event('unopim.admin.catalog.attributes.create._form_controls.before') !!}

                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.catalog.attributes.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Back Button -->
                        <a
                            href="{{ route('admin.catalog.attributes.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.catalog.attributes.edit.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.attributes.edit.save-btn')
                        </button>
                    </div>
                </div>

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5">
                    <!-- Left sub Component -->
                    <div class="flex flex-col flex-1 gap-2 overflow-auto">

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.label.before', ['attribute' => $attribute]) !!}

                        <!-- Label -->
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.attributes.edit.general')
                            </p>
                                <!-- Attribute Code -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.edit.code')
                                </x-admin::form.control-group.label>

                                @php
                                    $selectedOption = old('type') ?: $attribute->code;
                                @endphp

                                <x-admin::form.control-group.control
                                    type="text"
                                    class="cursor-not-allowed"
                                    name="code"
                                    rules="required"
                                    :value="$selectedOption"
                                    :disabled="(boolean) $selectedOption"
                                    readonly
                                    :label="trans('admin::app.catalog.attributes.edit.code')"
                                    :placeholder="trans('admin::app.catalog.attributes.edit.code')"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="code"
                                    :value="$selectedOption"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <!-- Attribute Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.edit.type')
                                </x-admin::form.control-group.label>

                                @php
                                    $supportedTypes = ['text', 'textarea', 'price', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'gallery', 'file', 'checkbox'];

                                    $attributeTypes = [];

                                    foreach($supportedTypes as $type) {
                                        $attributeTypes[] = [
                                            'id'    => $type,
                                            'label' => trans('admin::app.catalog.attributes.edit.'. $type)
                                        ];
                                    }

                                    $attributeTypesJson = json_encode($attributeTypes);

                                    $selectedOption = old('type') ?: $attribute->type;
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="type"
                                    class="cursor-not-allowed"
                                    name="type"
                                    rules="required"
                                    :options="$attributeTypesJson"
                                    :value="$selectedOption"
                                    v-model="attributeType"
                                    :disabled="(boolean) $selectedOption"
                                    :label="trans('admin::app.catalog.attributes.edit.type')"
                                    track-by="id"
                                    label-by="label"
                                >
                                     
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="type"
                                    :value="$attribute->type"
                                />

                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>

                            <!-- Textarea Switcher -->
                                <x-admin::form.control-group v-show="{{ $attribute->type == 'textarea' }}">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.edit.enable-wysiwyg')
                                    </x-admin::form.control-group.label>

                                    <input
                                        type="hidden"
                                        name="enable_wysiwyg"
                                        value="0"
                                    />

                                    @php $selectedOption = old('enable_wysiwyg') ?: $attribute->enable_wysiwyg @endphp

                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="enable_wysiwyg"
                                        value="1"
                                        :label="trans('admin::app.catalog.attributes.edit.enable-wysiwyg')"
                                        :checked="(bool) $selectedOption"
                                    />
                                </x-admin::form.control-group>
                        </div>

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.label.after', ['attribute' => $attribute]) !!}

                        <!-- Labels -->
                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                <!-- Locales Inputs -->
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            :name="$locale->code . '[name]'"
                                            :value="old($locale->code)['name'] ?? ($attribute->translate($locale->code)->name ?? '')"
                                        />

                                        <x-admin::form.control-group.error :control-name="$locale->code . '[name]'" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>

                        <!-- Options -->
                        <div
                            class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded"
                            v-if="(
                                    selectedAttributeType == 'select'
                                    || selectedAttributeType == 'multiselect'
                                    || selectedAttributeType == 'checkbox'
                            )"
                        >
                            <div class="flex justify-between items-center mb-3">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.options')
                                </p>

                                <!-- Add Row Button -->
                                <div
                                    class="secondary-button text-sm"
                                    @click="$refs.addOptionsRow.toggle();swatchValue='';optionIsNew=true;"
                                >
                                    @lang('admin::app.catalog.attributes.edit.add-row')
                                </div>
                            </div>

                            <!-- For Attribute Options If Data Exist -->
                            <div class="mt-4 overflow-x-auto">
                                <div class="flex gap-4 items-center max-sm:flex-wrap">
                                    <!-- Input Options -->
                                  
                                </div>

                                <template v-if="optionsData?.length">
                                    @if (
                                        $attribute->type == 'select'
                                        || $attribute->type == 'multiselect'
                                        || $attribute->type == 'checkbox'
                                    )
                                        <!-- Table Information -->
                                        <x-admin::table>
                                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                                <x-admin::table.thead.tr>
                                                    <x-admin::table.th class="!p-0"></x-admin::table.th>
    
                                                    <!-- Swatch Select -->
                                                    <x-admin::table.th v-if="showSwatch && (selectedSwatchType == 'color' || selectedSwatchType == 'image')">
                                                        @lang('admin::app.catalog.attributes.edit.swatch')
                                                    </x-admin::table.th>
    
                                                    <!-- Admin tables heading -->
                                                    <x-admin::table.th>
                                                        @lang('admin::app.catalog.attributes.edit.code')
                                                    </x-admin::table.th>
    
                                                    <!-- Loacles tables heading -->
                                                    @foreach ($locales as $locale)
                                                        <x-admin::table.th>
                                                            {{ $locale->name }}
                                                        </x-admin::table.th>
                                                    @endforeach
    
                                                    <!-- Action tables heading -->
                                                    <x-admin::table.th></x-admin::table.th>
                                                </x-admin::table.thead.tr>
                                            </x-admin::table.thead>
    
                                            <!-- Draggable Component -->
                                            <draggable
                                                tag="tbody"
                                                ghost-class="draggable-ghost"
                                                handle=".icon-drag"
                                                v-bind="{animation: 200}"
                                                :list="optionsData"
                                                item-key="id"
                                            >
                                                <template #item="{ element, index }">
                                                    <x-admin::table.thead.tr
                                                        class="hover:bg-violet-50 hover:bg-opacity-50 dark:hover:bg-cherry-800"
                                                        v-show="! element.isDelete"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            :name="'options[' + element.id + '][isNew]'"
                                                            :value="element.isNew"
                                                        >
    
                                                        <input
                                                            type="hidden"
                                                            :name="'options[' + element.id + '][isDelete]'"
                                                            :value="element.isDelete"
                                                        >
    
                                                        <!-- Draggable Icon -->
                                                        <x-admin::table.td class="!px-0 text-center">
                                                            <i class="icon-drag text-2xl transition-all group-hover:text-gray-700 cursor-grab"></i>
    
                                                            <input
                                                                type="hidden"
                                                                :name="'options[' + element.id + '][sort_order]'"
                                                                :value="index"
                                                            />
                                                        </x-admin::table.td>

                                                        <!-- Admin-->
                                                        <x-admin::table.td>
                                                            <p
                                                                class="dark:text-white"
                                                                v-text="element.code"
                                                                :data-attr="JSON.stringify(locales)"
                                                            >
                                                            </p>
    
                                                            <input
                                                                type="hidden"
                                                                :name="'options[' + element.id + '][code]'"
                                                                v-model="element.code"
                                                            />
                                                        </x-admin::table.td>
    
                                                        <!-- Loacles -->
                                                        <x-admin::table.td v-for="locale in locales">
                                                            <p
                                                                class="dark:text-white"
                                                                v-text="element['locales'][locale.code]"
                                                            >
                                                            </p>
    
                                                            <input
                                                                type="hidden"
                                                                :name="'options[' + element.id + '][' + locale.code + '][label]'"
                                                                v-model="element['locales'][locale.code]"
                                                            />
                                                        </x-admin::table.td>
    
                                                        <!-- Actions Button -->
                                                        <x-admin::table.td class="!px-0">
                                                            <span
                                                                class="icon-edit p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                                @click="editOptions(element)"
                                                            >
                                                            </span>
    
                                                            <span
                                                                class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-gray-800  max-sm:place-self-center"
                                                                @click="removeOption(element.id)"
                                                            >
                                                            </span>
                                                        </x-admin::table.td>
                                                    </x-admin::table.thead.tr>
                                                </template>
                                            </draggable>
                                        </x-admin::table>
                                    @endif
                                </template>

                                <!-- For Empty Attribute Options -->
                                <template v-else>
                                    <div class="grid gap-3.5 justify-items-center py-10 px-2.5">
                                        <!-- Attribute Option Image -->
                                        <img
                                            class="w-[120px] h-[120px] dark:invert dark:mix-blend-exclusion"
                                            src="{{ unopim_asset('images/icon-add-product.svg') }}"
                                            alt="{{ trans('admin::app.catalog.attributes.edit.add-attribute-options') }}"
                                        >

                                        <!-- Add Attribute Options Information -->
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <p class="text-base text-gray-400 font-semibold">
                                                @lang('admin::app.catalog.attributes.edit.add-attribute-options')
                                            </p>

                                            <p class="text-gray-400">
                                                @lang('admin::app.catalog.attributes.edit.add-options-info')
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Right sub-component -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.accordian.validations.before', ['attribute' => $attribute]) !!}

                        <!-- Validations -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.validations')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Input Validation -->
                                @if($attribute->type == 'text')
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.catalog.attributes.edit.input-validation')
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
                                            name="validation"
                                            v-model="validationType"
                                            :options="$validationTypesJson"
                                            track-by="id"
                                            label-by="label"
                                        >
                                        </x-admin::form.control-group.control>
                                    </x-admin::form.control-group>
                                @endif

                                <!-- REGEX -->
                                <x-admin::form.control-group v-if="'regex' == selectedValidationType">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.create.regex')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="regex_pattern"
                                        v-model="regex_pattern"
                                        :value="old('regex_pattern') ?? $attribute->regex_pattern"
                                    />

                                    <x-admin::form.control-group.error control-name="regex_pattern" />
                                </x-admin::form.control-group>

                                <!-- Is Required -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    @php
                                        $selectedOption = old('is_required') ?? $attribute->is_required
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="is_required"
                                        value="0"
                                    />

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        name="is_required"
                                        id="is_required"
                                        for="is_required"
                                        value="1"
                                        :checked="(boolean) $selectedOption"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_required"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.is-required')
                                    </label>
                                </x-admin::form.control-group>

                                <!-- Is Unique -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-0 opacity-70 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="is_unique"
                                        name="is_unique"
                                        value="1"
                                        for="is_unique"
                                        :checked="(boolean) $attribute->is_unique"
                                        disabled
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_unique"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.is-unique')
                                    </label>    

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        :name="$type"
                                        :value="$attribute->is_unique"
                                    />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.accordian.validations.after', ['attribute' => $attribute]) !!}

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.accordian.configuration.before', ['attribute' => $attribute]) !!}

                        <!-- Configurations -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.configuration')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Value Per Locale -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 opacity-70 select-none">
                                    @php
                                        $valuePerLocale = old('value_per_locale') ?? $attribute->value_per_locale;
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="value_per_locale"
                                        name="value_per_locale"
                                        value="1"
                                        :checked="(boolean) $valuePerLocale"
                                        :disabled="(boolean) $valuePerLocale"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-not-allowed"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.value-per-locale')
                                    </label>   

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="value_per_locale"
                                        :value="(boolean) $valuePerLocale"
                                    />
                                </x-admin::form.control-group>

                                <!-- Value Per Channel -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 opacity-70 select-none">
                                    @php
                                        $valuePerChannel = old('value_per_channel') ?? $attribute->value_per_channel
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="value_per_channel"
                                        name="value_per_channel"
                                        value="1"
                                        :checked="(boolean) $valuePerChannel"
                                        :disabled="(boolean) $valuePerChannel"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-not-allowed"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.value-per-channel')
                                    </label>   

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="value_per_channel"
                                        :value="(boolean) $valuePerChannel"
                                    />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.accordian.configuration.configuration.after', ['attribute' => $attribute]) !!}
                    </div>
                </div>
            </x-admin::form>

            <!-- Add Options Model Form -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modelForm"
            >
                <form
                    @submit.prevent="handleSubmit($event, storeOptions)"
                    enctype="multipart/form-data"
                    ref="editOptionsForm"
                >
                    <x-admin::modal
                        ref="addOptionsRow"
                    >
                        <!-- Modal Header !-->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.attributes.edit.add-option')
                            </p>
                        </x-slot>

                        <!-- Modal Content !-->
                        <x-slot:content>
                            <div class="grid">
                                <!-- Image Input -->
                                <x-admin::form.control-group
                                    class="w-full"
                                    v-if="selectedSwatchType == 'image'"
                                >
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.edit.image')
                                    </x-admin::form.control-group.label>

                                    <div class="hidden">
                                        <x-admin::media.images
                                            name="swatch_value[]"
                                            ::uploaded-images='swatchValue.image'
                                        />
                                    </div>

                                    <v-media-images
                                        name="swatch_value"
                                        :uploaded-images='swatchValue.image'
                                    >
                                    </v-media-images>

                                    <x-admin::form.control-group.error control-name="swatch_value" />
                                </x-admin::form.control-group>

                                 
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <!-- Hidden Id Input -->
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="isNew"
                                    ::value="optionIsNew"
                                />

                                <!-- Attribute Option Code Input -->
                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.attributes.edit.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        :label="trans('admin::app.catalog.attributes.edit.code')"
                                        :placeholder="trans('admin::app.catalog.attributes.edit.code')"
                                        ref="inputAdmin"
                                        ::disabled="true !== optionIsNew"
                                        v-code
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Locales Input -->
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group class="w-full mb-2.5">
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="locales.{{ $locale->code }}"
                                            :label="$locale->name"
                                        />

                                        <x-admin::form.control-group.error control-name="locales.{{ $locale->code }}" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </x-slot>

                        <!-- Modal Footer !-->
                        <x-slot:footer>
                            <!-- Save Button -->
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.catalog.attributes.edit.option.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.attributes.edit.after') !!}

        </script>

        <script type="module">
            app.component('v-edit-attributes', {
                template: '#v-edit-attributes-template',

                props: ['locales'],

                data: function() {
                    return {
                        showSwatch: {{ in_array($attribute->type, ['select', 'checkbox', 'price', 'multiselect']) ? 'true' : 'false' }},

                        swatchType: "{{ $attribute->swatch_type == '' ? 'dropdown' : $attribute->swatch_type }}",

                        selectedSwatchType: "{{ $attribute->swatch_type == '' ? 'dropdown' : $attribute->swatch_type }}",

                        validationType: "{{ $attribute->validation }}",

                        selectedValidationType: "{{ $attribute->validation }}",

                        selectedAttributeType: "{{ $attribute->type }}",

                        swatchValue: [
                            {
                                image: [],
                            }
                        ],

                        optionsData: [],

                        optionIsNew: true,

                        optionId: 0,

                        src: "{{ route('admin.catalog.attributes.options', $attribute->id) }}",
                    }
                },

                created: function () {
                    this.getAttributesOption();
                },

                watch: {
                    validationType(value) {
                        this.selectedValidationType = this.parseValue(value)?.id;
                    },
                    swatchType(value) {
                        this.selectedSwatchType = this.parseValue(value)?.id;
                    }
                },
                methods: {
                    storeOptions(params, { resetForm, setValues }) {
                        if (! params.id) {
                            params.id = 'option_' + this.optionId;
                            this.optionId++;
                        }

                        let foundIndex = this.optionsData.findIndex(item => item.id === params.id);

                        if (foundIndex !== -1) {
                            this.optionsData.splice(foundIndex, 1, params);
                        } else {
                            let existAlready = this.optionsData.findIndex(item => item.code === params.code);

                            if (existAlready !== -1) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.attributes.edit.same-code-error')" });

                                return;
                            }

                            this.optionsData.push(params);
                        }

                        let formData = new FormData(this.$refs.editOptionsForm);

                        const sliderImage = formData.get("swatch_value[]");

                        if (sliderImage) {
                            params.swatch_value = sliderImage;
                        }

                        this.$refs.addOptionsRow.toggle();

                        if (params.swatch_value instanceof File) {
                            this.setFile(sliderImage, params.id);
                        }

                        resetForm();
                    },

                    editOptions(value) {
                        this.optionIsNew = false;

                        this.swatchValue = {
                            image: value.swatch_value_url
                            ? [{ id: value.id, url: value.swatch_value_url }]
                            : [],
                        };

                        this.$refs.modelForm.setValues(value);

                        this.$refs.addOptionsRow.toggle();
                    },

                    removeOption(id) {
                        let foundIndex = this.optionsData.findIndex(item => item.id === id);

                        if (foundIndex !== -1) {
                            if (this.optionsData[foundIndex].isNew) {
                                this.optionsData.splice(foundIndex, 1);
                            } else {
                                this.optionsData[foundIndex].isDelete = true;
                            }
                        }
                    },

                    getAttributesOption() {
                        this.$axios.get(`${this.src}`)
                            .then(response => {
                                let options = response.data;

                                options.forEach((option) => {
                                    let row = {
                                        'id': option.id,
                                        'code': option.code,
                                        'sort_order': option.sort_order,
                                        'swatch_value': option.swatch_value,
                                        'swatch_value_url': option.swatch_value_url,
                                        'notRequired': '',
                                        'locales': {},
                                        'isNew': false,
                                        'isDelete': false,
                                    };

                                    option.translations.forEach((translation) => {
                                        row['locales'][translation.locale] = translation.label ?? '';
                                    });

                                    this.optionsData.push(row);
                                });
                            });
                    },

                    setFile(file, id) {
                        let dataTransfer = new DataTransfer();

                        dataTransfer.items.add(file);

                        // Use Set timeout because need to wait for render dom before set the src or get the ref value
                        setTimeout(() => {
                            this.$refs['image_' + id].src =  URL.createObjectURL(file);

                            this.$refs['imageInput_' + id].files = dataTransfer.files;
                        }, 0);
                    },

                    parseValue(value) {  
                        try {
                            return value ? JSON.parse(value) : null;
                        } catch (error) {
                            return value;
                        }
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
