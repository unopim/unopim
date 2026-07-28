@props([
    'items' => null,
])

<v-switcher
    @if (! is_null($items))
        :items='@json($items)'
    @endif
    {{ $attributes }}
></v-switcher>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-switcher-template"
    >
        <x-admin::form.searchable-menu
            ::items="menuItems"
            ::model-value="modelValue"
            ::searchable="searchable"
            @update:model-value="$emit('update:modelValue', $event)"
        >
            <x-slot:toggle>
                <button
                    type="button"
                    :class="toggleClass"
                    :title="selectedLabel"
                >
                    <span
                        :class="[icon, {'text-2xl': variant === 'button'}]"
                        v-if="icon"
                    ></span>

                    <span v-text="toggleLabel"></span>

                    <span
                        class="icon-chevron-down"
                        :class="{'text-2xl': variant === 'button'}"
                    ></span>
                </button>
            </x-slot>
        </x-admin::form.searchable-menu>
    </script>

    <script type="module">
        app.component('v-switcher', {
            template: '#v-switcher-template',

            props: {
                items: {
                    type: Array,
                    default: () => [],
                },

                modelValue: {
                    type: String,
                    default: '',
                },

                /**
                 * Ids to tick in the menu -- translated locales, configured
                 * channels, whatever the caller considers "already filled in".
                 */
                marked: {
                    type: Array,
                    default: () => [],
                },

                icon: {
                    type: String,
                    default: 'icon-language',
                },

                variant: {
                    type: String,
                    default: 'chip',
                },

                searchable: {
                    type: Boolean,
                    default: true,
                },
            },

            emits: ['update:modelValue'],

            computed: {
                menuItems() {
                    return this.items.map(item => ({
                        id:      item.id,
                        label:   item.label,
                        checked: this.marked.includes(item.id),
                    }));
                },

                selectedLabel() {
                    return this.items.find(item => item.id === this.modelValue)?.label ?? this.modelValue;
                },

                toggleLabel() {
                    return this.variant === 'button' ? this.selectedLabel : '(' + this.modelValue + ')';
                },

                toggleClass() {
                    return this.variant === 'button'
                        ? 'flex gap-x-1 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer appearance-none transition-all hover:!bg-primary-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50'
                        : 'flex gap-1 items-center px-1 h-5 rounded-full box-shadow bg-gray-100 border border-gray-200 text-gray-600 dark:!text-gray-600 uppercase cursor-pointer transition-all hover:bg-primary-50';
                },
            },
        });
    </script>
@endPushOnce
