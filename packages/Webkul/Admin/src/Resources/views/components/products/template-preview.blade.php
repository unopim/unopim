@props(['productId', 'field', 'label', 'channelCode', 'localeCode'])

<v-product-template-preview
    preview-url="{{ route('admin.catalog.products.template_preview', ['id' => $productId, 'channel' => $channelCode, 'locale' => $localeCode]) }}"
    attribute="{{ $field->code }}"
    label="{{ $label }}"
></v-product-template-preview>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-template-preview-template"
    >
        <span class="inline-flex">
            <button
                type="button"
                class="icon-view inline-flex items-center justify-center w-6 h-6 rounded-md bg-gray-400 text-base text-white hover:bg-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:bg-cherry-700 cursor-pointer"
                title="@lang('admin::app.catalog.products.edit.templates.preview.button')"
                aria-label="@lang('admin::app.catalog.products.edit.templates.preview.button')"
                @click="open"
            ></button>

            <x-admin::modal
                ref="previewModal"
                type="large"
            >
                <x-slot:header>
                    <div class="flex items-center gap-2 min-w-0">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">
                            @lang('admin::app.catalog.products.edit.templates.preview.title')
                        </p>

                        <span
                            class="truncate text-sm text-gray-500 dark:text-gray-400"
                            v-text="label"
                        ></span>

                        <button
                            type="button"
                            class="shrink-0 p-1 rounded-md text-gray-500 hover:bg-primary-50 dark:text-gray-300 dark:hover:bg-cherry-800 disabled:opacity-50"
                            title="@lang('admin::app.catalog.products.edit.templates.preview.refresh')"
                            aria-label="@lang('admin::app.catalog.products.edit.templates.preview.refresh')"
                            :disabled="loading"
                            @click="refresh"
                        >
                            <svg
                                class="w-5 h-5"
                                :class="{ 'animate-spin': loading }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                        </button>
                    </div>
                </x-slot>

                <x-slot:content class="flex flex-col gap-3">
                    <div
                        v-if="loaded"
                        class="max-h-[50vh] overflow-y-auto p-3 rounded-md border border-gray-200 dark:border-cherry-800 bg-gray-50 dark:bg-cherry-900"
                    >
                        <div
                            v-if="output !== '' && richOutput !== null"
                            class="rich-content-preview break-words text-sm text-gray-800 dark:text-white"
                            v-html="richOutput"
                        ></div>

                        <p
                            v-else-if="output !== ''"
                            class="whitespace-pre-wrap break-words text-sm text-gray-800 dark:text-white"
                            v-text="output"
                        ></p>

                        <p
                            v-else
                            class="text-sm italic text-gray-500 dark:text-gray-400"
                        >
                            @lang('admin::app.catalog.products.edit.templates.preview.empty')
                        </p>
                    </div>

                    <p
                        v-if="failed"
                        class="text-xs text-red-600 dark:text-red-400"
                    >
                        @lang('admin::app.catalog.products.edit.templates.preview.failed')
                    </p>

                    <ul
                        v-if="errors.length"
                        class="flex flex-col gap-1"
                    >
                        <li
                            v-for="(error, index) in errors"
                            :key="'error-' + index"
                            class="text-xs text-red-600 dark:text-red-400"
                            v-text="error"
                        ></li>
                    </ul>

                    <ul
                        v-if="warnings.length"
                        class="flex flex-col gap-1"
                    >
                        <li
                            v-for="(warning, index) in warnings"
                            :key="'warning-' + index"
                            class="text-xs text-amber-700 dark:text-amber-300"
                            v-text="warning"
                        ></li>
                    </ul>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @lang('admin::app.catalog.products.edit.templates.preview.saved-values')
                    </p>
                </x-slot>
            </x-admin::modal>
        </span>
    </script>

    <script type="module">
        app.component('v-product-template-preview', {
            template: '#v-product-template-preview-template',

            props: {
                previewUrl: {
                    type: String,
                    required: true,
                },

                attribute: {
                    type: String,
                    required: true,
                },

                label: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    loading: false,

                    loaded: false,

                    failed: false,

                    request: 0,

                    output: '',

                    rich: false,

                    errors: [],

                    warnings: [],
                };
            },

            computed: {
                richOutput() {
                    if (! this.rich || typeof window.DOMPurify === 'undefined') {
                        return null;
                    }

                    return window.DOMPurify.sanitize(this.output, {
                        FORBID_TAGS: ['style'],
                        FORBID_ATTR: ['style', 'class'],
                    });
                },
            },

            methods: {
                open() {
                    this.$refs.previewModal.open();

                    this.refresh();
                },

                refresh() {
                    const form = this.$el.closest('form');

                    if (! form) {
                        return;
                    }

                    const request = ++this.request;

                    this.loading = true;

                    this.$axios
                        .post(this.previewUrl, this.payload(form))
                        .then(({ data }) => {
                            if (request !== this.request) {
                                return;
                            }

                            this.output = data.output;

                            this.rich = data.rich_text === true;

                            this.errors = data.errors;

                            this.warnings = data.warnings;

                            this.failed = false;

                            this.loaded = true;
                        })
                        .catch(() => {
                            if (request === this.request) {
                                this.failed = true;
                            }
                        })
                        .finally(() => {
                            if (request === this.request) {
                                this.loading = false;
                            }
                        });
                },

                payload(form) {
                    const payload = new FormData();

                    payload.append('attribute', this.attribute);

                    for (const [name, value] of new FormData(form)) {
                        if (name.startsWith('values[') && typeof value === 'string') {
                            payload.append(name, value);
                        }
                    }

                    return payload;
                },
            },
        });
    </script>
@endPushOnce
