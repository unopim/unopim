@php
    $locales = app('Webkul\Core\Repositories\LocaleRepository')->getActiveLocales();
@endphp

<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.attributes.create.title')
    </x-slot>

    <!-- Create Attributes Vue Components -->
    <v-create-attributes :locales="{{ $locales->toJson() }}"></v-create-attributes>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-attributes-template"
        >

            {!! view_render_event('unopim.admin.catalog.attributes.create.before') !!}

            <!-- Input Form -->
            <x-admin::form
                :action="route('admin.catalog.attributes.store')"
                enctype="multipart/form-data"
            >

                {!! view_render_event('unopim.admin.catalog.attributes.create.create_form_controls.before') !!}

                <!-- actions buttons -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.catalog.attributes.create.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Cancel Button -->
                        <a
                            href="{{ route('admin.catalog.attributes.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.catalog.attributes.create.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.attributes.create.save-btn')
                        </button>
                    </div>
                </div>

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5">

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.label.before') !!}

                    <!-- Left sub Component -->
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <!-- General -->
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.attributes.create.general')
                            </p>

                            <!-- Attribute Code -->
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

                            <!-- Attribute Type -->
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

                            <!-- Textarea Switcher -->
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

                        <!-- Labels -->
                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">                                    
                                    @lang('admin::app.catalog.attributes.create.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                <!-- Locales Inputs -->
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
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>

                        <!-- Options -->
                        <div
                            class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded"
                            v-if=" (
                                    selectedAttributeType == 'select'
                                    || selectedAttributeType == 'multiselect'
                                    || selectedAttributeType == 'checkbox'
                                )"
                        >
                            <div class="flex justify-between items-center mb-3">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.catalog.attributes.create.options')
                                </p>

                                <!-- Add Row Button -->
                                <div
                                    class="secondary-button text-sm"
                                    @click="$refs.addOptionsRow.toggle();swatchValue='';optionIsNew=true;"
                                >
                                    @lang('admin::app.catalog.attributes.create.add-row')
                                </div>
                            </div>

                            <!-- For Attribute Options If Data Exist -->
                            <div class="mt-4 overflow-x-auto">
                                <div class="flex gap-4 max-sm:flex-wrap">
                                    <!-- Input Options -->
                                  
                                      
                                </div>

                                <template v-if="this.options?.length">
                                    <!-- Table Information -->
                                    <x-admin::table>
                                        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                            <x-admin::table.thead.tr>
                                                <x-admin::table.th class="!p-0" />

                                                <!-- Swatch Select -->
                                                <x-admin::table.th v-if="selectedSwatchType == 'color' || selectedSwatchType == 'image'">
                                                    @lang('admin::app.catalog.attributes.create.swatch')
                                                </x-admin::table.th>

                                                <!-- Admin tables heading -->
                                                <x-admin::table.th>
                                                    @lang('admin::app.catalog.attributes.create.code')
                                                </x-admin::table.th>

                                                <!-- Loacles tables heading -->
                                                @foreach ($locales as $locale)
                                                    <x-admin::table.th>
                                                        {{ $locale->name }}
                                                    </x-admin::table.th>
                                                @endforeach

                                                <!-- Action tables heading -->
                                                <x-admin::table.th />
                                            </x-admin::table.thead.tr>
                                        </x-admin::table.thead>

                                        <!-- Draggable Component -->
                                        <draggable
                                            tag="tbody"
                                            ghost-class="draggable-ghost"
                                            handle=".icon-drag"
                                            v-bind="{animation: 200}"
                                            :list="options"
                                            item-key="id"
                                        >
                                            <template #item="{ element, index }">
                                                <x-admin::table.thead.tr class="hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800">
                                                    <!-- Draggable Icon -->
                                                    <x-admin::table.td class="!px-0 text-center">
                                                        <i class="icon-drag text-2xl transition-all group-hover:text-gray-700 cursor-grab"></i>

                                                        <input
                                                            type="hidden"
                                                            :name="'options[' + element.id + '][position]'"
                                                            :value="index"
                                                        />
                                                    </x-admin::table.td>

                                                    <!-- Admin-->
                                                    <x-admin::table.td>
                                                        <p
                                                            class="dark:text-white"
                                                            v-text="element.params.code"
                                                        >
                                                        </p>

                                                        <input
                                                            type="hidden"
                                                            :name="'options[' + element.id + '][code]'"
                                                            v-model="element.params.code"
                                                        />
                                                    </x-admin::table.td>

                                                    <x-admin::table.td v-for="locale in locales">
                                                        <p
                                                            class="dark:text-white"
                                                            v-text="element.params[locale.code]"
                                                        >
                                                        </p>

                                                        <input
                                                            type="hidden"
                                                            :name="'options[' + element.id + '][' + locale.code + '][label]'"
                                                            v-model="element.params[locale.code]"
                                                        />
                                                    </x-admin::table.td>

                                                    <!-- Actions button -->
                                                    <x-admin::table.td class="!px-0">
                                                        <span
                                                            class="icon-edit p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                            @click="editModal(element)"
                                                        >
                                                        </span>

                                                        <span
                                                            class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                            @click="removeOption(element.id)"
                                                        >
                                                        </span>
                                                    </x-admin::table.td>
                                                </x-admin::table.thead.tr>
                                            </template>
                                        </draggable>
                                    </x-admin::table>
                                </template>

                                <!-- For Empty Attribute Options -->
                                <template v-else>
                                    <div class="grid gap-3.5 justify-items-center py-10 px-2.5">
                                        <!-- Attribute Option Image -->
                                        <img
                                            class="w-[120px] h-[120px] dark:invert dark:mix-blend-exclusion"
                                            src="{{ unopim_asset('images/icon-add-product.svg') }}"
                                            alt="@lang('admin::app.catalog.attributes.create.add-attribute-options')"
                                        />

                                        <!-- Add Attribute Options Information -->
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <p class="text-base text-gray-400 font-semibold">
                                                @lang('admin::app.catalog.attributes.create.add-attribute-options')
                                            </p>

                                            <p class="text-gray-400">
                                                @lang('admin::app.catalog.attributes.create.add-options-info')
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.label.after') !!}

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.general.before') !!}

                    <!-- Right sub-component -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full">
                        <!-- Validations -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.create.validations')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Input Validation -->
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

                                <!-- REGEX -->
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

                                <!-- Is Required -->
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

                                <!-- Is Unique -->
                                <x-admin::form.control-group
                                    class="flex gap-2.5 items-center !mb-0 select-none"
                                    v-if="selectedAttributeType == 'text' || selectedAttributeType == 'date' || selectedAttributeType == 'datetime'"
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

                        <!-- Configurations -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.create.configuration')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Value Per Locale -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="value_per_locale"
                                        name="value_per_locale"
                                        value="1"
                                        for="value_per_locale"
                                    />
                
                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="value_per_locale"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.value-per-locale')
                                    </label>
                                </x-admin::form.control-group>

                                <!-- Value Per Channel -->
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
                            </x-slot>
                        </x-admin::accordion>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attributes.create.card.general.after') !!}

                </div>

                {!! view_render_event('unopim.admin.catalog.attributes.create_form_controls.after') !!}
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
                    ref="createOptionsForm"
                >
                    <x-admin::modal
                        ref="addOptionsRow"
                    >
                        <!-- Modal Header !-->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold" ::data-attr="selectedSwatchType">
                                @lang('admin::app.catalog.attributes.create.add-option')
                            </p>
                        </x-slot>

                        <!-- Modal Content !-->
                        <x-slot:content>
                            <div
                                class="grid"
                                v-if="selectedSwatchType == 'image' || selectedSwatchType == 'color'"
                            >
                                <!-- Image Input -->
                                <x-admin::form.control-group
                                    class="w-full"
                                    v-if="selectedSwatchType == 'image'"
                                >
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.create.image')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="image"
                                        name="swatch_value"
                                        :placeholder="trans('admin::app.catalog.attributes.create.image')"
                                    />

                                    <div class="hidden">
                                        <x-admin::media.images
                                            name="swatch_value"
                                            ::uploaded-images='swatchValue.image'
                                        />
                                    </div>

                                    <x-admin::form.control-group.error control-name="swatch_value" />
                                </x-admin::form.control-group>

                                
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <!-- Hidden Id Input -->
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                />

                                <!-- Attribute Option Code Input -->
                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.attributes.create.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        :label="trans('admin::app.catalog.attributes.create.code')"
                                        :placeholder="trans('admin::app.catalog.attributes.create.code')"
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
                                            :name="$locale->code"
                                            :label="$locale->name"
                                        />

                                        <x-admin::form.control-group.error :control-name="$locale->code" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </x-slot>

                        <!-- Modal Footer !-->
                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.catalog.attributes.create.option.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
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

                        attributeType: '',

                        selectedAttributeType: '',

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
                    storeOptions(params, { resetForm }) {
                        let existAlready = this.options.findIndex(item => item.params.code === params.code);

                        if (params.id) {
                            let foundIndex = this.options.findIndex(item => item.id === params.id);

                            if (existAlready !== -1 && existAlready !== foundIndex) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.attributes.create.same-code-error')" });

                                return;
                            }

                            this.options.splice(foundIndex, 1, {
                                ...this.options[foundIndex],
                                params: {
                                    ...this.options[foundIndex].params,
                                    ...params,
                                }
                            });
                        } else {
                            if (existAlready !== -1) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.attributes.create.same-code-error')" });

                                return;
                            }

                            this.options.push({
                                id: 'option_' + this.optionRowCount,
                                params
                            });

                            params.id = 'option_' + this.optionRowCount;
                            this.optionRowCount++;
                        }

                        let formData = new FormData(this.$refs.createOptionsForm);

                        const sliderImage = formData.get("swatch_value[]");

                        if (sliderImage) {
                            params.swatch_value = sliderImage;
                        }

                        this.$refs.addOptionsRow.toggle();

                        if (params.swatch_value instanceof File) {
                            this.setFile(params);
                        }

                        resetForm();
                    },

                    editModal(values) {
                        this.optionIsNew = false;
                        
                        values.params.id = values.id;

                        this.swatchValue = {
                            image: values.swatch_value_url
                            ? [{ id: values.id, url: values.swatch_value_url }]
                            : [],
                        };

                        this.$refs.modelForm.setValues(values.params);

                        this.$refs.addOptionsRow.toggle();
                    },

                    removeOption(id) {
                        this.options = this.options.filter(option => option.id !== id);
                    },

                    setFile(event) {
                        let dataTransfer = new DataTransfer();

                        dataTransfer.items.add(event.swatch_value);

                        // use settimeout because need to wait for render dom before set the src or get the ref value
                        setTimeout(() => {
                            this.$refs['image_' + event.id].src =  URL.createObjectURL(event.swatch_value);

                            this.$refs['imageInput_' + event.id].files = dataTransfer.files;
                        }, 0);
                    }
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
