@props([
    'title'      => '',
    'value'      => '',
    'isSelected' => false,
])

<v-tab-item
    title="{{ $title }}"
    value="{{ $value ?: $title }}"
    is-selected="{{ $isSelected }}"
    {{ $attributes->merge(['class' => 'p-5 max-1180:px-5']) }}
>
    <template v-slot>
        {{ $slot }}
    </template>
</v-tab-item>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-tab-item-template"
    >
        <div
            v-if="isActive"
            class="animate-[on-fade_0.5s_ease-in-out]"
        >
            <slot></slot>
        </div>
    </script>

    <script type="module">
        app.component('v-tab-item', {
            template: '#v-tab-item-template',

            props: ['title', 'value', 'isSelected'],

            data() {
                return {
                    isActive: false,

                    badge: null,
                }
            },

            mounted() {
                this.isActive = this.isSelected;

                /**
                 * On mounted, pushing element to its parents component.
                 */
                this.$parent.$data.tabs.push(this);
            }
        });
    </script>
@endPushOnce
