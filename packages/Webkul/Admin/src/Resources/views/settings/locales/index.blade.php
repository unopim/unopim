<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.locales.index.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.locales.create.before') !!}

    <v-locales>
        <x-admin::page-header :title="trans('admin::app.settings.locales.index.title')">
            <x-slot:actions>
                @if (bouncer()->hasPermission('settings.locales.create'))
                    <button
                        type="button"
                        class="primary-button"
                    >
                        @lang('admin::app.settings.locales.index.create-btn')
                    </button>
                @endif
            </x-slot>
        </x-admin::page-header>

        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />
    </v-locales>

    {!! view_render_event('unopim.admin.settings.locales.create.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-locales-template"
        >
            <x-admin::page-header :title="trans('admin::app.settings.locales.index.title')">
                <x-slot:actions>
                    <!-- Locale Create Button -->
                    @if (bouncer()->hasPermission('settings.locales.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="selectedLocales=0;resetForm();$refs.localeUpdateOrCreateModal.toggle()"
                        >
                            @lang('admin::app.settings.locales.index.create-btn')
                        </button>
                    @endif
                </x-slot>
            </x-admin::page-header>

            @php
                $hasDeletePermission = bouncer()->hasPermission('settings.locales.delete');

                $hasEditPermission = bouncer()->hasPermission('settings.locales.edit');

                $hasMassActionPermission = bouncer()->hasPermission('settings.locales.mass_update') || bouncer()->hasPermission('settings.locales.mass_delete');
            @endphp
            <x-admin::datagrid :src="route('admin.settings.locales.index')" ref="datagrid">
                <!-- DataGrid Body -->
                <template #body="{ columns, records, performAction, applied, setCurrentSelectionMode }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 cursor-pointer transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="selectedLocales=1; editModal(record.actions.find(action => action.index === 'edit')?.url)"
                    >
                        <!-- Mass actions -->
                        @if ($hasMassActionPermission)
                            <div @click.stop>
                                <input
                                    type="checkbox"
                                    :name="`mass_action_select_record_${record.id}`"
                                    :id="`mass_action_select_record_${record.id}`"
                                    :value="record.id"
                                    class="hidden peer"
                                    v-model="applied.massActions.indices"
                                    @change="setCurrentSelectionMode"
                                >

                                <label
                                    class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-primary-700"
                                    :for="`mass_action_select_record_${record.id}`"
                                ></label>
                            </div>
                        @endif

                        <!-- Id -->
                        <p v-text="record.id"></p>

                        <!-- Code -->
                        <p v-text="record.code" class="truncate" :title="record.code"></p>

                        <!-- Name -->
                        <p v-text="record.name" class="truncate" :title="record.name"></p>

                        <p v-html="record.status"></p>

                        <!-- Actions -->
                        <div class="flex justify-end" @click.stop>
                            @if ($hasEditPermission)
                                <a @click="selectedLocales=1; editModal(record.actions.find(action => action.index === 'edit')?.url)">
                                    <span
                                        :class="record.actions.find(action => action.index === 'edit')?.icon"
                                        title="@lang('admin::app.settings.locales.index.datagrid.edit')"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            @endif

                            @if ($hasDeletePermission)
                                <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                    <span
                                        :class="record.actions.find(action => action.index === 'delete')?.icon"
                                        title="@lang('admin::app.settings.locales.index.datagrid.delete')"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, updateOrCreate)"
                    ref="createLocaleForm"
                >

                    {!! view_render_event('unopim.admin.settings.locales.create_form_controls.before') !!}

                    <x-admin::modal ref="localeUpdateOrCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                <span v-if="selectedLocales">
                                    @lang('admin::app.settings.locales.index.edit.title')
                                </span>

                                <span v-else>
                                    @lang('admin::app.settings.locales.index.create.title')
                                </span>
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            {!! view_render_event('unopim.admin.settings.locale.create.before') !!}

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                                v-model="locale.id"
                            />

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.locales.index.create.code')

                                    <a href="https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes" target="_blank" rel="noopener noreferrer" class="inline-flex items-center cursor-pointer text-gray-400 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors" title="{{ trans('admin::app.settings.locales.index.create.code-hint') }}"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></a>
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="code"
                                    name="code"
                                    rules="required"
                                    v-model="locale.code"
                                    :label="trans('admin::app.settings.locales.index.create.code')"
                                    :placeholder="trans('admin::app.settings.locales.index.create.code-placeholder')"
                                    ::disabled="locale.id"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.locales.index.create.status')
                                </x-admin::form.control-group.label>

                                <input type="hidden" name="status" value="0" />

                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="status"
                                    :value="1"
                                    v-model="locale.status"
                                    :label="trans('admin::app.settings.locales.index.create.status')"
                                    ::checked="locale.status"
                                />

                                <x-admin::form.control-group.error control-name="status" />
                            </x-admin::form.control-group>
 
                            {!! view_render_event('unopim.admin.settings.locale.create.after') !!}
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('admin::app.settings.locales.index.create.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>

                    {!! view_render_event('unopim.admin.settings.locales.create_form_controls.after') !!}

                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-locales', {
                template: '#v-locales-template',

                data() {
                    return {
                        locale: {
                            id: null,
                            code: null,
                            name: null,
                            status: false,
                        },

                        selectedLocales: 0,
                    }
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        if (this.$refs.datagrid.available.massActions.length) {
                            ++count;
                        }

                        return count;
                    },
                },

                methods: {
                    updateOrCreate(params, { resetForm, setErrors  }) {
                        let formData = new FormData(this.$refs.createLocaleForm);

                        if (params.id) {
                            formData.append('_method', 'put');
                        }

                        this.$axios.post(params.id ? "{{ route('admin.settings.locales.update') }}" : "{{ route('admin.settings.locales.store') }}", formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        })
                        .then((response) => {
                            this.$refs.localeUpdateOrCreateModal.close();

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

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
                        this.$navigate(url);
                    },

                    resetForm() {
                        this.locale = {
                            id: null,
                            code: null,
                            name: null,
                        };
                    }
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
