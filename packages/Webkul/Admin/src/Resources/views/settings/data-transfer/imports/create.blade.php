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
                            <div class="flex flex-col gap-3">

                                <!-- Path input row -->
                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-300 font-medium block mb-1">
                                        @lang('admin::app.settings.data-transfer.imports.create.images-directory')
                                    </label>

                                    <div class="flex items-center gap-0 rounded-sm border dark:border-gray-800 overflow-hidden focus-within:border-violet-500 transition-colors">
                                        <span class="px-2.5 py-2 text-xs text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-cherry-800 border-r dark:border-gray-700 shrink-0 select-none">
                                            storage/app/public/
                                        </span>
                                        <input
                                            type="text"
                                            name="images_directory_path"
                                            v-model="imagesDirectoryPath"
                                            placeholder="import-images/my-products"
                                            class="flex-1 py-2 px-3 text-sm text-gray-700 dark:text-gray-300 dark:bg-cherry-900 outline-none bg-transparent"
                                        />
                                    </div>

                                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                                        @lang('admin::app.settings.data-transfer.imports.edit.file-info-example')
                                    </p>
                                </div>

                                <!-- ZIP upload zone -->
                                <div>
                                    <!-- State: idle -->
                                    <label
                                        v-if="!zipUploading && !zipUploadedPath"
                                        class="flex flex-col items-center justify-center w-full border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-violet-50 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 transition-colors"
                                        @dragover.prevent="$event.currentTarget.classList.add('border-violet-500', 'bg-violet-50')"
                                        @dragleave.prevent="$event.currentTarget.classList.remove('border-violet-500', 'bg-violet-50')"
                                        @drop.prevent="handleZipDrop($event)"
                                    >
                                        <div class="flex flex-col items-center justify-center py-6">
                                            <span class="icon-export text-gray-500 dark:text-gray-400 text-4xl"></span>
                                            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="font-semibold">@lang('admin::app.settings.data-transfer.imports.create.zip-click-upload')</span>
                                                {{ __('or drag and drop') }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">ZIP &mdash; max 100 MB</p>
                                        </div>

                                        <input
                                            type="file"
                                            ref="zipFileInput"
                                            accept=".zip"
                                            class="hidden"
                                            @change="uploadImagesZip"
                                        />
                                    </label>

                                    <!-- State: uploading -->
                                    <div
                                        v-else-if="zipUploading"
                                        class="flex items-center justify-center w-full border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <div class="flex flex-col items-center justify-center py-6">
                                            <svg class="w-9 h-9 text-violet-500 animate-spin mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0 1 16 0"/>
                                            </svg>
                                            <p class="mb-2 text-sm font-semibold text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.imports.create.zip-uploading')</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs">@{{ zipFileName }}</p>
                                        </div>
                                    </div>

                                    <!-- State: success -->
                                    <div
                                        v-else-if="zipUploadedPath"
                                        class="flex items-center justify-center w-full border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <div class="flex flex-col items-center justify-center py-6 px-4 w-full">
                                            <span class="icon-product text-4xl mb-4 text-gray-500 dark:text-gray-400"></span>

                                            <div class="flex justify-between items-center w-full text-sm text-gray-500 dark:text-gray-400">
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">@{{ zipFileName }}</p>
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                        @{{ zipFilesCount }} @lang('admin::app.settings.data-transfer.imports.create.zip-files-extracted')
                                                    </p>
                                                    <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                                                        storage/app/public/@{{ zipUploadedPath }}
                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="removeZipUpload"
                                                    class="icon-cancel text-3xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md ml-2 shrink-0"
                                                ></button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error -->
                                    <div
                                        v-if="zipUploadError"
                                        class="flex items-center gap-2 mt-2 px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-sm text-xs text-red-600 dark:text-red-400"
                                    >
                                        <i class="icon-cancel shrink-0"></i>
                                        @{{ zipUploadError }}
                                    </div>
                                </div>

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
                        entityType: "{{ old('entity_type') ?? 'categories' }}",
                        enableFileShow: @json($importerConfig[old('entity_type') ?? 'categories']['has_file_options'] ?? false),
                        importerConfig: @json($importerConfig),
                        filterFields: @json($importerConfig['categories']['filters']['fields'] ?? null),
                        imagesDirectoryPath: "{{ old('images_directory_path', '') }}",
                        zipUploading: false,
                        zipUploadError: null,
                        zipUploadedPath: null,
                        zipFileName: null,
                        zipFilesCount: 0,
                    };
                },

                watch: {
                    entityType(value) {
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

                    uploadImagesZip(event) {
                        const file = event.target.files[0];

                        if (! file) {
                            return;
                        }

                        this.processZipFile(file);
                    },

                    handleZipDrop(event) {
                        event.currentTarget.classList.remove('border-violet-500', 'bg-violet-50');
                        const file = event.dataTransfer.files[0];

                        if (! file || ! file.name.toLowerCase().endsWith('.zip')) {
                            this.zipUploadError = 'Please select a valid .zip file.';
                            return;
                        }

                        this.processZipFile(file);
                    },

                    processZipFile(file) {
                        this.zipUploading = true;
                        this.zipUploadError = null;
                        this.zipUploadedPath = null;
                        this.zipFileName = file.name;
                        this.zipFilesCount = 0;

                        const formData = new FormData();

                        formData.append('images_zip', file);

                        this.$axios.post('{{ route('admin.settings.data_transfer.imports.upload_images_zip') }}', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        })
                        .then(response => {
                            this.zipUploading = false;
                            this.zipUploadedPath = response.data.path;
                            this.zipFilesCount = response.data.files_count ?? 0;
                            this.imagesDirectoryPath = response.data.path;
                        })
                        .catch(error => {
                            this.zipUploading = false;
                            this.zipUploadError = error.response?.data?.message
                                ?? '{{ trans('admin::app.settings.data-transfer.imports.create.zip-upload-error') }}';

                            if (this.$refs.zipFileInput) {
                                this.$refs.zipFileInput.value = '';
                            }
                        });
                    },

                    removeZipUpload() {
                        this.zipUploadedPath = null;
                        this.zipFileName = null;
                        this.zipFilesCount = 0;
                        this.zipUploadError = null;
                        this.imagesDirectoryPath = '';

                        if (this.$refs.zipFileInput) {
                            this.$refs.zipFileInput.value = '';
                        }
                    },
                },
            })
        </script>
    @endPushOnce

</x-admin::layouts>
