@props([
'name' => 'images',
'allowMultiple' => false,
'showPlaceholders' => false,
'uploadedImages' => [],
'width' => '120px',
'height' => '120px'
])

<v-media-gallery
    name="{{ $name }}"
    v-bind:allow-multiple="{{ $allowMultiple ? true : false }}"
    v-bind:show-placeholders="{{ $showPlaceholders ? 'true' : 'false' }}"
    :uploaded-images='{{ json_encode($uploadedImages) }}'
    width="{{ $width }}"
    height="{{ $height }}"
    :errors="errors">
    <x-admin::shimmer.image class="w-[110px] h-[110px] rounded" />
</v-media-gallery>

@pushOnce('scripts')
<script type="text/x-template" id="v-media-gallery-template">
    <!-- Panel Content -->
    <div class="grid">
        <div class="flex flex-wrap gap-1">
            <!-- Upload Image Button -->
            <template v-if="allowMultiple || images.length == 0">
                <!-- AI Image Generation Button -->
                <label
                    class="grid justify-items-center items-center w-full h-[120px] max-w-[120px] min-w-[110px] max-h-[120px] min-h-[110px] border border-dashed border-violet-300 rounded cursor-pointer transition-all hover:border-violet-700  dark:invert dark:mix-blend-exclusion"
                    :style="{'max-width': this.width, 'max-height': this.height}"
                    v-if="ai.enabled"
                    @click="resetAIModal(); $refs.magicAIImageModal.open()">
                    <div class="flex flex-col items-center">
                        <span class="icon-magic text-2xl text-violet-700"></span>

                        <p class="grid text-sm text-violet-700 font-semibold text-center">
                            @lang('admin::app.components.media.images.ai-add-image-btn')

                            <span class="text-xs">
                                @lang('admin::app.components.media.images.ai-btn-info')
                            </span>

                        </p>
                    </div>
                </label>

                <!-- Upload Image Button -->
                <label
                    class="grid justify-items-center items-center w-full h-[120px] max-w-[210px] max-h-[120px] border border-dashed dark:border-gray-300 rounded cursor-pointer transition-all hover:border-gray-400 border-gray-300"
                    :style="{'max-width': this.width, 'max-height': this.height}"
                    :for="$.uid + '_imageInput'">
                    <div class="flex flex-col items-center">
                        <span class="icon-image text-2xl"></span>

                        <p class="grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center">
                            @lang('admin::app.components.media.images.upload-media-btn')
                            <span class="text-xs">
                                @lang('admin::app.components.media.images.allowed-types')
                            </span>
                            <span class="text-xs">
                                @lang('admin::app.components.media.videos.allowed-types')
                            </span>
                        </p>

                        <input
                            type="file"
                            class="hidden"
                            :id="$.uid + '_imageInput'"
                            accept="image/*, video/*"
                            :multiple="allowMultiple"
                            :ref="$.uid + '_imageInput'"
                            @change="add" />
                    </div>
                </label>
            </template>

            <!-- Uploaded Images -->
            <draggable
                class="flex flex-wrap gap-1 mt-2"
                ghost-class="draggable-ghost"
                v-bind="{animation: 200}"
                :list="images"
                item-key="id">
                <template #item="{ element, index }">
                    <v-media-gallery-item
                        :allowMultiple="{{ $allowMultiple ? true : false }}"
                        :name="name"
                        :index="index"
                        :image="element"
                        :width="width"
                        :height="height"
                        @onRemove="remove($event)">
                    </v-media-gallery-item>
                </template>
            </draggable>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div">
                <form @submit="handleSubmit($event, generate)">
                    <!-- AI Content Generation Modal -->
                    <x-admin::modal ref="magicAIImageModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <template v-if="! ai.images.length">
                                <p class="flex gap-2.5 items-center text-lg text-gray-800 dark:text-white font-bold">
                                    <span class="icon-magic text-2xl text-gray-800"></span>

                                    @lang('admin::app.components.media.images.ai-generation.title')
                                </p>
                            </template>

                            <template v-else>
                                <p class="text-lg text-gray-800 truncate dark:text-white font-bold">
                                    <span
                                        class="align-middle mr-1 icon-arrow-right text-2xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md"
                                        @click="ai.images = []"></span>

                                    <span class="align-middle">
                                        @{{ ai.prompt }}
                                    </span>
                                </p>
                            </template>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <div v-show="! ai.images.length">
                                    <!-- Prompt -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.prompt')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="textarea"
                                            name="prompt"
                                            rules="required"
                                            v-model="ai.prompt"
                                            :label="trans('admin::app.components.media.images.ai-generation.prompt')" />

                                        <x-admin::form.control-group.error control-name="prompt" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.model')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="model"
                                            rules="required"
                                            v-model="ai.model"
                                            :label="trans('admin::app.components.media.images.ai-generation.model')">
                                            <option value="dall-e-2">
                                                @lang('admin::app.components.media.images.ai-generation.dall-e-2')
                                            </option>

                                            <option value="dall-e-3">
                                                @lang('admin::app.components.media.images.ai-generation.dall-e-3')
                                            </option>
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="model" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if="ai.model == 'dall-e-2'">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.number-of-images')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="n"
                                            rules="required"
                                            v-model="ai.n"
                                            :label="trans('admin::app.components.media.images.ai-generation.number-of-images')" />

                                        <x-admin::form.control-group.error control-name="n" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.size')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="size"
                                            rules="required"
                                            v-model="ai.size"
                                            :label="trans('admin::app.components.media.images.ai-generation.size')">
                                            <option value="1024x1024">
                                                @lang('admin::app.components.media.images.ai-generation.1024x1024')
                                            </option>

                                            <option value="1024x1792">
                                                @lang('admin::app.components.media.images.ai-generation.1024x1792')
                                            </option>

                                            <option value="1792x1024">
                                                @lang('admin::app.components.media.images.ai-generation.1792x1024')
                                            </option>
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="size" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if="ai.model == 'dall-e-3'">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.quality')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="quality"
                                            rules="required"
                                            v-model="ai.quality"
                                            :label="trans('admin::app.components.media.images.ai-generation.quality')">
                                            <option value="standard">
                                                @lang('admin::app.components.media.images.ai-generation.standard')
                                            </option>

                                            <option value="hd">
                                                @lang('admin::app.components.media.images.ai-generation.hd')
                                            </option>
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="quality" />
                                    </x-admin::form.control-group>
                                </div>

                                <div v-show="ai.images.length">
                                    <div class="grid grid-cols-4 gap-5">
                                        <div
                                            class="grid justify-items-center min-w-[120px] max-h-[120px] relative border-[3px] border-transparent rounded overflow-hidden transition-all hover:opacity-80 cursor-pointer"
                                            :class="{'!border-violet-700 ': image.selected}"
                                            v-for="image in ai.images"
                                            @click="image.selected = ! image.selected">
                                            <!-- Image Preview -->
                                            <img
                                                class="w-[120px] h-[120px]"
                                                :src="image.url" />
                                        </div>
                                    </div>
                                </div>
                                </x-slot>

                                <!-- Modal Footer -->
                                <x-slot:footer>
                                    <div class="flex gap-x-2.5 items-center">
                                        <template v-if="! ai.images.length">
                                            <button class="secondary-button">
                                                <!-- Spinner -->
                                                <template v-if="isLoading">
                                                    <img
                                                        class="animate-spin h-5 w-5 text-violet-700"
                                                        src="{{ unopim_asset('images/spinner.svg') }}" />

                                                    @lang('admin::app.components.media.images.ai-generation.generating')
                                                </template>

                                                <template v-else>
                                                    <span class="icon-magic  text-violet-700"></span>

                                                    @lang('admin::app.components.media.images.ai-generation.generate')
                                                </template>
                                            </button>
                                        </template>

                                        <template v-else>
                                            <button class="secondary-button">
                                                <!-- Spinner -->
                                                <template v-if="isLoading">
                                                    <img
                                                        class="animate-spin h-5 w-5 text-violet-700"
                                                        src="{{ unopim_asset('images/spinner.svg') }}" />

                                                    @lang('admin::app.components.media.images.ai-generation.regenerating')
                                                </template>

                                                <template v-else>
                                                    <span class="icon-magic text-2xl text-violet-700"></span>

                                                    @lang('admin::app.components.media.images.ai-generation.regenerate')
                                                </template>
                                            </button>

                                            <button
                                                type="button"
                                                class="primary-button"
                                                :disabled="! selectedAIImages.length"
                                                @click="apply">
                                                @lang('admin::app.components.media.images.ai-generation.apply')
                                            </button>
                                        </template>
                                    </div>
                                    </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </div>
