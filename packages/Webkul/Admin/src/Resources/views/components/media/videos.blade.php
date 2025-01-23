@props([
'name' => 'videos',
'allowMultiple' => false,
'uploadedVideos' => [],
'width' => '210px',
'height' => '120px'
])

<v-media-videos
    name="{{ $name }}"
    v-bind:allow-multiple="{{ $allowMultiple ? true : false }}"
    :uploaded-videos='{{ json_encode($uploadedVideos) }}'
    width="{{ $width }}"
    height="{{ $height }}"
    :errors="errors"
    {{ $attributes->get('class') }}>
</v-media-videos>

@pushOnce('scripts')
<script
    type="text/x-template"
    id="v-media-videos-template">
    <!-- Panel Content -->
    <div class="grid">
        <div class="flex gap-1">
            <!-- Upload Video Button -->

            <label
                class="grid justify-items-center items-center w-full h-[120px] max-w-[210px] max-h-[120px] border border-dashed dark:border-gray-300 rounded cursor-pointer transition-all hover:border-gray-400 border-gray-300"
                :class="[errors['videos.files[0]'] ? 'border border-red-500' : 'border-gray-300']"
                :for="$.uid + '_videoInput'"
                v-if="allowMultiple || videos.length == 0">
                <div class="flex flex-col items-center">
                    <span class="icon-image text-2xl"></span>

                    <p class="grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center">
                        @lang('admin::app.components.media.videos.add-video-btn')

                        <span class="text-xs">
                            @lang('admin::app.components.media.videos.allowed-types')
                        </span>
                    </p>

                    <input
                        type="file"
                        class="hidden"
                        :id="$.uid + '_videoInput'"
                        accept="video/*"
                        :multiple="allowMultiple"
                        :ref="$.uid + '_videoInput'"
                        @change="add" />
                </div>
            </label>

            <!-- Uploaded Videos -->
            <draggable
                class="flex gap-1"
                ghost-class="draggable-ghost"
                v-bind="{animation: 200}"
                :list="videos"
                item-key="id">
                <template #item="{ element, index }">
                    <v-media-video-item
                        :allowMultiple="{{ $allowMultiple ? true : false }}"
                        :name="name"
                        :index="index"
                        :video="element"
                        :width="width"
                        :height="height"
                        @onRemove="remove($event)">
                    </v-media-video-item>
                </template>
            </draggable>
        </div>
    </div>
</script>

<script type="text/x-template" id="v-media-video-item-template">
    <div class="grid justify-items-center h-[120px] max-w-[210px] min-w-[210px] max-h-[120px] relative border border-dashed border-gray-300 dark:border-cherry-800 rounded overflow-hidden transition-all hover:border-gray-400 group cursor-grab">
            <!-- Video Preview -->
            <video
                class="w-[210px] h-[120px] object-cover"
                ref="videoPreview"
                v-if="video.url.length > 0"
            >
                <source :src="video.url" type="video/mp4">
            </video>

            <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
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
                            class="h-6 w-6 text-white" 
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
                        :for="$.uid + '_videoInput_' + index"
                    ></label>

                    <input type="hidden" :name="name + '[' + video.id + ']'" v-if="allowMultiple && ! video.is_new && video.value" :value="video.value"/>

                    <input type="hidden" :name="name" v-if="! allowMultiple && ! video.is_new && video.value" :value="video.value"/>

                    <input
                        type="file"
                        :name="name + '[]'"
                        class="hidden"
                        accept="video/*"
                        :id="$.uid + '_videoInput_' + index"
                        :ref="$.uid + '_videoInput_' + index"
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
                        <source :src="video.url" type="video/mp4">
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
        </div>
    </script>

<script type="module">
    app.component('v-media-videos', {
        template: '#v-media-videos-template',

        props: {
            name: {
                type: String,
                default: 'videos',
            },

            allowMultiple: {
                type: Boolean,
                default: false,
            },

            uploadedVideos: {
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
                videos: [],
            }
        },

        mounted() {
            this.videos = this.uploadedVideos;
        },

        methods: {
            add() {
                let videoInput = this.$refs[this.$.uid + '_videoInput'];

                if (videoInput.files == undefined) {
                    return;
                }

                const validFiles = Array.from(videoInput.files).every(file => file.type.includes('video/'));

                if (!validFiles) {
                    this.$emitter.emit('add-flash', {
                        type: 'warning',
                        message: "@lang('admin::app.components.media.videos.not-allowed-error')"
                    });

                    return;
                }

                videoInput.files.forEach((file, index) => {
                    this.videos.push({
                        id: 'video_' + this.videos.length,
                        url: '',
                        file: file
                    });
                });
            },

            remove(video) {
                let index = this.videos.indexOf(video);

                this.videos.splice(index, 1);
            },
        }
    });

    app.component('v-media-video-item', {
        template: '#v-media-video-item-template',

        props: ['allowMultiple', 'index', 'video', 'name', 'width', 'height'],

        data() {
            return {
                isPlaying: false,
                isFullScreen: false,
            }
        },

        mounted() {
            if (this.video.file instanceof File) {
                this.setFile(this.video.file);

                this.readFile(this.video.file);
            }
        },

        methods: {
            showFullScreen() {
                this.isFullScreen = true;
                this.$nextTick(() => {
                    this.$refs.fullScreenContainer.focus();
                });
            },

            closeFullScreen() {
                this.isFullScreen = false;
            },

            edit() {
                let videoInput = this.$refs[this.$.uid + '_videoInput_' + this.index];

                if (videoInput.files == undefined) {
                    return;
                }
                console.log(videoInput.files, videoInput);
                const validFiles = Array.from(videoInput.files).every(file => file.type.includes('video/'));

                if (!validFiles) {
                    this.$emitter.emit('add-flash', {
                        type: 'warning',
                        message: "@lang('admin::app.components.media.videos.not-allowed-error')"
                    });

                    return;
                }

                this.setFile(videoInput.files[0]);

                this.readFile(videoInput.files[0]);
            },

            remove() {
                this.$emit('onRemove', this.video)
            },

            setFile(file) {
                this.video.is_new = 1;

                const dataTransfer = new DataTransfer();

                dataTransfer.items.add(file);

                this.$refs[this.$.uid + '_videoInput_' + this.index].files = dataTransfer.files;
            },

            readFile(file) {
                let reader = new FileReader();

                reader.onload = (e) => {
                    this.video.url = e.target.result;
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