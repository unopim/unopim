@props([
    'media'              => [],
    'mode'               => 'image',
    'width'              => null,
    'height'             => null,
    'allowReplace'       => false,
    'allowRemove'        => false,
    'allowPreview'       => true,
    'allowDrag'          => false,
    'allowSelect'        => false,
    'selectable'         => false,
    'selected'           => false,
    'showDragHandle'     => false,
    'showFilename'       => true,
    'showExtension'      => true,
    'showBadge'          => false,
    'actionsLayout'      => 'bottom',
    'objectFit'          => 'contain',
])

<v-media-card
    :media='@json($media)'
    mode="{{ $mode }}"
    :width='@json($width)'
    :height='@json($height)'
    :allow-replace="@json($allowReplace)"
    :allow-remove="@json($allowRemove)"
    :allow-preview="@json($allowPreview)"
    :allow-drag="@json($allowDrag)"
    :allow-select="@json($allowSelect)"
    :selectable="@json($selectable)"
    :selected="@json($selected)"
    :show-drag-handle="@json($showDragHandle)"
    :show-filename="@json($showFilename)"
    :show-extension="@json($showExtension)"
    :show-badge="@json($showBadge)"
    actions-layout="{{ $actionsLayout }}"
    object-fit="{{ $objectFit }}"
    {{ $attributes }}
></v-media-card>

