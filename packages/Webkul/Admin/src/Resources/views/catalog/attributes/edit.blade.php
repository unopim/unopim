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
                                    $supportedTypes = config('attribute_types');

                                    $attributeTypes = [];

                                    foreach($supportedTypes as $key => $type) {
                                        $attributeTypes[] = [
                                            'id'    => $key,
                                            'label' => trans($type['name'])
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
                            
                            <!-- Swatch Changer And Empty Field Section -->
                            <div
                                v-if="showSwatch"
                                class="flex items-center gap-4 max-sm:flex-wrap"  
                            >
                                <!-- Input Options -->
                                <x-admin::form.control-group
                                    class="mb-2.5 w-full"
                                >
                                    <x-admin::form.control-group.label for="swatchType">
                                        @lang('admin::app.catalog.attributes.edit.input-options')
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
                                        id="swatchType"
                                        name="swatch_type"   
                                        :options="$optionsInJson"
                                        v-model="swatchType"
                                        @change="showSwatch=true"
                                        track-by="id"
                                        label-by="label"
                                        :disabled="(boolean) $attribute->swatch_type"
                                    >
                                    </x-admin::form.control-group.control>
                                        
                                    <x-admin::form.control-group.error control-name="swatch_type" />
                                </x-admin::form.control-group>

                                <div class="w-full">
                                </div>
                            </div>

                            <!-- For Attribute Options If Data Exist -->
                            <div class="overflow-x-auto">
                                <x-admin::datagrid
                                    :src="route('admin.catalog.attributes.options.index', $attribute->id)"
                                    ref="optionsDataGrid"
                                >
                                    <template #header="{ columns, records, sortPage, selectAllRecords, applied, isLoading, actions}">
                                        <template v-if="! isLoading">
                                            <div
                                                class="row grid grid-rows-1 gap-2.5 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold"
                                                :style="'grid-template-columns: 0.2fr repeat(' + (actions.length ? columns.length + (selectedSwatchType=='color' || selectedSwatchType == 'image' ? 2 : 1 ) : (columns.length )) + ', 1fr)'"
                                            >
                                            <!-- Empty div to manage layout  -->
                                            <div>
                                            </div>
                                                <!-- Column Headers -->
                                                 <div v-if="showSwatch && (selectedSwatchType == 'color' || selectedSwatchType == 'image')"
                                                    class="flex items-center select-none">
                                                    <p class="text-gray-600 dark:text-gray-300">
                                                        @lang('admin::app.catalog.attributes.edit.swatch')
                                                    </p>
                                                </div>

                                                <div
                                                    class="flex items-center select-none"
                                                    v-for="(column, index) in columns"
                                                >
                                                    <p class="text-gray-600 dark:text-gray-300">
                                                        <span class="[&>*]:after:content-['_/_']">
                                                            <span
                                                                class="after:content-['/'] last:after:content-['']"
                                                                :class="{
                                                                    'text-gray-800 dark:text-white font-medium': applied.sort.column == column.index,
                                                                    'cursor-pointer hover:text-gray-800 dark:hover:text-white': column.sortable,
                                                                }"
                                                                @click="
                                                                    column?.sortable ? sortPage(column.index): {}
                                                                "
                                                            >
                                                                @{{ column?.label }}
                                                            </span>
                                                        </span>
                                                    </p>
                                                </div>

                                                <!-- Actions -->
                                                <div
                                                    class="flex gap-2.5 items-center justify-end select-none"
                                                    v-if="actions?.length"
                                                >
                                                    <p
                                                        class="text-gray-600 dark:text-gray-300"
                                                        v-if="actions?.length"
                                                    >
                                                        @lang('admin::app.components.datagrid.table.actions')
                                                    </p>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Datagrid Head Shimmer -->
                                        <template v-else>
                                            <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
                                        </template>
                                    </template>
                                    <template #body="{ columns, records, performAction, applied, actions, isLoading }">
                                        <template v-if="! isLoading">
                                            <div
                                                v-for="(record, index) in records"
                                                class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                                :style="'grid-template-columns: 0.2fr repeat(' + (actions.length ? columns.length + (selectedSwatchType=='color' || selectedSwatchType == 'image' ? 2 : 1 ) : (columns.length )) + ', 1fr)'"
                                                :draggable="isSortable"
                                                @dragstart="onDragStart(index)"
                                                @dragover.prevent
                                                @dragenter.prevent="onDragEnter(index)"
                                                @drop="onDrop(records)"
                                            >

                                                <i class="icon-drag text-2xl transition-all group-hover:text-gray-700 cursor-grab" :class="{ 'invisible': !isSortable }"></i>

                                                <div v-if="showSwatch && (selectedSwatchType == 'color' || selectedSwatchType == 'image')">
                                                    <!-- Swatch Image -->
                                                    <div v-if="selectedSwatchType == 'image'">
                                                        <div>
                                                                <img
                                                                    :src="record.swatch_value_url || '{{ unopim_asset('images/product-placeholders/front.svg') }}'"
                                                                    class="h-[50px] w-[50px] max-w-[50px] min-w-[50px] max-h-[50px] min-h-[50px] rounded-lg border border-gray-300 shadow-sm object-cover"
                                                                >
                                                        </div>
                                                    </div>
                                                    <!-- Swatch Color -->
                                                    <div v-if="selectedSwatchType == 'color'">
                                                        <div
                                                            class="h-[25px] w-[25px] rounded-md border border-gray-200 dark:border-gray-800"
                                                            :style="{ background: record.swatch_value }"
                                                        >
                                                        </div>
                                                    </div>
                                                </div>

                                                <p 
                                                    v-text="record.code"
                                                    class="text-nowrap overflow-hidden text-ellipsis break-words hover:text-wrap"
                                                >
                                                </p>

                                                <p
                                                    v-for="locale in locales"
                                                    v-text="record['name_' + locale.code]"
                                                    class="text-nowrap overflow-hidden text-ellipsis break-words hover:text-wrap"
                                                >
                                                </p>

                                                <!-- Actions -->
                                                <div class="flex justify-end">
                                                    <span
                                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                        :class="action.icon"
                                                        v-text="!action.icon ? action.title : ''"
                                                        v-for="action in record.actions"
                                                        :title="action.title ?? ''"
                                                        @click="checkAndPerformAction(record.id, action, performAction)"
                                                    >
                                                    </span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Datagrid Shimmer for body when loading data  -->
                                        <template v-else>
                                            <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
                                        </template>
                                    </template>
                                </x-admin::datagrid>
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
                                        disabled
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
                                @php
                                    $selectedOption = old('type') ?: $attribute->type;
                                @endphp

                                @if($valuePerLocale != 0 && ($selectedOption == 'textarea' || $selectedOption == 'text'))
                                    <!-- AI Translate -->
                                    <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2  select-none">
                                        @php
                                            $valueTranslate = old('ai_translate') ?? $attribute->ai_translate;
                                        @endphp

                                        <x-admin::form.control-group.control
                                            type="checkbox"
                                            id="ai_translate"
                                            name="ai_translate"
                                            value="1"
                                            :checked="(boolean) $valueTranslate"
                                            for="ai_translate"
                                        />

                                        <label
                                            class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                            for="ai_translate"
                                        >
                                            @lang('admin::app.catalog.attributes.edit.ai-translate')
                                        </label>

                                        <x-admin::form.control-group.control
                                            type="hidden"
                                            name="ai_translate"
                                            :value="(boolean) $valueTranslate"
                                        />
                                    </x-admin::form.control-group>
                                @endif
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
                                        disabled
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

                                <!-- Filterable  -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none {{ $attribute->code === 'sku' ? 'opacity-70' : '' }}">
                                    @php
                                        $isFilterable = old('is_filterable') ?? $attribute->is_filterable;
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="is_filterable"
                                        value="0"
                                    />

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        name="is_filterable"
                                        id="is_filterable"
                                        for="is_filterable"
                                        value="1"
                                        :checked="(boolean) $isFilterable"
                                        :disabled="$attribute->code === 'sku'"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium {{ $attribute->code === 'sku' ? 'cursor-not-allowed' : 'cursor-pointer' }}"
                                        for="is_filterable"
                                    >
                                        @lang('admin::app.catalog.attributes.edit.is-filterable')
                                    </label>
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
                    @submit.prevent="handleSubmit($event, storeOption)"
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

                                <!-- Color Input -->
                                <x-admin::form.control-group
                                    class="w-2/6"
                                    v-if="selectedSwatchType == 'color'"
                                >
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.attributes.edit.color')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="color"
                                        name="swatch_value"
                                        :placeholder="trans('admin::app.catalog.attributes.edit.color')"
                                    />

                                    <x-admin::form.control-group.error control-name="swatch_value[]" />
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
                        showSwatch: {{ in_array($attribute->type, ['select', 'multiselect']) ? 'true' : 'false' }},

                        swatchType: "{{ $attribute->swatch_type == '' ? 'text' : $attribute->swatch_type }}",

                        selectedSwatchType: "{{ $attribute->swatch_type == '' ? 'text' : $attribute->swatch_type }}",

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

                        src: "{{ route('admin.catalog.attributes.options.edit', ['attribute_id' => $attribute->id, 'id' => '_ID']) }}",

                        optionCreateRoute: "{{ route('admin.catalog.attributes.options.store', ['attribute_id' => $attribute->id]) }}",
                        optionUpdateRoute: "{{ route('admin.catalog.attributes.options.update', ['attribute_id' => $attribute->id, 'id' => '_ID']) }}",
                        updateSortOrder: "{{ route('admin.catalog.attributes.options.update_sort', ['attribute_id' => $attribute->id]) }}",
                        dragFromIndex: null,
                        dragToIndex: null,
                    }
                },

                watch: {
                    validationType(value) {
                        this.selectedValidationType = this.parseValue(value)?.id;
                    },
                    swatchType(value) {
                        this.selectedSwatchType = this.parseValue(value)?.id;
                    }
                },

                computed: {
                    isSortable() {
                        return this.$refs.optionsDataGrid.applied.filters.columns.filter(
                            item => item.index === 'all' && item.value == ''
                        ).length > 0;
                    }
                },

                methods: {
                    storeOption(params, { resetForm, setValues }) {
                        const formData = new FormData();

                        for (const [localeCode, label] of Object.entries(params.locales)) {
                            formData.append(`locales[${localeCode}][label]`, label !== undefined && label !== null ? label : '');
                        }

                        for (const key in params) {
                            if (key !== 'locales' && key !== 'swatch_value') {
                                const value = params[key] !== undefined && params[key] !== null ? params[key] : '';
                                formData.append(key, value);
                            }
                        }

                        if (this.selectedSwatchType === 'image') {
                            const fileInput = this.$refs.editOptionsForm.querySelector('input[name="swatch_value[]"]');

                            if (fileInput && fileInput.files.length > 0) {
                                formData.append('swatch_value', fileInput.files[0]);
                            } else if (!this.swatchValue.image || this.swatchValue.image.length === 0) {
                                formData.append('swatch_value', '');
                            } else {
                                const relativePath = params.swatch_value;
                                formData.append('swatch_value', relativePath);
                            }
                        } else if (this.selectedSwatchType === 'color') {
                            formData.append('swatch_value', params.swatch_value ?? '');
                        }

                        let request;

                        if (params.id) {
                            formData.append('_method', 'PUT');
                            request = this.$axios.post(this.optionUpdateRoute.replace('_ID', params.id), formData);
                        } else {
                            request = this.$axios.post(this.optionCreateRoute, formData);
                        }

                        request.then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                                
                                this.$refs.optionsDataGrid.get();

                                this.$refs.addOptionsRow.toggle();

                                resetForm();
                            })
                            .catch(error => {
                                if (error.response.status === 422) {
                                    this.$refs.modelForm.setErrors(error.response.data.errors);
                                }
                            });
                    },

                    async checkAndPerformAction(id, action, performAction) {
                        if (action.index === 'edit') {
                            let option = await this.getAttributeOption(id);
                            this.editOptions(option);

                            return;
                        }

                        performAction(action);
                    },

                    editOptions(value) {
                        this.optionIsNew = false;

                        const cloned = JSON.parse(JSON.stringify(value));

                        this.swatchValue = {
                            image: cloned.swatch_value_url
                            ? [{ id: cloned.id, url: cloned.swatch_value_url }]
                            : [],
                        };

                        this.$refs.modelForm.setValues(cloned);

                        this.$refs.addOptionsRow.toggle();
                    },

                    getAttributeOption(id) {
                        return this.$axios.get(this.src.replace('_ID', id))
                            .then(response => {
                                    let option = response.data.option;

                                    this.optionsData.push(option);

                                    return option;
                                });
                    },

                    parseValue(value) {  
                        try {
                            return value ? JSON.parse(value) : null;
                        } catch (error) {
                            return value;
                        }
                    },

                    onDragStart(index) {
                        if (! this.isSortable) {
                            return;
                        }

                        this.dragFromIndex = index;
                    },

                    onDragEnter(index) {
                        if (! this.isSortable) {
                            return;
                        }

                        this.dragToIndex = index;
                    },

                    onDrop(records) {
                        if (! this.isSortable) {
                            return;
                        }

                        if (
                            this.dragFromIndex !== null &&
                            this.dragToIndex !== null &&
                            this.dragFromIndex !== this.dragToIndex
                        ) {
                            let recordsArray = JSON.parse(JSON.stringify(records));

                            this.$refs.optionsDataGrid.isLoading = true;

                            const fromIndex = recordsArray[this.dragFromIndex].id;

                            const toIndex = recordsArray[this.dragToIndex].id;

                            const movedItem = recordsArray.splice(this.dragFromIndex, 1)[0];

                            recordsArray.splice(this.dragToIndex, 0, movedItem);

                            let dataToSort = this.dragToIndex > this.dragFromIndex
                                ? recordsArray.slice(this.dragFromIndex, this.dragToIndex + 1)
                                : recordsArray.slice(this.dragToIndex, this.dragFromIndex + 1);

                            dataToSort = dataToSort.map(item => item.id);

                            this.$axios.put(this.updateSortOrder, {
                                    attributeId: {{ $attribute->id }},
                                    fromIndex: fromIndex,
                                    toIndex: toIndex,
                                    optionIds: dataToSort,
                                    direction: this.dragToIndex > this.dragFromIndex ? 'down' : 'up',
                                })
                                .then(response => {
                                    this.$refs.optionsDataGrid.isLoading = false;

                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: response.data.message,
                                    });
                                }).catch(error => {
                                    this.$refs.optionsDataGrid.isLoading = false;

                                    this.$emitter.emit('add-flash', {
                                        type: 'error',
                                        message: error.response.data.message,
                                    });
                                }).finally(() => this.$refs.optionsDataGrid.get());
                        }

                        this.dragFromIndex = null;
                        this.dragToIndex = null;
                    }
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
