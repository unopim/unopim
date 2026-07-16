@props([
    'name'             => 'images',
    'allowMultiple'    => false,
    'showPlaceholders' => false,
    'uploadedImages'   => [],
    'width'            => '120px',
    'height'           => '120px'
])

@php
    $dynamicUploadedImages = $attributes->get('::uploaded-images') ?? $attributes->get(':uploaded-images');
    $rootAttributes = $attributes->except(['::uploaded-images', ':uploaded-images', 'uploaded-images']);
@endphp

<v-media-images
    {{ $rootAttributes }}
    name="{{ $name }}"
    v-bind:allow-multiple="{{ $allowMultiple ? 'true' : 'false' }}"
    v-bind:show-placeholders="{{ $showPlaceholders ? 'true' : 'false' }}"
    @if ($dynamicUploadedImages)
        :uploaded-images="{{ $dynamicUploadedImages }}"
    @else
        :uploaded-images='{{ json_encode($uploadedImages) }}'
    @endif
    width="{{ $width }}"
    height="{{ $height }}"
    :errors="errors"
>
    <x-admin::shimmer.image class="w-[110px] h-[110px] rounded" />
</v-media-images>

@pushOnce('scripts')
    <script type="text/x-template" id="v-media-images-template">
        <!-- Panel Content -->
        <div class="grid">
            <div class="flex flex-wrap gap-1">
                <!-- Upload Image Button -->
                <template v-if="allowMultiple || images.length == 0">
                    <!-- AI Image Generation Button -->
                    <label
                        class="group flex flex-col justify-center items-center rounded border border-dashed border-gray-300 bg-white text-center cursor-pointer transition-colors hover:border-primary-500 hover:bg-gray-50 dark:border-cherry-700 dark:bg-cherry-900 dark:hover:border-primary-400 dark:hover:bg-cherry-800"
                        :class="isDragging ? '!border-primary-500 !bg-primary-50 dark:!bg-cherry-800 shadow-md' : ''"
                        :style="{ width: width, height: height, minWidth: '110px', minHeight: '110px' }"
                        v-if="ai.enabled"
                        aria-label="@lang('admin::app.components.media.images.ai-add-image-btn')"
                        @click="resetAIModal(); $refs.magicAIImageModal.open()"
                        @dragover.prevent="isDragging = true"
                        @dragenter.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                    >
                        <div class="flex flex-col items-center px-2">
                            <span class="icon-magic flex h-9 w-9 items-center justify-center rounded border border-primary-200 bg-gray-50 text-2xl text-primary-700 transition-colors group-hover:border-primary-300 dark:border-cherry-700 dark:bg-cherry-800"></span>

                            <p class="mt-2 grid text-sm font-semibold leading-5 text-gray-800 text-center dark:text-white">
                                @lang('admin::app.components.media.images.ai-add-image-btn')
                                
                                <span class="mt-1 text-xs font-normal leading-4 text-gray-500 dark:text-gray-400">
                                    @lang('admin::app.components.media.images.ai-btn-info')
                                </span>
                            </p>
                        </div>
                    </label>

                    <!-- Upload Image Button -->
                    <label
                        class="group flex flex-col justify-center items-center rounded border border-dashed border-gray-300 bg-white text-center cursor-pointer transition-colors hover:border-primary-500 hover:bg-gray-50 dark:border-cherry-700 dark:bg-cherry-900 dark:hover:border-primary-400 dark:hover:bg-cherry-800"
                        :class="isDragging ? '!border-primary-500 !bg-primary-50 dark:!bg-cherry-800 shadow-md' : ''"
                        :style="{ width: width, height: height, minWidth: '110px', minHeight: '110px' }"
                        :for="$.uid + '_imageInput'"
                        aria-label="@lang('admin::app.components.media.images.add-image-btn')"
                        @dragover.prevent="isDragging = true"
                        @dragenter.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                    >
                        <div class="flex flex-col items-center px-2">
                            <span class="icon-image flex h-9 w-9 items-center justify-center rounded border border-gray-200 bg-gray-50 text-2xl text-gray-500 transition-colors group-hover:border-violet-200 group-hover:text-violet-600 dark:border-cherry-700 dark:bg-cherry-800 dark:text-gray-300"></span>

                            <p class="mt-2 grid text-sm font-semibold leading-5 text-gray-800 text-center dark:text-white">
                                @lang('admin::app.components.media.images.add-image-btn')

                                <span class="mt-1 text-xs font-normal leading-4 text-gray-500 dark:text-gray-400">
                                    @lang('admin::app.components.media.images.drag-drop-hint')
                                </span>

                                <span class="mt-1 text-[11px] font-normal leading-4 text-gray-400 dark:text-gray-500">
                                    @lang('admin::app.components.media.images.allowed-types')
                                </span>
                            </p>

                            <input
                                type="file"
                                class="hidden"
                                :id="$.uid + '_imageInput'"
                                accept="image/*"
                                :multiple="allowMultiple"
                                :ref="$.uid + '_imageInput'"
                                @change="add"
                            />
                        </div>
                    </label>
                </template>

                <!-- Uploaded Images -->
                <draggable
                    class="flex flex-wrap gap-1"
                    ghost-class="draggable-ghost"
                    v-bind="{animation: 200}"
                    :list="images"
                    item-key="id"
                >
                    <template #item="{ element, index }">
                        <v-media-image-item
                            :name="name"
                            :index="index"
                            :image="element"
                            :width="width"
                            :height="height"
                            @onRemove="remove($event)"
                        >
                        </v-media-image-item>
                    </template>
                </draggable>

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
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
                                            class="align-middle mr-1 icon-arrow-right text-2xl cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 hover:rounded-md"
                                            @click="ai.images = []"
                                        ></span>

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
                                            :label="trans('admin::app.components.media.images.ai-generation.prompt')"
                                        />

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
                                            :label="trans('admin::app.components.media.images.ai-generation.model')"
                                        >
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
                                            :label="trans('admin::app.components.media.images.ai-generation.number-of-images')"
                                        />

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
                                            :label="trans('admin::app.components.media.images.ai-generation.size')"
                                        >
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
                                            :label="trans('admin::app.components.media.images.ai-generation.quality')"
                                        >
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
                                            :class="{'!border-primary-700 ': image.selected}"
                                            v-for="image in ai.images"
                                            :key="image.url"
                                            @click="image.selected = ! image.selected"
                                        >
                                            <!-- Image Preview -->
                                            <img
                                                class="w-[120px] h-[120px]"
                                                :src="image.url"
                                            />
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
                                                    class="animate-spin h-5 w-5 text-primary-700"
                                                    src="{{ unopim_asset('images/spinner.svg') }}"
                                                />

                                                @lang('admin::app.components.media.images.ai-generation.generating')
                                            </template>

                                            <template v-else>
                                                <span class="icon-magic  text-primary-700"></span>
                                                
                                                @lang('admin::app.components.media.images.ai-generation.generate')
                                            </template>
                                        </button>
                                    </template>

                                    <template v-else>
                                        <button class="secondary-button">
                                            <!-- Spinner -->
                                            <template v-if="isLoading">
                                                <img
                                                    class="animate-spin h-5 w-5 text-primary-700"
                                                    src="{{ unopim_asset('images/spinner.svg') }}"
                                                />

                                                @lang('admin::app.components.media.images.ai-generation.regenerating')
                                            </template>

                                            <template v-else>
                                                <span class="icon-magic text-2xl text-primary-700"></span>
                                                
                                                @lang('admin::app.components.media.images.ai-generation.regenerate')
                                            </template>
                                        </button>

                                        <button
                                            type="button"
                                            class="primary-button"
                                            :disabled="! selectedAIImages.length"
                                            @click="apply"
                                        >
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

    <script type="text/x-template" id="v-media-image-item-template">
        <div class="grid justify-items-center min-w-[120px] max-h-[120px] relative rounded overflow-hidden transition-all hover:border-gray-400 group" :style="{'width': this.width, 'height': this.height}">
            <!-- Image Preview -->
            <img
                :src="image.url"
                class="w-full h-full object-cover object-top"
            />

            <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
                <!-- Image Name -->
                <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>

                <!-- Actions -->
                <div class="flex justify-between">
                    <span
                        class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer hover:bg-primary-100 dark:hover:bg-gray-800"
                        @click="remove"
                    ></span>

                    <label
                        class="icon-edit text-2xl p-1.5 rounded-md cursor-pointer hover:bg-primary-100 dark:hover:bg-gray-800"
                        :for="$.uid + '_imageInput_' + index"
                    ></label>

                    <input type="hidden" :name="name + '[' + image.id + ']'" v-if="! image.is_new"/>

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
        </div>
    </script>

    <script type="module">
        app.component('v-media-images', {
            template: '#v-media-images-template',

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

                    isDragging: false,

                    placeholders: [
                    ],

                    isLoading: false,

                    ai: {
                        enabled: @json(!! core()->getConfigData('general.magic_ai.image_generation.enabled') && bouncer()->hasPermission('ai-agent')),

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
                onDrop(event) {
                    this.isDragging = false;

                    let files = event.dataTransfer ? event.dataTransfer.files : null;

                    if (! files || ! files.length) {
                        return;
                    }

                    let selectedFiles = Array.from(this.allowMultiple ? files : [files[0]]);

                    const validFiles = selectedFiles.every(file => file.type.startsWith('image/'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @json(trans('admin::app.components.media.images.not-allowed-error'))
                        });

                        return;
                    }

                    selectedFiles.forEach((file) => {
                        this.images.push({
                            id: 'image_' + this.images.length,
                            url: '',
                            file: file
                        });
                    });
                },

                add() {
                    let imageInput = this.$refs[this.$.uid + '_imageInput'];

                    if (imageInput.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(imageInput.files).every(file => file.type.startsWith('image/'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @json(trans('admin::app.components.media.images.not-allowed-error'))
                        });

                        return;
                    }

                    Array.from(imageInput.files).forEach((file, index) => {
                        this.images.push({
                            id: 'image_' + this.images.length,
                            url: '',
                            file: file
                        });
                    });


                },

                remove(image) {
                    let index = this.images.indexOf(image);

                    this.images.splice(index, 1);
                },

                generate(params, { setErrors }) {
                    this.isLoading = true;

                    let self = this;

                    this.$axios.post("{{ route('admin.magic_ai.image') }}", params)
                        .then(response => {
                            this.isLoading = false;

                            self.ai.images = response.data.images;

                            if (self.ai.images.length === 1) {
                                self.ai.images[0].selected = true;
                            }
                        })
                        .catch(error => {
                            this.isLoading = false;

                            if (error.response.status == 422) {
                                setErrors(error.response.data.errors);
                            } else {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
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

                    return new File([u8arr], filename, {type:mime});
                },

                resetAIModal() {
                    this.ai = {
                        enabled: @json(!! core()->getConfigData('general.magic_ai.image_generation.enabled') && bouncer()->hasPermission('ai-agent')),

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

        app.component('v-media-image-item', {
            template: '#v-media-image-item-template',

            props: ['index', 'image', 'name', 'width', 'height'],

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

                    const validFiles = Array.from(imageInput.files).every(file => file.type.startsWith('image/'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @json(trans('admin::app.components.media.images.not-allowed-error'))
                        });

                        return;
                    }

                    this.setFile(imageInput.files[0]);

                    this.readFile(imageInput.files[0]);
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
                    }

                    reader.readAsDataURL(file);
                },
            }
        });
    </script>
@endPushOnce
