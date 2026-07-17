@props([
    'name'             => 'image',
    'showPlaceholders' => false,
    'showSuggestions'  => true,
    'showUploadHint'   => true,
    'uploadedImages'   => [],
    'width'            => '120px',
    'height'           => '120px',
    'objectFit'          => 'cover',
    'responsive'         => false,
    'hasContext'         => false,
    'fullPreview'        => true,
    'acceptedExtensions' => [],
    'instructions'         => '',
])

@php
    $dynamicUploadedImages = $attributes->get('::uploaded-images') ?? $attributes->get(':uploaded-images');
    $rootAttributes = $attributes->except(['::uploaded-images', ':uploaded-images', 'uploaded-images']);
@endphp

<x-admin::media.field type="image" :name="$name" :instructions="$instructions">

<v-media-image
    {{ $rootAttributes }}
    name="{{ $name }}"
    v-bind:show-placeholders="{{ $showPlaceholders ? 'true' : 'false' }}"
    v-bind:show-suggestions="{{ $showSuggestions ? 'true' : 'false' }}"
    v-bind:show-upload-hint="{{ $showUploadHint ? 'true' : 'false' }}"
    @if ($dynamicUploadedImages)
        :uploaded-images="{{ $dynamicUploadedImages }}"
    @else
        :uploaded-images='{{ json_encode($uploadedImages) }}'
    @endif
    width="{{ $width }}"
    height="{{ $height }}"
    object-fit="{{ $objectFit }}"
    v-bind:responsive="{{ $responsive ? 'true' : 'false' }}"
    v-bind:has-context="{{ $hasContext ? 'true' : 'false' }}"
    v-bind:full-preview="{{ $fullPreview ? 'true' : 'false' }}"
    :accepted-extensions='@json($acceptedExtensions)'
    :errors="errors"
>
    <x-admin::shimmer.media />
</v-media-image>

    <x-admin::media.image-viewer v-if="false" />
</x-admin::media.field>

