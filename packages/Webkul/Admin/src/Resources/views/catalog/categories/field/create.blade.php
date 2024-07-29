<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.category_fields.create.title')
    </x-slot>

    <!-- Create Attributes Vue Components -->
    <v-create-category-field :locales="{{ $locales->toJson() }}"></v-create-category-field>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-category-field-template"
        >

            {!! view_render_event('unopim.admin.catalog.category_fields.create.before') !!}

            <!-- Input Form -->
            <x-admin::form
                :action="route('admin.catalog.category_fields.store')"
                enctype="multipart/form-data"
            >

                {!! view_render_event('unopim.admin.catalog.category_fields.create.create_form_controls.before') !!}

                <!-- actions buttons -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.catalog.category_fields.create.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Cancel Button -->
                        <a
                            href="{{ route('admin.catalog.category_fields.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.catalog.category_fields.create.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.category_fields.create.save-btn')
                        </button>
                    </div>
                </div>

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5">

                    {!! view_render_event('unopim.admin.catalog.category_fields.create.card.label.before') !!}

                    <!-- Left sub Component -->
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <!-- General -->
                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.create.general')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                <!-- CatgeoryField Code -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.code')
                                    </x-admin::form.control-group.label>

                                    <v-field
                                        type="text"
                                        name="code"
                                        rules="required"
                                        value="{{ old('code') }}"
                                        v-slot="{ field }"
                                        label="{{ trans('admin::app.catalog.category_fields.create.code') }}"
                                    >
                                        <input
                                            type="text"
                                            id="code"
                                            :class="[errors['{{ 'code' }}'] ? 'border border-red-600 hover:border-red-600' : '']"
                                            class="flex w-full min-h-[39px] py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 dark:focus:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-800"
                                            name="slug"
                                            v-bind="field"
                                            placeholder="{{ trans('admin::app.catalog.category_fields.create.code') }}"
                                            v-code
                                        >
                                    </v-field>

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Category Field Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.type')
                                    </x-admin::form.control-group.label>

                                 
                                    @php
                                        $supportedTypes = ['text', 'textarea', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'file', 'checkbox'];

                                        $fieldTypes = [];

                                        foreach($supportedTypes as $type) {
                                            $fieldTypes[] = [
                                                'id'    => $type,
                                                'label' => trans('admin::app.catalog.category_fields.create.'. $type)
                                            ];
                                        }

                                        $fieldTypesJson = json_encode($fieldTypes);

                                        $selectedType = [
                                            'id'    => old('type'),
                                            'label' => trans('admin::app.catalog.category_fields.create.'. old('type'))
                                        ];

                                    @endphp
                                    
                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="type"
                                        class="cursor-pointer"
                                        name="type"
                                        rules="required"
                                        :value="old('type')"
                                        v-model="categoryFieldType"
                                        :label="trans('admin::app.catalog.category_fields.create.type')"
                                        @change="swatchAttribute=true"
                                        :options="$fieldTypesJson"
                                        track-by="id"
                                        label-by="label"
                                    >
                                        
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>

                                <!-- Textarea Switcher -->
                                <x-admin::form.control-group v-show="selectedCategoryFieldType == 'textarea'">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.create.enable-wysiwyg')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="enable_wysiwyg"
                                        value="1"
                                        :label="trans('admin::app.catalog.category_fields.create.enable-wysiwyg')"
                                    />
                                </x-admin::form.control-group>
                            </div>
                        </div>

                        <!-- Label -->
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.category_fields.create.label')
                            </p>
 

                            <!-- Locales Inputs -->
                            @foreach ($locales as $locale)
                                <x-admin::form.control-group class="last:!mb-0">
                                    <x-admin::form.control-group.label>
                                        {{ $locale->name }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        :name="$locale->code . '[name]'"
                                        :value="old($locale->code . '.name')"
                                    />
                                </x-admin::form.control-group>
                            @endforeach
                        </div>

                        <!-- Options -->
                        <div
                            class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded"
                            v-if="selectedCategoryFieldType == 'select'
                                    || selectedCategoryFieldType == 'multiselect'
                                    || selectedCategoryFieldType == 'checkbox'
                                "
                        >
                            <div class="flex justify-between items-center mb-3">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.catalog.category_fields.create.options')
                                </p>

                                <!-- Add Row Button -->
                                <div
                                    class="secondary-button text-sm"
                                    @click="$refs.addOptionsRow.toggle();swatchValue='';optionIsNew=true;"
                                >
                                    @lang('admin::app.catalog.category_fields.create.add-row')
                                </div>
                            </div>

                            <!-- For Category Field Options If Data Exist -->
                            <div class="mt-4 overflow-x-auto">

                                <template v-if="this.options?.length">
                                    <!-- Table Information -->
                                    <x-admin::table>
                                        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                            <x-admin::table.thead.tr>
                                                <x-admin::table.th class="!p-0" />

                                                <!-- Admin tables heading -->
                                                <x-admin::table.th>
                                                    @lang('admin::app.catalog.category_fields.create.code')
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
                                                <x-admin::table.thead.tr class="hover:bg-violet-50 dark:hover:bg-cherry-800" ::data-list="JSON.stringify(options)">
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

                                <!-- For Empty Category Fields Options -->
                                <template v-else>
                                    <div class="grid gap-3.5 justify-items-center py-10 px-2.5">
                                        <!-- Category Fields Option Image -->
                                        <img
                                            class="w-[120px] h-[120px] dark:invert dark:mix-blend-exclusion"
                                            src="{{ unopim_asset('images/icon-add-product.svg') }}"
                                            alt="@lang('admin::app.catalog.category_fields.create.add-field-options')"
                                        />

                                        <!-- Add Category Fields Options Information -->
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <p class="text-base text-gray-400 font-semibold">
                                                @lang('admin::app.catalog.category_fields.create.add-field-options')
                                            </p>

                                            <p class="text-gray-400">
                                                @lang('admin::app.catalog.category_fields.create.add-options-info')
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.category_fields.create.card.label.after') !!}

                    {!! view_render_event('unopim.admin.catalog.category_fields.create.card.general.before') !!}

                    <!-- Right sub-component -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full">
                        <!-- Validations -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.create.validations')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Input Validation -->
                                <x-admin::form.control-group v-if="selectedCategoryFieldType == 'text'">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.create.input-validation')
                                    </x-admin::form.control-group.label>

                                    @php

                                        $supportedOptions = ['number', 'email', 'decimal', 'url', 'regex'];

                                        $options = [];

                                        foreach($supportedOptions as $type) {
                                            $options[] = [
                                                'id'    => $type,
                                                'label' => trans('admin::app.catalog.category_fields.create.'. $type)
                                            ];
                                        }

                                        $optionsJson = json_encode($options);

                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        class="cursor-pointer"
                                        id="validation"
                                        name="validation"
                                        :value="old('validation')"
                                        v-model="validationType"
                                        :label="trans('admin::app.catalog.category_fields.create.input-validation')"
                                        refs="validation"
                                        @change="inputValidation=true"
                                        :options="$optionsJson"
                                        track-by="id"
                                        label-by="label"
                                    >

                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="validation" />
                                </x-admin::form.control-group>

                                <!-- REGEX -->
                                <x-admin::form.control-group v-show="'regex' == selectedValidationType">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.regex')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="regex_pattern"
                                        :value="old('regex_pattern')"
                                        :placeholder="trans('admin::app.catalog.category_fields.create.regex')"
                                    />

                                    <x-admin::form.control-group.error control-name="regex_pattern" />
                                </x-admin::form.control-group>

                                <!-- Is Required -->
                                 <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2">

                                    <input type="hidden" name="is_required" value="0">

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="is_required"
                                        name="is_required"
                                        value="1"
                                        for="is_required"
                                        :checked="1 == (old('is_required'))"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_required"
                                    >
                                        @lang('admin::app.catalog.category_fields.edit.is-required')
                                    </label>
                                </x-admin::form.control-group>

                                <!-- Is Unique -->
                                <x-admin::form.control-group
                                    class="flex gap-2.5 items-center !mb-0 select-none"
                                    v-if="selectedCategoryFieldType == 'text' || selectedCategoryFieldType == 'date' || selectedCategoryFieldType == 'datetime'"
                                >
                                    <input type="hidden" name="is_unique" value="0">

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="is_unique"
                                        name="is_unique"
                                        value="1"
                                        for="is_unique"
                                        :checked="1 == (old('is_unique'))"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="is_unique"
                                    >
                                        @lang('admin::app.catalog.category_fields.edit.is-unique')
                                    </label>
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        <!-- Configurations -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.create.configuration')
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
                                        :checked="1 == old('value_per_locale')"
                                    />
                
                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="value_per_locale"
                                    >
                                        @lang('admin::app.catalog.category_fields.create.value-per-locale')
                                    </label>
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        <!-- Settings Section -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.create.settings')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Enable/Disable -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.create.status')
                                    </x-admin::form.control-group.label>
                                    <input 
                                        type="hidden"
                                        name="status"
                                        value="0"
                                    />

                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="status"
                                        value="1"
                                        :checked="1 == (old('status') ?? 1)"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.position')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="position"
                                        rules="required|numeric|min_value:0"
                                        :value="old('position') ?? 0"
                                    />
                                    <x-admin::form.control-group.error control-name="position" />
                                </x-admin::form.control-group>

                                <!-- Display section -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.set-section')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $supportedTypes = ['left', 'right'];

                                        $options = [];

                                        foreach($supportedTypes as $type) {
                                            $options[] = [
                                                'id'    => $type,
                                                'label' => trans('admin::app.catalog.category_fields.create.set-section-' . $type),
                                            ];
                                        }

                                        $optionsInJson = json_encode($options);

                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="section"
                                        name="section"
                                        v-model="section"
                                        :options=$optionsInJson
                                        :value="old('section') ?? $supportedTypes[0]"
                                        track-by="id"
                                        label-by="label"
                                    >

                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="section" />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.category_fields.create.card.general.after') !!}

                </div>

                {!! view_render_event('unopim.admin.catalog.category_fields.create_form_controls.after') !!}
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
                        @toggle="listenModal"
                        ref="addOptionsRow"
                    >
                        <!-- Modal Header !-->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.category_fields.create.add-option')
                            </p>
                        </x-slot>

                        <!-- Modal Content !-->
                        <x-slot:content>
                            <div
                                class="grid"
                                v-if="swatchType == 'image' || swatchType == 'color'"
                            >
                                <!-- Image Input -->
                                <x-admin::form.control-group
                                    class="w-full"
                                    v-if="swatchType == 'image'"
                                >
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.create.image')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="image"
                                        name="swatch_value"
                                        :placeholder="trans('admin::app.catalog.category_fields.create.image')"
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

                                <!-- Code Input -->
                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        :label="trans('admin::app.catalog.category_fields.create.code')"
                                        :placeholder="trans('admin::app.catalog.category_fields.create.code')"
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
                                @lang('admin::app.catalog.category_fields.create.option.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.category_fields.create.after') !!}

        </script>

        <script type="module">
            app.component('v-create-category-field', {
                template: '#v-create-category-field-template',

                props: ['locales'],

                data() {
                    return {
                        optionRowCount: 1,

                        selectedCategoryFieldType: '',

                        categoryFieldType: '',

                        validationType: '',

                        selectedValidationType: '',

                        inputValidation: false,

                        swatchType: '',

                        swatchAttribute: false,

                        showSwatch: false,

                        isNullOptionChecked: false,

                        options: [],

                        optionIsNew: true,

                        swatchValue: [
                            {
                                image: [],
                            }
                        ],
                    }
                },

                watch: {
                    categoryFieldType(value) {
                        this.selectedCategoryFieldType = this.parseValue(value)?.id;
                    },
                    validationType(value) {
                        this.selectedValidationType = this.parseValue(value)?.id;
                    }
                },

                methods: {
                    storeOptions(params, { resetForm }) {
                        let existAlready = this.options.findIndex(item => item.params.code === params.code);

                        if (params.id) {
                            let foundIndex = this.options.findIndex(item => item.id === params.id);

                            if (existAlready !== -1 && existAlready !== foundIndex) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.category_fields.create.same-code-error')" });

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
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.category_fields.create.same-code-error')" });

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
                            image: values?.swatch_value_url
                            ? [{ id: values.id, url: values.swatch_value_url }]
                            : [],
                        };

                        this.$refs.modelForm.setValues(values.params);

                        this.$refs.addOptionsRow.toggle();
                    },

                    removeOption(id) {
                        this.options = this.options.filter(option => option.id !== id);
                    },

                    listenModal(event) {
                        if (! event.isActive) {
                            this.isNullOptionChecked = false;
                        }
                    },

                    setFile(event) {
                        let dataTransfer = new DataTransfer();

                        dataTransfer.items.add(event.swatch_value);

                        // use settimeout because need to wait for render dom before set the src or get the ref value
                        setTimeout(() => {
                            this.$refs['image_' + event.id].src =  URL.createObjectURL(event.swatch_value);

                            this.$refs['imageInput_' + event.id].files = dataTransfer.files;
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
</x-admin::layouts>
