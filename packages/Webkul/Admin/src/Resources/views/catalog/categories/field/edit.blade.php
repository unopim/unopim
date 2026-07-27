<x-admin::layouts.with-history>
    <x-slot:entityName>
        category_field
    </x-slot>

    <x-slot:title>
        @lang('admin::app.catalog.category_fields.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.catalog.category_fields.edit.title')"
            :back-url="route('admin.catalog.category_fields.index')"
            :back-label="trans('admin::app.catalog.category_fields.edit.back-btn')"
            form="category-field-edit-form"
            :sticky="false"
        />
    </x-slot>

    <!-- Edit category_fields Vue Components -->
    <v-edit-category-fields :locales="{{ $locales->toJson() }}"></v-edit-category-fields>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-category-fields-template"
        >

            {!! view_render_event('unopim.admin.catalog.category_fields.edit.before') !!}

            <x-admin::form
                id="category-field-edit-form"
                ajax
                :action="route('admin.catalog.category_fields.update', $categoryField->id)"
                enctype="multipart/form-data"
                method="PUT"
            >
                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5">
                    <div class="flex flex-col flex-1 gap-2 overflow-auto">

                        {!! view_render_event('unopim.admin.catalog.category_fields.edit.card.general.before', ['categoryField' => $categoryField]) !!}

                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.category_fields.edit.general')
                            </p>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.category_fields.edit.code')
                                </x-admin::form.control-group.label>

                                @php
                                    $selectedOption = old('code') ?: $categoryField->code;
                                @endphp

                                <x-admin::form.control-group.control
                                    type="text"
                                    class="cursor-not-allowed"
                                    name="code"
                                    rules="required"
                                    :value="$selectedOption"
                                    :disabled="(boolean) $selectedOption"
                                    readonly
                                    :label="trans('admin::app.catalog.category_fields.edit.code')"
                                    :placeholder="trans('admin::app.catalog.category_fields.edit.code')"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="code"
                                    :value="$selectedOption"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.category_fields.edit.type')
                                </x-admin::form.control-group.label>

                                
                                @php
                                    $supportedTypes = config('category_field_types');

                                    $fieldType = $categoryField->type;

                                    $selectedOption = json_encode([
                                        'id'    => $fieldType,
                                        'label' => trans($supportedTypes[$fieldType]['name'] ?? '')
                                    ]);

                                    $attributeTypes = [];

                                    foreach($supportedTypes as $key => $type) {
                                        $attributeTypes[] = [
                                            'id'    => $key,
                                            'label' => trans($type['name'])
                                        ];
                                    }

                                    $attributeTypesJson = json_encode($attributeTypes);
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="type"
                                    class="cursor-not-allowed"
                                    name="type"
                                    rules="required"
                                    v-model="categoryField.type"
                                    disabled
                                    :label="trans('admin::app.catalog.category_fields.edit.type')"
                                    :options="$attributeTypesJson"
                                    :value="$selectedOption"
                                    track-by="id"
                                    label-by="label"
                                >
                                    
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="type"
                                    :value="$categoryField->type"
                                />

                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>

                            @if($categoryField->type == 'textarea')
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.edit.enable-wysiwyg')
                                    </x-admin::form.control-group.label>

                                    <input
                                        type="hidden"
                                        name="enable_wysiwyg"
                                        value="0"
                                    />

                                    @php $selectedOption = old('enable_wysiwyg') ?: $categoryField->enable_wysiwyg @endphp

                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="enable_wysiwyg"
                                        value="1"
                                        :label="trans('admin::app.catalog.category_fields.edit.enable-wysiwyg')"
                                        :checked="(bool) $selectedOption"
                                    />
                                </x-admin::form.control-group>
                            @endif
                        </div>

                        {!! view_render_event('unopim.admin.catalog.category_fields.edit.card.general.after', ['categoryField' => $categoryField]) !!}

                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.edit.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label
                                            class="w-full"
                                            localizable="true"
                                            :current-locale-code="$locale->code"
                                        >
                                            @lang('admin::app.catalog.category_fields.edit.label')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            :name="$locale->code . '[name]'"
                                            :value="old($locale->code)['name'] ?? ($categoryField->translate($locale->code)->name ?? '')"
                                        />

                                        <x-admin::form.control-group.error :control-name="$locale->code . '[name]'" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>

                        <div
                            data-dirty-section
                            class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded {{ in_array($categoryField->type, ['select', 'multiselect', 'checkbox', 'price']) ?: 'hidden' }}"
                            v-if="showSwatch"
                        >
                            {{-- Stable name the unsaved-changes tracker can watch for option changes. --}}
                            <input
                                type="hidden"
                                name="_options_dirty"
                                ref="optionsDirtyMarker"
                                :value="optionsTick"
                            />

                            <div class="flex justify-between items-center mb-3">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.catalog.category_fields.edit.options')
                                </p>

                                <div
                                    class="secondary-button text-sm"
                                    @click="$refs.addOptionsRow.toggle();swatchValue='';optionIsNew=true;"
                                >
                                    @lang('admin::app.catalog.category_fields.edit.add-row')
                                </div>
                            </div>

                            <div class="mt-4 overflow-x-auto">

                                <template v-if="optionsData?.length">
                                    @if (
                                        $categoryField->type == 'select'
                                        || $categoryField->type == 'multiselect'
                                        || $categoryField->type == 'checkbox'
                                        || $categoryField->type == 'price'
                                    )
                                        <x-admin::table>
                                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                                <x-admin::table.thead.tr>
                                                    <x-admin::table.th class="!p-0"></x-admin::table.th>
    
                                                    <x-admin::table.th v-if="showSwatch && (swatchType == 'color' || swatchType == 'image')">
                                                        @lang('admin::app.catalog.category_fields.edit.swatch')
                                                    </x-admin::table.th>
    
                                                    <x-admin::table.th>
                                                        @lang('admin::app.catalog.category_fields.edit.code')
                                                    </x-admin::table.th>
    
                                                    @foreach ($locales as $locale)
                                                        <x-admin::table.th>
                                                            {{ $locale->name }}
                                                        </x-admin::table.th>
                                                    @endforeach
    
                                                    <x-admin::table.th></x-admin::table.th>
                                                </x-admin::table.thead.tr>
                                            </x-admin::table.thead>
    
                                            <draggable
                                                tag="tbody"
                                                ghost-class="draggable-ghost"
                                                handle=".icon-drag"
                                                v-bind="{animation: 200}"
                                                :list="optionsData"
                                                item-key="id"
                                                @change="signalOptionsChanged"
                                            >
                                                <template #item="{ element, index }">
                                                    <x-admin::table.thead.tr
                                                        class="hover:bg-primary-50 dark:hover:bg-cherry-800"
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
    
                                                        <x-admin::table.td class="!px-0 text-center">
                                                            <i class="icon-drag text-2xl transition-all group-hover:text-gray-700 cursor-grab"></i>
    
                                                            <input
                                                                type="hidden"
                                                                :name="'options[' + element.id + '][sort_order]'"
                                                                :value="index"
                                                            />
                                                        </x-admin::table.td>
    
                                                        <x-admin::table.td>
                                                            <p
                                                                class="dark:text-white"
                                                                v-text="element.code"
                                                            >
                                                            </p>
    
                                                            <input
                                                                type="hidden"
                                                                :name="'options[' + element.id + '][code]'"
                                                                v-model="element.code"
                                                            />
                                                        </x-admin::table.td>
    
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
    
                                                        <x-admin::table.td class="!px-0">
                                                            <span
                                                                class="icon-edit p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                                @click="editOptions(element)"
                                                            >
                                                            </span>
    
                                                            <span
                                                                class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-primary-50 dark:hover:bg-gray-800  max-sm:place-self-center"
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

                                <template v-else>
                                    <div class="grid gap-3.5 justify-items-center py-10 px-2.5">
                                        <img
                                            class="w-[120px] h-[120px] dark:invert dark:mix-blend-exclusion"
                                            src="{{ unopim_asset('images/icon-add-product.svg') }}"
                                            alt="{{ trans('admin::app.catalog.category_fields.edit.add-field-options') }}"
                                        >

                                        <div class="flex flex-col gap-1.5 items-center">
                                            <p class="text-base text-gray-400 font-semibold">
                                                @lang('admin::app.catalog.category_fields.edit.add-field-options')
                                            </p>

                                            <p class="text-gray-400">
                                                @lang('admin::app.catalog.category_fields.edit.add-options-info')
                                            </p>
                                        </div>

                                        <div
                                            class="secondary-button text-sm"
                                            @click="$refs.addOptionsRow.toggle();optionIsNew=true"
                                        >
                                            @lang('admin::app.catalog.category_fields.edit.add-row')
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        {!! view_render_event('unopim.admin.catalog.category_fields.edit.card.accordian.validations.before', ['categoryField' => $categoryField]) !!}
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.edit.validations')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                @if($categoryField->type == 'text')
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.catalog.category_fields.edit.input-validation')
                                        </x-admin::form.control-group.label>

                                        @php

                                            $supportedOptions = ['number', 'email', 'decimal', 'url', 'regex'];

                                            $options = [];

                                            foreach($supportedOptions as $type) {
                                                $options[] = [
                                                    'id'    => $type,
                                                    'label' => trans('admin::app.catalog.category_fields.edit.'. $type)
                                                ];
                                            }

                                            $optionsJson = json_encode($options);

                                        @endphp
                                        
                                        <x-admin::form.control-group.control
                                            type="select"
                                            class="cursor-pointer"
                                            name="validation"
                                            :value="$categoryField->validation"
                                            v-model="validationType"
                                            :options="$optionsJson"
                                            track-by="id"
                                            label-by="label"
                                            track-by="id"
                                            label-by="label"
                                        >
                                            
                                        </x-admin::form.control-group.control>
                                    </x-admin::form.control-group>
                                @endif

                                <x-admin::form.control-group v-if="'regex' == selectedValidationType">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.edit.regex')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="regex_pattern"
                                        rules="required"
                                        :value="$categoryField->regex_pattern"
                                    />

                                    <x-admin::form.control-group.error control-name="regex_pattern" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    @php
                                        $selectedOption = old('is_required') ?? $categoryField->is_required
                                    @endphp

                                    <input type="hidden" name="is_required" value="0">

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
                                        @lang('admin::app.catalog.category_fields.edit.is-required')
                                    </label>
                                </x-admin::form.control-group>

                                {{-- Uniqueness only means something for a single typed value. --}}
                                @if (in_array($categoryField->type, ['text', 'date', 'datetime']))
                                    <x-admin::form.control-group class="flex gap-2.5 items-center !mb-0 select-none">
                                        <input type="hidden" name="is_unique" value="0">

                                        <x-admin::form.control-group.control
                                            type="checkbox"
                                            id="is_unique"
                                            name="is_unique"
                                            value="1"
                                            for="is_unique"
                                            :checked="(boolean) (old('is_unique') ?? $categoryField->is_unique)"
                                        />

                                        <label
                                            class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                            for="is_unique"
                                        >
                                            @lang('admin::app.catalog.category_fields.edit.is-unique')
                                        </label>
                                    </x-admin::form.control-group>
                                @endif
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.catalog.category_fields.edit.card.accordian.validations.after', ['categoryField' => $categoryField]) !!}

                        {!! view_render_event('unopim.admin.catalog.category_fields.edit.card.accordian.configuration.before', ['categoryField' => $categoryField]) !!}

                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.edit.configuration')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2 select-none">
                                    @php
                                        $valuePerLocale = old('value_per_locale') ?? $categoryField->value_per_locale;
                                    @endphp

                                    <input type="hidden" name="value_per_locale" value="0">

                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="value_per_locale"
                                        name="value_per_locale"
                                        value="1"
                                        for="value_per_locale"
                                        :checked="(boolean) $valuePerLocale"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="value_per_locale"
                                    >
                                        @lang('admin::app.catalog.category_fields.edit.value-per-locale')
                                    </label>
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>


                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.category_fields.edit.settings')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.edit.status')
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
                                        :checked="(boolean) $categoryField->status"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.edit.position')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="position"
                                        rules="required|numeric|min_value:0"
                                        :value="old('position') ?? $categoryField->position"
                                    />

                                    <x-admin::form.control-group.error control-name="position" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.edit.set-section')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $supportedTypes = ['left', 'right'];

                                        $options = [];

                                        foreach($supportedTypes as $type) {
                                            $options[] = [
                                                'id'    => $type,
                                                'label' => trans('admin::app.catalog.category_fields.create.set-section-' . $type)
                                            ];
                                        }

                                        $optionsInJson = json_encode($options);

                                        $selectedOption = json_encode(['id' => $categoryField->section, 'label' => trans('admin::app.catalog.category_fields.create.set-section-'.$categoryField->section)]);
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="section"
                                        name="section"
                                        v-model="categoryField.section"
                                        :value="$selectedOption"
                                        :options="$optionsInJson"
                                        track-by="id"
                                        label-by="label"
                                    >

                                    </x-admin::form.control-group.control>
                                    
                                    <x-admin::form.control-group.error  control-name="section" />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.catalog.category_fields.edit.card.accordian.configuration.configuration.after', ['categoryField' => $categoryField]) !!}
                    </div>
                </div>
            </x-admin::form>

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
                        @toggle="listenModel"
                        ref="addOptionsRow"
                    >
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.category_fields.edit.add-option')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <div class="grid">
                                <x-admin::form.control-group
                                    class="w-full"
                                    v-if="swatchType == 'image'"
                                >
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.catalog.category_fields.edit.image')
                                    </x-admin::form.control-group.label>

                                    <div class="hidden">
                                        <x-admin::media.image
                                            name="swatch_value[]"
                                            ::uploaded-images='swatchValue.image'
                                        />
                                    </div>

                                    <v-media-image
                                        name="swatch_value"
                                        :uploaded-images='swatchValue.image'
                                    >
                                    </v-media-image>

                                    <x-admin::form.control-group.error control-name="swatch_value" />
                                </x-admin::form.control-group>
 
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="isNew"
                                    ::value="optionIsNew"
                                />

                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.edit.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        :label="trans('admin::app.catalog.category_fields.edit.code')"
                                        :placeholder="trans('admin::app.catalog.category_fields.edit.code')"
                                        ::disabled="true !== optionIsNew"
                                        ref="inputAdmin"
                                        v-code
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group class="w-full mb-2.5">
                                        <x-admin::form.control-group.label
                                            class="w-full"
                                            localizable="true"
                                            :current-locale-code="$locale->code"
                                        >
                                            @lang('admin::app.catalog.category_fields.edit.label')
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

                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.catalog.category_fields.edit.option.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.category_fields.edit.after') !!}

        </script>

        <script type="module">
            app.component('v-edit-category-fields', {
                // Passed as markup, not '#id': Vue caches compiled templates by selector,
                // so ajax-navigating between fields would render the previous one's data.
                template: document.querySelector('#v-edit-category-fields-template').innerHTML,

                props: ['locales'],

                data: function() {
                    return {
                        showSwatch: {{ in_array($categoryField->type, ['select', 'checkbox', 'price', 'multiselect']) ? 'true' : 'false' }},

                        swatchType: "{{ $categoryField->swatch_type == '' ? 'dropdown' : $categoryField->swatch_type }}",

                        validationType: "{{ $categoryField->validation }}",

                        selectedValidationType: "{{ $categoryField->validation }}",

                        swatchValue: [
                            {
                                image: [],
                            }
                        ],

                        optionsData: [],

                        optionsTick: 0,

                        optionIsNew: true,

                        optionId: 0,

                        categoryField: @json($categoryField),

                        src: "{{ route('admin.catalog.category_fields.options', $categoryField->id) }}",
                    }
                },

                created: function () {
                    this.getCatgoryFieldOptions();
                },

                mounted() {
                    this.$emitter.on('unsaved-changes:reset', this.restoreOptions);
                },

                beforeUnmount() {
                    this.$emitter.off('unsaved-changes:reset', this.restoreOptions);
                },

                watch: {
                    validationType(value) {
                        this.selectedValidationType = this.parseValue(value)?.id;
                    }
                },

                methods: {
                    // The form sits in this component's template, so the root's handlers
                    // are out of scope and the browser would submit natively.
                    onAjaxSubmit(...args) {
                        return this.$root.onAjaxSubmit(...args);
                    },

                    onInvalidSubmit(...args) {
                        return this.$root.onInvalidSubmit(...args);
                    },

                    // Discard reverts native inputs only; option rows are Vue state.
                    restoreOptions() {
                        this.optionsTick = 0;

                        this.getCatgoryFieldOptions();
                    },

                    // Option rows are hidden inputs written by Vue, so no native event
                    // reaches the tracker and their names are absent from its snapshot.
                    signalOptionsChanged() {
                        this.optionsTick++;

                        this.$nextTick(() => {
                            // By ref: this component renders a fragment, so it has no root element.
                            const marker = this.$refs.optionsDirtyMarker;

                            if (! marker) {
                                return;
                            }

                            marker.dispatchEvent(new Event('input', { bubbles: true }));
                            marker.dispatchEvent(new Event('change', { bubbles: true }));
                            marker.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                                bubbles: true,
                                detail: { name: '_options_dirty' },
                            }));
                        });
                    },

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
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.category_fields.edit.same-code-error')" });

                                return;
                            }

                            this.optionsData.push(params);
                        }

                        this.signalOptionsChanged();

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

                            this.signalOptionsChanged();
                        }
                    },

                    listenModel(event) {
                        if (! event.isActive) {
                            this.isNullOptionChecked = false;
                        }
                    },

                    getCatgoryFieldOptions() {
                        this.$axios.get(`${this.src}`)
                            .then(response => {
                                let options = response.data;

                                // Rebuilt, not appended, so a discard reload cannot duplicate rows.
                                this.optionsData = [];

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

                                    if (! option.label) {
                                        this.isNullOptionChecked = true;
                                        this.idNullOption = option.id;
                                        row['notRequired'] = true;
                                    } else {
                                        row['notRequired'] = false;
                                    }

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
