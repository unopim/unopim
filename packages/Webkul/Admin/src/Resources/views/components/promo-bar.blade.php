@props(['banners' => []])

<v-promo-bar
    :banners='@json($banners)'
    dismiss-url="{{ route('admin.help.promo.dismiss') }}"
    dont-show-label="{{ trans('admin::app.help.banners.dont-show-again') }}"
></v-promo-bar>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-promo-bar-template"
    >
        <div
            v-if="list.length"
            id="unopim-promo-bar"
            class="shrink-0 w-full overflow-hidden"
        >
            <div
                v-for="(slide, slideIndex) in list"
                :key="slide.key + '-' + slide.version"
                v-show="slideIndex === idx"
                class="flex items-center gap-4 px-5 h-12 text-[13.5px]"
                :class="slide.key === 'cloud'
                    ? 'bg-gradient-to-r from-primary-700 to-primary-500 text-white'
                    : 'bg-gradient-to-r from-cherry-800 to-cherry-600 text-primary-100'"
            >
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <span
                        class="inline-flex shrink-0"
                        v-html="slide.icon"
                    ></span>

                    <span
                        class="inline-flex items-center h-5 px-[9px] rounded-full text-[10px] font-extrabold tracking-[0.06em] uppercase shrink-0"
                        :class="slide.key === 'cloud'
                            ? 'bg-white/[0.18] text-white'
                            : 'bg-white/[0.14] text-primary-200'"
                    >@{{ slide.tag }}</span>

                    <span
                        class="min-w-0 truncate font-medium [&>b]:font-bold"
                        v-html="slide.message"
                    ></span>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a
                        :href="slide.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 h-[30px] px-[14px] rounded-lg text-[12.5px] font-bold no-underline whitespace-nowrap transition-all hover:-translate-y-px hover:shadow-lg"
                        :class="slide.key === 'cloud'
                            ? 'bg-white text-primary-700'
                            : 'bg-primary-500 text-white'"
                    >
                        @{{ slide.cta }}

                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M5 12h14 M13 6l6 6-6 6"></path>
                        </svg>
                    </a>

                    <div
                        v-if="list.length > 1"
                        class="flex items-center gap-1.5"
                    >
                        <button
                            v-for="(dot, dotIndex) in list"
                            :key="'dot-' + dotIndex"
                            type="button"
                            class="h-[7px] rounded-full transition-all"
                            :class="dotIndex === idx ? 'w-[17px] bg-white' : 'w-[7px] bg-white/40'"
                            @click="go(dotIndex)"
                        ></button>
                    </div>

                    <button
                        type="button"
                        class="text-xs font-semibold opacity-80 hover:opacity-100 hover:underline cursor-pointer whitespace-nowrap max-[820px]:hidden"
                        @click="dismiss(slide)"
                    >
                        @{{ dontShowLabel }}
                    </button>

                    <button
                        type="button"
                        class="flex items-center justify-center w-7 h-7 rounded-md opacity-80 hover:opacity-100 hover:bg-white/20 transition-all"
                        @click="dismiss(slide)"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M18 6 6 18 M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-promo-bar', {
            template: '#v-promo-bar-template',

            props: {
                banners: {
                    type: Array,
                    default: () => [],
                },

                dismissUrl: {
                    type: String,
                    default: '',
                },

                dontShowLabel: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    list: [...this.banners],
                    idx: 0,
                    timer: null,
                };
            },

            mounted() {
                if (this.list.length > 1) {
                    this.timer = setInterval(() => this.next(), 6000);
                }
            },

            beforeUnmount() {
                if (this.timer) {
                    clearInterval(this.timer);
                }
            },

            methods: {
                next() {
                    this.idx = (this.idx + 1) % this.list.length;
                },

                go(i) {
                    this.idx = i;
                },

                dismiss(banner) {
                    this.$axios.post(this.dismissUrl, {
                        banner: banner.key,
                        version: banner.version,
                    }).then(() => {
                        this.list = this.list.filter(item => item.key !== banner.key || item.version !== banner.version);

                        if (this.idx >= this.list.length) {
                            this.idx = 0;
                        }

                        if (! this.list.length && this.timer) {
                            clearInterval(this.timer);

                            this.timer = null;
                        }
                    }).catch(() => {});
                },
            },
        });
    </script>
@endPushOnce
