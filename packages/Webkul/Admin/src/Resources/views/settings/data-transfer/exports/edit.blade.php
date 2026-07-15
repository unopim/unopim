<x-admin::layouts.with-history>
    <x-slot:entityName>
        job_instance
    </x-slot>

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.settings.data-transfer.exports.edit.title')"
            :back-url="route('admin.settings.data_transfer.exports.index')"
            :back-label="trans('admin::app.settings.data-transfer.exports.edit.back-btn')"
            form="export-profile-edit-form"
            :sticky="false"
        />
    </x-slot>

    {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.before') !!}

    <v-export-profile-edit></v-export-profile-edit>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-export-profile-edit-template">
            <x-admin::form
                id="export-profile-edit-form"
                ajax
                :action="route('admin.settings.data_transfer.exports.update', $export->id)"
                method="PUT"
                enctype="multipart/form-data"
            >
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.edit_form_controls.before') !!}

                @php
                    $exportFilters = $export->filters ?? [];

                    $fieldNames = collect($exporterConfig[$export->entity_type]['filters']['fields'] ?? [])->pluck('name');
                    $scopeFields = $fieldNames->intersect(['channels', 'locales', 'currencies', 'attributes']);
                    $productFilterFields = $fieldNames->intersect(['attribute_families', 'categories', 'completeness', 'time_condition', 'status', 'sku']);
                    $supportsConditions = $fieldNames->contains('attributes');
                    $supportsCategories = $fieldNames->contains('categories');

                    $savedCustomAttributes = $exportFilters['custom_attributes'] ?? [];
                    $savedCustomAttributes = is_string($savedCustomAttributes)
                        ? (json_decode($savedCustomAttributes, true) ?? [])
                        : $savedCustomAttributes;
                @endphp

                <!-- Body Content -->
                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <!-- Left Container -->
                    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.general.before') !!}

                        <!-- General -->
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.edit.general')
                            </p>

                            <div class="grid grid-cols-2 max-sm:grid-cols-1 gap-x-5">
                                <!-- Code -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.exports.create.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        :disabled="(boolean) $export->code"
                                        :value="old('code') ?? $export->code"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.exports.create.code')"
                                        :placeholder="trans('admin::app.settings.data-transfer.exports.create.code')"
                                    />

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="code"
                                        :value="old('code') ?? $export->code"
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.exports.edit.type')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $options = [];
                                        foreach (config('exporters') as $index => $exporter) {
                                            $options[] = ['id' => $index, 'label' => trans($exporter['title'])];
                                        }
                                        $optionsJson = json_encode($options);
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="entity_type"
                                        id="export-type"
                                        :disabled="(boolean) $export->entity_type"
                                        :value="old('entity_type') ?? $export->entity_type"
                                        ref="exportType"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.exports.edit.type')"
                                        :options="$optionsJson"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.general.after') !!}

                        @if ($scopeFields->isNotEmpty())
                            {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.scope.before') !!}

                            <!-- Data to export -->
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    @lang('admin::app.settings.data-transfer.exports.create.scope-filters')
                                </p>

                                <x-admin::data-transfer.filter-fields
                                    :entity-type="$export->entity_type"
                                    :values="$exportFilters"
                                    :exporter-config="json_encode($exporterConfig)"
                                    only="channels,locales,currencies,attributes"
                                    grid-class="grid grid-cols-1"
                                />
                            </div>

                            {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.scope.after') !!}
                        @endif

                        @if ($productFilterFields->isNotEmpty())
                            <!-- Product filters -->
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    @lang('admin::app.settings.data-transfer.exports.create.product-filters')
                                </p>

                                <x-admin::data-transfer.filter-fields
                                    :entity-type="$export->entity_type"
                                    :values="$exportFilters"
                                    :exporter-config="json_encode($exporterConfig)"
                                    only="attribute_families,status"
                                    grid-class="grid grid-cols-2 max-sm:grid-cols-1 gap-x-5"
                                />

                                <x-admin::data-transfer.filter-fields
                                    :entity-type="$export->entity_type"
                                    :values="$exportFilters"
                                    :exporter-config="json_encode($exporterConfig)"
                                    only="completeness,time_condition,time_value,time_date,time_date_end"
                                    grid-class="grid grid-cols-2 max-sm:grid-cols-1 gap-x-5"
                                />

                                @if ($supportsCategories)
                                    <!-- Category (tree) -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('data_transfer::app.exporters.products.filters.categories')
                                        </x-admin::form.control-group.label>

                                        <x-admin::data-transfer.category-tree
                                            :value="$exportFilters['categories'] ?? []"
                                        />
                                    </x-admin::form.control-group>
                                @endif

                                <x-admin::data-transfer.filter-fields
                                    :entity-type="$export->entity_type"
                                    :values="$exportFilters"
                                    :exporter-config="json_encode($exporterConfig)"
                                    only="sku"
                                    grid-class="grid grid-cols-1"
                                />

                            </div>
                        @endif

                        @if ($supportsConditions)
                            <!-- Attribute Conditions -->
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    @lang('admin::app.settings.data-transfer.exports.create.attribute-conditions')
                                </p>

                                <x-admin::data-transfer.attribute-conditions
                                    :values="$savedCustomAttributes"
                                    :attribute-route="route('admin.settings.data_transfer.exports.filters.attributes')"
                                    :exclude-attributes="[\Webkul\DataTransfer\Enums\ProductFilter::SKU->value]"
                                    :operators="\Webkul\DataTransfer\Helpers\Sources\Export\Filters\AttributeConditionOperators::frontendMap()"
                                >
                                </x-admin::data-transfer.attribute-conditions>
                            </div>
                        @endif
                    </div>

                    <!-- Right Container -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.filters.befor') !!}

                        <!-- Output -->
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.create.output')
                            </p>

                            <x-admin::data-transfer.filter-fields
                                :entity-type="$export->entity_type"
                                :values="$exportFilters"
                                :exporter-config="json_encode($exporterConfig)"
                                only="file_format,with_media,header_row,use_labels,date_format,file_path"
                            />
                        </div>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.filters.after') !!}

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.settings.before') !!}

                        <!-- Format settings -->
                        <div
                            v-if="selectedFileFormat == 'Csv'"
                            class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
                        >
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.create.format-settings')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.exports.edit.field-separator')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="field_separator"
                                    rules="required"
                                    :value="old('field_separator') ?? $export->field_separator"
                                    :label="trans('admin::app.settings.data-transfer.exports.edit.field-separator')"
                                    :placeholder="trans('admin::app.settings.data-transfer.exports.edit.field-separator')"
                                />

                                <x-admin::form.control-group.error control-name="field_separator" />
                            </x-admin::form.control-group>
                        </div>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.settings.after') !!}
                    </div>
                </div>

                {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.create_form_controls.after') !!}
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-export-profile-edit', {
                template: '#v-export-profile-edit-template',

                data() {
                    return {
                        fileFormat: @json($export->filters['file_format'] ?? null),
                        selectedFileFormat: @json($export->filters['file_format'] ?? null),
                    };
                },

                mounted() {
                    this.$emitter.on('filter-value-changed', this.handleFilterValues);
                },

                methods: {
                    handleFilterValues(changed) {
                        if ('file_format' == changed.filterName) {
                            this.selectedFileFormat = changed.value;
                        }
                    },
                },
            })
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
