<x-admin::layouts.with-history>
    <x-slot:entityName>
        job_instance
    </x-slot>

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.imports.edit.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.before') !!}

            <x-admin::form
                :action="route('admin.settings.data_transfer.imports.update', $import->id)"
                method="PUT"
                enctype="multipart/form-data"
            >
                {!! view_render_event('unopim.admin.settings.data_transfer.imports.create.create_form_controls.before') !!}
        
                <!-- Page Header -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.settings.data-transfer.imports.edit.title')
                    </p>
        
                    <div class="flex gap-x-2.5 items-center">
                        <!-- Cancel Button -->
                        <a
                            href="{{ route('admin.settings.data_transfer.imports.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.settings.data-transfer.imports.edit.back-btn')
                        </a>
        
                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.settings.data-transfer.imports.edit.save-btn')
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
                                @lang('admin::app.settings.data-transfer.imports.edit.general')
                            </p>
        
                            <!-- Code -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.imports.create.code')
                                </x-admin::form.control-group.label>
        
                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    :disabled="(boolean) $import->code"
                                    :value="old('code') ?? $import->code"
                                    rules="required"
                                    :label="trans('admin::app.settings.data-transfer.imports.create.code')"
                                    :placeholder="trans('admin::app.settings.data-transfer.imports.create.code')"
                                />
        
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="code"
                                    :value="old('code') ?? $import->code"
                                />
        
                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>
        
                            <!-- Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.imports.edit.type')
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
                                    :disabled="(boolean) $import->entity_type"
                                    :value="old('entity_type') ?? $import->entity_type"
                                    ref="importType"
                                    rules="required"
                                    :label="trans('admin::app.settings.data-transfer.imports.edit.type')"
                                    :options="$optionsJson"
                                    track-by="id"
                                    label-by="label"
                                >
                                </x-admin::form.control-group.control>
        
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="entity_type"
                                    :value="old('entity_type') ?? $import->entity_type"
                                />
                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>
                        </div>

                        @if (isset($importerConfig[$import->entity_type]['has_file_options']) && $importerConfig[$import->entity_type]['has_file_options'])
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.imports.create.media')
                            </p>
        
                            <!-- File Path -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.data-transfer.imports.edit.file')
                                </x-admin::form.control-group.label>
        
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.data-transfer.imports.edit.allowed-file-types')
                                </x-admin::form.control-group.label>
        
                                <x-admin::form.control-group.control
                                    type="file"
                                    name="file"
                                    :value="old('file_path') ?? $import->file_path"
                                    :info="trans('CSV, XLSX, JSON (MAX. 2MB)')"
                                    :label="trans('admin::app.settings.data-transfer.imports.edit.file')"
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
        
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.data-transfer.imports.edit.images')
                            </x-admin::form.control-group.label>
                            
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <div class="flex flex-col">
                                    <!-- Images Directory Path -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <div class="flex items-center space-x-4 gap-2">
                                            <x-admin::form.control-group.label>
                                                @lang('admin::app.settings.data-transfer.imports.edit.images-directory')
                                            </x-admin::form.control-group.label>
        
                                            <x-admin::form.control-group.control
                                                type="text"
                                                name="images_directory_path"
                                                :value="old('images_directory_path') ?? $import->images_directory_path"
                                                :placeholder="trans('admin::app.settings.data-transfer.imports.edit.images-directory')"
                                                class="flex-1"
                                            />
        
                                            <span class="text-gray-600 mb-1.5 hover:text-gray-900 text-sm hidden">{{ old('images_directory_path') ?? '/images/product-images' }}</span>
                                        
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
                                        <x-admin::form.control-group.error control-name="upload_images" />
        
                                        <x-admin::form.control-group.control
                                            type="file"
                                            name="upload_images"
                                            :label="trans('admin::app.admin.data-transfer.imports.create.upload_images')"
                                        />
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
                        @endif
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
                                        @lang('admin::app.settings.data-transfer.imports.edit.settings')
                                    </p>
                                </div>
                            </x-slot>
        
                            <x-slot:content>
                                {!! view_render_event('unopim.admin.settings.data_transfer.imports.edit.filters.fields.before') !!}
                                @if (isset($importerConfig[$import->entity_type]['has_file_options']) && $importerConfig[$import->entity_type]['has_file_options'])
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.settings.data-transfer.imports.edit.action')
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
                                            :disabled="(boolean) $import->action"
                                            :value="old('action') ?? $import->action"
                                            rules="required"
                                            :label="trans('admin::app.settings.data-transfer.imports.edit.action')"
                                            :options="$optionsJson"
                                            track-by="id"
                                            label-by="label"
                                        > 
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.control
                                            type="hidden"
                                            name="action"
                                            :value="old('action') ?? $import->action"
                                        />
                                        
                                        <x-admin::form.control-group.error control-name="action" />
                                    </x-admin::form.control-group>

                                    <!-- Validation Strategy -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.settings.data-transfer.imports.edit.validation-strategy')
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
                                            :value="old('validation_strategy') ?? $import->validation_strategy"
                                            rules="required"                                
                                            :label="trans('admin::app.settings.data-transfer.imports.edit.validation-strategy')"
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
                                            @lang('admin::app.settings.data-transfer.imports.edit.allowed-errors')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="allowed_errors"
                                            :value="old('allowed_errors') ?? $import->allowed_errors"
                                            rules="required"
                                            :label="trans('admin::app.settings.data-transfer.imports.edit.allowed-errors')"
                                            :placeholder="trans('admin::app.settings.data-transfer.imports.edit.allowed-errors')"
                                        />

                                        <x-admin::form.control-group.error control-name="allowed_errors" />
                                    </x-admin::form.control-group>

                                    <!-- CSV Field Separator -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.settings.data-transfer.imports.edit.field-separator')
                                            <span>*</span>
                                            <span>(@lang('admin::app.settings.data-transfer.imports.edit.separator-info'))</span>
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="field_separator"
                                            :value="old('field_separator') ?? $import->field_separator"
                                            rules="required"
                                            :label="trans('admin::app.settings.data-transfer.imports.edit.field-separator')"
                                            :placeholder="trans('admin::app.settings.data-transfer.imports.edit.field-separator')"
                                        />

                                        <x-admin::form.control-group.error control-name="field_separator" />
                                    </x-admin::form.control-group>
                                @endif
                                @php
                                    $fields = $importerConfig[$import->entity_type]['filters']['fields'] ?? [];
                                    $filters = $import->filters ?? [];
                                @endphp
                                <x-admin::data-transfer.import-setting-fields
                                    :entity-type="$import->entity_type"
                                    :values="$filters"
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
</x-admin::layouts.with-history>
