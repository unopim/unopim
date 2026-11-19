<v-magic-ai-prompt-input {{ $attributes }}></v-magic-ai-prompt-input>

@pushOnce('scripts')
    <script type="text/x-template" id="v-magic-ai-prompt-input-template">
        <div class="relative w-full">
            <x-admin::form.control-group.control
                type="textarea"
                class="h-full"
                name="prompt"
                rules="required"
                v-model="prompt"
                ref="promptInput"
                :label="trans('admin::app.configuration.prompt.create.prompt')"
                :placeholder="trans('admin::app.configuration.prompt.create.prompt')"
            />

            <button
                type="button"
                class="absolute bottom-2.5 ltr:left-1 rtl:right-1 text-gray-400 cursor-pointer text-2xl"
                aria-label="@lang('admin::app.configuration.prompt.create.insert-attribute')"
                title="@lang('admin::app.configuration.prompt.create.insert-attribute')"
                @click="openSuggestions"
            >
                <span class="icon-at"></span>
            </button>
        </div>
    </script>

    <script type="module">
        app.component('v-magic-ai-prompt-input', {
            template: '#v-magic-ai-prompt-input-template',

            props: {
                modelValue: {
                    type: String,
                    default: '',
                },

                entityName: {
                    type: String,
                    default: null,
                },
            },

            emits: ['update:modelValue'],

            data() {
                return {
                    prompt: this.modelValue ?? '',
                };
            },

            watch: {
                modelValue(value) {
                    this.prompt = value ?? '';
                },

                prompt(value) {
                    this.$emit('update:modelValue', value);
                },
            },

            mounted() {
                const tribute = this.$tribute.init({
                    values: this.fetchSuggestionValues,
                    lookup: 'name',
                    fillAttr: 'code',
                    noMatchTemplate: "@lang('admin::app.common.no-match-found')",
                    selectTemplate: (item) => `@${item.original.code}`,
                    menuItemTemplate: (item) => {
                        const element = document.createElement('div');
                        element.className = 'p-1.5 rounded-md text-base cursor-pointer transition-all max-sm:place-self-center';
                        element.textContent = item.original.name || '[' + item.original.code + ']';

                        return element.outerHTML;
                    },
                });

                tribute.attach(this.$refs.promptInput);
            },

            methods: {
                openSuggestions() {
                    this.prompt += ' @';

                    this.$nextTick(() => {
                        const textarea = this.$refs.promptInput;

                        textarea.focus();
                        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                        textarea.dispatchEvent(new KeyboardEvent('keydown', { key: '@', bubbles: true }));
                        textarea.dispatchEvent(new KeyboardEvent('keyup', { key: '@', bubbles: true }));
                    });
                },

                async fetchSuggestionValues(text, cb) {
                    const response = await fetch(
                        `{{ route('admin.magic_ai.suggestion_values') }}?query=${encodeURIComponent(text)}&entity_name=${encodeURIComponent(this.entityName)}&locale={{ core()->getRequestedLocaleCode() }}`
                    );

                    cb(await response.json());
                },
            },
        });
    </script>
@endPushOnce
