@props([
    'name'           => 'files',
    'uploadedFiles' => [],
    'width'          => '210px',
    'height'         => '120px'
])

<v-media-files
    name="{{ $name }}"
    :uploaded-files='{{ json_encode($uploadedFiles) }}'
    width="{{ $width }}"
    height="{{ $height }}"
    :errors="errors"
    class="{{ $attributes->get('class') }}"
>
</v-media-files>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-media-files-template"
    >
        <!-- Panel Content -->
        <div class="grid">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <!-- Add File tile (always first; hidden once a file exists) -->
                <label
                    class="group flex flex-col justify-center items-center min-h-[160px] rounded-lg border-2 border-dashed border-gray-300 dark:border-cherry-500 bg-gradient-to-br from-violet-50/40 to-white dark:from-cherry-900/40 dark:to-cherry-900 cursor-pointer transition-all hover:border-violet-500 dark:hover:border-violet-400 hover:shadow-md"
                    :class="{ 'border-red-500 dark:border-red-500': errors['inputFiles.files[0]'] }"
                    :for="$.uid + '_fileInput'"
                    v-if="0 == inputFiles.length"
                >
                    <span class="icon-folder text-3xl text-gray-400 group-hover:text-violet-600 transition-colors"></span>
                    <p class="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @lang('admin::app.components.media.files.add-file-btn')
                    </p>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 text-center px-2 leading-tight">
                        @lang('admin::app.components.media.files.allowed-types')
                    </p>

                    <input
                        type="file"
                        class="hidden"
                        :id="$.uid + '_fileInput'"
                        accept="application/pdf"
                        :ref="$.uid + '_fileInput'"
                        @change="add"
                    />
                </label>

                <!-- Uploaded Files -->
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
        <div class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900 overflow-hidden shadow-sm transition-all hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-700">
            <!-- File icon preview area -->
            <div class="relative flex flex-col items-center justify-center w-full h-[140px] bg-gray-50 dark:bg-cherry-800">
                <span class="icon-folder text-5xl text-gray-400 dark:text-gray-500"></span>
                <span class="mt-1 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase">PDF</span>

                <!-- Hover overlay with actions -->
                <div class="absolute inset-0 flex items-end justify-center gap-2 p-2 bg-gradient-to-t from-black/60 via-black/20 to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                    <a :href="inputFile.url" target="_blank" class="flex items-center">
                        <span class="icon-down-stat text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-white/30 cursor-pointer"></span>
                    </a>
                    <label
                        class="icon-edit text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-white/30 cursor-pointer"
                        :for="$.uid + '_fileInput_' + index"
                    ></label>
                    <span
                        class="icon-delete text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-red-500/80 cursor-pointer"
                        @click="remove"
                    ></span>

                    <input type="hidden" :name="name" v-if="! inputFile.is_new && inputFile.value" :value="inputFile.value"/>
                    <input
                        type="file"
                        :name="name + '[]'"
                        class="hidden"
                        accept="application/pdf"
                        :id="$.uid + '_fileInput_' + index"
                        :ref="$.uid + '_fileInput_' + index"
                        @change="edit"
                    />
                </div>
            </div>

            <!-- Filename caption -->
            <p
                class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-center truncate"
                :title="inputFile?.file?.name ?? inputFile.fileName"
                v-text="inputFile?.file?.name ?? inputFile.fileName"
            ></p>
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

                errors: {
                    type: Object,
                    default: () => {}
                }
            },

            data() {
                return {
                    inputFiles: [],
                }
            },

            mounted() {
                this.inputFiles = this.uploadedFiles;
            },

            methods: {
                add() {
                    let inputs = this.$refs[this.$.uid + '_fileInput'];

                    if (inputs.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(inputs.files).every(file => file.type.includes('application/pdf'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.media.files.not-allowed-error')"
                        });

                        return;
                    }

                    inputs.files.forEach((file, index) => {
                        this.inputFiles.push({
                            id: 'file_' + this.inputFiles.length,
                            url: '',
                            file: file
                        });
                    });
                },

                remove(file) {
                    let index = this.inputFiles.indexOf(file);

                    this.inputFiles.splice(index, 1);
                },
                change(file) {
                    this.inputFiles[0].file = file;
                },
            }
        });

        app.component('v-media-files-item', {
            template: '#v-media-files-item-template',

            props: ['index', 'inputFile', 'name', 'width', 'height'],

            data() {
                return {
                    isPlaying: false
                }
            },

            mounted() {
                if (this.inputFile.file instanceof File) {
                    this.setFile(this.inputFile.file);

                    this.readFile(this.inputFile.file);
                }
            },

            methods: {
                edit() {
                    let inputs = this.$refs[this.$.uid + '_fileInput_' + this.index];

                    if (inputs.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(inputs.files).every(file => file.type.includes('application/pdf'));

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
