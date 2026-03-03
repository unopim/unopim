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
                        <div class="flex flex-col gap-2">

                            <label class="text-sm text-gray-600 dark:text-gray-300 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.edit.images-directory')
                            </label>

                            <!-- Hidden file input — must live outside overflow-hidden container -->
                            <input type="file" id="zip-upload-edit" accept=".zip" style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;" />

                            <!-- Path input + inline Browse button -->
                            <div class="flex items-center gap-0 rounded-sm border dark:border-gray-800 overflow-hidden focus-within:border-violet-500 transition-colors">
                                <span class="px-2.5 py-2 text-xs text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-cherry-800 border-r dark:border-gray-700 shrink-0 select-none">
                                    storage/app/public/
                                </span>

                                <input
                                    type="text"
                                    id="images-directory-path"
                                    name="images_directory_path"
                                    value="{{ old('images_directory_path', $import->images_directory_path) }}"
                                    placeholder="import-images/my-products"
                                    class="flex-1 py-2 px-3 text-sm text-gray-700 dark:text-gray-300 dark:bg-cherry-900 outline-none bg-transparent"
                                />

                                <!-- Browse button -->
                                <input
                                    type="button"
                                    id="browse-btn"
                                    class="secondary-button !rounded-none border-l dark:border-l-gray-700 shrink-0 cursor-pointer"
                                    value="@lang('admin::app.settings.data-transfer.imports.create.upload_images')"
                                />
                            </div>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                @lang('admin::app.settings.data-transfer.imports.edit.file-info-example')
                            </p>

                            <!-- Uploading indicator -->
                            <div id="zip-state-uploading" class="hidden flex items-center gap-2 px-3 py-2 rounded-sm bg-gray-50 dark:bg-cherry-800 border dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 shrink-0 text-violet-500 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0 1 16 0"/>
                                </svg>
                                <span>@lang('admin::app.settings.data-transfer.imports.create.zip-uploading')</span>
                                <span id="zip-uploading-name" class="truncate text-xs text-gray-400"></span>
                            </div>

                            <!-- Success indicator -->
                            <div id="zip-state-success" class="hidden flex items-center justify-between gap-2 px-3 py-2 rounded-sm bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="icon-product text-xl text-green-600 dark:text-green-400 shrink-0"></span>
                                    <div class="min-w-0">
                                        <p id="zip-success-filename" class="text-sm font-medium text-green-700 dark:text-green-400 truncate"></p>
                                        <p id="zip-success-count" class="text-xs text-green-600 dark:text-green-500"></p>
                                        <p id="zip-success-path" class="text-xs font-mono text-gray-400 dark:text-gray-500 truncate"></p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    id="zip-remove-btn"
                                    class="icon-cancel text-2xl shrink-0 text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                                ></button>
                            </div>

                            <!-- Error indicator -->
                            <div id="zip-state-error" class="hidden flex items-center gap-2 px-3 py-2 rounded-sm bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-xs text-red-600 dark:text-red-400">
                                <i class="icon-cancel shrink-0"></i>
                                <span id="zip-error-msg"></span>
                            </div>

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

    @push('scripts')
        <script>
            (function () {
                const browseBtn       = document.getElementById('browse-btn');
                const fileInput       = document.getElementById('zip-upload-edit');
                const pathInput       = document.getElementById('images-directory-path');
                const stateUploading  = document.getElementById('zip-state-uploading');
                const stateSuccess    = document.getElementById('zip-state-success');
                const stateError      = document.getElementById('zip-state-error');
                const uploadingName   = document.getElementById('zip-uploading-name');
                const successFilename = document.getElementById('zip-success-filename');
                const successCount    = document.getElementById('zip-success-count');
                const successPath     = document.getElementById('zip-success-path');
                const removeBtn       = document.getElementById('zip-remove-btn');
                const errorMsg        = document.getElementById('zip-error-msg');

                if (! fileInput || ! browseBtn) return;

                const originalPath   = pathInput ? pathInput.value : '';
                const filesExtracted = @json(trans('admin::app.settings.data-transfer.imports.create.zip-files-extracted'));
                const uploadError    = @json(trans('admin::app.settings.data-transfer.imports.create.zip-upload-error'));

                /* Browse button opens the hidden file input */
                browseBtn.addEventListener('click', () => fileInput.click());

                function setStatus(state) {
                    stateUploading.classList.toggle('hidden', state !== 'uploading');
                    stateSuccess.classList.toggle('hidden', state !== 'success');
                    stateError.classList.toggle('hidden', state !== 'error');
                    browseBtn.disabled = (state === 'uploading');
                }

                function handleFile(file) {
                    if (! file || ! file.name.toLowerCase().endsWith('.zip')) {
                        errorMsg.textContent = 'Please select a valid .zip file.';
                        setStatus('error');
                        return;
                    }

                    uploadingName.textContent = file.name;
                    setStatus('uploading');

                    const formData = new FormData();
                    formData.append('images_zip', file);

                    axios.post(
                        '{{ route('admin.settings.data_transfer.imports.upload_images_zip') }}',
                        formData,
                        { headers: { 'Content-Type': 'multipart/form-data' } }
                    ).then((response) => {
                        const { path, files_count, zip_name } = response.data;

                        pathInput.value = path;
                        successFilename.textContent = zip_name ?? file.name;
                        successCount.textContent = (files_count ?? 0) + ' ' + filesExtracted;
                        successPath.textContent = 'storage/app/public/' + path;

                        setStatus('success');
                    }).catch((err) => {
                        errorMsg.textContent = err.response?.data?.message ?? uploadError;
                        fileInput.value = '';
                        setStatus('error');
                    });
                }

                fileInput.addEventListener('change', () => {
                    if (fileInput.files[0]) handleFile(fileInput.files[0]);
                });

                removeBtn.addEventListener('click', () => {
                    pathInput.value = originalPath;
                    fileInput.value = '';
                    setStatus('none');
                });
            })();
        </script>
    @endpush
</x-admin::layouts.with-history>
