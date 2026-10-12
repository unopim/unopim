@props([
    'navigationTitle' => '',
    'infoTitle'       => '',
    'storageKey'      => 'unopim-side-rail',
])

<v-side-rail
    navigation-title="{{ $navigationTitle }}"
    info-title="{{ $infoTitle }}"
    storage-key="{{ $storageKey }}"
    {{ $attributes }}
>
    @isset($navigation)
        <template v-slot:navigation>
            {{ $navigation }}
        </template>
    @endisset

    @isset($info)
        <template v-slot:info>
            {{ $info }}
        </template>
    @endisset
</v-side-rail>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-side-rail-template"
    >
        <div
            class="right-column relative flex flex-col self-start shrink-0 transition-all"
            :class="{'items-center': collapsed}"
            :style="{ width: collapsed ? '44px' : '360px', maxWidth: '100%' }"
        >
            <button
                type="button"
                class="absolute flex shrink-0 items-center justify-center w-7 h-7 rounded-md text-gray-400 transition-all hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-cherry-800 dark:hover:text-white ltr:right-0 rtl:left-0"
                style="top: -50px"
                :title="collapsed ? expandLabel : collapseLabel"
                :aria-label="collapsed ? expandLabel : collapseLabel"
                @click="toggle"
            >
                <span
                    class="icon-collapse text-2xl transition-all"
                    :class="collapsed ? 'ltr:rotate-[0] rtl:rotate-[180deg]' : 'ltr:rotate-[180deg] rtl:rotate-[0]'"
                ></span>
            </button>

            <div
                class="flex flex-col gap-2 w-full"
                v-show="! collapsed"
            >
                <slot name="navigation"></slot>

                <slot name="info"></slot>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-side-rail', {
            template: '#v-side-rail-template',

            props: {
                navigationTitle: {
                    type: String,
                    default: '',
                },

                infoTitle: {
                    type: String,
                    default: '',
                },

                storageKey: {
                    type: String,
                    default: 'unopim-side-rail',
                },
            },

            data() {
                return {
                    collapsed: false,
                    collapseLabel: @json(trans('admin::app.components.layouts.side-rail.collapse')),
                    expandLabel: @json(trans('admin::app.components.layouts.side-rail.expand')),
                };
            },

            mounted() {
                this.collapsed = window.localStorage.getItem(this.storageKey) === '1';
            },

            methods: {
                toggle() {
                    this.collapsed = ! this.collapsed;

                    window.localStorage.setItem(this.storageKey, this.collapsed ? '1' : '0');
                },
            },
        });
    </script>
@endPushOnce
