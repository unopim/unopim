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
            <div class="flex gap-1">
                <!-- Upload File Button -->

                <label
                    class="grid justify-items-center items-center w-full h-[120px] max-w-[210px] max-h-[120px] border border-dashed dark:border-gray-300 rounded cursor-pointer transition-all hover:border-gray-400"
                    :class="[errors['inputFiles.files[0]'] ? 'border border-red-500' : 'border-gray-300']"
                    :for="$.uid + '_fileInput'"
                    v-if="0 == inputFiles.length"
                >
                    <div class="flex flex-col items-center">
                        <span class="icon-folder text-2xl"></span>

                        <p class="grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center">
                            @lang('admin::app.components.media.files.add-file-btn')
                            
                            <span class="text-xs">
                                @lang('admin::app.components.media.files.allowed-types')
                            </span>
                        </p>

                        <input
                            type="file"
                            class="hidden"
                            :id="$.uid + '_fileInput'"
                            accept="application/pdf"
                            :ref="$.uid + '_fileInput'"
                            @change="add"
                        />
                    </div>
                </label>

                <!-- Uploaded Files -->
                <draggable
                    class="flex gap-1"
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
        <div class="grid justify-items-center h-[120px] max-w-[210px] min-w-[210px] max-h-[120px] relative border border-dashed border-gray-300 dark:border-cherry-800 rounded overflow-hidden transition-all hover:border-gray-400 group">
            <!-- File Name -->
            <div class="flex flex-col justify-between visible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:invisible">
                <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all" v-text="inputFile?.file?.name ?? inputFile.fileName"></p>
            </div>
            <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
                <!-- File Name -->
                <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all" v-text="inputFile?.file?.name ?? inputFile.fileName"></p>

                <!-- Actions -->
                <div class="flex justify-between">
                    <!-- Remove Button -->
                    <span
                        class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                        @click="remove"
                    ></span>

                    <!-- Download Button -->

                    <a :href="inputFile.url" target="_blank" class="flex items-center cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800">
                        <span
                            class="text-2xl p-1.5 rounded-md icon-down-stat"
                        ></span>
                    </a>

                    <!-- Edit Button -->
                    <label
                        class="icon-edit text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                        :for="$.uid + '_fileInput_' + index"
                    ></label>

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
