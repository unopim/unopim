@props([
    'src'      => null,
    'fileName' => null,
])

<v-image-viewer
    :src="{{ $src ?? "''" }}"
    :file-name="{{ $fileName ?? "''" }}"
    {{ $attributes }}
></v-image-viewer>

@pushOnce('scripts')
    <script type="text/x-template" id="v-image-viewer-template">
        <div class="fixed inset-0 flex flex-col bg-white dark:bg-cherry-900">
            <div class="flex items-center justify-between gap-2 px-4 py-2.5 border-b dark:border-cherry-800 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span
                        class="px-2 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-200 text-[10px] font-bold uppercase shrink-0"
                        v-if="extension"
                        v-text="extension"
                    ></span>

                    <p
                        class="text-sm font-semibold text-gray-800 dark:text-white truncate"
                        :title="fileName"
                        v-text="fileName"
                    ></p>
                </div>

                <span
                    class="icon-cancel text-2xl cursor-pointer text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 rounded-md shrink-0"
                    role="button"
                    tabindex="0"
                    aria-label="@lang('admin::app.components.media.image-viewer.close')"
                    @click="$emit('close')"
                    @keydown.enter.prevent="$emit('close')"
                ></span>
            </div>

            <div
                class="relative flex-1 min-h-0 overflow-hidden flex items-center justify-center select-none bg-gray-50 dark:bg-cherry-950"
                @wheel.prevent="onWheel"
                @mousedown="onMouseDown"
                :class="isDragging ? 'cursor-grabbing' : (zoom > 1 ? 'cursor-grab' : 'cursor-default')"
            >
                <img
                    v-if="! imageError"
                    ref="viewerImg"
                    :src="src"
                    :alt="fileName"
                    class="max-w-none max-h-none block pointer-events-none"
                    :style="{
                        transform: transformStyle,
                        transformOrigin: 'center center',
                        transition: isDragging ? 'none' : 'transform 0.15s ease',
                        maxHeight: '100%',
                        maxWidth: '100%',
                    }"
                    draggable="false"
                    v-on:error="imageError = true"
                />

                <div
                    v-else
                    class="flex flex-col items-center justify-center gap-3 text-gray-400 dark:text-gray-500"
                >
                    <span class="icon-file text-6xl"></span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">@{{ fileName }}</span>
                    <span class="text-xs uppercase">@{{ extension }}</span>
                </div>

                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1 px-3 py-1.5 rounded-full bg-black/60 text-white text-xs shadow-lg z-10 select-none">
                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors"
                        title="@lang('admin::app.components.media.image-viewer.rotate-left')"
                        @click="rotateLeft"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors"
                        title="@lang('admin::app.components.media.image-viewer.rotate-right')"
                        @click="rotateRight"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    </button>

                    <span class="w-px h-4 bg-white/30 mx-1"></span>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors"
                        title="@lang('admin::app.components.media.image-viewer.zoom-out')"
                        @click="zoomOut"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>

                    <span class="min-w-[44px] text-center font-mono tabular-nums">@{{ zoomPercent }}%</span>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors"
                        title="@lang('admin::app.components.media.image-viewer.zoom-in')"
                        @click="zoomIn"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>

                    <span class="w-px h-4 bg-white/30 mx-1"></span>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors"
                        title="@lang('admin::app.components.media.image-viewer.fit-to-screen')"
                        @click="fitToScreen"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    </button>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors text-[11px] font-bold"
                        title="@lang('admin::app.components.media.image-viewer.actual-size')"
                        @click="actualSize"
                    >1:1</button>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors"
                        title="@lang('admin::app.components.media.image-viewer.reset-all')"
                        @click="reset"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-image-viewer', {
            template: '#v-image-viewer-template',

            props: ['src', 'fileName'],

            emits: ['close'],

            data() {
                return {
                    zoom:       1,
                    rotation:   0,
                    panX:       0,
                    panY:       0,
                    isDragging: false,
                    dragStartX: 0,
                    dragStartY: 0,
                    panStartX:  0,
                    panStartY:  0,
                    imageError: false,
                };
            },

            watch: {
                src() {
                    this.imageError = false;
                },
            },

            computed: {
                transformStyle() {
                    return `translate(${this.panX}px, ${this.panY}px) scale(${this.zoom}) rotate(${this.rotation}deg)`;
                },

                zoomPercent() {
                    return Math.round(this.zoom * 100);
                },

                extension() {
                    const name = this.fileName || '';
                    const dot = name.lastIndexOf('.');

                    return dot > -1 ? name.slice(dot + 1) : '';
                },
            },

            mounted() {
                window.addEventListener('mousemove', this.onMouseMove);
                window.addEventListener('mouseup', this.onMouseUp);
                window.addEventListener('keydown', this.onKeydown);
            },

            beforeUnmount() {
                window.removeEventListener('mousemove', this.onMouseMove);
                window.removeEventListener('mouseup', this.onMouseUp);
                window.removeEventListener('keydown', this.onKeydown);
            },

            methods: {
                zoomIn() {
                    this.zoom = Math.min(10, parseFloat((this.zoom + 0.25).toFixed(2)));
                },

                zoomOut() {
                    this.zoom = Math.max(0.1, parseFloat((this.zoom - 0.25).toFixed(2)));
                },

                rotateRight() {
                    this.rotation = (this.rotation + 90) % 360;
                },

                rotateLeft() {
                    this.rotation = (this.rotation - 90 + 360) % 360;
                },

                fitToScreen() {
                    this.zoom = 1;
                    this.panX = 0;
                    this.panY = 0;
                },

                actualSize() {
                    const img = this.$refs.viewerImg;

                    if (! img || ! img.naturalWidth || ! img.offsetWidth) {
                        this.reset();

                        return;
                    }

                    this.zoom = parseFloat(Math.max(0.1, Math.min(10, img.naturalWidth / img.offsetWidth)).toFixed(3));
                    this.panX = 0;
                    this.panY = 0;
                },

                reset() {
                    this.zoom = 1;
                    this.rotation = 0;
                    this.panX = 0;
                    this.panY = 0;
                },

                onWheel(event) {
                    const factor = event.deltaY < 0 ? 1.1 : 0.9;

                    this.zoom = Math.min(10, Math.max(0.1, parseFloat((this.zoom * factor).toFixed(3))));
                },

                onMouseDown(event) {
                    if (event.button !== 0) {
                        return;
                    }

                    this.isDragging = true;
                    this.dragStartX = event.clientX;
                    this.dragStartY = event.clientY;
                    this.panStartX = this.panX;
                    this.panStartY = this.panY;

                    event.preventDefault();
                },

                onMouseMove(event) {
                    if (! this.isDragging) {
                        return;
                    }

                    this.panX = this.panStartX + (event.clientX - this.dragStartX);
                    this.panY = this.panStartY + (event.clientY - this.dragStartY);
                },

                onMouseUp() {
                    this.isDragging = false;
                },

                onKeydown(event) {
                    switch (event.key) {
                        case '+': case '=': this.zoomIn(); break;
                        case '-': case '_': this.zoomOut(); break;
                        case 'r': case 'R': this.rotateRight(); break;
                        case 'l': case 'L': this.rotateLeft(); break;
                        case '0': this.reset(); break;
                        case 'Escape': this.$emit('close'); break;
                    }
                },
            },
        });
    </script>
@endPushOnce