</script>

<script type="text/x-template" id="v-media-gallery-item-template">
    <div v-if="image.type.startsWith('image/')" 
     class="grid justify-items-center max-w-[210px] min-w-[210px] relative border border-dashed border-gray-300 dark:border-cherry-800 rounded transition-all hover:border-gray-400 group cursor-grab"  
     :style="{'width': this.width}">
    
    <!-- Image Preview -->
        <img
            :src="image.url" :type="image.type"
            class="w-[210px] h-[120px] object-cover"
        />

        <div class="flex flex-col justify-between invisible w-full max-h-[120px] p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
            <!-- Image Name -->
            <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>
            <!-- Actions -->
            <div class="flex justify-between">
                <span
                    class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                    @click="remove"
                ></span>

                <label
                    class="icon-edit text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                    :for="$.uid + '_imageInput_' + index"
                ></label>

                <input type="hidden" :name="name + '[' + image.id + ']'" v-if="allowMultiple && ! image.is_new && image.value" :value="image.value"/>

                <input type="hidden" :name="name" v-if="! allowMultiple && ! image.is_new && image.value" :value="image.value"/>

                <input
                    type="file"
                    :name="name + '[]'"
                    class="hidden"
                    accept="image/*"
                    :id="$.uid + '_imageInput_' + index"
                    :ref="$.uid + '_imageInput_' + index"
                    @change="edit"
                />
            </div>
        </div>
         <!-- Image Name -->
        <label class="mt-1 grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center break-all" :key="image.url">
                @{{ image.name }}
        </label>
    </div>

    <div v-else-if="image.type.startsWith('video/')" 
        class="grid justify-items-center max-w-[210px] min-w-[210px] relative border border-dashed border-gray-300 dark:border-cherry-800 rounded transition-all hover:border-gray-400 group cursor-grab">
        <!-- Video Preview -->
        <video
            class="w-[210px] h-[120px] object-cover"
            ref="videoPreview"
            v-if="image.url.length > 0"
            :key="image.url"
        >
            <source :src="image.url" type="video/mp4">
        </video>

        <div class="flex flex-col justify-between invisible w-full max-h-[120px] p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
            <!-- Video Name -->
            <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>

            <!-- Actions -->
            <div class="flex justify-between">
                <!-- Remove Button -->
                <span
                    class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                    @click="remove"
                ></span>

                <!-- Play Pause Button -->
                <span
                    class="text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                    :class="[isPlaying ? 'icon-pause': 'icon-play']"
                    @click="playPause"
                ></span>

                <!-- Full-Screen Button -->
                <span 
                    class="cursor-pointer p-1.5 hover:bg-violet-100 dark:hover:bg-gray-800 rounded-md"
                    @click="showFullScreen"
                >
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-6 w-6 text-gray-600 dark:text-gray-300" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor" 
                        stroke-width="2"
                    >
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            d="M4 8V5a1 1 0 011-1h3m8 0h3a1 1 0 011 1v3m0 8v3a1 1 0 01-1 1h-3m-8 0H5a1 1 0 01-1-1v-3"
                        />
                    </svg>
                </span>

                <!-- Edit Button -->
                <label
                    class="icon-edit text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                    :for="$.uid + '_imageInput_' + index"
                ></label>

                <input type="hidden" :name="name + '[' + image.id + ']'" v-if="allowMultiple && ! image.is_new && image.value" :value="image.value"/>

                <input type="hidden" :name="name" v-if="! allowMultiple && ! image.is_new && image.value" :value="image.value"/>

                <input
                    type="file"
                    :name="name + '[]'"
                    class="hidden"
                    accept="video/*"
                    :id="$.uid + '_imageInput_' + index"
                    :ref="$.uid + '_imageInput_' + index"
                    @change="edit"
                />
            </div>
        </div>
        <div v-if="isFullScreen"
                ref="fullScreenContainer"
                style="display: flex; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); z-index: 1000;"
                @keydown.esc="closeFullScreen"
                tabindex="0"
            >
                <div style="position: relative; width: 100%; height: 100%; max-width: 90%; max-height: 90%; display: flex; justify-content: center; align-items: center;">
                    <!-- Full-Screen Video -->
                    <video style="max-width: 100%; max-height: 100%;" controls autoplay>
                        <source :src="image.url" type="video/mp4">
                    </video>
                        <!-- Close Button -->
                    <button
                        style="position: absolute; top: 0.5rem; right: 0.5rem; background-color: rgba(0, 0, 0, 0.7); color: white; border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; transition: background-color 0.3s ease;"
                        @click="closeFullScreen"
                        >
                        ✕
                    </button>
                </div>
        </div>
         <!-- Image Name -->
        <label class="mt-1 text-xs text-gray-600 dark:text-gray-300 font-semibold text-center break-all" :key="image.url">
                @{{ image.name }}
        </label>
    </div>
