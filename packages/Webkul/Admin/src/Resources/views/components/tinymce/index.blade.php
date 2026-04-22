<v-tinymce {{ $attributes }}></v-tinymce>

@pushOnce('scripts')
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.6.2/tinymce.min.js"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>

    <script
        type="text/x-template"
        id="v-tinymce-template"
    >
        <x-admin::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
        >
            <form @submit="handleSubmit($event, generate)" ref="magicAiGenerateForm">
                <!-- AI Content Generation Modal -->
                <x-admin::modal ref="magicAIModal" @toggle="toggleMagicAIModal">
                    <!-- Modal Header -->
                    <x-slot:header>
                        <p class="flex gap-2.5 items-center text-lg text-gray-800 dark:text-white font-bold">
                            <span class="icon-magic text-2xl text-gray-800"></span>
                            @lang('admin::app.components.tinymce.ai-generation.title')
                        </p>
                    </x-slot>

                    <!-- Modal Content -->
                    <x-slot:content>
                        <div v-show="! ai.content">
                            <!-- Default Prompt -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.components.tinymce.ai-generation.default-prompt')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select"
                                    name="default_prompt"
                                    :label="trans('admin::app.components.tinymce.ai-generation.default-prompt')"
                                    ::options="defaultPrompts"
                                    track-by="prompt"
                                    label-by="title"
                                    @input="onChangePrompt"
                                >
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>

                            <!-- Prompt -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.components.tinymce.ai-generation.prompt')
                                </x-admin::form.control-group.label>

                                <div class="relative w-full">
                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        class="h-[150px]"
                                        name="prompt"
                                        rules="required"
                                        v-model="ai.prompt"
                                        ref="promptInput"
                                        :label="trans('admin::app.components.tinymce.ai-generation.prompt')"
                                    />

                                    <div
                                        class="absolute bottom-2.5 left-1 text-gray-400 cursor-pointer text-2xl"
                                        @click="openSuggestions"
                                    >
                                        <span class="icon-at"></span>
                                    </div>
                                </div>

                                <x-admin::form.control-group.error control-name="prompt" />
                            </x-admin::form.control-group>

                            <!-- System Prompt Section -->
                            <div class="border rounded-md dark:border-cherry-800 mt-2">
                                <div
                                    class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-cherry-800 rounded-t-md"
                                    @click="showSystemPrompt = !showSystemPrompt"
                                >
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        @lang('admin::app.components.tinymce.ai-generation.system-prompt')
                                        <span class="text-xs text-gray-400 ml-1" v-if="selectedSystemPrompt">(@{{ selectedSystemPrompt.title }})</span>
                                    </span>
                                    <span class="text-gray-400 text-lg" v-text="showSystemPrompt ? '&#9650;' : '&#9660;'"></span>
                                </div>

                                <div v-show="showSystemPrompt" class="px-3 pb-3 border-t dark:border-cherry-800">
                                    <!-- System Prompt Selector -->
                                    <x-admin::form.control-group class="mt-2">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.components.tinymce.ai-generation.select-system-prompt')
                                        </x-admin::form.control-group.label>
                                        <select
                                            name="tone"
                                            v-model="ai.system_prompt_id"
                                            @change="onSystemPromptChange()"
                                            class="w-full py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                                        >
                                            <option v-for="sp in systemPrompts" :key="sp.id" :value="sp.id">
                                                @{{ sp.title }}
                                            </option>
                                        </select>
                                    </x-admin::form.control-group>

                                    <!-- Editable tone, max_tokens, temperature -->
                                    <x-admin::form.control-group class="mt-1">
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.components.tinymce.ai-generation.tone-instructions')
                                        </x-admin::form.control-group.label>
                                        <textarea
                                            v-model="ai.tone"
                                            rows="3"
                                            class="w-full py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                                        ></textarea>
                                    </x-admin::form.control-group>

                                    <div class="grid grid-cols-2 gap-3 mt-1">
                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label>
                                                @lang('admin::app.components.tinymce.ai-generation.max-tokens')
                                            </x-admin::form.control-group.label>
                                            <input
                                                type="number"
                                                v-model.number="ai.max_tokens"
                                                min="1"
                                                max="32768"
                                                class="w-full py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                                            />
                                        </x-admin::form.control-group>

                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label>
                                                @lang('admin::app.components.tinymce.ai-generation.temperature')
                                            </x-admin::form.control-group.label>
                                            <input
                                                type="number"
                                                v-model.number="ai.temperature"
                                                min="0"
                                                max="2"
                                                step="0.05"
                                                class="w-full py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                                            />
                                        </x-admin::form.control-group>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Generated Content -->
                        <div class="mt-5" v-show="ai.content">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs text-gray-800 dark:text-white font-medium">
                                    @lang('admin::app.components.tinymce.ai-generation.generated-content')
                                </label>

                                <label v-if="contentHasHtml" class="flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" v-model="showRichPreview" class="rounded text-violet-600" />
                                    @lang('admin::app.components.tinymce.ai-generation.rich-preview')
                                </label>
                            </div>

                            <!-- Rich HTML Preview -->
                            <div
                                v-if="contentHasHtml && showRichPreview"
                                class="h-[180px] overflow-y-auto p-3 border rounded-md bg-white dark:bg-cherry-800 dark:border-cherry-800 text-sm text-gray-700 dark:text-gray-300 max-w-none rich-content-preview"
                                v-html="ai.content"
                            ></div>

                            <style>
                                .rich-content-preview h1, .rich-content-preview h2, .rich-content-preview h3, .rich-content-preview h4 { font-weight: 700; margin: 0.5em 0 0.25em; }
                                .rich-content-preview h1 { font-size: 1.5em; }
                                .rich-content-preview h2 { font-size: 1.25em; }
                                .rich-content-preview h3 { font-size: 1.1em; }
                                .rich-content-preview p { margin: 0.4em 0; }
                                .rich-content-preview strong, .rich-content-preview b { font-weight: 700; }
                                .rich-content-preview em, .rich-content-preview i { font-style: italic; }
                                .rich-content-preview ul, .rich-content-preview ol { padding-left: 1.5em; margin: 0.4em 0; }
                                .rich-content-preview ul { list-style-type: disc; }
                                .rich-content-preview ol { list-style-type: decimal; }
                                .rich-content-preview li { margin: 0.2em 0; }
                                .rich-content-preview a { color: #6d28d9; text-decoration: underline; }
                                .rich-content-preview table { border-collapse: collapse; width: 100%; margin: 0.5em 0; }
                                .rich-content-preview th, .rich-content-preview td { border: 1px solid #e5e7eb; padding: 0.4em 0.6em; text-align: left; }
                                .rich-content-preview th { background: #f3f4f6; font-weight: 600; }
                            </style>

                            <!-- Plain text editor -->
                            <textarea
                                v-else
                                v-model="ai.content"
                                class="w-full h-[180px] py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                            ></textarea>

                            <span class="text-xs text-gray-500 mt-1 block">
                                @lang('admin::app.components.tinymce.ai-generation.generated-content-info')
                            </span>
                        </div>
                    </x-slot>

                    <!-- Modal Footer -->
                    <x-slot:footer>
                        <div class="flex items-center justify-between w-full">
                            <!-- Platform & Model compact selectors (left side, copilot-style) -->
                            <div class="flex items-center gap-2" v-if="!ai.content">
                                <select
                                    v-model="ai.platform_id"
                                    @change="onPlatformChange()"
                                    class="py-1.5 px-2 border rounded-md text-xs text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800 max-w-[140px]"
                                    title="@lang('admin::app.components.tinymce.ai-generation.platform')"
                                >
                                    <option v-for="p in platforms" :key="p.id" :value="p.id">@{{ p.label }}</option>
                                </select>
                                <select
                                    v-model="ai.model"
                                    class="py-1.5 px-2 border rounded-md text-xs text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800 max-w-[160px]"
                                    title="@lang('admin::app.components.tinymce.ai-generation.model')"
                                >
                                    <option v-for="m in aiModels" :key="m.id" :value="m.id">@{{ m.label }}</option>
                                </select>
                            </div>
                            <div v-else></div>

                            <!-- Action buttons (right side) -->
                            <div class="flex gap-x-2.5 items-center">
                            <template v-if="!ai.content">
                                <button
                                    type="submit"
                                    class="secondary-button"
                                    :disabled="isLoading"
                                    :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
                                >
                                    <template v-if="isLoading">
                                        <img
                                            class="animate-spin h-5 w-5 text-violet-700"
                                            src="{{ unopim_asset('images/spinner.svg') }}"
                                        />
                                        @lang('admin::app.components.tinymce.ai-generation.generating')
                                    </template>

                                    <template v-else>
                                        <span class="icon-magic text-2xl text-violet-700"></span>
                                        @lang('admin::app.components.tinymce.ai-generation.generate')
                                    </template>
                                </button>
                            </template>

                            <template v-else>
                                <button
                                    class="secondary-button"
                                    :disabled="isLoading"
                                    :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
                                >
                                    <template v-if="isLoading">
                                        <img
                                            class="animate-spin h-5 w-5 text-violet-700"
                                            src="{{ unopim_asset('images/spinner.svg') }}"
                                        />
                                        @lang('admin::app.components.media.images.ai-generation.regenerating')
                                    </template>

                                    <template v-else>
                                        <span class="icon-magic text-2xl text-violet-700"></span>
                                        @lang('admin::app.components.media.images.ai-generation.regenerate')
                                    </template>
                                </button>

                                <button
                                    type="button"
                                    class="primary-button"
                                    :disabled="!ai.content"
                                    @click="apply"
                                >
                                    @lang('admin::app.components.tinymce.ai-generation.apply')
                                </button>
                            </template>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </form>
        </x-admin::form>
    </script>

    <script type="module">
        app.component('v-tinymce', {
            template: '#v-tinymce-template',

            props: ['selector', 'field', 'prompt'],

            data() {
                return {
                    currentSkin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                    currentContentCSS: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                    isLoading: false,

                    ai: {
                        enabled: Boolean("{{ core()->getConfigData('general.magic_ai.settings.enabled') }}"),
                        platform_id: null,
                        model: null,
                        prompt: null,
                        content: null,
                        system_prompt_id: null,
                        tone: '',
                        max_tokens: 1024,
                        temperature: 0.7,
                    },

                    showRichPreview: true,
                    showSystemPrompt: false,
                    selectedSystemPrompt: null,
                    systemPrompts: @json(app(\Webkul\MagicAI\Repository\MagicAISystemPromptRepository::class)->all()->toArray()),
                    platforms: [],
                    aiModels: [],
                    defaultPrompts: [],
                    suggestionValues: [],
                    resourceId: "{{ request()->id }}",
                    entityName: "{{ $attributes->get('entity-name', 'attribute') }}",
                };
            },

            computed: {
                contentHasHtml() {
                    if (!this.ai.content) return false;
                    return /<[a-z][\s\S]*>/i.test(this.ai.content);
                },
            },

            mounted() {
                this.init();
                this.$emitter.on('change-theme', (theme) => {
                    tinymce.get(0).destroy();
                    this.currentSkin = (theme === 'dark') ? 'oxide-dark' : 'oxide';
                    this.currentContentCSS = (theme === 'dark') ? 'dark' : 'default';
                    this.init();
                });
            },

            methods: {
                init() {
                    let self = this;

                    let tinyMCEHelper = {
                        initTinyMCE: function(extraConfiguration) {
                            let self2 = this;

                            let config = {
                                relative_urls: false,
                                menubar: false,
                                remove_script_host: false,
                                document_base_url: '{{ asset('/') }}',
                                uploadRoute: '{{ route('admin.tinymce.upload') }}',
                                csrfToken: '{{ csrf_token() }}',
                                ...extraConfiguration,
                                skin: self.currentSkin,
                                content_css: self.currentContentCSS,
                            };

                            const image_upload_handler = (blobInfo, progress) => new Promise((resolve,reject) => {
                                self2.uploadImageHandler(config, blobInfo, resolve, reject, progress);
                            });

                            tinymce.init({
                                ...config,

                                file_picker_callback: function(cb, value, meta) {
                                    self2.filePickerCallback(config, cb, value, meta);
                                },

                                images_upload_handler: image_upload_handler,
                            });
                        },

                        filePickerCallback: function(config, cb, value, meta) {
                            let input = document.createElement('input');
                            input.setAttribute('type', 'file');
                            input.setAttribute('accept', 'image/*');

                            input.onchange = function() {
                                let file = this.files[0];

                                let reader = new FileReader();
                                reader.readAsDataURL(file);
                                reader.onload = function() {
                                    let id = 'blobid' + new Date().getTime();
                                    let blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                    let base64 = reader.result.split(',')[1];
                                    let blobInfo = blobCache.create(id, file, base64);

                                    blobCache.add(blobInfo);

                                    cb(blobInfo.blobUri(), {
                                        title: file.name
                                    });
                                };
                            };

                            input.click();
                        },

                        uploadImageHandler: function(config, blobInfo, resolve, reject, progress) {
                            let xhr, formData;

                            xhr = new XMLHttpRequest();

                            xhr.withCredentials = false;

                            xhr.open('POST', config.uploadRoute);

                            xhr.upload.onprogress = ((e) => progress((e.loaded / e.total) * 100));

                            xhr.onload = function() {
                                let json;

                                if (xhr.status === 403) {
                                    reject("@lang('admin::app.error.tinymce.http-error')", {
                                        remove: true
                                    });

                                    return;
                                }

                                if (xhr.status < 200 || xhr.status >= 300) {
                                    reject("@lang('admin::app.error.tinymce.http-error')");

                                    return;
                                }

                                json = JSON.parse(xhr.responseText);

                                if (!json || typeof json.location != 'string') {
                                    reject("@lang('admin::app.error.tinymce.invalid-json')" + xhr.responseText);

                                    return;
                                }

                                resolve(json.location);
                            };

                            xhr.onerror = (() => reject("@lang('admin::app.error.tinymce.upload-failed')"));

                            formData = new FormData();
                            formData.append('_token', config.csrfToken);
                            formData.append('file', blobInfo.blob(), blobInfo.filename());

                            xhr.send(formData);
                        },
                    };

                    const baseToolbar = 'formatselect | fontsize bold italic strikethrough forecolor backcolor image alignleft aligncenter alignright alignjustify | link hr numlist bullist outdent indent removeformat code table';
                    const toolbar1 = self.ai.enabled ? baseToolbar + ' | aibutton' : baseToolbar;

                    tinyMCEHelper.initTinyMCE({
                        selector: this.selector,
                        plugins: 'image media wordcount save fullscreen code table lists link',
                        toolbar1: toolbar1,
                        image_advtab: true,
                        directionality: "ltr",

                        setup: editor => {
                            if (self.ai.enabled) {
                                editor.ui.registry.addIcon('magic',
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"> <g clip-path="url(#clip0_3148_2242)"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#6d28d9"/> <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#6d28d9"/> <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#6d28d9"/> <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#6d28d9"/> </g> <defs> <clipPath id="clip0_3148_2242"> <rect width="24" height="24" fill="white"/> </clipPath> </defs> </svg>'
                                    );

                                editor.ui.registry.addButton('aibutton', {
                                    text: "@lang('admin::app.components.tinymce.ai-btn-tile')",
                                    icon: 'magic',

                                    onAction: function() {
                                        self.ai = {
                                            ...self.ai,
                                            prompt: self.prompt,
                                            content: null,
                                        };

                                        self.magicAIModalToggle()
                                    }
                                });
                            }

                            editor.on('keyup', () => {
                                this.field.onInput(editor.getContent());
                            });
                        },
                    });
                },

                magicAIModalToggle() {
                    this.$refs.magicAIModal.toggle();
                },

                toggleMagicAIModal() {
                    this.$nextTick(() => {
                        if (this.$refs.promptInput) {
                            if (this.platforms.length === 0) {
                                this.fetchPlatforms();
                            }

                            if (this.defaultPrompts.length === 0) {
                                this.fetchDefaultPrompts();
                            }

                            const tribute = this.$tribute.init({
                                values: this.fetchSuggestionValues,
                                lookup: 'name',
                                fillAttr: 'code',
                                noMatchTemplate: "@lang('admin::app.common.no-match-found')",
                                selectTemplate: (item) => `@${item.original.code}`,
                                menuItemTemplate: (item) =>
                                    `<div class="p-1.5 rounded-md text-base cursor-pointer transition-all max-sm:place-self-center">${item.original.name || '[' + item.original.code + ']'}</div>`,
                            });

                            tribute.attach(this.$refs.promptInput);
                        }
                    });
                },

                async fetchPlatforms() {
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.platforms') }}");
                        this.platforms = response.data.platforms || [];

                        if (this.platforms.length) {
                            let defaultPlatform = this.platforms.find(p => p.is_default);
                            this.ai.platform_id = defaultPlatform ? defaultPlatform.id : this.platforms[0].id;
                            this.loadModelsForPlatform();
                        }

                        // Set default system prompt
                        if (this.systemPrompts.length && !this.ai.system_prompt_id) {
                            let enabled = this.systemPrompts.find(sp => sp.is_enabled);
                            let sp = enabled || this.systemPrompts[0];
                            this.ai.system_prompt_id = sp.id;
                            this.applySystemPrompt(sp);
                        }
                    } catch (error) {
                        console.error("Failed to fetch platforms:", error);
                    }
                },

                onSystemPromptChange() {
                    let sp = this.systemPrompts.find(s => s.id == this.ai.system_prompt_id);
                    if (sp) {
                        this.applySystemPrompt(sp);
                    }
                },

                applySystemPrompt(sp) {
                    this.selectedSystemPrompt = sp;
                    this.ai.tone = sp.tone;
                    this.ai.max_tokens = sp.max_tokens;
                    this.ai.temperature = sp.temperature;
                },

                onPlatformChange() {
                    this.loadModelsForPlatform();
                },

                loadModelsForPlatform() {
                    let platform = this.platforms.find(p => p.id === this.ai.platform_id);

                    if (platform && platform.models) {
                        this.aiModels = platform.models.map(m => ({ id: m, label: m }));
                        this.ai.model = this.aiModels[0]?.id || null;
                    } else {
                        this.aiModels = [];
                        this.ai.model = null;
                    }
                },

                openSuggestions() {
                    this.ai.prompt += ' @';
                    this.$nextTick(() => {
                        this.$refs.promptInput.focus();
                        const textarea = this.$refs.promptInput;
                        const keydownEvent = new KeyboardEvent("keydown", {
                            key: "@",
                            bubbles: true
                        });
                        textarea.dispatchEvent(keydownEvent);
                        const event = new KeyboardEvent("keyup", {
                            key: "@",
                            bubbles: true
                        });
                        textarea.dispatchEvent(event);
                    });
                },

                async fetchDefaultPrompts() {
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.default_prompt') }}", {
                            params: {
                                field: this.entityName,
                                purpose: 'text_generation',
                            }
                        });

                        this.defaultPrompts = response.data.prompts;
                    } catch (error) {
                        console.error("Failed to fetch Default Prompt:", error);
                    }
                },

                async fetchSuggestionValues(text, cb) {
                    if (!text && this.suggestionValues.length) {
                        cb(this.suggestionValues);
                        return;
                    }

                    const response = await fetch(`{{ route('admin.magic_ai.suggestion_values') }}?query=${text}&&entity_name=${this.entityName}&&locale={{ core()->getRequestedLocaleCode() }}`);
                    const data = await response.json();
                    this.suggestionValues = data;

                    cb(this.suggestionValues);
                },

                onChangePrompt(value) {
                    this.ai.prompt = JSON.parse(value)?.prompt;
                },

                generate(params, {
                    resetForm,
                    resetField,
                    setErrors
                }) {
                    this.isLoading = true;

                    this.$axios.post("{{ route('admin.magic_ai.content') }}", {
                            prompt: params['prompt'],
                            model: this.ai.model,
                            tone: this.ai.system_prompt_id,
                            system_prompt_text: this.ai.tone,
                            max_tokens: this.ai.max_tokens,
                            temperature: this.ai.temperature,
                            platform_id: this.ai.platform_id,
                            resource_id: this.resourceId,
                            resource_type: this.getResourceType(),
                            field_type: 'tinymce',
                            locale: "{{ core()->getRequestedLocaleCode() }}",
                            channel: "{{ core()->getRequestedChannelCode() }}",
                        })
                        .then(response => {
                            this.isLoading = false;

                            this.ai.content = response.data.content.replace(/<think[^>]*>.*?<\/think>/gs, '');
                        })
                        .catch(error => {
                            this.isLoading = false;

                            if (error.response.status == 422) {
                                setErrors(error.response.data.errors);
                            } else {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            }
                        });
                },

                getResourceType() {
                    switch (this.entityName) {
                        case 'category_field':
                            return 'category';
                        default:
                            return 'product';
                    }
                },

                apply() {
                    if (!this.ai.content) {
                        return;
                    }

                    tinymce.get(this.selector.replace('textarea#', '')).setContent(this.ai.content.replace(/\r?\n/g,''))

                    this.field.onInput(this.ai.content.replace(/\r?\n/g, ''));

                    this.$refs.magicAIModal.close();
                },
            },
        })
    </script>
@endPushOnce