@pushOnce('scripts')
    <script type="text/x-template" id="v-media-card-template">
        <div
            class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-cherry-800 dark:bg-cherry-900"
            :style="cardStyle"
            :draggable="allowDrag"
            :class="{ 'ring-2 ring-primary-500': selected, 'ring-2 ring-red-500 border-red-400': invalid }"
            @click="allowSelect && $emit('select', media)"
            @dragstart="$emit('drag-start', media)"
            @dragend="$emit('drag-end', media)"
        >
            <div class="relative flex-1 min-h-0">
                <img
                    v-if="isImage && ! imageError"
                    :src="media.url"
                    :alt="displayName"
                    class="h-full w-full bg-gray-100 dark:bg-cherry-800"
                    :class="objectFit === 'cover' ? 'object-cover' : 'object-contain'"
                    v-on:error="imageError = true"
                />
                <video v-else-if="isVideo && ! imageError" class="h-full w-full object-cover bg-gray-900" muted preload="metadata">
                    <source :src="media.url" :type="media.mime_type || media.type">
                </video>
                <div v-else class="flex h-full flex-col items-center justify-center gap-2 bg-gray-100 text-xs text-gray-500 dark:bg-cherry-800 dark:text-gray-300">
                    <span :class="mediaIcon" class="text-4xl text-gray-400 dark:text-gray-500"></span>
                    <span class="uppercase">@{{ extension || mode }}</span>
                </div>

                <span
                    v-if="showBadge && extension"
                    class="absolute top-1.5 right-1.5 z-10 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-md"
                    :class="badgeClass"
                >@{{ extension }}</span>

                <div
                    v-if="isVideo || isAudio"
                    class="pointer-events-none absolute inset-0 flex items-center justify-center"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-xl text-white shadow-lg"
                        :class="isVideo ? 'icon-play' : 'icon-audio'"
                    ></span>
                </div>

                <div
                    class="absolute flex items-center justify-center gap-2 opacity-0 transition-opacity group-hover:opacity-100"
                    :class="actionsLayout === 'center'
                        ? 'inset-0 bg-black/80 dark:bg-cherry-800/90'
                        : 'inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2'"
                >
                    <button v-if="allowDrag && showDragHandle" type="button" class="icon-drag rounded bg-white/20 p-1.5 text-white" @click.stop="$emit('drag-handle', media)"></button>
                    <button v-if="allowPreview" type="button" class="icon-view rounded bg-white/20 p-1.5 text-white" aria-label="@lang('admin::app.components.media.images.preview-image')" @click.stop="$emit('preview', media)"></button>
                    <button v-if="allowReplace" type="button" class="icon-edit rounded bg-white/20 p-1.5 text-white" aria-label="@lang('admin::app.components.media.images.replace-image')" @click.stop="$emit('replace', media)"></button>
                    <button v-if="allowRemove" type="button" class="icon-delete rounded bg-white/20 p-1.5 text-white" aria-label="@lang('admin::app.components.media.images.delete-image')" @click.stop="$emit('remove', media)"></button>
                    <slot name="actions" :media="media"></slot>
                </div>
            </div>

            <div
                v-if="showFilename"
                class="flex items-center gap-1.5 px-2 py-1.5"
                :class="selectable ? '' : 'justify-center'"
            >
                <label v-if="selectable" class="flex shrink-0 cursor-pointer items-center" @click.stop>
                    <input
                        type="checkbox"
                        class="peer hidden"
                        :checked="selected"
                        @change="$emit('update:selected', $event.target.checked)"
                    />
                    <span class="icon-checkbox-normal shrink-0 rounded-md text-2xl peer-checked:icon-checkbox-check peer-checked:text-violet-700"></span>
                </label>

                <p
                    class="truncate text-xs text-gray-700 dark:text-gray-300"
                    :class="selectable ? '' : 'w-full text-center'"
                    :title="displayName"
                >
                    <span v-if="showExtension && extension" class="mr-1 uppercase text-gray-400">@{{ extension }}</span>@{{ displayName }}
                </p>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-media-card', {
            template: '#v-media-card-template',
            props: {
                media: { type: Object, default: () => ({}) },
                mode: { type: String, default: 'image' },
                width: { type: [String, Number], default: null },
                height: { type: [String, Number], default: null },
                allowReplace: Boolean,
                allowRemove: Boolean,
                allowPreview: { type: Boolean, default: true },
                allowDrag: Boolean,
                allowSelect: Boolean,
                selectable: Boolean,
                selected: Boolean,
                showDragHandle: Boolean,
                showFilename: { type: Boolean, default: true },
                showExtension: { type: Boolean, default: true },
                showBadge: Boolean,
                actionsLayout: { type: String, default: 'bottom' },
                objectFit: { type: String, default: 'contain' },
                invalid: Boolean,
            },
            emits: ['preview', 'replace', 'remove', 'select', 'drag-start', 'drag-end', 'drag-handle', 'update:selected'],
            data() {
                return {
                    imageError: false,
                };
            },
            watch: {
                'media.url'() {
                    this.imageError = false;
                },
            },
            computed: {
                displayName() {
                    return this.media.name || this.media.file_name || this.media.filename || '';
                },
                extension() {
                    return (this.media.extension || this.displayName.split('.').pop() || '').toLowerCase();
                },
                isImage() {
                    return (this.media.mime_type || this.media.type || '').startsWith('image/') || this.mode === 'image';
                },
                isVideo() {
                    return (this.media.mime_type || this.media.type || '').startsWith('video/') || this.mode === 'video';
                },
                isAudio() {
                    return (this.media.mime_type || this.media.type || '').startsWith('audio/') || this.mode === 'audio';
                },
                mediaIcon() {
                    if (this.isVideo || this.extension.match(/^(mp4|webm|mov|avi)$/)) {
                        return 'icon-video';
                    }

                    if (this.isAudio || this.extension.match(/^(mp3|wav|ogg|m4a)$/)) {
                        return 'icon-audio';
                    }

                    if (this.extension === 'pdf') {
                        return 'icon-file-pdf';
                    }

                    return 'icon-file';
                },
                badgeClass() {
                    if (this.isVideo || this.isAudio) {
                        return 'bg-violet-600';
                    }

                    if (this.extension === 'pdf') {
                        return 'bg-red-600';
                    }

                    return 'bg-gray-600';
                },
                cardStyle() {
                    return {
                        width: this.width || undefined,
                        height: this.height || undefined,
                    };
                },
            },
        });
    </script>
@endPushOnce
