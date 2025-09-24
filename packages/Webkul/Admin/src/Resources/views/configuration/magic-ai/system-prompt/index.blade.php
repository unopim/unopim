<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.configuration.system-prompt.create.title')
    </x-slot>
    <v-create-system-prompt-form>
        <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.configuration.system-prompt.create.title')
            </p>
            <div class="flex gap-x-2.5 items-center">
                <button
                    type="button"
                    class="primary-button"
                >
                    @lang('admin::app.configuration.system-prompt.create.create-btn')
                </button>
            </div>
        </div>

        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />

    </v-create-system-prompt-form>
    @pushOnce('scripts')
        <script type="text/x-template" id="v-create-system-prompt-form-template">
            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('admin::app.configuration.system-prompt.create.title')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    <button
                        type="button"
                        class="primary-button"
                        @click="selectedPrompt=0;resetForm();$refs.promptUpdateOrCreateModal.toggle();toggleMagicAIModal()"
                    >
                        @lang('admin::app.configuration.system-prompt.create.create-btn')
                    </button>
                </div>
            </div>

            <x-admin::datagrid src="{{ route('admin.magic_ai.system_prompt.index') }}" ref="datagrid">

                <template #body="{ columns, records, performAction, applied, setCurrentSelectionMode }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                    >

                        <!-- Title -->
                        <p v-text="record.title"></p>

                        <!-- Tone -->
                        <p v-text="record.tone"></p>

                        <!-- Max Tokens -->
                         <p v-text="record.max_tokens"></p>

                        <!-- Temperature -->
                        <p v-text="record.temperature"></p>

                        <!-- Status -->
                        <p v-html="record.is_enabled"></p>

                        <!-- Time Stamp -->
                        <p v-html="record.created_at"></p>
                        <p v-html="record.updated_at"></p>

                        <!-- Actions -->
                        <div class="flex justify-end">
                            <a @click="selectedPrompt=1;editModal(record.actions.find(action => action.index === 'action_1')?.url)">
                                <span
                                    :class="record.actions.find(action => action.index === 'action_1')?.icon"
                                    title="@lang('admin::app.configuration.system-prompt.datagrid.edit')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>
                            <a @click="performAction(record.actions.find(action => action.index === 'action_2'))">
                                <span
                                    :class="record.actions.find(action => action.index === 'action_2')?.icon"
                                    title="@lang('admin::app.configuration.system-prompt.datagrid.delete')"
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
                    <form @submit="handleSubmit($event, updateOrCreate)" ref="systemPromptCreateForm">
                        <!-- prompt Create Modal -->
                        <x-admin::modal ref="promptUpdateOrCreateModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <span v-if="selectedPrompt" class="dark:text-slate-50">
                                     @lang('admin::app.configuration.system-prompt.create.edit-title')
                                </span>

                                <span v-else class="dark:text-slate-50">
                                     @lang('admin::app.configuration.system-prompt.create.create-title')
                                </span>
                            </x-slot>
                            <!-- Modal Content -->
                            <x-slot:content>
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                    v-model="id"
                                />

                                 <!-- Title -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.system-prompt.create.label-title')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="title"
                                        v-model="title"
                                        rules="required"
                                    />
                                    <x-admin::form.control-group.error control-name="title" />
                                </x-admin::form.control-group>


                                 <!-- Status(is_enabled) -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.configuration.system-prompt.datagrid.status')
                                    </x-admin::form.control-group.label>
                                    <input 
                                        type="hidden"
                                        name="is_enabled"
                                        value="0"
                                    />

                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="is_enabled"
                                        value="1"
                                        ::checked="is_enabled"
                                    />
                                </x-admin::form.control-group>

                                 <!-- Max Token -->
                                 <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.configuration.system-prompt.create.max-tokens')
                                          <span 
                                            class="icon tooltip-icon" 
                                            title="Allowed Max Output Token range: 100 to 5000 tokens"
                                            style="cursor: pointer; margin-left: 6px;"
                                        >
                                            &#9432;
                                        </span>
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="max_tokens"
                                        v-model="max_tokens"
                                        min="1"
                                        max="5000"
                                        step="1"
                                    />
                                    <x-admin::form.control-group.error control-name="max_tokens" />
                                </x-admin::form.control-group>

                                <!-- Temperature -->
                                 <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.configuration.system-prompt.create.temperature')

                                       <span 
                                            class="icon tooltip-icon" 
                                            title="Temperature controls creativity. Range: 0 to 2. Lower values (e.g., 0.4) give more accurate and focused responses."
                                            style="cursor: pointer; margin-left: 6px;"
                                        >
                                            &#9432;
                                        </span>

                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="temperature"
                                        v-model="temperature"
                                        min="0"
                                        max="2"
                                        step="0.01"
                                    />
                                    <x-admin::form.control-group.error control-name="temperature" />
                                </x-admin::form.control-group>

                                 <!-- Tone -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.system-prompt.datagrid.tone')
                                    </x-admin::form.control-group.label>
                                    
                                        <x-admin::form.control-group.control
                                            type="textarea"
                                            class="h-[180px]"
                                            name="tone"
                                            rules="required"
                                            v-model="tone"
                                            :label="trans('admin::app.configuration.system-prompt.datagrid.tone')"
                                        />
                                        <x-admin::form.control-group.error control-name="tone" />
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
                                    @lang('admin::app.configuration.system-prompt.datagrid.save')
                                    </button>
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-system-prompt-form', {
                template: '#v-create-system-prompt-form-template',
                data() {
                    return {
                        ai: {
                            prompt: '',
                        },
                        selectedPrompt: 0,
                        title: null,
                        tone: null,
                        id: null,
                        max_tokens: null,
                        temperature : null,
                        is_enabled : false,
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
                    updateOrCreate(params, {
                        resetForm,
                        setErrors
                    }) {
                        let formData = new FormData(this.$refs.systemPromptCreateForm);

                        if (params.id) {
                            formData.append('_method', 'put');
                        }

                        this.$axios.post(params.id ? "{{ route('admin.magic_ai.system_prompt.update') }}" : "{{ route('admin.magic_ai.system_prompt.store') }}", formData)
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

                    editModal(url) {
                        this.$axios.get(url)
                            .then((response) => {
                                let data = response.data.data;
                                this.id = data.id;
                                this.title = data.title;
                                this.is_enabled = data.is_enabled;
                                this.tone = data.tone;
                                this.max_tokens = data.max_tokens;
                                this.temperature = data.temperature;
                                this.$refs.promptUpdateOrCreateModal.toggle();
                                this.toggleMagicAIModal();
                            })
                    },

                    resetForm() {
                        this.title = null;
                        this.tone = '';
                        this.id = null;
                        this.entityName = null
                    }

                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
