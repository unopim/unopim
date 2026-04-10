@props(['position' => 'left'])

<v-tabs
    position="{{ $position }}"
    {{ $attributes }}
>
    <x-admin::shimmer.tabs />
</v-tabs>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-tabs-template"
    >
        <div>
            <div
                class="flex gap-4 pt-2"
                :class="containerClass"
                :style="positionStyles"
            >
                <div
                    v-for="tab in tabs"
                    class="pb-3.5 px-2.5 text-base font-medium cursor-pointer transition-all border-b-2"
                    :class="tab.isActive
                        ? 'border-violet-700 text-violet-700'
                        : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-violet-700'"
                    @click="change(tab)"
                >
                    <span v-text="tab.title"></span>

                    <span
                        v-if="tab.badge !== null && tab.badge !== undefined"
                        class="ml-1 inline-flex items-center justify-center min-w-5 h-5 px-1.5 bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 rounded-full text-xs font-semibold"
                        v-text="tab.badge"
                    ></span>
                </div>
            </div>

            <div>
                <slot></slot>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-tabs', {
            template: '#v-tabs-template',

            props: {
                position: {
                    type: String,
                    default: 'left',
                },

                containerClass: {
                    type: String,
                    default: 'justify-center bg-neutral-100 dark:bg-cherry-900 max-sm:hidden',
                },
            },

            data() {
                return {
                    tabs: []
                }
            },

            computed: {
                positionStyles() {
                    return [
                        `justify-content: ${this.position}`
                    ];
                },
            },

            methods: {
                change(selectedTab) {
                    // Compare by the same key we emit so tabs with distinct values
                    // but matching titles don't highlight the wrong tab.
                    const selectedKey = selectedTab.value ?? selectedTab.title;
                    this.tabs.forEach(tab => {
                        tab.isActive = ((tab.value ?? tab.title) === selectedKey);
                    });

                    this.$emit('change', selectedKey);
                },
            },
        });
    </script>
@endPushOnce
