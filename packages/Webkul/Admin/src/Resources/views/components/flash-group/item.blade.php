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
            :class="[borderClasses[flash.type] ?? borderClasses.info, surfaceClasses[flash.type] ?? surfaceClasses.info]"
            class="relative overflow-hidden flex items-start gap-3 w-max max-w-[90vw] min-w-[300px] p-3.5 rounded-xl shadow-[0_12px_40px_-4px_rgba(0,0,0,0.25)] ring-1 ring-black/5 dark:ring-white/10 border ltr:border-l-[4px] rtl:border-r-[4px]"
            role="alert"
            :aria-live="flash.type === 'error' ? 'assertive' : 'polite'"
            @mouseenter="pauseTimer"
            @mouseleave="resumeTimer"
        >
            <span
                :class="iconClasses[flash.type] ?? iconClasses.info"
                class="flex items-center justify-center w-5 h-5 shrink-0 rounded-full text-white mt-0.5"
            >
                <svg v-if="flash.type === 'success'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>

                <svg v-else-if="flash.type === 'error'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>

                <svg v-else-if="flash.type === 'warning'" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="7" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>

                <svg v-else class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="11" x2="12" y2="16"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
            </span>

            <span
                class="flex-1 text-sm font-medium text-gray-800 dark:text-gray-100 break-words leading-snug ltr:pr-1 rtl:pl-1"
                v-text="flash.message"
            ></span>

            <span
                class="shrink-0 cursor-pointer text-gray-400 hover:text-gray-700 dark:text-white/40 dark:hover:text-white transition-colors leading-none text-base -mt-0.5"
                role="button"
                :aria-label="'{{ trans('admin::app.components.flash-group.close') }}'"
                @click="remove"
            >&times;</span>

            <span
                :class="iconClasses[flash.type] ?? iconClasses.info"
                class="absolute bottom-0 ltr:left-0 rtl:right-0 h-[3px] opacity-60"
                :style="{ width: progress + '%' }"
            ></span>
        </div>
    </script>

    <script type="module">
        app.component('v-flash-item', {
            template: '#v-flash-item-template',

            props: ['flash'],

            data() {
                return {
                    duration: 5000,

                    elapsed: 0,

                    lastTs: null,

                    rafId: null,

                    progress: 100,

                    borderClasses: {
                        success: 'border-green-200 dark:border-green-900/50 ltr:border-l-green-500 rtl:border-r-green-500',

                        error: 'border-red-200 dark:border-red-900/50 ltr:border-l-red-500 rtl:border-r-red-500',

                        warning: 'border-yellow-200 dark:border-yellow-900/50 ltr:border-l-yellow-500 rtl:border-r-yellow-500',

                        info: 'border-sky-200 dark:border-sky-900/50 ltr:border-l-sky-500 rtl:border-r-sky-500',
                    },

                    surfaceClasses: {
                        success: 'bg-green-50 dark:bg-green-950/40',

                        error: 'bg-red-50 dark:bg-red-950/40',

                        warning: 'bg-yellow-50 dark:bg-yellow-950/40',

                        info: 'bg-sky-50 dark:bg-sky-950/40',
                    },

                    iconClasses: {
                        success: 'bg-green-500',

                        error: 'bg-red-500',

                        warning: 'bg-yellow-500',

                        info: 'bg-sky-500',
                    },
                };
            },

            created() {
                this.startTimer();
            },

            beforeUnmount() {
                this.clearTimer();
            },

            methods: {
                startTimer() {
                    this.lastTs = null;

                    this.rafId = requestAnimationFrame(this.tick);
                },

                tick(ts) {
                    if (this.lastTs === null) {
                        this.lastTs = ts;
                    }

                    this.elapsed += ts - this.lastTs;

                    this.lastTs = ts;

                    this.progress = Math.max(0, (1 - this.elapsed / this.duration) * 100);

                    if (this.elapsed >= this.duration) {
                        this.remove();

                        return;
                    }

                    this.rafId = requestAnimationFrame(this.tick);
                },

                clearTimer() {
                    if (this.rafId) {
                        cancelAnimationFrame(this.rafId);

                        this.rafId = null;
                    }
                },

                pauseTimer() {
                    this.clearTimer();
                },

                resumeTimer() {
                    if (this.elapsed < this.duration) {
                        this.startTimer();
                    }
                },

                remove() {
                    this.clearTimer();

                    this.$emit('onRemove', this.flash);
                },
            }
        });
    </script>
@endpushOnce
