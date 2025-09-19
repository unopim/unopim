@props([
    'name'             => 'images',
    'allowMultiple'    => false,
    'showPlaceholders' => false,
    'uploadedImages'   => [],
    'width'            => '120px',
    'height'           => '120px'
])

<v-media-gallery
    name="{{ $name }}"
    v-bind:allow-multiple="{{ $allowMultiple ? true : false }}"
    v-bind:show-placeholders="{{ $showPlaceholders ? 'true' : 'false' }}"
    :uploaded-images='{{ json_encode($uploadedImages) }}'
    width="{{ $width }}"
    height="{{ $height }}"
    :errors="errors"
>
    <x-admin::shimmer.image class="w-[110px] h-[110px] rounded" />
</v-media-gallery>

@pushOnce('scripts')
    <script type="text/x-template" id="v-media-gallery-template">
        <!-- Panel Content -->
        <div class="grid">
            <div class="flex flex-wrap gap-1">
                <div class="flex flex-col w-full max-w-[210px]">
                    <!-- AI Image Generation -->
                    <label
                        class="grid justify-items-center items-center w-full h-[120px] max-w-[210px] max-h-[120px] border border-dashed dark:border-gray-300 rounded cursor-pointer transition-all hover:border-gray-400 border-gray-300"
                        :style="{'max-width': this.width, 'max-height': this.height}"
                        :for="$.uid + '_imageInput'"
                        v-if="ai.enabled"
                        @click="resetAIModal(); $refs.choiceImageModal.open()"
                    >
                        <div class="flex flex-col items-center">
                            <span class="icon-image text-2xl"></span>

                            <p class="grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center">
                                @lang('admin::app.components.media.images.upload-media-btn')

                                <span class="text-xs mt-1 text-gray-600 dark:text-gray-300 font-medium text-center">
                                    @lang('admin::app.components.media.images.allowed-types')
                                </span>
                                <span class="text-xs text-gray-600 dark:text-gray-300 font-medium text-center">
                                    @lang('admin::app.components.media.videos.allowed-types')
                                </span>
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Uploaded Images -->
                <draggable
                    class="flex flex-wrap gap-1"
                    ghost-class="draggable-ghost"
                    v-bind="{animation: 200}"
                    :list="images"
                    item-key="id"
                    handle=".icon-drag"
                >
                    <template #item="{ element, index }">
                        <v-media-gallery-item
                            :allowMultiple="{{ $allowMultiple ? true : false }}"
                            :name="name"
                            :index="index"
                            :image="element"
                            :width="width"
                            :height="height"
                            @onRemove="remove($event)"
                        >
                        </v-media-gallery-item>
                    </template>
                </draggable>

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form>
                        <!-- AI Content Generation Modal -->
                        <x-admin::modal ref="choiceImageModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <p class="grid text-base text-gray-800 dark:text-gray-300 font-semibold text-center">
                                    @lang('admin::app.components.media.images.add-image-btn')
                                </p>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <div class="mb-4">
                                    <label
                                        class="cursor-pointer mb-2"
                                        @click="resetAIModal(); toggleImageAIModal(); $refs.choiceImageModal.close()"
                                    >
                                        <div class="flex flex-col">
                                            <div class="flex gap-1 p-3 border rounded-md text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" class="dark:invert">
                                                    <g clip-path="url(#clip0_3148_2242)"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#27272A"/>
                                                        <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#27272A"/>
                                                        <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#27272A"/>
                                                        <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#27272A"/>
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_3148_2242">
                                                            <rect width="24" height="24"/>
                                                        </clipPath>
                                                    </defs>
                                                </svg>

                                                <span class="text-gray-600 dark:text-slate-50 text-sm font-semibold">@lang('admin::app.components.media.images.generate-with-ai')</span>
                                            </div>

                                        </div>
                                    </label>
                                </div>

                                <div class="mb-4">
                                    <label
                                        class="cursor-pointer mb-2"
                                        :for="$.uid + '_imageInput_ai'"
                                    >
                                        <div class="flex flex-col">
                                            <div class="flex gap-1 p-3 border rounded-md text-sm">
                                                <span class="icon-export text-xl"></span>

                                                <span class="text-gray-600 dark:text-slate-50 text-sm font-semibold">@lang('admin::app.components.media.images.upload-from-device')</span>
                                            </div>

                                        </div>
                                        <input
                                            type="file"
                                            class="hidden"
                                            :id="$.uid + '_imageInput_ai'"
                                            accept="image/*"
                                            :multiple="allowMultiple"
                                            :ref="$.uid + '_imageInput'"
                                            @change="add"
                                        />
                                    </label>
                                </div>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <div class="flex gap-x-2.5 items-center">
                                    <a href="#" @click="$refs.choiceImageModal.close()" class="secondary-button">
                                        @lang('admin::app.components.media.images.cancel')
                                    </a>
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>

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
                                            class="align-middle mr-1 icon-arrow-right text-2xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md"
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
                                    <!-- Model -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.model')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="model"
                                            rules="required"
                                            ::value="ai.model"
                                            v-model="ai.model"
                                            ::options="aiModels"
                                            track-by="id"
                                            label-by="label"
                                            :label="trans('admin::app.components.media.images.ai-generation.model')"
                                        >
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="model" />
                                    </x-admin::form.control-group>
                                    <!-- Prompt -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.prompt')
                                        </x-admin::form.control-group.label>

                                        <div class="relative w-full">
                                            <x-admin::form.control-group.control
                                                type="textarea"
                                                class="h-[120px]"
                                                name="prompt"
                                                rules="required"
                                                v-model="ai.prompt"
                                                ref="imagePromptInput"
                                                :label="trans('admin::app.components.media.images.ai-generation.prompt')"
                                            />

                                            <!-- Icon inside textarea -->
                                            <div
                                                class="absolute bottom-2.5 left-1 text-gray-400 cursor-pointer text-2xl"
                                                @click="openSuggestions"
                                            >
                                                <span class="icon-at"></span>
                                            </div>
                                        </div>

                                        <x-admin::form.control-group.error control-name="prompt" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if="ai.model == 'dall-e-2'">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.number-of-images')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="n"
                                            rules="required|max_value:10|min_value:1"
                                            v-model="ai.n"
                                            :label="trans('admin::app.components.media.images.ai-generation.number-of-images')"
                                        />

                                        <x-admin::form.control-group.error control-name="n" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.size')
                                        </x-admin::form.control-group.label>


                                        @php
                                            $aiImageSizes = json_encode([
                                                    [
                                                        'label' => trans('admin::app.components.media.images.ai-generation.1024x1024'),
                                                        'value' => '1024x1024'
                                                    ], [
                                                        'label' => trans('admin::app.components.media.images.ai-generation.1024x1792'),
                                                        'value' => '1024x1792'
                                                    ], [
                                                        'label' => trans('admin::app.components.media.images.ai-generation.1792x1024'),
                                                        'value' => '1792x1024'
                                                    ]
                                                ]);
                                        @endphp

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="size"
                                            rules="required"
                                            v-model="ai.size"
                                            :options="$aiImageSizes"
                                            track-by="value"
                                            label-by="label"
                                            :label="trans('admin::app.components.media.images.ai-generation.size')"
                                        >
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="size" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if="ai.model == 'dall-e-3'">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.media.images.ai-generation.quality')
                                        </x-admin::form.control-group.label>
                                        @php
                                            $aiImageQualities = json_encode([
                                                    [
                                                        'label' => trans('admin::app.components.media.images.ai-generation.standard'),
                                                        'value' => 'standard'
                                                    ], [
                                                        'label' => trans('admin::app.components.media.images.ai-generation.hd'),
                                                        'value' => 'hd'
                                                    ]
                                                ]);
                                        @endphp
                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="quality"
                                            rules="required"
                                            v-model="ai.quality"
                                            :options="$aiImageQualities"
                                            track-by="value"
                                            label-by="label"
                                            :label="trans('admin::app.components.media.images.ai-generation.quality')"
                                        >
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
                                            @click="selectImage(image, allowMultiple)"
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
                                                    class="animate-spin h-5 w-5 text-violet-700"
                                                    src="{{ unopim_asset('images/spinner.svg') }}"
                                                />

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
                                                    src="{{ unopim_asset('images/spinner.svg') }}"
                                                />

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

    <script type="text/x-template" id="v-media-gallery-item-template">
        <div class="flex gap-1.6 max-w-max py-1.5 ltr:pr-1.5 rtl:pl-1.5 text-gray-600 dark:text-gray-300 group border border-dashed border-gray-300 rounded transition-all hover:border-gray-400 group-hover:visible">

            <i class="icon-drag text-4xl transition-all group-hover:text-gray-700 cursor-pointer"></i>

            <div
                v-if="image.type?.startsWith('image/')" 
                class="grid justify-items-center max-w-[210px] min-w-[210px] relative"
                :style="{ 'width': this.width }"
            >
                <!-- Image Preview -->
                <img
                    :src="image.url" :type="image.type"
                    class="w-[210px] h-[120px] object-cover"
                />
                <x-admin::modal ref="mediaPreviewModal" type="large">
                    <x-slot:header>
                            <p class="text-sm text-gray-800 dark:text-white font-bold"><span> @{{ getDisplayFileName(image.name) }} </span></p>
                    </x-slot>
                    <x-slot:content>
                        <div>
                            <img
                                :src="image.url"
                                class="w-full h-full object-cover object-top"
                            />
                        </div>
                    </x-slot>
                </x-admin::modal>

                <div class="flex flex-col justify-between invisible w-full max-h-[120px] p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
                    <!-- Image Name -->
                    <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>
                    <!-- Actions -->
                    <div class="flex justify-between">
                        <span
                            class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="remove"
                        ></span>

                        <span
                            class="icon-view text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="previewMedia"
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
                <label class="mt-1 grid text-xs text-gray-700 dark:text-gray-300 font-medium text-center break-all" :key="image.url">
                        @{{ getDisplayFileName(image.name) }}
                </label>
            </div>

            <div
                v-else-if="image.type.startsWith('video/')" 
                class="grid justify-items-center max-w-[210px] min-w-[210px] relative"
            >
                <!-- Video Preview -->
                <video
                    class="w-[210px] h-[120px] object-cover"
                    ref="videoPreview"
                    v-if="image.url.length > 0"
                    :key="image.url"
                >
                    <source :src="image.url" :type="image.type">
                </video>

                <x-admin::modal ref="mediaPreviewModal">
                    <x-slot:header>
                            <p class="text-sm text-gray-800 dark:text-white font-bold"><span> @{{ getDisplayFileName(image.name) }} </span></p>
                    </x-slot>
                    <x-slot:content>
                        <div >
                            <video class="w-full h-full" controls autoplay>
                                <source :src="image.url" type="video/mp4">
                            </video>
                        </div>
                    </x-slot>
                </x-admin::modal>

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

                        <!-- Full Screen Button -->
                        <span
                            class="icon-play text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="previewMedia"
                        ></span>

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
                <!-- Video Name -->
                <label class="mt-1 text-xs text-gray-700 dark:text-gray-300 font-medium text-center break-all" :key="image.url">
                        @{{ getDisplayFileName(image.name) }}
                </label>
            </div>
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

                    placeholders: [
                    ],

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

                    aiModels: [],
                    suggestionValues: [],
                    selectedModel: null,
                    resourceId: "{{ request()->id }}",
                    entityName: "{{ $attributes->get('entity-name', 'attribute') }}",
                }
            },

            computed: {
                selectedAIImages() {
                    return this.ai.images.filter(image => image.selected);
                }
            },

            watch: {
                'ai.model': function (newVal, oldVal) {
                    try {
                        this.ai.model = JSON.parse(newVal)?.id     // Return true if parsing succeeds
                    } catch (e) {}
                },

                'ai.size': function (newVal, oldVal) {
                    try {
                        this.ai.size = JSON.parse(newVal)?.value     // Return true if parsing succeeds
                    } catch (e) {}
                },

                'ai.quality': function (newVal, oldVal) {
                    try {
                        this.ai.quality = JSON.parse(newVal)?.value     // Return true if parsing succeeds
                    } catch (e) {}
                }
            },

            mounted() {
                this.images = this.uploadedImages;
            },

            methods: {
                selectImage(image, allowMultiple) {
                    if (allowMultiple) {
                        image.selected =!image.selected;
                    } else {
                        this.ai.images.filter(image => image.selected = false)
                        image.selected = true;
                    }
                },

                add() {
                    let imageInput = this.$refs[this.$.uid + '_imageInput'];

                    if (imageInput.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(imageInput.files).every(file => file.type.includes('image/') || file.type.includes('video/'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.media.gallery.not-allowed-error')"
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

                    if (this.ai.enabled) {
                        this.$refs.choiceImageModal.close()
                    }
                },

                remove(image) {
                    let index = this.images.indexOf(image);

                    this.images.splice(index, 1);
                },

                toggleImageAIModal() {
                    this.$refs.magicAIImageModal.open();
                    this.$nextTick(() => {
                        if (this.$refs.imagePromptInput) {
                            if (this.aiModels.length === 0) {
                                this.fetchModels();
                            }

                            const tribute = this.$tribute.init({
                                values: this.fetchSuggestionValues,
                                lookup: 'name',
                                fillAttr: 'code',
                                noMatchTemplate: "@lang('admin::app.common.no-match-found')",
                                selectTemplate: (item) => `@${item.original.code}`,
                                menuItemTemplate: (item) => `<div class="p-1.5 rounded-md text-base cursor-pointer transition-all max-sm:place-self-center">${item.original.name || '[' + item.original.code + ']'}</div>`,
                            });

                            tribute.attach(this.$refs.imagePromptInput);
                        }
                    });
                },

                async fetchModels() {
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.available_model') }}");

                        this.aiModels = response.data.models.filter(model => model.id === 'dall-e-2' || model.id === 'dall-e-3');
                        this.ai.model = this.aiModels[0] ? this.aiModels[0].id : '';
                    } catch (error) {
                        console.error("Failed to fetch AI models:", error);
                    }
                },

                async fetchSuggestionValues(text, cb) {
                    if (!text && this.suggestionValues.length) {
                        cb(this.suggestionValues);
                        return;
                    }

                    const response = await fetch(`{{ route('admin.magic_ai.suggestion_values') }}?query=${text}&&entity_name=${this.entityName}&&locale={{ core()->getRequestedLocaleCode() }}`);
                    const data = await response.json();
                    this.suggestionValues = data;

                    cb(this.suggestionValues);
                },

                openSuggestions() {
                    this.ai.prompt = this.ai.prompt ?? '';
                    this.ai.prompt += ' @';
                    this.$nextTick(() => {
                        this.$refs.imagePromptInput.focus();
                        const textarea = this.$refs.imagePromptInput;
                        const keydownEvent = new KeyboardEvent("keydown", { key: "@", bubbles: true });
                        textarea.dispatchEvent(keydownEvent);
                        const event = new KeyboardEvent("keyup", { key: "@", bubbles: true });
                        textarea.dispatchEvent(event);
                    });
                },

                getResourceType() {
                    switch (this.entityName) {
                        case 'category-field':
                            return 'category';
                        default:
                            return 'product';
                    }
                },

                generate(params, { setErrors }) {
                    this.isLoading = true;

                    let self = this;

                    params.resource_id = this.resourceId;
                    params.resource_type = this.getResourceType();
                    params.field_type = 'image';
                    params.model = this.ai.model;
                    params.channel = "{{ core()->getRequestedChannelCode() }}";
                    params.locale = "{{ core()->getRequestedLocaleCode() }}";

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
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            }
                        });
                },

                apply() {
                    this.selectedAIImages.forEach((image, index) => {
                        this.images.push({
                            id: 'image_' + this.images.length,
                            url: '',
                            file: this.getBase64ToFile(image.url, 'temp.png'),
                            type: file.type,
                            name: file.name
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
                        enabled: Boolean("{{ core()->getConfigData('general.magic_ai.settings.enabled') && core()->getConfigData('general.magic_ai.image_generation.enabled') }}"),

                        prompt: null,

                        model: this.aiModels[0] ? this.aiModels[0].id : '',

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

                    const validFiles = Array.from(imageInput.files).every(file => file.type.includes('image/') || file.type.includes('video/'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.media.images.not-allowed-error')"
                        });

                        return;
                    }

                    this.setFile(imageInput.files[0]);

                    this.readFile(imageInput.files[0]);
                },

                remove() {
                    this.$emit('onRemove', this.image)
                },

                previewMedia() {
                    this.$refs.mediaPreviewModal.toggle();
                },

                closeImageModal() {
                    this.$refs.mediaPreviewModal.close();
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
                getDisplayFileName(fileName) {
                    if (fileName.length > 29) {
                        return fileName.substring(0, 20) + '...' + fileName.substring(fileName.lastIndexOf('.'));
                    }

                    return fileName;
                }
            }
        });
    </script>
@endPushOnce
