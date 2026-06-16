<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.create.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.before') !!}

    <v-export-profile></v-export-profile>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-export-profile-template">
            <x-admin::form
                :action="route('admin.settings.data_transfer.exports.store')"
                enctype="multipart/form-data"
                ref="exportCreateForm"
            >
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.create_form_controls.before') !!}

                <!-- Page Header -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.settings.data-transfer.exports.create.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a
                            href="{{ route('admin.settings.data_transfer.exports.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.settings.data-transfer.exports.create.back-btn')
                        </a>

                        <button type="submit" class="primary-button">
                            @lang('admin::app.settings.data-transfer.exports.create.save-btn')
                        </button>
                    </div>
                </div>

                <!-- Body Content -->
                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <!-- Left Container -->
                    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.general.before') !!}

                        <!-- General -->
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.create.general')
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
                                        :value="old('code')"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.exports.create.code')"
                                        :placeholder="trans('admin::app.settings.data-transfer.exports.create.code')"
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.exports.create.type')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $options = [];
                                        foreach (config('exporters') as $index => $export) {
                                            $options[] = ['id' => $index, 'label' => trans($export['title'])];
                                        }
                                        $optionsJson = json_encode($options);
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="entity_type"
                                        id="export-type"
                                        :value="old('entity_type')"
                                        v-model="entityType"
                                        ref="exportType"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.exports.create.type')"
                                        :options="$optionsJson"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="entity_type" />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.general.after') !!}

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.scope.before') !!}

                        <!-- Data to export -->
                        <div
                            v-if="hasScopeFilters"
                            class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
                        >
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.create.scope-filters')
                            </p>

                            <x-admin::data-transfer.filter-fields
                                ::entity-type="entityType"
                                :exporter-config="json_encode($exporterConfig)"
                                only="channels,locales,currencies,attributes"
                                grid-class="grid grid-cols-1"
                            />
                        </div>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.scope.after') !!}

                        <!-- Product filters -->
                        <div
                            v-if="hasProductFilters"
                            class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
                        >
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.create.product-filters')
                            </p>

                            <x-admin::data-transfer.filter-fields
                                ::entity-type="entityType"
                                :exporter-config="json_encode($exporterConfig)"
                                only="attribute_families,status"
                                grid-class="grid grid-cols-2 max-sm:grid-cols-1 gap-x-5"
                            />

                            <x-admin::data-transfer.filter-fields
                                ::entity-type="entityType"
                                :exporter-config="json_encode($exporterConfig)"
                                only="completeness,time_condition,time_value,time_date,time_date_end"
                                grid-class="grid grid-cols-2 max-sm:grid-cols-1 gap-x-5"
                            />

                            <!-- Category (tree) -->
                            <template v-if="supportsCategories">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('data_transfer::app.exporters.products.filters.categories')
                                    </x-admin::form.control-group.label>

                                    <x-admin::data-transfer.category-tree
                                        :value="old('filters.categories') ?? []"
                                    />
                                </x-admin::form.control-group>
                            </template>

                            <x-admin::data-transfer.filter-fields
                                ::entity-type="entityType"
                                :exporter-config="json_encode($exporterConfig)"
                                only="sku"
                                grid-class="grid grid-cols-1"
                            />
                        </div>

                        <!-- Attribute Conditions -->
                        <template v-if="supportsConditions">
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    @lang('admin::app.settings.data-transfer.exports.create.attribute-conditions')
                                </p>

                                <x-admin::data-transfer.attribute-conditions
                                    :attribute-route="route('admin.settings.data_transfer.exports.filters.attributes')"
                                    :exclude-attributes="[\Webkul\DataTransfer\Enums\ProductFilter::SKU->value]"
                                    :operators="\Webkul\DataTransfer\Helpers\Sources\Export\Filters\AttributeConditionOperators::frontendMap()"
                                >
                                </x-admin::data-transfer.attribute-conditions>
                            </div>
                        </template>
                    </div>

                    <!-- Right Container -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.accordion.filters.befor') !!}

                        <!-- Output -->
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.create.output')
                            </p>

                            <x-admin::data-transfer.filter-fields
                                ::entity-type="entityType"
                                ::fields="filterFields"
                                :exporter-config="json_encode($exporterConfig)"
                                only="file_format,with_media,header_row,use_labels,date_format,file_path"
                            />
                        </div>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.accordion.filters.after') !!}

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.accordion.settings.before') !!}

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
                                    @lang('admin::app.settings.data-transfer.exports.create.field-separator')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="field_separator"
                                    rules="required"
                                    :value="old('field_separator') ?? ','"
                                    :label="trans('admin::app.settings.data-transfer.exports.create.field-separator')"
                                    :placeholder="trans('admin::app.settings.data-transfer.exports.create.field-separator')"
                                />

                                <x-admin::form.control-group.error control-name="field_separator" />
                            </x-admin::form.control-group>
                        </div>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.accordion.settings.after') !!}
                    </div>
                </div>

                {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.create_form_controls.after') !!}
            </x-admin::form>
        </script>

        <script type="module">
            @php
                /**
                 * Seed the component from the previously selected type so a validation
                 * error (which reloads the form with old input) keeps the matching filter
                 * fields visible instead of falling back to the default entity and hiding
                 * the user's still-present input.
                 */
                $selectedEntityType = old('entity_type', 'categories');

                if (! isset($exporterConfig[$selectedEntityType]['filters']['fields'])) {
                    $selectedEntityType = 'categories';
                }
            @endphp

            app.component('v-export-profile', {
                template: '#v-export-profile-template',

                data() {
                    return {
                        fileFormat: 'Csv',
                        selectedFileFormat: "{{ old('filters.file_format') ?? null }}",
                        entityType: "{{ $selectedEntityType }}",
                        exporterConfig: @json($exporterConfig),
                        filterFields: @json($exporterConfig[$selectedEntityType]['filters']['fields']),
                    };
                },

                mounted() {
                    this.$emitter.on('filter-value-changed', this.handleFilterValues);
                },

                computed: {
                    hasScopeFilters() {
                        return this.filterFields.some(field => ['channels', 'locales', 'currencies', 'attributes'].includes(field.name));
                    },

                    hasProductFilters() {
                        return this.filterFields.some(field => ['attribute_families', 'categories', 'completeness', 'time_condition', 'status', 'sku'].includes(field.name));
                    },

                    supportsConditions() {
                        return this.filterFields.some(field => field.name === 'attributes');
                    },

                    supportsCategories() {
                        return this.filterFields.some(field => field.name === 'categories');
                    },
                },

                watch: {
                    fileFormat(value) {
                        this.selectedFileFormat = JSON.parse(value).value;
                    },

                    entityType(value) {
                        let configKey = this.parseValue(value)?.id;

                        if (! configKey) {
                            return;
                        }

                        this.filterFields = this.exporterConfig[configKey]['filters']['fields'];

                        if (this.filterFields.filter(field => field.name == 'file_format').length == 0) {
                            this.selectedFileFormat = '';
                        }

                        this.$emitter.emit('entity-type-changed', value);

                        let formValues = this.$refs.exportCreateForm.values;

                        let resetState = {
                            values: {code: formValues.code},
                            errors: this.$refs.exportCreateForm.errors,
                        };

                        this.$refs.exportCreateForm.resetForm(resetState);
                    },
                },

                methods: {
                    parseValue(value) {
                        try {
                            return value ? JSON.parse(value) : null;
                        } catch (error) {
                            return value;
                        }
                    },

                    handleFilterValues(changed) {
                        if ('file_format' == changed.filterName) {
                            this.selectedFileFormat = changed.value;
                        }
                    },
                },
            })
        </script>
    @endPushOnce
</x-admin::layouts>
