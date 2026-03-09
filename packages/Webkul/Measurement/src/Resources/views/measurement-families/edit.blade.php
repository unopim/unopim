<x-admin::layouts.with-history>
    <x-slot:entityName>
        measurementFamily
    </x-slot:entityName>

    <x-slot:title>
        @lang('measurement::app.measurement.edit.measurement_edit')
    </x-slot:title>

    <x-admin::form
        method="PUT"
        action="{{ route('admin.measurement.families.update', $family->id) }}">
        
        <div class="grid gap-2.5">
            <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
                <div class="grid gap-1.5">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold leading-6">
                        @lang('measurement::app.measurement.edit.measurement_edit') | Name: {{ $family->name }}
                    </p>
                </div>

                <div class="flex gap-x-2.5 items-center">

                    
                    <a
                        href="{{ route('admin.measurement.families.index') }}"
                        class="transparent-button"
                    >
                        @lang('measurement::app.measurement.edit.back')
                    </a>

                    
                    <button class="primary-button">
                        @lang('measurement::app.measurement.edit.save')
                    </button>
                </div>
            </div>
        </div>

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">

            <div class="left-column flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <div class="relative p-4 bg-white dark:bg-cherry-900 rounded box-shadow">

                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('measurement::app.measurement.edit.general')
                    </p>

                   
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                           @lang('measurement::app.measurement.edit.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code"
                            value="{{ old('code', $family->code) }}"
                            rules="required"
                            placeholder="{{ __('Enter family name') }}"
                            disabled
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                  

                   
                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    @lang('measurement::app.measurement.edit.label')
                                </p>
                            </div>

                            <div class="">
                                
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="labels[{{ $locale->code }}]"
                                            value="{{ old('labels.'.$locale->code, $labels[$locale->code] ?? '') }}"
                                            placeholder="Enter {{ $locale->name }} label"
                                        />
                                    </x-admin::form.control-group>
                                @endforeach

                            </div>
                        </div>


                </div> 
            </div>

        </div> 
    </x-admin::form>




    <div class="mt-4 p-4 bg-white dark:bg-cherry-900 box-shadow rounded">

        <v-locales>
            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('measurement::app.measurement.edit.units')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    @if (bouncer()->hasPermission('settings.locales.create'))
                        <button
                            type="button"
                            class="primary-button"
                        >
                            @lang('measurement::app.measurement.edit.create_units')
                        </button>
                    @endif
                </div>
            </div>

        
            <x-admin::shimmer.datagrid />
        </v-locales>


    </div>

    @pushOnce('scripts')

        <script
            type="text/x-template"
            id="v-locales-template"
        >
            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('measurement::app.measurement.edit.units')
                </p>

                <div class="flex gap-x-2.5 items-center">
                
                    @if (bouncer()->hasPermission('settings.locales.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="selectedLocales=0;resetForm();$refs.localeUpdateOrCreateModal.toggle()"
                        >
                            @lang('measurement::app.measurement.edit.create_units')
                        </button>
                    @endif
                </div>
            </div>

            @php
                $hasDeletePermission = bouncer()->hasPermission('settings.locales.delete');

                $hasEditPermission = bouncer()->hasPermission('settings.locales.edit');

                $hasMassActionPermission = bouncer()->hasPermission('settings.locales.mass_update') || bouncer()->hasPermission('settings.locales.mass_delete');
            @endphp
            <x-admin::datagrid :src="route('admin.measurement.families.units', $family->id)" ref="datagrid">
                
                <template #body="{ columns, records, performAction, applied, setCurrentSelectionMode }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                    >
                        
                        @if ($hasMassActionPermission)
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.code}`"
                                :id="`mass_action_select_record_${record.code}`"
                                :value="record.code"
                                class="hidden peer"
                                v-model="applied.massActions.indices"
                                @change="setCurrentSelectionMode"
                            >

                            <label
                                class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"
                                :for="`mass_action_select_record_${record.code}`"
                            ></label>
                        @endif

                    
                        <p v-text="record.code"></p>

                        
                        <p v-text="record.label"></p>

                        
                        <p v-html="record.is_standard"></p>



                        <!-- Actions -->
                        <div class="flex justify-end">
                            @if ($hasEditPermission)
                                <a @click="selectedLocales=1; editModal(record.actions.find(action => action.index === 'edit')?.url)">
                                    <span
                                        :class="record.actions.find(action => action.index === 'edit')?.icon"
                                        title="@lang('admin::app.settings.locales.index.datagrid.edit')"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            @endif

                            @if ($hasDeletePermission)
                                <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                    <span
                                        :class="record.actions.find(action => action.index === 'delete')?.icon"
                                        title="@lang('admin::app.settings.locales.index.datagrid.delete')"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
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

                  

                    <x-admin::modal ref="localeUpdateOrCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                <span v-if="selectedLocales"> 
                                    @lang('measurement::app.measurement.unit.edit_unit')
                                </span>

                                <span v-else>
                                    @lang('measurement::app.measurement.unit.create_unit')
                                </span>
                            </p>
                        </x-slot>

                        <x-slot:content>
                            

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                                v-model="locale.id"
                            />

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.locales.index.create.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="code"
                                    name="code"
                                    rules="required"
                                    v-model="locale.code"
                                    :label="trans('admin::app.settings.locales.index.create.code')"
                                    :placeholder="trans('admin::app.settings.locales.index.create.code')"
                                    ::readonly="selectedLocales === 1"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <div class="">
                                    @foreach ($locales as $locale)
                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label>
                                                {{ $locale->name }}
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="text"
                                                id="label"
                                                ::name="`labels[{{ $locale->code }}]`"
                                                rules="required"
                                                v-model="locale.labels['{{ $locale->code }}']"
                                                :label="trans('admin::app.settings.locales.index.create.code')"
                                                placeholder="Enter Labels"  
                                            />
                                        </x-admin::form.control-group>
                                    @endforeach

                            </div>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('measurement::app.measurement.unit.symbol')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="symbol"
                                    name="symbol"
                                    rules="required"
                                    v-model="locale.symbol"
                                    :label="trans('admin::app.settings.locales.index.create.code')"
                                    placeholder="Symbol"
                                    
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                        </x-slot>

                    
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('measurement::app.measurement.unit.save')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>

                   
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
                            labels: {},
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
                    updateOrCreate(params, { resetForm, setErrors }) {
                        let formData = new FormData(this.$refs.createLocaleForm);

                        let url = "{{ route('admin.measurement.families.units.store', $family->id) }}";

                        
                        if (this.selectedLocales && this.locale.code) {
                            url = "{{ route('admin.measurement.families.units.update', ['familyId' => $family->id, 'code' => '__CODE__']) }}"
                                .replace('__CODE__', this.locale.code);

                            formData.append('_method', 'PUT');
                        }

                        this.$axios.post(url, formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        })
                        .then((response) => {
                            this.$refs.localeUpdateOrCreateModal.close();

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: 'Unit saved successfully',
                            });

                            this.$refs.datagrid.get();
                            resetForm();
                        })
                        .catch(error => {
                            if (error.response?.status === 422) {
                                setErrors(error.response.data.errors);
                            }
                        });
                    },

                    editModal(url) {
                        this.$axios.get(url).then((response) => {
                            this.locale = {
                                code: response.data.data.code,
                                labels: response.data.data.labels ?? {},
                                symbol: response.data.data.symbol ?? null,
                            };

                            this.selectedLocales = 1; 
                            this.$refs.localeUpdateOrCreateModal.toggle();
                        });
                    },

                    resetForm() {
                        this.locale = {
                            code: null,
                            labels: {},
                            symbol: null,
                        };

                        this.selectedLocales = 0;
                    }
                }

            });
        </script>
    @endPushOnce

</x-admin::layouts.with-history>
