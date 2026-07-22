@props([
    'title'        => '',
    'hint'         => '',
    'allowedTypes' => '',
    'accept'       => '',
    'inputId'      => 'mediaInput',
    'icon'         => 'icon-image',
    'multiple'     => false,
    'compact'      => false,
    'triggerModal' => false,
])

<v-media-add-tile
    title="{{ $title }}"
    hint="{{ $hint }}"
    allowed-types="{{ $allowedTypes }}"
    :accept="@json($accept)"
    input-id="{{ $inputId }}"
    icon="{{ $icon }}"
    :multiple="@json($multiple)"
    :compact="@json($compact)"
    :trigger-modal="@json($triggerModal)"
    {{ $attributes }}
></v-media-add-tile>

@pushOnce('scripts')
    <script type="text/x-template" id="v-media-add-tile-template">
        <label
            class="group flex min-h-[176px] w-full cursor-pointer flex-col items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 p-3.5 text-center transition-colors hover:border-unopim-primary hover:bg-gray-100 dark:border-gray-600 dark:bg-cherry-800 dark:hover:border-unopim-primary dark:hover:bg-cherry-700"
            :class="isDragging ? '!border-primary-500 !bg-primary-50 dark:!bg-cherry-700 shadow-md' : ''"
            :for="triggerModal ? null : inputId"
            :aria-label="title"
            :title="allowedTypes"
            @click="triggerModal && $emit('trigger')"
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <span
                class="flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 transition-colors group-hover:border-unopim-primary/30 group-hover:text-unopim-primary dark:border-cherry-700 dark:bg-cherry-900 dark:text-gray-300"
                :class="[icon, compact ? 'h-8 w-8 text-xl' : 'h-9 w-9 text-2xl']"
            ></span>

            <p class="mt-2 text-sm font-semibold leading-5 text-gray-800 dark:text-white">@{{ title }}</p>

            <p v-if="hint && ! compact" class="mt-1 max-w-[9rem] px-2 text-xs leading-4 text-gray-500 dark:text-gray-400">@{{ hint }}</p>

            <slot></slot>

            <input
                v-if="! triggerModal"
                ref="input"
                type="file"
                class="hidden"
                :id="inputId"
                :accept="accept"
                :multiple="multiple"
                @change="onChange"
            />
        </label>
    </script>

    <script type="module">
        app.component('v-media-add-tile', {
            template: '#v-media-add-tile-template',
            props: {
                title: { type: String, default: '' },
                hint: { type: String, default: '' },
                allowedTypes: { type: String, default: '' },
                accept: { type: String, default: '' },
                inputId: { type: String, default: 'mediaInput' },
                icon: { type: String, default: 'icon-image' },
                multiple: Boolean,
                compact: Boolean,
                triggerModal: Boolean,
            },
            emits: ['change', 'drop', 'trigger'],
            data() {
                return {
                    isDragging: false,
                };
            },
            methods: {
                onChange(event) {
                    this.$emit('change', event.target.files);
                },
                onDrop(event) {
                    this.isDragging = false;

                    const files = event.dataTransfer ? event.dataTransfer.files : null;

                    if (files && files.length) {
                        this.$emit('drop', files);
                    }
                },
            },
        });
    </script>
@endPushOnce