@pushOnce('scripts')
    <script type="text/x-template" id="v-media-image-template">
        <div class="grid" data-media-control>
            <div :class="responsive
                ? ['grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5', images.length > 1 ? 'gap-3' : '']
                : ['flex flex-wrap', images.length > 1 ? 'gap-3' : '']"
            >
                {{-- Add Image tile (shared dropzone; ordered last via CSS order) --}}
                <template v-if="images.length === 0">
                    <v-media-add-tile
                        v-if="ai.enabled"
                        :trigger-modal="true"
                        :compact="isCompactTile"
                        :style="{ ...tileStyle, order: 9999 }"
                        title="@lang('admin::app.components.media.images.add-image-btn')"
                        :hint="showUploadHint ? @js(trans('admin::app.components.media.images.drag-drop-hint')) : ''"
                        allowed-types="@lang('admin::app.components.media.images.allowed-types')"
                        @trigger="resetAIModal(); $refs.choiceImageModal.open()"
                        @drop="onDrop"
                    ></v-media-add-tile>

                    <v-media-add-tile
                        v-else
                        :compact="isCompactTile"
                        :style="{ ...tileStyle, order: 9999 }"
                        title="@lang('admin::app.components.media.images.add-image-btn')"
                        :hint="showUploadHint ? @js(trans('admin::app.components.media.images.drag-drop-hint')) : ''"
                        allowed-types="@lang('admin::app.components.media.images.allowed-types')"
                        :accept="acceptAttribute"
                        :input-id="$.uid + '_imageInput'"
                        @change="add"
                        @drop="onDrop"
                    ></v-media-add-tile>
                </template>

                <draggable
                    class="contents"
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
                            :objectFit="objectFit"
                            :responsive="responsive"
                            :fullPreview="fullPreview"
                            :accepted-extensions="acceptedExtensions"
                            @onRemove="remove($event)"
                        >
                        </v-media-image-item>
                    </template>
                </draggable>

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form>
                        <x-admin::modal ref="choiceImageModal">
                            <x-slot:header>
                                <p class="grid text-base text-gray-800 dark:text-gray-300 font-semibold text-center">
                                    @lang('admin::app.components.media.images.add-image-btn')
                                </p>
                            </x-slot>

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
                                            :accept="acceptAttribute"
                                            @change="add($event.target.files)"
                                        />
                                    </label>
                                </div>
                            </x-slot>

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
                        <x-admin::modal ref="magicAIImageModal">
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

                            <x-slot:content>
                                <div v-show="! ai.images.length">
                                    <x-admin::form.control-group v-if="imagePrompts.length">
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.components.tinymce.ai-generation.default-prompt')
                                        </x-admin::form.control-group.label>
                                        <select
                                            @change="onImagePromptChange($event)"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                                        >
                                            <option value="">@lang('admin::app.components.tinymce.ai-generation.select-prompt-template')</option>
                                            <option v-for="p in imagePrompts" :key="p.title" :value="p.prompt">@{{ p.title }}</option>
                                        </select>
                                    </x-admin::form.control-group>

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

                                            {{-- Icon inside textarea (only when there's a resource context to interpolate from) --}}
                                            <div
                                                v-if="hasContext && showSuggestions"
                                                class="absolute bottom-2.5 left-1 text-gray-400 cursor-pointer text-2xl"
                                                @click="openSuggestions"
                                            >
                                                <span class="icon-at"></span>
                                            </div>
                                        </div>

                                        <x-admin::form.control-group.error control-name="prompt" />
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
                                            :class="{'!border-primary-700 ': image.selected}"
                                            v-for="image in ai.images"
                                            :key="image.url"
                                            @click="selectImage(image)"
                                        >
                                            <img
                                                class="w-[120px] h-[120px]"
                                                :src="image.url"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </x-slot>

                            <x-slot:footer>
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex items-center gap-2" v-if="!ai.images.length">
                                        <select
                                            v-model="ai.platform_id"
                                            @change="onPlatformChange()"
                                            class="py-1.5 px-2 border rounded-md text-xs text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800 max-w-[140px]"
                                            title="@lang('admin::app.components.tinymce.ai-generation.platform')"
                                        >
                                            <option v-for="p in platforms" :key="p.id" :value="p.id">@{{ p.label }}</option>
                                        </select>
                                        <select
                                            v-model="ai.model"
                                            class="py-1.5 px-2 border rounded-md text-xs text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800 max-w-[160px]"
                                            title="@lang('admin::app.components.media.images.ai-generation.model')"
                                        >
                                            <option v-for="m in aiModels" :key="m.id" :value="m.id">@{{ m.label }}</option>
                                        </select>
                                    </div>
                                    <div v-else></div>

                                    <div class="flex gap-x-2.5 items-center">
                                    <template v-if="! ai.images.length">
                                        <button
                                            class="secondary-button"
                                            :disabled="isLoading"
                                            :class="{ 'opacity-50 cursor-not-allowed': isLoading }">
                                            <template v-if="isLoading">
                                                <img
                                                    class="animate-spin h-5 w-5 text-primary-700"
                                                    src="{{ unopim_asset('images/spinner.svg') }}"
                                                />
                                                @lang('admin::app.components.tinymce.ai-generation.generating')
                                            </template>

                                            <template v-else>
                                                <span class="icon-magic text-2xl text-primary-700"></span>
                                                @lang('admin::app.components.tinymce.ai-generation.generate')
                                            </template>
                                        </button>
                                    </template>

                                    <template v-else>
                                         <button
                                            class="secondary-button"
                                            :disabled="isLoading"
                                            :class="{ 'opacity-50 cursor-not-allowed': isLoading }">
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
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="v-media-image-item-template">
        <div>
            <v-media-card
                :media="image"
                mode="image"
                :width="responsive ? null : width"
                :height="responsive ? '176px' : `calc(${height} + 36px)`"
                :object-fit="objectFit"
                :allow-preview="true"
                :allow-replace="true"
                :allow-remove="true"
                :show-extension="false"
                :invalid="isInvalid"
                @preview="preview"
                @replace="replace"
                @remove="remove"
            ></v-media-card>

            <input type="hidden" :name="name" v-if="! image.is_new && image.value" :value="image.value"/>
            <input
                type="file"
                :name="name + '[]'"
                class="hidden"
                :accept="acceptAttribute"
                :id="$.uid + '_imageInput_' + index"
                :ref="$.uid + '_imageInput_' + index"
                @change="edit"
            />

            <x-admin::modal ref="imagePreviewModal">
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @{{ getDisplayFileName(image) }}
                    </p>
                </x-slot>
                <x-slot:content>
                    <div style="max-width: 100%;height: 260px;">
                        <img
                            :src="image.url"
                            class="w-full h-full object-contain object-top"
                        />
                    </div>
                </x-slot>
            </x-admin::modal>

            <x-admin::modal ref="imagePreviewModalFull" no-class="true">
                <x-slot:content>
                    <v-image-viewer
                        v-if="image"
                        :src="image.url"
                        :file-name="getDisplayFileName(image)"
                        @close="closeFullPreview"
                    ></v-image-viewer>
                </x-slot>
            </x-admin::modal>

        </div>
    </script>

    <script type="module">
        app.component('v-media-image', {
            template: '#v-media-image-template',

            props: {
                name: {
                    type: String,
                    default: 'images',
                },


                showPlaceholders: {
                    type: Boolean,
                    default: false,
                },

                showSuggestions: {
                    type: Boolean,
                    default: true,
                },

                showUploadHint: {
                    type: Boolean,
                    default: true,
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

                objectFit: {
                    type: String,
                    default: 'cover'
                },

                acceptedExtensions: {
                    type: Array,
                    default: () => [],
                },

                errors: {
                    type: Object,
                    default: () => {}
                },

                responsive: {
                    type: Boolean,
                    default: false,
                },

                hasContext: {
                    type: Boolean,
                    default: false,
                },

                fullPreview: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    images: [],

                    placeholders: [
                    ],

                    isLoading: false,

                    isDragging: false,

                    ai: {
                        enabled: @json(!! core()->getConfigData('general.magic_ai.image_generation.enabled') && bouncer()->hasPermission('ai-agent')),

                        prompt: null,

                        platform_id: null,

                        model: null,

                        n: 1,

                        size: '1024x1024',

                        quality: 'standard',

                        images: [],
                    },

                    platforms: [],
                    aiModels: [],
                    imagePrompts: [],
                    suggestionValues: [],
                    resourceId: "{{ request()->id ?? auth()->id() }}",
                    entityName: "{{ $attributes->get('entity-name', 'attribute') }}",
                }
            },

            computed: {
                acceptAttribute() {
                    if (! this.acceptedExtensions || ! this.acceptedExtensions.length) {
                        return 'image/*';
                    }

                    return this.acceptedExtensions.map(extension => `.${extension.replace(/^\./, '')}`).join(',');
                },

                isCompactTile() {
                    return this.parseDimension(this.width) <= 220
                        && this.parseDimension(this.height) <= 160;
                },

                tileStyle() {
                    if (this.responsive) {
                        return null;
                    }

                    return {
                        width: this.width,
                        height: `calc(${this.height} + 36px)`,
                        minWidth: '120px',
                        minHeight: '120px',
                        padding: this.isCompactTile ? '10px' : '14px',
                    };
                },

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
                selectImage(image) {
                    this.ai.images.forEach(item => item.selected = false);
                    image.selected = true;
                },

                add(files) {
                    if (! files || ! files.length) {
                        return;
                    }

                    if (! this.addFiles(files)) {
                        return;
                    }

                    if (this.ai.enabled) {
                        this.$refs.choiceImageModal.close()
                    }
                },

                onDrop(files) {
                    if (! files || ! files.length) {
                        return;
                    }

                    this.addFiles([files[0]]);
                },

                parseDimension(value) {
                    const parsed = Number.parseInt(String(value), 10);

                    return Number.isNaN(parsed) ? 120 : parsed;
                },

                isFileAccepted(file) {
                    if (this.acceptedExtensions && this.acceptedExtensions.length) {
                        const extension = (file.name.split('.').pop() || '').toLowerCase();

                        return this.acceptedExtensions
                            .map(value => value.toLowerCase().replace(/^\./, ''))
                            .includes(extension);
                    }

                    return file.type.startsWith('image/');
                },

                addFiles(files) {
                    const validFiles = Array.from(files).every(file => this.isFileAccepted(file));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @json(trans('admin::app.components.media.images.not-allowed-error'))
                        });

                        return false;
                    }

                    Array.from(files).forEach((file) => {
                        this.images.push({
                            id: 'image_' + this.images.length,
                            url: '',
                            file: file,
                            name: file.name
                        });
                    });

                    this.signalChange();

                    return true;
                },

                remove(image) {
                    let index = this.images.indexOf(image);

                    this.images.splice(index, 1);

                    this.signalChange();
                },

                signalChange() {
                    // Notify any enclosing unsaved-changes tracker: media widgets mutate
                    // file inputs programmatically (no native input/change), so without
                    // this the "unsaved" bar would never see image add/remove/replace.
                    this.$nextTick(() => {
                        if (this.$el && this.$el.dispatchEvent) {
                            this.$el.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                                bubbles: true,
                                detail: { name: this.name },
                            }));
                        }
                    });
                },

                toggleImageAIModal() {
                    this.$refs.magicAIImageModal.open();
                    this.$nextTick(() => {
                        if (this.$refs.imagePromptInput) {
                            if (this.platforms.length === 0) {
                                this.fetchPlatforms();
                            }

                            if (this.imagePrompts.length === 0) {
                                this.fetchImagePrompts();
                            }

                            // Skip @ suggestions when there's no resource context to interpolate or suggestions disabled.
                            if (! this.hasContext || ! this.showSuggestions) {
                                return;
                            }

                            const tribute = this.$tribute.init({
                                values: this.fetchSuggestionValues,
                                lookup: 'name',
                                fillAttr: 'code',
                                noMatchTemplate: @json(trans('admin::app.common.no-match-found')),
                                selectTemplate: (item) => `@${item.original.code}`,
                                menuItemTemplate: (item) => {
                                    const element = document.createElement('div');
                                    element.className = 'p-1.5 rounded-md text-base cursor-pointer transition-all max-sm:place-self-center';
                                    element.textContent = item.original.name || '[' + item.original.code + ']';

                                    return element.outerHTML;
                                },
                            });

                            tribute.attach(this.$refs.imagePromptInput);
                        }
                    });
                },

                async fetchPlatforms() {
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.platforms') }}", {
                            params: { purpose: 'image_generation' }
                        });
                        this.platforms = response.data.platforms || [];

                        if (this.platforms.length) {
                            let defaultPlatform = this.platforms.find(p => p.is_default);
                            this.ai.platform_id = defaultPlatform ? defaultPlatform.id : this.platforms[0].id;
                            this.loadModelsForPlatform();
                        }
                    } catch (error) {
                        console.error("Failed to fetch platforms:", error);
                    }
                },

                onPlatformChange() {
                    this.loadModelsForPlatform();
                },

                loadModelsForPlatform() {
                    let platform = this.platforms.find(p => p.id === this.ai.platform_id);

                    if (platform && platform.models) {
                        this.aiModels = platform.models.map(m => ({ id: m, label: m }));
                        this.ai.model = this.aiModels[0]?.id || null;
                    } else {
                        this.aiModels = [];
                        this.ai.model = null;
                    }
                },

                async fetchImagePrompts() {
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.default_prompt') }}", {
                            params: { purpose: 'image_generation' }
                        });
                        this.imagePrompts = response.data.prompts || [];
                    } catch (error) {
                        console.error("Failed to fetch image prompts:", error);
                    }
                },

                onImagePromptChange(event) {
                    if (event.target.value) {
                        this.ai.prompt = event.target.value;
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
                    params.platform_id = this.ai.platform_id;
                    params.channel = "{{ core()->getRequestedChannelCode() }}";
                    params.locale = "{{ core()->getRequestedLocaleCode() }}";

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
                    this.selectedAIImages.forEach((image) => {
                        const mime = image.url.match(/^data:(image\/[^;]+);base64,/)[1];

                        const extension = {
                            'image/jpeg': 'jpg',
                            'image/png': 'png',
                            'image/webp': 'webp',
                        }[mime] || 'png';

                        this.images.push({
                            id: 'image_' + this.images.length,
                            url: '',
                            file: this.getBase64ToFile(image.url, `temp.${extension}`)
                        });
                    });

                    this.signalChange();

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

                        model: this.aiModels[0] ? this.aiModels[0].id : '',

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

            props: ['index', 'image', 'name', 'width', 'height', 'objectFit', 'responsive', 'fullPreview', 'acceptedExtensions'],

            computed: {
                acceptAttribute() {
                    if (! this.acceptedExtensions || ! this.acceptedExtensions.length) {
                        return 'image/*';
                    }

                    return this.acceptedExtensions.map(extension => `.${extension.replace(/^\./, '')}`).join(',');
                },

                isInvalid() {
                    if (! this.acceptedExtensions || ! this.acceptedExtensions.length) {
                        return false;
                    }

                    const extension = (this.image?.name || '').split('.').pop()?.toLowerCase();

                    if (! extension) {
                        return false;
                    }

                    return ! this.acceptedExtensions
                        .map(value => value.toLowerCase().replace(/^\./, ''))
                        .includes(extension);
                },
            },

            mounted() {
                if (this.image.file instanceof File) {
                    this.setFile(this.image.file);

                    this.readFile(this.image.file);
                }
            },

            methods: {
                isFileAccepted(file) {
                    if (this.acceptedExtensions && this.acceptedExtensions.length) {
                        const extension = (file.name.split('.').pop() || '').toLowerCase();

                        return this.acceptedExtensions
                            .map(value => value.toLowerCase().replace(/^\./, ''))
                            .includes(extension);
                    }

                    return file.type.startsWith('image/');
                },

                replace() {
                    this.$refs[this.$.uid + '_imageInput_' + this.index].click();
                },

                edit() {
                    let imageInput = this.$refs[this.$.uid + '_imageInput_' + this.index];

                    if (imageInput.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(imageInput.files).every(file => this.isFileAccepted(file));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @json(trans('admin::app.components.media.images.not-allowed-error'))
                        });

                        return;
                    }

                    this.setFile(imageInput.files[0]);

                    this.readFile(imageInput.files[0]);

                    if (this.$el && this.$el.dispatchEvent) {
                        this.$el.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                            bubbles: true,
                            detail: { name: this.name },
                        }));
                    }
                },

                remove() {
                    this.$emit('onRemove', this.image)
                },

                preview() {
                    if (this.fullPreview) {
                        this.$refs.imagePreviewModalFull.toggle();
                    } else {
                        this.$refs.imagePreviewModal.toggle();
                    }
                },

                closeImageModal() {
                    this.$refs.imagePreviewModal.close();
                },

                closeFullPreview() {
                    this.$refs.imagePreviewModalFull.close();
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
                getDisplayFileName(image) {
                    let fileName = image?.name ?? (image?.value ? this.getNameFromValue(image.value) : '');

                    if ((fileName?.length ?? 0) > 20) {
                        return fileName.substring(0, 13) + '...' + fileName.substring(fileName.lastIndexOf('.'));
                    }

                    return fileName;
                },
                getNameFromValue(path) {
                    if (! path) {
                        return "";
                    }

                    const parts = path.split('/').filter(Boolean);

                    return parts.pop();
                },
            }
        });
    </script>
@endPushOnce
