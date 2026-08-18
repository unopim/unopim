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
    'readOnly'     => false,
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
    :read-only="@json($readOnly)"
    {{ $attributes }}
></v-media-add-tile>

@pushOnce('scripts')
    <script type="text/x-template" id="v-media-add-tile-template">
        <label
            class="group flex min-h-[176px] w-full flex-col items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 p-3.5 text-center transition-colors dark:border-gray-600 dark:bg-cherry-800"
            :class="[isDragging ? '!border-primary-500 !bg-primary-50 dark:!bg-cherry-700 shadow-md' : '', readOnly ? 'cursor-not-allowed' : 'cursor-pointer hover:border-unopim-primary hover:bg-gray-100 dark:hover:border-unopim-primary dark:hover:bg-cherry-700']"
            :for="readOnly ? null : (triggerModal ? null : inputId)"
            :aria-label="title"
            :title="allowedTypes"
            @click="! readOnly && triggerModal && $emit('trigger')"
            @dragover.prevent="! readOnly && (isDragging = true)"
            @dragenter.prevent="! readOnly && (isDragging = true)"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="! readOnly && onDrop($event)"
        >
            <span
                class="flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 transition-colors group-hover:border-unopim-primary/30 group-hover:text-unopim-primary dark:border-cherry-700 dark:bg-cherry-900 dark:text-gray-300"
                :class="[icon, compact ? 'h-8 w-8 text-xl' : 'h-9 w-9 text-2xl']"
            ></span>

            <p class="mt-2 text-sm font-semibold leading-5 text-gray-800 dark:text-white">@{{ title }}</p>

            <p v-if="hint && ! compact" class="mt-1 max-w-[9rem] px-2 text-xs leading-4 text-gray-500 dark:text-gray-400">@{{ hint }}</p>

            <slot></slot>

            <input
                v-if="! triggerModal && ! readOnly"
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
                readOnly: Boolean,
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