</script>

<script type="module">
    app.component('v-media-gallery', {
        template: '#v-media-gallery-template',

        props: {
            name: {
                type: String,
                default: 'images',
            },

            allowMultiple: {
                type: Boolean,
                default: false,
            },

            showPlaceholders: {
                type: Boolean,
                default: false,
            },

            uploadedImages: {
                type: Array,
                default: () => []
            },

            width: {
                type: String,
                default: '120px'
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
                images: [],

                placeholders: [],

                isLoading: false,

                ai: {
                    enabled: Boolean("{{ core()->getConfigData('general.magic_ai.settings.enabled') && core()->getConfigData('general.magic_ai.image_generation.enabled') }}"),

                    prompt: null,

                    model: 'dall-e-2',

                    n: 1,

                    size: '1024x1024',

                    quality: 'standard',

                    images: [],
                },
            }
        },

        computed: {
            selectedAIImages() {
                return this.ai.images.filter(image => image.selected);
            }
        },

        mounted() {
            this.images = this.uploadedImages;
        },

        methods: {
            add() {
                let imageInput = this.$refs[this.$.uid + '_imageInput'];

                if (imageInput.files == undefined) {
                    return;
                }

                const validFiles = Array.from(imageInput.files).every(file =>
                    file.type.includes('image/') || file.type.includes('video/')
                );

                if (!validFiles) {
                    this.$emitter.emit('add-flash', {
                        type: 'warning',
                        message: "@lang('admin::app.components.media.images.not-allowed-error')"
                    });

                    return;
                }

                Array.from(imageInput.files).forEach((file, index) => {
                    this.images.push({
                        id: 'image_' + this.images.length,
                        url: '',
                        file: file,
                        type: file.type,
                        name: file.name
                    });
                });
            },

            remove(image) {
                let index = this.images.indexOf(image);

                this.images.splice(index, 1);
            },

            generate(params, {
                setErrors
            }) {
                this.isLoading = true;

                let self = this;

                this.$axios.post("{{ route('admin.magic_ai.image') }}", params)
                    .then(response => {
                        this.isLoading = false;

                        self.ai.images = response.data.images;
                    })
                    .catch(error => {
                        this.isLoading = false;

                        if (error.response.status == 422) {
                            setErrors(error.response.data.errors);
                        } else {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response.data.message
                            });
                        }
                    });
            },

            apply() {
                this.selectedAIImages.forEach((image, index) => {
                    this.images.push({
                        id: 'image_' + this.images.length,
                        url: '',
                        file: this.getBase64ToFile(image.url, 'temp.png')
                    });
                });

                this.$refs.magicAIImageModal.close();
            },

            getBase64ToFile(base64, filename) {
                var arr = base64.split(','),
                    mime = arr[0].match(/:(.*?);/)[1],
                    bstr = atob(arr[arr.length - 1]),
                    n = bstr.length,
                    u8arr = new Uint8Array(n);

                while (n--) {
                    u8arr[n] = bstr.charCodeAt(n);
                }

                return new File([u8arr], filename, {
                    type: mime
                });
            },

            resetAIModal() {
                this.ai = {
                    enabled: Boolean("{{ core()->getConfigData('general.magic_ai.settings.enabled') && core()->getConfigData('general.magic_ai.image_generation.enabled') }}"),

                    prompt: null,

                    model: 'dall-e-2',

                    n: 1,

                    size: '1024x1024',

                    quality: 'standard',

                    images: [],
                };
            }
        }
    });

    app.component('v-media-gallery-item', {
        template: '#v-media-gallery-item-template',

        props: ['allowMultiple', 'index', 'image', 'name', 'width', 'height'],

        data() {
            return {
                isPlaying: false,
                isFullScreen: false,
            }
        },

        mounted() {
            if (this.image.file instanceof File) {
                this.setFile(this.image.file);

                this.readFile(this.image.file);
            }
        },

        methods: {
            edit() {
                let imageInput = this.$refs[this.$.uid + '_imageInput_' + this.index];

                if (imageInput.files == undefined) {
                    return;
                }

                const validFiles = Array.from(imageInput.files).every(file =>
                    file.type.includes('image/') || file.type.includes('video/')
                );

                if (!validFiles) {
                    this.$emitter.emit('add-flash', {
                        type: 'warning',
                        message: "@lang('admin::app.components.media.images.not-allowed-error')"
                    });

                    return;
                }

                this.setFile(imageInput.files[0]);

                this.readFile(imageInput.files[0]);
            },

            showFullScreen() {
                this.isFullScreen = true;
                this.$nextTick(() => {
                    this.$refs.fullScreenContainer.focus();
                });
            },

            closeFullScreen() {
                this.isFullScreen = false;
            },

            remove() {
                this.$emit('onRemove', this.image)
            },

            setFile(file) {
                this.image.is_new = 1;

                const dataTransfer = new DataTransfer();

                dataTransfer.items.add(file);

                this.$refs[this.$.uid + '_imageInput_' + this.index].files = dataTransfer.files;
            },

            readFile(file) {
                let reader = new FileReader();

                reader.onload = (e) => {
                    this.image.url = e.target.result;
                    this.image.name = file.name;
                }

                reader.readAsDataURL(file);
            },

            playPause() {
                let videoPreview = this.$refs.videoPreview;

                if (videoPreview.paused == true) {
                    this.isPlaying = true;

                    videoPreview.play();
                } else {
                    this.isPlaying = false;

                    videoPreview.pause();
                }
            }
        }
    });
</script>
@endPushOnce