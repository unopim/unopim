<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.imports.create.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.before') !!}

    <v-import-profile></v-import-profile>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-import-profile-template">
            <x-admin::form
                :action="route('admin.settings.data_transfer.imports.store')"
                enctype="multipart/form-data"
            >
            {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.create_form_controls.before') !!}

            <!-- Page Header -->
            <div class="flex justify-between items-center">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('admin::app.settings.data-transfer.imports.create.title')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    <!-- Cancel Button -->
                    <a
                        href="{{ route('admin.settings.data_transfer.imports.index') }}"
                        class="transparent-button"
                    >
                        @lang('admin::app.settings.data-transfer.imports.create.back-btn')
                    </a>

                    <!-- Save Button -->
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.settings.data-transfer.imports.create.save-btn')
                    </button>
                </div>
            </div>

            <!-- Body Content -->
            <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                <!-- Left Container -->
                <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                    {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.card.general.before') !!}

                    <!-- Setup Import Panel -->
                    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                        <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                            @lang('admin::app.settings.data-transfer.imports.create.general')
                        </p>
                        <!-- Code -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.data-transfer.imports.create.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                :value="old('code')"
                                rules="required"
                                :label="trans('admin::app.settings.data-transfer.imports.create.code')"
                                :placeholder="trans('admin::app.settings.data-transfer.imports.create.code')"
                            />

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <!-- Type -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.data-transfer.imports.create.type')
                            </x-admin::form.control-group.label>
    
                            @php
                                $options = [];
                                foreach(config('importers') as $index => $importer) {
                                        $options[] = [
                                            'id'    => $index,
                                            'label' => trans($importer['title'])
                                        ];
                                    }

                                $optionsJson = json_encode($options);

                            @endphp

                            <x-admin::form.control-group.control
                                type="select"
                                name="entity_type"
                                id="import-type"
                                :value="old('entity_type')"
                                v-model="entityType"
                                ref="importType"
                                rules="required"
                                :label="trans('admin::app.settings.data-transfer.imports.create.type')"
                                :options="$optionsJson"
                                track-by="id"
                                label-by="label"
                            >   
                            </x-admin::form.control-group.control>
                            
                            <x-admin::form.control-group.error control-name="entity_type" />
                        </x-admin::form.control-group>
                    </div>

                    <div v-if="enableFileShow" class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                        <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                            @lang('admin::app.settings.data-transfer.imports.create.media')
                        </p>
                        <!-- File Path -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.data-transfer.imports.create.file')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.data-transfer.imports.create.allowed-file-types')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="file"
                                name="file"
                                :info="trans('CSV, XLSX, JSON (MAX. 2MB)')"
                                :label="trans('admin::app.settings.data-transfer.imports.create.file')"
                            />
                            <x-admin::form.control-group.error control-name="file" />
                            <!-- Source Sample Download Links -->
                            <template v-if="$refs['importType'] && $refs['importType'].selectedOption">
                                <a
                                    :href="'{{ route('admin.settings.data_transfer.imports.download_sample') }}/' + $refs['importType'].selectedOption"
                                    target="_blank"
                                    id="source-sample-link"
                                    class="text-sm text-violet-700 dark:text-sky-500 cursor-pointer transition-all hover:underline mt-1"
                                >

                                    @{{ "@lang('admin::app.settings.data-transfer.imports.create.download-sample')".replace(':resource', $refs['importType'].selectedOption.replace(/^\w/, (c) => c.toUpperCase())) }}
                                </a>
                            </template>
                        </x-admin::form.control-group>

                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.data-transfer.imports.create.images')
                        </x-admin::form.control-group.label>

                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <div class="flex flex-col">
                                <!-- Images Directory Path -->
                                <x-admin::form.control-group class="!mb-0">
                                    <div class="flex items-center space-x-4 gap-2">
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.settings.data-transfer.imports.create.images-directory')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="images_directory_path"
                                            :value="old('images_directory_path')"
                                            placeholder="/images/product-images"
                                            class="flex-1"
                                        />

                                        <span class="text-gray-600 mb-1.5 hover:text-gray-900 text-sm hidden ">{{ old('images_directory_path') ?? '/images/product-images' }}</span>

                                    </div>

                                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-300 ml-12">
                                        @lang('admin::app.settings.data-transfer.imports.edit.file-info-example')
                                    </p>

                                </x-admin::form.control-group>

                                <!-- Image Zip Upload -->
                                <x-admin::form.control-group class="mt-3 hidden">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.imports.create.upload_images')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="file"
                                        name="upload_images"
                                        :info="trans('CSV, XLSX, JSON (MAX. 2MB)')"
                                        :label="trans('admin::app.admin.data-transfer.imports.create.upload_images')"
                                    />

                                    <x-admin::form.control-group.error control-name="upload_images" />

                                    <!-- Sample images zip link -->
                                    <a
                                        :href="'{{ route('admin.settings.data_transfer.imports.download_sample_zip') }}/' + $refs['importType']?.value"
                                        target="_blank"
                                        id="source-sample-link"
                                        class="text-sm text-violet-700 dark:text-sky-500 cursor-pointer transition-all hover:underline mt-1"
                                    >
                                        @lang('admin::app.settings.data-transfer.imports.create.download-sample-zip')
                                    </a>                                
                                </x-admin::form.control-group>
                            </div>
                        </div>
                    </div>

                    {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.card.general.after') !!}
                </div>

                <!-- Right Container -->
                <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                    {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.card.accordion.settings.before') !!}

                    <!-- Settings Panel -->
                    <x-admin::accordion>
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.settings.data-transfer.imports.create.settings')
                                </p>
                            </div>
                        </x-slot>
                                    
                        <x-slot:content>
                            <template v-if="enableFileShow">
                                <!-- Action -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.imports.create.action')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $options = [];
                                        foreach(config('import_settings')['actions'] as $action) {
                                                $options[] = [
                                                    'id'    => $action['id'],
                                                    'label' => trans($action['title'])
                                                ];
                                            }

                                        $optionsJson = json_encode($options);
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="action"
                                        id="action"
                                        :value="old('action') ?? 'append'"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.imports.create.action')"
                                        :options="$optionsJson"
                                        track-by="id"
                                        label-by="label"
                                    > 
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="action" />
                                </x-admin::form.control-group>

                                <!-- Validation Strategy -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.imports.create.validation-strategy')
                                    </x-admin::form.control-group.label>
                                    
                                    @php
                                        $options = [];
                                        foreach(config('import_settings')['validation_strategy'] as $action) {
                                                $options[] = [
                                                    'id'    => $action['id'],
                                                    'label' => trans($action['title'])
                                                ];
                                            }

                                        $optionsJson = json_encode($options);
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="validation_strategy"
                                        id="validation_strategy"
                                        :value="old('validation_strategy') ?? 'stop-on-errors'"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.imports.create.validation-strategy')"
                                        :options="$optionsJson"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="validation_strategy" />
                                </x-admin::form.control-group>

                                <!-- Allowed Errors -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.imports.create.allowed-errors')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="allowed_errors"
                                        :value="old('allowed_errors') ?? 10"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.imports.create.allowed-errors')"
                                        :placeholder="trans('admin::app.settings.data-transfer.imports.create.allowed-errors')"
                                    />
                                    <x-admin::form.control-group.error control-name="allowed_errors" />
                                </x-admin::form.control-group>

                                <!-- CSV Field Separator -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.data-transfer.imports.create.field-separator')
                                        <span>*</span>
                                        <span>(@lang('admin::app.settings.data-transfer.imports.create.separator-info'))</span>
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="field_separator"
                                        :value="old('field_separator') ?? ';'"
                                        rules="required"
                                        :label="trans('admin::app.settings.data-transfer.imports.create.field-separator')"
                                        :placeholder="trans('admin::app.settings.data-transfer.imports.create.field-separator')"
                                    />
                                    
                                    <x-admin::form.control-group.error control-name="field_separator" />
                                </x-admin::form.control-group>
                            </template>
                            <x-admin::data-transfer.import-setting-fields
                                        ::entity-type="entityType"
                                        ::fields="settingFields"
                                        :importer-config="json_encode($importerConfig)"
                            >
                            </x-admin::data-transfer.import-setting-fields>
                        </x-slot>
                    </x-admin::accordion>

                    {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.card.accordion.settings.after') !!}
                </div>
            </div>

            {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.create_form_controls.after') !!}
            </x-admin::form>
        </script>
        <script type="module">
            app.component('v-import-profile', {
                template: '#v-import-profile-template',
                
                data() {
                    return {
                        fileFormat: 'Csv',
                        selectedFileFormat: "{{ old('filters.file_format') ?? null }}",
                        entityType: "{{ old('entity_type') ?? 'categories' }}",
                        enableFileShow: @json($importerConfig[old('entity_type') ?? 'categories']['has_file_options'] ?? false),
                        importerConfig: @json($importerConfig), 
                        filterFields: @json($importerConfig['categories']['filters']['fields'] ?? null)
                    };
                },
                
                mounted() {
                    this.$emitter.on('filter-value-changed', this.handleFilterValues);
                },

                watch: {
                    fileFormat(value) {
                        this.selectedFileFormat = JSON.parse(value).value;
                    },
                    entityType(value) {
                        console.log(value);
                        this.enableFileShow = this.importerConfig[JSON.parse(value).id]?.has_file_options;
                        this.$emitter.emit('entity-type-changed', value);
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
