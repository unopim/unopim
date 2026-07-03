<v-flash-item
    v-for='flash in flashes'
    :key='flash.uid'
    :flash="flash"
    @onRemove="remove($event)"
>
</v-flash-item>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-flash-item-template"
    >
        <div
            class="flex items-center gap-3 w-max max-w-[90vw] py-2 ltr:pl-2 ltr:pr-4 rtl:pr-2 rtl:pl-4 rounded-full shadow-[0_8px_24px_rgba(0,0,0,0.12)] border border-gray-100 dark:border-cherry-700 bg-white dark:bg-cherry-800"
        >
            <span
                class="flex items-center justify-center w-6 h-6 shrink-0 rounded-full text-white"
                :style="'background: ' + iconColors[flash.type]"
            >
                <svg v-if="flash.type === 'success'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>

                <svg v-else-if="flash.type === 'error'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>

                <svg v-else-if="flash.type === 'warning'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="7" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>

                <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="11" x2="12" y2="16"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
            </span>

            <span class="text-sm font-medium text-gray-800 dark:text-white break-words" v-html="flash.message"></span>

            <span
                class="shrink-0 cursor-pointer text-gray-400 hover:text-gray-600 dark:text-white/50 dark:hover:text-white transition-colors leading-none text-lg"
                @click="remove"
            >&times;</span>
        </div>
    </script>

    <script type="module">
        app.component('v-flash-item', {
            template: '#v-flash-item-template',

            props: ['flash'],

            data() {
                return {
                    iconColors: {
                        success: '#22c55e',

                        error: '#ef4444',

                        warning: '#eab308',

                        info: '#0c8ce9',
                    },
                };
            },

            created() {
                var self = this;

                setTimeout(function() {
                    self.remove()
                }, 5000)
            },

            methods: {
                remove() {
                    this.$emit('onRemove', this.flash)
                }
            }
        });
    </script>
@endpushOnce
