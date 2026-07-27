@props([
    'name'               => 'files',
    'uploadedFiles'      => [],
    'width'              => '210px',
    'height'             => '120px',
    'acceptedExtensions' => \Webkul\Core\Rules\FileOrImageValidValue::FILE_ALLOWED_EXTENSION,
    'instructions'         => '',
])

<x-admin::media.field type="files" :name="$name" :instructions="$instructions">

<v-media-files
    name="{{ $name }}"
    :uploaded-files='{{ json_encode($uploadedFiles) }}'
    width="{{ $width }}"
    height="{{ $height }}"
    :accepted-extensions='@json($acceptedExtensions)'
    :errors="errors"
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
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                {{-- Add File tile (shared dropzone; hidden once a file exists) --}}
                <v-media-add-tile
                    v-if="0 == inputFiles.length"
                    title="@lang('admin::app.components.media.files.add-file-btn')"
                    hint="@lang('admin::app.components.media.images.drag-drop-hint')"
                    allowed-types="@lang('admin::app.components.media.files.allowed-types')"
                    :accept="acceptAttribute"
                    :input-id="$.uid + '_fileInput'"
                    icon="icon-file"
                    @change="add"
                    @drop="onDrop"
                ></v-media-add-tile>

                <draggable
                    class="contents"
                    ghost-class="draggable-ghost"
                    v-bind="{animation: 200}"
                    :list="inputFiles"
                    item-key="id"
                >
                    <template #item="{ element, index }">
                        <v-media-files-item
                            :name="name"
                            :index="index"
                            :inputFile="element"
                            :width="width"
                            :height="height"
                            :accepted-extensions="acceptedExtensions"
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
                :allow-replace="true"
                :allow-remove="true"
                :show-badge="true"
                :show-extension="false"
                @preview="preview"
                @replace="replace"
                @remove="remove"
            >
                <template #actions="{ media }">
                    <a
                        :href="media.url"
                        target="_blank"
                        class="icon-down-stat rounded bg-white/20 p-1.5 text-white"
                        aria-label="@lang('admin::app.export.download')"
                        @click.stop
                    ></a>
                </template>
            </v-media-card>

            <input type="hidden" :name="name" v-if="! inputFile.is_new && inputFile.value" :value="inputFile.value"/>
            <input
                type="file"
                :name="name + '[]'"
                class="hidden"
                :accept="acceptAttribute"
                :id="$.uid + '_fileInput_' + index"
                :ref="$.uid + '_fileInput_' + index"
                @change="edit"
            />

            <x-admin::modal ref="filePreviewModal" type="large">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white" v-text="cardMedia.name"></p>
                </x-slot>
                <x-slot:content>
                    <iframe :src="inputFile.url" class="w-full rounded" style="height: 70vh;"></iframe>
                </x-slot>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
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
                }
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

                addFiles(files) {
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

                    Array.from(files).forEach((file) => {
                        this.inputFiles.push({
                            id: 'file_' + this.inputFiles.length,
                            url: '',
                            file: file
                        });
                    });

                    this.signalChange();
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

            props: ['index', 'inputFile', 'name', 'width', 'height', 'acceptedExtensions'],

            computed: {
                cardMedia() {
                    const fileName = this.inputFile?.file?.name ?? this.inputFile?.fileName ?? '';

                    return {
                        url: this.inputFile.url,
                        name: fileName,
                        type: this.inputFile?.file?.type ?? 'application/pdf',
                        extension: (fileName.split('.').pop() || 'pdf').toLowerCase(),
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

                edit() {
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
