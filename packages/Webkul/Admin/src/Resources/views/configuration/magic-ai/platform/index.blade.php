<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.configuration.platform.title')
    </x-slot>

    <v-magic-ai-platform>
        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.configuration.platform.title')
            </p>
            <div class="flex gap-x-2.5 items-center">
                <button type="button" class="primary-button">
                    @lang('admin::app.configuration.platform.create-btn')
                </button>
            </div>
        </div>
        <x-admin::shimmer.datagrid />
    </v-magic-ai-platform>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-magic-ai-platform-template">
            <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('admin::app.configuration.platform.title')
                </p>
                <div class="flex gap-x-2.5 items-center">
                    <button type="button" class="primary-button" @click="openCreateModal()">
                        @lang('admin::app.configuration.platform.create-btn')
                    </button>
                </div>
            </div>

            <!-- Setup Guide Banner (no platforms configured) -->
            @if($platformCount === 0)
                <div class="mt-4 p-6 bg-gradient-to-r from-violet-50 to-blue-50 dark:from-cherry-900 dark:to-cherry-800 rounded-lg border border-violet-200 dark:border-cherry-700">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-violet-100 dark:bg-violet-900 rounded-lg flex items-center justify-center">
                            <span class="text-2xl" role="img" aria-label="@lang('admin::app.configuration.platform.setup.lightning-icon')" title="@lang('admin::app.configuration.platform.setup.lightning-icon')">&#9889;</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-50 mb-1">
                                @lang('admin::app.configuration.platform.setup.title')
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                @lang('admin::app.configuration.platform.setup.description')
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="flex-shrink-0 w-6 h-6 bg-violet-200 dark:bg-violet-800 rounded-full flex items-center justify-center text-xs font-bold text-violet-700 dark:text-violet-200">1</span>
                                    @lang('admin::app.configuration.platform.setup.step-1')
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="flex-shrink-0 w-6 h-6 bg-violet-200 dark:bg-violet-800 rounded-full flex items-center justify-center text-xs font-bold text-violet-700 dark:text-violet-200">2</span>
                                    @lang('admin::app.configuration.platform.setup.step-2')
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="flex-shrink-0 w-6 h-6 bg-violet-200 dark:bg-violet-800 rounded-full flex items-center justify-center text-xs font-bold text-violet-700 dark:text-violet-200">3</span>
                                    @lang('admin::app.configuration.platform.setup.step-3')
                                </div>
                            </div>
                            <button
                                type="button"
                                class="mt-4 primary-button"
                                @click="openCreateModal()"
                            >
                                @lang('admin::app.configuration.platform.setup.add-first')
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Warning: No default platform -->
            @if($platformCount > 0 && !$hasDefault)
                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700 flex items-center gap-3">
                    <span class="text-xl" role="img" aria-label="@lang('admin::app.configuration.platform.setup.warning-icon')" title="@lang('admin::app.configuration.platform.setup.warning-icon')">&#9888;</span>
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        @lang('admin::app.configuration.platform.setup.no-default-warning')
                    </p>
                </div>
            @endif

            <!-- DataGrid -->
            <x-admin::datagrid src="{{ route('admin.magic_ai.platform.index') }}" ref="datagrid">
                <template #body="{ columns, records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 cursor-pointer transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="editModal(record.actions.find(a => a.index === 'action_1')?.url)"
                    >
                        <p v-text="record.label" class="truncate" :title="record.label"></p>
                        <p v-html="record.provider"></p>
                        <p v-text="record.models" class="truncate" :title="record.models"></p>
                        <p v-html="record.is_default"></p>
                        <p v-html="record.status"></p>
                        <p v-text="record.created_at"></p>
                        <div class="flex justify-end gap-1" @click.stop>
                            <a @click="setAsDefault(record)" v-if="!record.is_default_raw" title="@lang('admin::app.configuration.platform.set-default')">
                                <span class="icon-star cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800"></span>
                            </a>
                            <a @click="editModal(record.actions.find(a => a.index === 'action_1')?.url)" title="@lang('admin::app.configuration.platform.datagrid.edit')" aria-label="@lang('admin::app.configuration.platform.datagrid.edit')">
                                <span :class="record.actions.find(a => a.index === 'action_1')?.icon" class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800"></span>
                            </a>
                            <a @click="performAction(record.actions.find(a => a.index === 'action_2'))" title="@lang('admin::app.configuration.platform.datagrid.delete')" aria-label="@lang('admin::app.configuration.platform.datagrid.delete')">
                                <span :class="record.actions.find(a => a.index === 'action_2')?.icon" class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800"></span>
                            </a>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            <!-- Modal -->
            <x-admin::form v-slot="{ meta, errors, handleSubmit }" as="div" ref="modalform">
                <form @submit="handleSubmit($event, saveWithTest)" ref="platformForm">
                    <x-admin::modal ref="platformModal">
                        <x-slot:header>
                            <span class="dark:text-slate-50 text-lg font-semibold">
                                @{{ isEditing ? '@lang('admin::app.configuration.platform.edit-title')' : '@lang('admin::app.configuration.platform.create-title')' }}
                            </span>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group.control type="hidden" name="id" v-model="form.id" />

                            <!-- Provider -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.configuration.platform.fields.provider')
                                </x-admin::form.control-group.label>
                                @php
                                    $providerOptions = collect(\Webkul\MagicAI\Enums\AiProvider::cases())
                                        ->map(fn ($provider) => [
                                            'id'    => $provider->value,
                                            'label' => $provider->label(),
                                        ])
                                        ->values();
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="provider"
                                    rules="required"
                                    ::value="form.provider"
                                    :label="trans('admin::app.configuration.platform.fields.provider')"
                                    :placeholder="trans('admin::app.configuration.platform.fields.select-provider')"
                                    :options="json_encode($providerOptions)"
                                    track-by="id"
                                    label-by="label"
                                    @input="onProviderChange($event)"
                                >
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="provider" />
                            </x-admin::form.control-group>

                            <template v-if="form.provider">
                                <!-- Label -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.platform.fields.label')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="label"
                                        v-model="form.label"
                                        rules="required"
                                        :label="trans('admin::app.configuration.platform.fields.label')"
                                    />
                                    <x-admin::form.control-group.error control-name="label" />
                                </x-admin::form.control-group>

                                <!-- API Key (not for Ollama) -->
                                <x-admin::form.control-group v-if="form.provider !== 'ollama'">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.platform.fields.api-key')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="password"
                                        name="api_key"
                                        v-model="form.api_key"
                                        rules="required"
                                        :label="trans('admin::app.configuration.platform.fields.api-key')"
                                        @change="onApiKeyEntered()"
                                    />
                                    <p v-if="fetchingModels" class="mt-1 text-xs text-violet-600">@lang('admin::app.configuration.platform.fetching-models')...</p>
                                    <x-admin::form.control-group.error control-name="api_key" />
                                </x-admin::form.control-group>

                                <!-- API URL -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.configuration.platform.fields.api-url')
                                    </x-admin::form.control-group.label>
                                    <input
                                        type="text"
                                        name="api_url"
                                        v-model="form.api_url"
                                        class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:bg-cherry-800 dark:border-cherry-800"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">@lang('admin::app.configuration.platform.fields.api-url-hint')</p>
                                    <x-admin::form.control-group.error control-name="api_url" />
                                </x-admin::form.control-group>

                                <!-- Azure-specific fields -->
                                <template v-if="form.provider === 'azure'">
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.configuration.platform.fields.azure-deployment')
                                        </x-admin::form.control-group.label>
                                        <x-admin::form.control-group.control type="text" name="azure_deployment" v-model="form.azure_deployment" placeholder="gpt-4o" />
                                    </x-admin::form.control-group>
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.configuration.platform.fields.azure-api-version')
                                        </x-admin::form.control-group.label>
                                        <x-admin::form.control-group.control type="text" name="azure_api_version" v-model="form.azure_api_version" placeholder="2024-10-21" />
                                    </x-admin::form.control-group>
                                </template>

                                <!-- Models Selection -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.platform.fields.models')
                                    </x-admin::form.control-group.label>

                                    <div class="flex flex-wrap gap-2 mb-2" v-if="selectedModels.length">
                                        <span
                                            v-for="(model, index) in selectedModels"
                                            :key="model"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200"
                                        >
                                            @{{ model }}
                                            <button type="button" @click="removeModel(index)" class="hover:text-red-600" :aria-label="'@lang('admin::app.configuration.platform.fields.remove-model')'.replace(':model', model)" :title="'@lang('admin::app.configuration.platform.fields.remove-model')'.replace(':model', model)">&times;</button>
                                        </span>
                                    </div>

                                    <div v-if="fetchedModels.length">
                                        <input
                                            type="text"
                                            v-model="modelSearch"
                                            placeholder="@lang('admin::app.configuration.platform.fields.search-models')"
                                            class="w-full py-1.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800 mb-2"
                                        />

                                        <div class="grid grid-cols-2 gap-1 max-h-[200px] overflow-y-auto border rounded-md p-3 dark:border-cherry-800 mb-2">
                                            <label
                                                v-for="model in filteredModels"
                                                :key="model"
                                                class="flex items-center gap-2 cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 rounded px-2 py-1.5 text-sm"
                                            >
                                                <input type="checkbox" :value="model" v-model="selectedModels" class="text-violet-600 rounded" />
                                                <span class="text-gray-700 dark:text-gray-300">@{{ model }}</span>
                                            </label>

                                            <p v-if="!filteredModels.length" class="col-span-2 text-xs text-gray-400 py-2 text-center">
                                                @lang('admin::app.configuration.platform.fields.no-models-match')
                                            </p>
                                        </div>
                                    </div>

                                    <span v-if="fetchError" class="text-xs text-red-500 block mb-2">@{{ fetchError }}</span>

                                    <p v-if="!fetchedModels.length && !selectedModels.length && !fetchingModels" class="text-xs text-gray-400 mb-2">
                                        @lang('admin::app.configuration.platform.fields.enter-key-to-fetch')
                                    </p>

                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            v-model="customModel"
                                            @keyup.enter.prevent="addCustomModel()"
                                            placeholder="@lang('admin::app.configuration.platform.fields.custom-model-placeholder')"
                                            class="flex-1 py-1.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-800 dark:border-cherry-800"
                                        />
                                        <button type="button" @click="addCustomModel()" class="secondary-button">
                                            + @lang('admin::app.configuration.platform.fields.add')
                                        </button>
                                    </div>

                                    <input type="hidden" name="models" :value="selectedModels.join(',')" />
                                    <x-admin::form.control-group.error control-name="models" />
                                </x-admin::form.control-group>

                                <!-- Default & Status -->
                                <div class="grid grid-cols-2 gap-4">
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.configuration.platform.fields.is-default')
                                        </x-admin::form.control-group.label>
                                        <input type="hidden" name="is_default" value="0" />
                                        <x-admin::form.control-group.control type="switch" name="is_default" value="1" ::checked="form.is_default" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.configuration.platform.fields.status')
                                        </x-admin::form.control-group.label>
                                        <input type="hidden" name="status" value="0" />
                                        <x-admin::form.control-group.control type="switch" name="status" value="1" ::checked="form.status" />
                                    </x-admin::form.control-group>
                                </div>
                            </template>
                        </x-slot>

                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button type="submit" class="primary-button" :disabled="saving">
                                    <span v-if="saving">@lang('admin::app.configuration.platform.saving')...</span>
                                    <span v-else>@lang('admin::app.configuration.platform.save-btn')</span>
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-magic-ai-platform', {
                template: '#v-magic-ai-platform-template',
                data() {
                    return {
                        isEditing: false,
                        saving: false,
                        fetchingModels: false,
                        fetchError: '',
                        customModel: '',
                        modelSearch: '',
                        selectedModels: [],
                        fetchedModels: [],
                        hasOtherDefault: {{ $hasDefault ? 'true' : 'false' }},
                        form: {
                            id: null,
                            label: '',
                            provider: '',
                            api_url: '',
                            api_key: '',
                            azure_deployment: 'gpt-4o',
                            azure_api_version: '2024-10-21',
                            is_default: false,
                            status: true,
                        },

                        providerLabels: {
                            openai: 'OpenAI', anthropic: 'Anthropic', gemini: 'Google Gemini',
                            groq: 'Groq', ollama: 'Ollama', xai: 'xAI (Grok)',
                            mistral: 'Mistral', deepseek: 'DeepSeek',
                            azure: 'Azure OpenAI', openrouter: 'OpenRouter',
                            custom: 'Custom (OpenAI-compatible)',
                        },
                    };
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;
                        if (this.$refs.datagrid.available.actions.length) ++count;
                        return count;
                    },

                    filteredModels() {
                        if (!this.modelSearch.trim()) {
                            return this.fetchedModels;
                        }
                        let search = this.modelSearch.toLowerCase().trim();
                        return this.fetchedModels.filter(m => m.toLowerCase().includes(search));
                    },

                    providerDefaultUrls() {
                        return {
                            openai: 'https://api.openai.com/v1', anthropic: 'https://api.anthropic.com/v1',
                            gemini: 'https://generativelanguage.googleapis.com/v1beta',
                            groq: 'https://api.groq.com/openai/v1', ollama: 'http://localhost:11434',
                            xai: 'https://api.x.ai/v1', mistral: 'https://api.mistral.ai/v1',
                            deepseek: 'https://api.deepseek.com', azure: '',
                            openrouter: 'https://openrouter.ai/api/v1',
                            custom: '',
                        };
                    },
                },

                methods: {
                    openCreateModal() {
                        this.isEditing = false;
                        this.resetForm();
                        if (!this.hasOtherDefault) {
                            this.form.is_default = true;
                        }
                        this.$refs.platformModal.toggle();
                    },

                    resetForm() {
                        this.form = {
                            id: null, label: '', provider: '', api_url: '', api_key: '',
                            azure_deployment: 'gpt-4o', azure_api_version: '2024-10-21',
                            is_default: false, status: true,
                        };
                        this.selectedModels = [];
                        this.fetchedModels = [];
                        this.customModel = '';
                        this.modelSearch = '';
                        this.fetchError = '';
                    },

                    onProviderChange(value = null) {
                        if (value !== null) {
                            try {
                                let selectedProvider = typeof value === 'string' ? JSON.parse(value) : value;

                                this.form.provider = selectedProvider?.id ?? selectedProvider ?? '';
                            } catch (error) {
                                this.form.provider = value ?? '';
                            }
                        }

                        if (!this.isEditing) {
                            this.form.label = this.providerLabels[this.form.provider] || this.form.provider;
                        }
                        this.selectedModels = [];
                        this.fetchedModels = [];
                        this.form.api_url = this.providerDefaultUrls[this.form.provider] || '';
                        this.fetchError = '';
                    },

                    onApiKeyEntered() {
                        if (this.form.api_key && this.form.api_key.length >= 10 && !this.form.api_key.match(/^\*+$/)) {
                            this.fetchModels();
                        }
                    },

                    fetchModels() {
                        this.fetchingModels = true;
                        this.fetchError = '';

                        this.$axios.post("{{ route('admin.magic_ai.platform.fetch_models') }}", {
                            provider: this.form.provider,
                            api_key: this.form.api_key,
                            api_url: this.form.api_url || undefined,
                            id: this.form.id || undefined,
                        }).then((response) => {
                            this.fetchingModels = false;
                            let models = response.data.models || [];
                            let recommended = response.data.recommended || [];
                            this.fetchedModels = models;

                            if (models.length && this.selectedModels.length === 0) {
                                if (recommended.length) {
                                    this.selectedModels = recommended.filter(m => models.includes(m));
                                }
                                if (!this.selectedModels.length) {
                                    this.selectedModels = models.slice(0, 3);
                                }
                            }

                            if (!models.length) {
                                this.fetchError = 'No models returned. Add models manually below.';
                            }
                        }).catch(error => {
                            this.fetchingModels = false;
                            this.fetchError = error.response?.data?.message || 'Failed to fetch. You can add models manually below.';
                        });
                    },

                    addCustomModel() {
                        let model = this.customModel.trim();

                        if (! model) {
                            return;
                        }

                        if (! /^[a-zA-Z0-9][a-zA-Z0-9\-._:\/@]+$/.test(model)) {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: '@lang("admin::app.configuration.platform.fields.invalid-model-name")',
                            });
                            return;
                        }

                        if (! this.selectedModels.includes(model)) {
                            this.selectedModels.push(model);
                        }

                        this.customModel = '';
                    },

                    removeModel(index) {
                        this.selectedModels.splice(index, 1);
                    },

                    saveWithTest(params, { resetForm, setErrors }) {
                        this.saving = true;
                        let saveData = new FormData(this.$refs.platformForm);

                        if (this.form.provider === 'azure') {
                            saveData.set('extras', JSON.stringify({
                                deployment: this.form.azure_deployment,
                                api_version: this.form.azure_api_version,
                            }));
                        }

                        let url;
                        if (this.form.id) {
                            saveData.append('_method', 'put');
                            url = "{{ route('admin.magic_ai.platform.update', ':id') }}".replace(':id', this.form.id);
                        } else {
                            url = "{{ route('admin.magic_ai.platform.store') }}";
                        }

                        this.$axios.post(url, saveData)
                            .then((response) => {
                                this.saving = false;
                                this.$refs.platformModal.close();
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                this.$refs.datagrid.get();
                                this.hasOtherDefault = true;
                                resetForm();
                            })
                            .catch(error => {
                                this.saving = false;
                                if (error.response?.status == 422) {
                                    setErrors(error.response.data.errors);
                                } else {
                                    this.$emitter.emit('add-flash', {
                                        type: 'error',
                                        message: error.response?.data?.message || 'Save failed. Please try again.',
                                    });
                                }
                            });
                    },

                    editModal(url) {
                        this.isEditing = true;
                        this.$axios.get(url).then((response) => {
                            let data = response.data.data;
                            let extras = {};
                            try {
                                extras = data.extras ? (typeof data.extras === 'string' ? JSON.parse(data.extras) : data.extras) : {};
                            } catch (e) { extras = {}; }

                            this.form = {
                                id: data.id, label: data.label, provider: data.provider,
                                api_url: data.api_url || '', api_key: data.api_key || '',
                                azure_deployment: extras.deployment || 'gpt-4o',
                                azure_api_version: extras.api_version || '2024-10-21',
                                is_default: data.is_default, status: data.status,
                            };
                            this.selectedModels = data.models ? data.models.split(',').map(m => m.trim()).filter(m => m) : [];
                            this.fetchedModels = [];
                            this.$refs.platformModal.toggle();
                            this.fetchModels();
                        });
                    },

                    setAsDefault(record) {
                        this.$axios.post("{{ route('admin.magic_ai.platform.set_default', ':id') }}".replace(':id', record.id))
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                this.$refs.datagrid.get();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message || 'An error occurred.' });
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
