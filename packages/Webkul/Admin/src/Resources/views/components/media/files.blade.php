@props([
    'name'               => 'files',
    'uploadedFiles'      => [],
    'width'              => '210px',
    'height'             => '120px',
    'acceptedExtensions' => \Webkul\Core\Rules\FileOrImageValidValue::FILE_ALLOWED_EXTENSION,
    'instructions'         => '',
    'readOnly'           => false,
    'allowDownload'      => false,
])

<x-admin::media.field type="files" :name="$name" :instructions="$instructions">

<v-media-files
    name="{{ $name }}"
    :uploaded-files='{{ json_encode($uploadedFiles) }}'
    width="{{ $width }}"
    height="{{ $height }}"
    :accepted-extensions='@json($acceptedExtensions)'
    :errors="errors"
    v-bind:read-only="{{ $readOnly ? 'true' : 'false' }}"
    v-bind:allow-download="{{ $allowDownload ? 'true' : 'false' }}"
    class="{{ $attributes->get('class') }}"
>
</v-media-files>
</x-admin::media.field>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-media-files-template"
    >
        <div class="grid">
            <div
                class="grid gap-3"
                :style="{ gridTemplateColumns: `repeat(auto-fill, minmax(${width}, 1fr))` }"
            >
                {{-- Add File tile (shared dropzone; hidden once a file exists) --}}
                <v-media-add-tile
                    v-if="0 == inputFiles.length"
                    title="@lang('admin::app.components.media.files.add-file-btn')"
                    hint="@lang('admin::app.components.media.images.drag-drop-hint')"
                    :allowed-types="acceptedExtensions.join(', ')"
                    :accept="acceptAttribute"
                    :input-id="$.uid + '_fileInput'"
                    icon="icon-file"
                    :read-only="readOnly"
                    @change="add"
                    @drop="onDrop"
                ></v-media-add-tile>

                <draggable
                    class="contents"
                    ghost-class="draggable-ghost"
                    v-bind="{animation: 200}"
                    :list="inputFiles"
                    item-key="id"
                    :disabled="readOnly"
                >
                    <template #item="{ element, index }">
                        <v-media-files-item
                            :name="name"
                            :index="index"
                            :inputFile="element"
                            :width="width"
                            :height="height"
                            :accepted-extensions="acceptedExtensions"
                            :read-only="readOnly"
                            :allow-download="allowDownload"
                            @onRemove="remove($event)"
                            @onChange="change($event)"
                        >
                        </v-media-files-item>
                    </template>
                </draggable>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="v-media-files-item-template">
        <div>
            <v-media-card
                :media="cardMedia"
                mode="file"
                width="100%"
                height="176px"
                :allow-preview="true"
                :allow-replace="! readOnly"
                :allow-remove="! readOnly"
                :allow-download="allowDownload"
                :show-drag-handle="! readOnly"
                :show-badge="true"
                :show-extension="false"
                @preview="preview"
                @replace="replace"
                @remove="remove"
            ></v-media-card>

            <input type="hidden" :name="name" v-if="! readOnly && ! inputFile.is_new && inputFile.value" :value="inputFile.value"/>
            <input
                v-if="! readOnly"
                type="file"
                :name="name + '[]'"
                class="hidden"
                :accept="acceptAttribute"
                :id="$.uid + '_fileInput_' + index"
                :ref="$.uid + '_fileInput_' + index"
                @change="edit"
            />

            <x-admin::modal ref="filePreviewModal" no-class="true">
                <x-slot:header class="bg-white dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white" v-text="cardMedia.name"></p>
                </x-slot>
                <x-slot:content class="flex h-[calc(100vh-60px)] items-center justify-center bg-gray-900 p-6">
                    <iframe v-if="canPreview" :src="previewUrl" :sandbox="previewSandbox" class="h-full w-full rounded"></iframe>
                    <div v-else class="flex flex-col items-center gap-4 text-center text-white">
                        <span class="icon-file text-5xl text-gray-300"></span>
                        <p class="text-base">@lang('admin::app.components.media.files.preview-unavailable')</p>
                        <a v-if="allowDownload" :href="downloadUrl" class="primary-button" download>
                            @lang('admin::app.export.download')
                        </a>
                    </div>
                </x-slot>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        const mediaPreviewRoute = @json(route('admin.media.preview'));
        const mediaDownloadRoute = @json(route('admin.media.download'));

        app.component('v-media-files', {
            template: '#v-media-files-template',

            props: {
                name: {
                    type: String, 
                    default: 'inputFiles',
                },

                uploadedFiles: {
                    type: Array,
                    default: () => []
                },

                width: {
                    type: String,
                    default: '210px'
                },

                height: {
                    type: String,
                    default: '120px'
                },

                acceptedExtensions: {
                    type: Array,
                    default: () => [],
                },

                errors: {
                    type: Object,
                    default: () => {}
                },

                readOnly: {
                    type: Boolean,
                    default: false,
                },

                allowDownload: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    inputFiles: [],

                    isDragging: false,
                }
            },

            computed: {
                acceptAttribute() {
                    if (! this.acceptedExtensions.length) {
                        return '';
                    }

                    return this.acceptedExtensions.map(extension => `.${extension.replace(/^\./, '')}`).join(',');
                },
            },

            mounted() {
                this.inputFiles = this.uploadedFiles;
                this.initialFiles = this.uploadedFiles.map(file => ({ ...file }));

                this.$emitter.on('unsaved-changes:reset', this.resetToInitial);
            },

            beforeUnmount() {
                this.$emitter.off('unsaved-changes:reset', this.resetToInitial);
            },

            methods: {
                resetToInitial() {
                    this.inputFiles = this.initialFiles.map(file => ({ ...file }));

                    this.signalChange();
                },
                isFileAccepted(file) {
                    if (! this.acceptedExtensions.length) {
                        return true;
                    }

                    const extension = (file.name.split('.').pop() || '').toLowerCase();

                    return this.acceptedExtensions
                        .map(value => value.toLowerCase().replace(/^\./, ''))
                        .includes(extension);
                },

                onDrop(files) {
                    this.addFiles(files);
                },

                add(files) {
                    this.addFiles(files);
                },

                async addFiles(files) {
                    if (! files || ! files.length) {
                        return;
                    }

                    const validFiles = Array.from(files).every(file => this.isFileAccepted(file));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.media.files.not-allowed-error')"
                        });

                        return;
                    }

                    for (const file of Array.from(files)) {
                        if (! await this.scanFile(file)) {
                            continue;
                        }

                        this.inputFiles.push({
                            id: 'file_' + this.inputFiles.length,
                            url: '',
                            file: file
                        });
                    }

                    this.signalChange();
                },

                scanFile(file) {
                    return this.$scanMedia(file, {
                        url: "{{ route('admin.media.scan') }}",
                        acceptedExtensions: this.acceptedExtensions,
                        fallbackMessage: "@lang('admin::app.components.media.files.not-allowed-error')",
                    });
                },

                remove(file) {
                    let index = this.inputFiles.indexOf(file);

                    this.inputFiles.splice(index, 1);

                    this.signalChange();
                },
                change(file) {
                    this.inputFiles[0].file = file;

                    this.signalChange();
                },

                signalChange() {
                    this.$nextTick(() => {
                        if (this.$el && this.$el.dispatchEvent) {
                            this.$el.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                                bubbles: true,
                                detail: { name: this.name },
                            }));
                        }
                    });
                },
            }
        });

        app.component('v-media-files-item', {
            template: '#v-media-files-item-template',

            props: ['index', 'inputFile', 'name', 'width', 'height', 'acceptedExtensions', 'readOnly', 'allowDownload'],

            computed: {
                previewSandbox() {
                    return this.cardMedia.extension === 'pdf' ? null : '';
                },

                previewUrl() {
                    if (this.inputFile.is_new || ! this.inputFile.value) {
                        return this.inputFile.url;
                    }

                    return mediaPreviewRoute + '?path=' + encodeURIComponent(this.inputFile.value);
                },

                canPreview() {
                    return ['pdf', 'svg'].includes(this.cardMedia.extension);
                },

                downloadUrl() {
                    if (this.inputFile.is_new || ! this.inputFile.value) {
                        return this.inputFile.url;
                    }

                    return mediaDownloadRoute + '?path=' + encodeURIComponent(this.inputFile.value);
                },

                cardMedia() {
                    const fileName = this.inputFile?.file?.name ?? this.inputFile?.fileName ?? '';
                    const extensionSource = fileName.includes('.')
                        ? fileName
                        : (this.inputFile?.value ?? fileName);

                    return {
                        url: this.inputFile.url,
                        name: fileName,
                        type: this.inputFile?.file?.type ?? 'application/pdf',
                        extension: (extensionSource.split('.').pop() || 'pdf').toLowerCase(),
                        value: this.inputFile?.value,
                        is_new: this.inputFile?.is_new,
                    };
                },

                acceptAttribute() {
                    if (! this.acceptedExtensions || ! this.acceptedExtensions.length) {
                        return '';
                    }

                    return this.acceptedExtensions.map(extension => `.${extension.replace(/^\./, '')}`).join(',');
                },
            },

            mounted() {
                if (this.inputFile.file instanceof File) {
                    this.setFile(this.inputFile.file);

                    this.readFile(this.inputFile.file);
                }
            },

            methods: {
                preview() {
                    this.$refs.filePreviewModal.toggle();
                },

                replace() {
                    this.$refs[this.$.uid + '_fileInput_' + this.index].click();
                },

                scanFile(file) {
                    return this.$scanMedia(file, {
                        url: "{{ route('admin.media.scan') }}",
                        acceptedExtensions: this.acceptedExtensions,
                        fallbackMessage: "@lang('admin::app.components.media.files.not-allowed-error')",
                    });
                },

                async edit() {
                    let inputs = this.$refs[this.$.uid + '_fileInput_' + this.index];

                    if (inputs.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(inputs.files).every(file => {
                        if (! this.acceptedExtensions || ! this.acceptedExtensions.length) {
                            return true;
                        }

                        const extension = (file.name.split('.').pop() || '').toLowerCase();

                        return this.acceptedExtensions
                            .map(value => value.toLowerCase().replace(/^\./, ''))
                            .includes(extension);
                    });

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.media.files.not-allowed-error')"
                        });

                        return;
                    }

                    if (! await this.scanFile(inputs.files[0])) {
                        return;
                    }

                    this.setFile(inputs.files[0]);

                    this.readFile(inputs.files[0]);

                    this.$emit('onChange', inputs.files[0])
                },

                remove() {
                    this.$emit('onRemove', this.inputFile)
                },

                setFile(file) {
                    this.inputFile.is_new = 1;

                    const dataTransfer = new DataTransfer();

                    dataTransfer.items.add(file);

                    this.$refs[this.$.uid + '_fileInput_' + this.index].files = dataTransfer.files;
                },

                readFile(file) {
                    let reader = new FileReader();

                    this.inputFile.url = URL.createObjectURL(file);
                },
            }
        });
    </script>
@endPushOnce
