<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.configuration.prompt.create.title')
    </x-slot>
    <v-create-prompt-form>
        <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.configuration.prompt.create.title')
            </p>
            <div class="flex gap-x-2.5 items-center">
                <button
                    type="button"
                    class="primary-button"
                >
                    @lang('admin::app.configuration.prompt.create.create-btn')
                </button>
            </div>
        </div>
        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />
    </v-create-prompt-form>
    @pushOnce('scripts')
        <script type="text/x-template" id="v-create-prompt-form-template">
            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('admin::app.configuration.prompt.create.title')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    <button
                        type="button"
                        class="primary-button"
                        @click="selectedPrompt=0;resetForm();$refs.promptUpdateOrCreateModal.toggle();toggleMagicAIModal()"
                    >
                        @lang('admin::app.configuration.prompt.create.create-btn')
                    </button>
                </div>
            </div>
            <x-admin::datagrid src="{{ route('admin.magic_ai.prompt.index') }}" ref="datagrid">

                <template #body="{ columns, records, performAction, applied, setCurrentSelectionMode }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                    >
                        <!-- Code -->
                        <p v-text="record.title"></p>

                        <!-- Name -->
                        <p v-text="record.prompt"></p>

                        <p v-html="record.type"></p>

                        <p v-html="record.created_at"></p>
                        <p v-html="record.updated_at"></p>

                        <!-- Actions -->
                        <div class="flex justify-end">
                            <a @click="selectedPrompt=1;editModal(record.actions.find(action => action.index === 'action_1')?.url)">
                                <span
                                    :class="record.actions.find(action => action.index === 'action_1')?.icon"
                                    title="@lang('admin::app.configuration.prompt.datagrid.edit')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>
                            <a @click="performAction(record.actions.find(action => action.index === 'action_2'))">
                                <span
                                    :class="record.actions.find(action => action.index === 'action_2')?.icon"
                                    title="@lang('admin::app.configuration.prompt.datagrid.delete')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>
            <div>
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="modalform"
                >
                    <form @submit="handleSubmit($event, updateOrCreate)" ref="promptCreateForm">
                        <!-- prompt Create Modal -->
                        <x-admin::modal ref="promptUpdateOrCreateModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <span v-if="selectedPrompt" class="dark:text-slate-50">
                                     @lang('admin::app.configuration.prompt.create.edit-title')
                                </span>

                                <span v-else class="dark:text-slate-50">
                                     @lang('admin::app.configuration.prompt.create.create-title')
                                </span>
                            </x-slot>
                            <!-- Modal Content -->
                            <x-slot:content>
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                    v-model="id"
                                />
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.prompt.create.label-title')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="title"
                                        v-model="title"
                                    />
                                    <x-admin::form.control-group.error control-name="title" />
                                </x-admin::form.control-group>

                                <!-- Select Component -->
                                <x-admin::form.control-group>
                                <!-- Label for the Select Element -->
                                <x-admin::form.control-group.label class="required">
                                @lang('admin::app.configuration.prompt.create.type')
                                </x-admin::form.control-group.label>

                                @php
                                    $supportedTypes = ['product', 'category'];
                                    $options = [];
                                    foreach($supportedTypes as $type) {
                                        $options[] = [
                                            'id'    => $type,
                                            'label' => ucfirst($type)
                                        ];
                                    }
                                    $optionsInJson = json_encode($options);
                                @endphp

                                <!-- The Select Control with dynamic options -->
                                <x-admin::form.control-group.control
                                    type="select"
                                    id="type"
                                    name="type"
                                    v-model="type"
                                    rules="required"
                                    :options="$optionsInJson"
                                    :value="old('section') ?? $supportedTypes[0]"
                                    track-by="id"
                                    label-by="label"
                                    @input="checkType($event)"
                                >
                                </x-admin::form.control-group.control>

                                <!-- Error handling for the Select element -->
                                <x-admin::form.control-group.error control-name="section" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.prompt.create.prompt')
                                    </x-admin::form.control-group.label>
                                    <div class="relative w-full">
                                        <x-admin::form.control-group.control
                                            type="textarea"
                                            class="h-[180px]"
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
                                </x-admin::form.control-group>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <!-- Modal Submission -->
                                <div class="flex gap-x-2.5 items-center">
                                    <button
                                        type="submit"
                                        class="primary-button"
                                    >
                                    @lang('admin::app.configuration.prompt.create.save-btn')
                                    </button>
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-prompt-form', {
                template: '#v-create-prompt-form-template',
                data() {
                    return {
                        attributes: [],
                        ai: {
                            prompt: '',

                        },
                        selectedPrompt: 0,
                        title: null,
                        type: null,
                        id: null,
                        entityName: null,
                    };
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        return count;
                    },
                },

                methods: {
                    checkType(value) {
                        let selectedField = JSON.parse(value).id;
                        if (selectedField == 'category') {
                            this.entityName = 'category_field';
                        } else {
                            this.entityName = null;
                        }
                    },

                    updateOrCreate(params, {
                        resetForm,
                        setErrors
                    }) {
                        let formData = new FormData(this.$refs.promptCreateForm);

                        if (params.id) {
                            formData.append('_method', 'put');
                        }

                        this.$axios.post(params.id ? "{{ route('admin.magic_ai.prompt.update') }}" : "{{ route('admin.magic_ai.prompt.store') }}", formData)
                            .then((response) => {
                                this.$refs.promptUpdateOrCreateModal.close();

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                this.$refs.datagrid.get();

                                resetForm();
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    toggleMagicAIModal() {
                        this.$nextTick(() => {
                            if (this.$refs.promptInput) {

                                const tribute = this.$tribute.init({
                                    values: this.fetchSuggestionValues,
                                    lookup: 'name',
                                    fillAttr: 'code',
                                    noMatchTemplate: "@lang('admin::app.common.no-match-found')",
                                    selectTemplate: (item) => `@${item.original.code}`,
                                    menuItemTemplate: (item) => `<div class="p-1.5 rounded-md text-base cursor-pointer transition-all max-sm:place-self-center">${item.original.name || '[' + item.original.code + ']'}</div>`,
                                });
                                tribute.attach(this.$refs.promptInput);

                            }
                        });
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
                    async fetchSuggestionValues(text, cb) {
                        const response = await fetch(`{{ route('admin.magic_ai.suggestion_values') }}?query=${text}&&entity_name=${this.entityName}&&locale={{ core()->getRequestedLocaleCode() }}`);
                        const data = await response.json();
                        this.suggestionValues = data;
                        cb(this.suggestionValues);
                    },

                    editModal(url) {
                        this.$axios.get(url)
                            .then((response) => {
                                let data = response.data.data;
                                this.id = data.id;
                                this.title = data.title;
                                this.ai.prompt = data.prompt;
                                this.type = data.type;
                                this.$refs.promptUpdateOrCreateModal.toggle();
                                this.toggleMagicAIModal();
                            })
                    },

                    resetForm() {
                        this.title = null;
                        this.type = "product";
                        this.ai.prompt = '';
                        this.id = null;
                        this.entityName = null
                    }

                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
