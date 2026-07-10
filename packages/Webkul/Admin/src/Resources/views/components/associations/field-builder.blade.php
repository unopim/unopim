@props([
    'fields' => [],
])

@php
    use Webkul\Core\Repositories\LocaleRepository;
    use Webkul\Product\Contracts\AssociationTypeField as AssociationTypeFieldContract;

    /**
     * Locales are resolved here (rather than accepted as a prop) so that both the
     * create and edit blades can stay thin wrappers that only pass `:fields`.
     */
    $activeLocales = app(LocaleRepository::class)->getActiveLocales();

    $localesForJs = $activeLocales->map(fn ($locale) => [
        'code' => $locale->code,
        'name' => $locale->name,
    ])->values();

    $fieldTypeOptions = collect(config('association_field_types'))
        ->map(fn ($type, $key) => [
            'id'    => $key,
            'label' => trans($type['name']),
        ])
        ->values();

    $validationTypeCodes = ['number', 'email', 'decimal', 'url', 'regex'];

    $validationOptions = collect($validationTypeCodes)
        ->map(fn ($type) => [
            'id'    => $type,
            'label' => trans('admin::app.catalog.category_fields.create.'.$type),
        ])
        ->values();

    $sectionOptions = collect(['left', 'right'])
        ->map(fn ($section) => [
            'id'    => $section,
            'label' => trans('admin::app.catalog.category_fields.create.set-section-'.$section),
        ])
        ->values();

    /**
     * Normalizes either an Eloquent collection of `AssociationTypeField` models
     * (edit prefill) or a plain array coming back from `old('fields')` (a
     * validation-failure redisplay, keyed the same way the form submits it)
     * into one shared shape the Vue repeater below understands.
     */
    $normalizeOption = function ($option, $key) use ($activeLocales) {
        $optionLocales = [];

        foreach ($activeLocales as $locale) {
            $optionLocales[$locale->code] = $option instanceof \Webkul\Product\Contracts\AssociationTypeFieldOption
                ? (optional($option->translate($locale->code))->label ?? '')
                : ($option[$locale->code]['label'] ?? '');
        }

        if ($option instanceof \Webkul\Product\Contracts\AssociationTypeFieldOption) {
            return [
                'id'         => $option->id,
                'isNew'      => false,
                'isDelete'   => false,
                'code'       => $option->code,
                'sort_order' => $option->sort_order,
                'locales'    => $optionLocales,
            ];
        }

        $option = (array) $option;

        return [
            'id'         => is_int($key) ? 'option_'.$key : $key,
            'isNew'      => filter_var($option['isNew'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'isDelete'   => filter_var($option['isDelete'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'code'       => $option['code'] ?? '',
            'sort_order' => $option['sort_order'] ?? 0,
            'locales'    => $optionLocales,
        ];
    };

    $normalizeField = function ($field, $key) use ($activeLocales, $normalizeOption) {
        $fieldLocales = [];

        foreach ($activeLocales as $locale) {
            $fieldLocales[$locale->code] = $field instanceof AssociationTypeFieldContract
                ? (optional($field->translate($locale->code))->name ?? '')
                : ($field[$locale->code]['name'] ?? '');
        }

        if ($field instanceof AssociationTypeFieldContract) {
            $options = collect($field->options ?? [])
                ->map(fn ($option, $optionKey) => $normalizeOption($option, $optionKey))
                ->values()
                ->all();

            return [
                'id'               => $field->id,
                'isNew'            => false,
                'isDelete'         => false,
                'code'             => $field->code,
                'type'             => $field->type,
                'validation'       => $field->validation,
                'regex_pattern'    => $field->regex_pattern,
                'is_required'      => (bool) $field->is_required,
                'is_unique'        => (bool) $field->is_unique,
                'value_per_locale' => (bool) $field->value_per_locale,
                'section'          => $field->section ?: 'left',
                'status'           => (bool) $field->status,
                'locales'          => $fieldLocales,
                'options'          => $options,
            ];
        }

        $field = (array) $field;

        $options = collect($field['options'] ?? [])
            ->map(fn ($option, $optionKey) => $normalizeOption($option, $optionKey))
            ->values()
            ->all();

        return [
            'id'               => is_int($key) ? 'new_'.$key : $key,
            'isNew'            => filter_var($field['isNew'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'isDelete'         => filter_var($field['isDelete'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'code'             => $field['code'] ?? '',
            'type'             => $field['type'] ?? '',
            'validation'       => $field['validation'] ?? '',
            'regex_pattern'    => $field['regex_pattern'] ?? '',
            'is_required'      => filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_unique'        => filter_var($field['is_unique'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'value_per_locale' => filter_var($field['value_per_locale'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'section'          => $field['section'] ?? 'left',
            'status'           => filter_var($field['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'locales'          => $fieldLocales,
            'options'          => $options,
        ];
    };

    $normalizedFields = collect($fields ?? [])
        ->map(fn ($field, $key) => $normalizeField($field, $key))
        ->values()
        ->all();
@endphp

<v-association-field-builder
    :fields='@json($normalizedFields)'
    :locales='@json($localesForJs)'
    :field-type-options='@json($fieldTypeOptions)'
    :validation-options='@json($validationOptions)'
    :section-options='@json($sectionOptions)'
></v-association-field-builder>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-association-field-builder-template"
    >
        <div>
            {!! view_render_event('unopim.admin.catalog.association_types.fields.before') !!}

            <!-- Fields -->
            <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex flex-col gap-1">
                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.catalog.association_types.fields.title')
                        </p>

                        <p class="text-xs text-gray-500 dark:text-gray-300">
                            @lang('admin::app.catalog.association_types.fields.info')
                        </p>
                    </div>

                    <!-- Add Field Button -->
                    <div
                        class="secondary-button text-sm"
                        @click="openAddField"
                    >
                        @lang('admin::app.catalog.association_types.fields.add-field-btn')
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <template v-if="fields.length">
                        <x-admin::table>
                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                <x-admin::table.thead.tr>
                                    <x-admin::table.th class="!p-0" />

                                    <x-admin::table.th>
                                        @lang('admin::app.catalog.category_fields.create.code')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('admin::app.catalog.category_fields.create.type')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('admin::app.catalog.category_fields.create.set-section')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('admin::app.catalog.category_fields.create.status')
                                    </x-admin::table.th>

                                    <x-admin::table.th />
                                </x-admin::table.thead.tr>
                            </x-admin::table.thead>

                            <!-- Draggable Fields -->
                            <draggable
                                tag="tbody"
                                ghost-class="draggable-ghost"
                                handle=".icon-drag"
                                v-bind="{animation: 200}"
                                :list="fields"
                                item-key="id"
                            >
                                <template #item="{ element, index }">
                                    <x-admin::table.thead.tr
                                        class="hover:bg-violet-50 dark:hover:bg-cherry-800"
                                        v-show="! element.isDelete"
                                    >
                                        <input type="hidden" :name="'fields[' + element.id + '][isNew]'" :value="element.isNew">
                                        <input type="hidden" :name="'fields[' + element.id + '][isDelete]'" :value="element.isDelete">
                                        <input type="hidden" :name="'fields[' + element.id + '][code]'" :value="element.code">
                                        <input type="hidden" :name="'fields[' + element.id + '][type]'" :value="element.type">
                                        <input type="hidden" :name="'fields[' + element.id + '][validation]'" :value="element.validation">
                                        <input type="hidden" :name="'fields[' + element.id + '][regex_pattern]'" :value="element.regex_pattern">
                                        <input type="hidden" :name="'fields[' + element.id + '][is_required]'" :value="element.is_required ? 1 : 0">
                                        <input type="hidden" :name="'fields[' + element.id + '][is_unique]'" :value="element.is_unique ? 1 : 0">
                                        <input type="hidden" :name="'fields[' + element.id + '][value_per_locale]'" :value="element.value_per_locale ? 1 : 0">
                                        <input type="hidden" :name="'fields[' + element.id + '][section]'" :value="element.section">
                                        <input type="hidden" :name="'fields[' + element.id + '][status]'" :value="element.status ? 1 : 0">
                                        <input type="hidden" :name="'fields[' + element.id + '][position]'" :value="index">

                                        <template v-for="locale in locales" :key="'field-locale-' + element.id + '-' + locale.code">
                                            <input
                                                type="hidden"
                                                :name="'fields[' + element.id + '][' + locale.code + '][name]'"
                                                :value="element.locales[locale.code]"
                                            >
                                        </template>

                                        <template v-for="(option, optionIndex) in element.options" :key="'field-option-' + element.id + '-' + option.id">
                                            <input type="hidden" :name="'fields[' + element.id + '][options][' + option.id + '][isNew]'" :value="option.isNew">
                                            <input type="hidden" :name="'fields[' + element.id + '][options][' + option.id + '][isDelete]'" :value="option.isDelete">
                                            <input type="hidden" :name="'fields[' + element.id + '][options][' + option.id + '][code]'" :value="option.code">
                                            <input type="hidden" :name="'fields[' + element.id + '][options][' + option.id + '][sort_order]'" :value="optionIndex">

                                            <template v-for="locale in locales" :key="'field-option-locale-' + element.id + '-' + option.id + '-' + locale.code">
                                                <input
                                                    type="hidden"
                                                    :name="'fields[' + element.id + '][options][' + option.id + '][' + locale.code + '][label]'"
                                                    :value="option.locales[locale.code]"
                                                >
                                            </template>
                                        </template>

                                        <!-- Draggable Icon -->
                                        <x-admin::table.td class="!px-0 text-center">
                                            <i class="icon-drag text-2xl transition-all group-hover:text-gray-700 cursor-grab"></i>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="element.code"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="fieldTypeLabel(element.type)"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="sectionLabel(element.section)"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <span v-if="element.status" class="label-active">@lang('admin::app.catalog.association_types.index.datagrid.activated')</span>
                                            <span v-else class="label-info">@lang('admin::app.catalog.association_types.index.datagrid.disabled')</span>
                                        </x-admin::table.td>

                                        <!-- Actions -->
                                        <x-admin::table.td class="!px-0">
                                            <span
                                                class="icon-edit p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                @click="openEditField(element)"
                                            >
                                            </span>

                                            <span
                                                class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                @click="removeField(element)"
                                            >
                                            </span>
                                        </x-admin::table.td>
                                    </x-admin::table.thead.tr>
                                </template>
                            </draggable>
                        </x-admin::table>
                    </template>

                    <!-- Empty State -->
                    <template v-else>
                        <div class="grid gap-3.5 justify-items-center py-10 px-2.5">
                            <img
                                class="w-[120px] h-[120px] dark:invert dark:mix-blend-exclusion"
                                src="{{ unopim_asset('images/icon-add-product.svg') }}"
                                alt="@lang('admin::app.catalog.association_types.fields.add-field-btn')"
                            />

                            <div class="flex flex-col gap-1.5 items-center">
                                <p class="text-base text-gray-400 font-semibold">
                                    @lang('admin::app.catalog.association_types.fields.add-field-btn')
                                </p>

                                <p class="text-gray-400">
                                    @lang('admin::app.catalog.association_types.fields.add-fields-info')
                                </p>
                            </div>

                            <div
                                class="secondary-button text-sm"
                                @click="openAddField"
                            >
                                @lang('admin::app.catalog.association_types.fields.add-field-btn')
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {!! view_render_event('unopim.admin.catalog.association_types.fields.after') !!}

            <!-- Add / Edit Field Modal -->
            <x-admin::form
                v-slot="{ handleSubmit }"
                as="div"
                ref="fieldForm"
            >
                <form
                    @submit.prevent="handleSubmit($event, saveField)"
                    ref="fieldFormElement"
                >
                    <x-admin::modal
                        @toggle="listenFieldModal"
                        ref="fieldModal"
                    >
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.association_types.fields.modal-title')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Field Code -->
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
                                        v-code
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Field Type -->
                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.category_fields.create.type')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        class="cursor-pointer"
                                        name="type"
                                        rules="required"
                                        v-model="fieldType"
                                        :label="trans('admin::app.catalog.category_fields.create.type')"
                                        ::options="fieldTypeOptions"
                                        track-by="id"
                                        label-by="label"
                                    />

                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>
                            </div>

                            <!-- Locales Inputs -->
                            <p class="mb-2.5 text-sm text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.category_fields.create.label')
                            </p>

                            <div class="grid grid-cols-2 gap-4 mb-2.5">
                                <template v-for="locale in locales" :key="'field-modal-locale-' + locale.code">
                                    <x-admin::form.control-group class="w-full mb-2.5">
                                        <x-admin::form.control-group.label v-text="locale.name" />

                                        <x-admin::form.control-group.control
                                            type="text"
                                            ::name="locale.code"
                                        />
                                    </x-admin::form.control-group>
                                </template>
                            </div>

                            <!-- Input Validation -->
                            <x-admin::form.control-group v-if="selectedFieldType == 'text'">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.catalog.category_fields.create.input-validation')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    class="cursor-pointer"
                                    name="validation"
                                    v-model="validationType"
                                    :label="trans('admin::app.catalog.category_fields.create.input-validation')"
                                    ::options="validationOptions"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="validation" />
                            </x-admin::form.control-group>

                            <!-- Regex -->
                            <x-admin::form.control-group v-show="'regex' == selectedValidationType">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.category_fields.create.regex')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="regex_pattern"
                                    :placeholder="trans('admin::app.catalog.category_fields.create.regex')"
                                />

                                <x-admin::form.control-group.error control-name="regex_pattern" />
                            </x-admin::form.control-group>

                            <div class="flex flex-wrap gap-x-6 gap-y-2 mb-2.5">
                                <!-- Is Required -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-0 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="field_is_required"
                                        name="is_required"
                                        value="1"
                                        for="field_is_required"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="field_is_required"
                                    >
                                        @lang('admin::app.catalog.category_fields.edit.is-required')
                                    </label>
                                </x-admin::form.control-group>

                                <!-- Is Unique -->
                                <x-admin::form.control-group
                                    class="flex gap-2.5 items-center !mb-0 select-none"
                                    v-if="['text', 'date', 'datetime'].includes(selectedFieldType)"
                                >
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="field_is_unique"
                                        name="is_unique"
                                        value="1"
                                        for="field_is_unique"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="field_is_unique"
                                    >
                                        @lang('admin::app.catalog.category_fields.edit.is-unique')
                                    </label>
                                </x-admin::form.control-group>

                                <!-- Value Per Locale -->
                                <x-admin::form.control-group class="flex gap-2.5 items-center !mb-0 select-none">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        id="field_value_per_locale"
                                        name="value_per_locale"
                                        value="1"
                                        for="field_value_per_locale"
                                    />

                                    <label
                                        class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                        for="field_value_per_locale"
                                    >
                                        @lang('admin::app.catalog.category_fields.create.value-per-locale')
                                    </label>
                                </x-admin::form.control-group>
                            </div>

                            <!-- Display Section -->
                            <x-admin::form.control-group class="w-full mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.category_fields.create.set-section')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="section"
                                    rules="required"
                                    ::options="sectionOptions"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="section" />
                            </x-admin::form.control-group>

                            <!-- Options -->
                            <div v-if="['select', 'multiselect', 'checkbox'].includes(selectedFieldType)">
                                <div class="flex justify-between items-center mb-3">
                                    <p class="text-sm text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.catalog.category_fields.create.options')
                                    </p>

                                    <div
                                        class="secondary-button text-sm"
                                        @click="addOptionRow"
                                    >
                                        @lang('admin::app.catalog.category_fields.create.add-row')
                                    </div>
                                </div>

                                <div
                                    class="overflow-x-auto"
                                    v-if="draftOptions.filter(option => ! option.isDelete).length"
                                >
                                    <x-admin::table>
                                        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                            <x-admin::table.thead.tr>
                                                <x-admin::table.th>
                                                    @lang('admin::app.catalog.category_fields.create.code')
                                                </x-admin::table.th>

                                                <template v-for="locale in locales" :key="'option-head-' + locale.code">
                                                    <x-admin::table.th v-text="locale.name" />
                                                </template>

                                                <x-admin::table.th />
                                            </x-admin::table.thead.tr>
                                        </x-admin::table.thead>

                                        <draggable
                                            tag="tbody"
                                            ghost-class="draggable-ghost"
                                            handle=".icon-drag"
                                            v-bind="{animation: 200}"
                                            :list="draftOptions"
                                            item-key="id"
                                        >
                                            <template #item="{ element, index }">
                                                <x-admin::table.thead.tr
                                                    class="hover:bg-violet-50 dark:hover:bg-cherry-800"
                                                    v-show="! element.isDelete"
                                                >
                                                    <x-admin::table.td>
                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            ::name="'draft_option_code_' + index"
                                                            v-model="element.code"
                                                        />
                                                    </x-admin::table.td>

                                                    <x-admin::table.td v-for="locale in locales" ::key="'option-cell-' + element.id + '-' + locale.code">
                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            ::name="'draft_option_label_' + locale.code + '_' + index"
                                                            v-model="element.locales[locale.code]"
                                                        />
                                                    </x-admin::table.td>

                                                    <x-admin::table.td class="!px-0">
                                                        <span
                                                            class="icon-drag text-2xl transition-all group-hover:text-gray-700 cursor-grab"
                                                        >
                                                        </span>

                                                        <span
                                                            class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                            @click="removeOptionRow(element)"
                                                        >
                                                        </span>
                                                    </x-admin::table.td>
                                                </x-admin::table.thead.tr>
                                            </template>
                                        </draggable>
                                    </x-admin::table>
                                </div>
                            </div>
                        </x-slot>

                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.catalog.association_types.fields.save-field-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-association-field-builder', {
            template: '#v-association-field-builder-template',

            props: {
                fields: {
                    type: Array,
                    default: () => [],
                },
                locales: {
                    type: Array,
                    default: () => [],
                },
                fieldTypeOptions: {
                    type: Array,
                    default: () => [],
                },
                validationOptions: {
                    type: Array,
                    default: () => [],
                },
                sectionOptions: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    fields: JSON.parse(JSON.stringify(this.fields ?? [])),

                    draftOptions: [],

                    fieldSeq: 0,

                    optionSeq: 0,

                    isFieldNew: true,

                    editingFieldId: null,

                    fieldType: '',

                    selectedFieldType: '',

                    validationType: '',

                    selectedValidationType: '',
                }
            },

            watch: {
                fieldType(value) {
                    this.selectedFieldType = this.parseValue(value)?.id ?? '';
                },

                validationType(value) {
                    this.selectedValidationType = this.parseValue(value)?.id ?? '';
                },
            },

            methods: {
                blankLocales() {
                    let locales = {};

                    this.locales.forEach((locale) => {
                        locales[locale.code] = '';
                    });

                    return locales;
                },

                fieldTypeLabel(type) {
                    return this.fieldTypeOptions.find(option => option.id === type)?.label ?? type;
                },

                sectionLabel(section) {
                    return this.sectionOptions.find(option => option.id === section)?.label ?? section;
                },

                openAddField() {
                    this.isFieldNew = true;
                    this.editingFieldId = null;
                    this.fieldType = '';
                    this.validationType = '';
                    this.draftOptions = [];

                    this.$nextTick(() => {
                        this.$refs.fieldForm.setValues({
                            code: '',
                            section: JSON.stringify(this.sectionOptions[0] ?? {}),
                        });

                        this.$refs.fieldModal.toggle();
                    });
                },

                openEditField(element) {
                    this.isFieldNew = false;
                    this.editingFieldId = element.id;

                    let typeOption = this.fieldTypeOptions.find(option => option.id === element.type) ?? null;
                    let validationOption = this.validationOptions.find(option => option.id === element.validation) ?? null;
                    let sectionOption = this.sectionOptions.find(option => option.id === element.section) ?? null;

                    this.fieldType = typeOption ? JSON.stringify(typeOption) : '';
                    this.validationType = validationOption ? JSON.stringify(validationOption) : '';
                    this.draftOptions = JSON.parse(JSON.stringify(element.options ?? []));

                    let values = {
                        code: element.code,
                        regex_pattern: element.regex_pattern,
                        is_required: element.is_required,
                        is_unique: element.is_unique,
                        value_per_locale: element.value_per_locale,
                        section: sectionOption ? JSON.stringify(sectionOption) : '',
                    };

                    this.locales.forEach((locale) => {
                        values[locale.code] = element.locales[locale.code] ?? '';
                    });

                    this.$nextTick(() => {
                        this.$refs.fieldForm.setValues(values);

                        this.$refs.fieldModal.toggle();
                    });
                },

                addOptionRow() {
                    this.draftOptions.push({
                        id: 'option_' + this.optionSeq++,
                        isNew: true,
                        isDelete: false,
                        code: '',
                        sort_order: this.draftOptions.length,
                        locales: this.blankLocales(),
                    });
                },

                removeOptionRow(option) {
                    let foundIndex = this.draftOptions.findIndex(item => item.id === option.id);

                    if (foundIndex === -1) {
                        return;
                    }

                    if (this.draftOptions[foundIndex].isNew) {
                        this.draftOptions.splice(foundIndex, 1);
                    } else {
                        this.draftOptions[foundIndex].isDelete = true;
                    }
                },

                saveField(params, { setErrors }) {
                    let isDuplicateCode = this.fields.some((field) => {
                        return ! field.isDelete
                            && field.id !== this.editingFieldId
                            && field.code === params.code;
                    });

                    if (isDuplicateCode) {
                        setErrors({
                            code: "@lang('admin::app.catalog.association_types.fields.same-code-error')",
                        });

                        return;
                    }

                    let locales = {};

                    this.locales.forEach((locale) => {
                        locales[locale.code] = params[locale.code] ?? '';
                    });

                    let sectionValue = this.parseValue(params.section)?.id ?? 'left';

                    if (this.isFieldNew) {
                        this.fields.push({
                            id: 'new_' + this.fieldSeq++,
                            isNew: true,
                            isDelete: false,
                            code: params.code,
                            type: this.selectedFieldType,
                            validation: this.selectedValidationType,
                            regex_pattern: params.regex_pattern ?? '',
                            is_required: !! params.is_required,
                            is_unique: !! params.is_unique,
                            value_per_locale: !! params.value_per_locale,
                            section: sectionValue,
                            status: true,
                            locales: locales,
                            options: this.draftOptions,
                        });
                    } else {
                        let foundIndex = this.fields.findIndex(item => item.id === this.editingFieldId);

                        if (foundIndex !== -1) {
                            this.fields.splice(foundIndex, 1, {
                                ...this.fields[foundIndex],
                                validation: this.selectedFieldType == 'text' ? this.selectedValidationType : '',
                                regex_pattern: params.regex_pattern ?? '',
                                is_required: !! params.is_required,
                                is_unique: !! params.is_unique,
                                value_per_locale: !! params.value_per_locale,
                                section: sectionValue,
                                locales: locales,
                                options: this.draftOptions,
                            });
                        }
                    }

                    this.$refs.fieldModal.toggle();
                },

                removeField(element) {
                    let foundIndex = this.fields.findIndex(item => item.id === element.id);

                    if (foundIndex === -1) {
                        return;
                    }

                    if (this.fields[foundIndex].isNew) {
                        this.fields.splice(foundIndex, 1);
                    } else {
                        this.fields[foundIndex].isDelete = true;
                    }
                },

                listenFieldModal(event) {
                    if (! event.isActive) {
                        this.draftOptions = [];
                    }
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
