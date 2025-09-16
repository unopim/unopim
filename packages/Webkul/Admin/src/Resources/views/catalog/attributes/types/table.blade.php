<v-edit-table-attribute :locales="{{ $locales->toJson() }}"></v-edit-table-attribute>

@pushOnce('scripts')
<script type="text/x-template" id="v-edit-table-attribute-template">
    @php
        $attribute = $attribute ?? null;  
        $supportedTypes = config('attribute_types');
        $attributeTypes = [];

        foreach ($supportedTypes as $key => $type) {
            $attributeTypes[] = [
                'id'    => $key,
                'label' => trans($type['name']),
            ];
        }

        $attributeTypesJson = json_encode($attributeTypes);

        $columnTypes = [
            [ 'key' => 'text', 'label' => trans('admin::app.catalog.attributes.create.text') ],
            [ 'key' => 'boolean', 'label' => trans('admin::app.catalog.attributes.create.boolean') ],
            [ 'key' => 'date', 'label' => trans('admin::app.catalog.attributes.create.date') ],
            [ 'key' => 'image', 'label' => trans('admin::app.catalog.attributes.create.image') ],
            [ 'key' => 'select', 'label' => trans('admin::app.catalog.attributes.create.select') ],
            [ 'key' => 'multiselect', 'label' => trans('admin::app.catalog.attributes.create.multiselect') ],
        ];

        $columnValidationTypes = [
            'number'   => ['text'],
            'email'    => ['text'],
            'required' => ['text', 'boolean', 'date', 'select', 'multiselect'],
            'decimal'  => ['text'],
        ];

        $validationTypes = [];

        foreach ($columnValidationTypes as $key => $type) {
            $validationTypes[] = [
                'id'       => $key,
                'label'    => trans('admin::app.catalog.attributes.create.' . $key),
                'supports' => $type,
            ];
        }

        $columnValidationTypesJson = json_encode($validationTypes);
        $columnTypesJson = json_encode($columnTypes);
    @endphp

    <!-- Main Wrapper -->
    <div v-if="selectedAttributeType == 'table'">
        <div class="flex justify-between items-center mb-3">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('admin::app.catalog.attributes.edit.column.title')
            </p>

            <div class="secondary-button text-sm" @click="$refs.addColumns.toggle(); columnIsNew = true; selectedType = null;">
                @lang('admin::app.catalog.attributes.edit.add-column')
            </div>
        </div>
        <x-admin::datagrid ref="columnDatagrid" src="{{ route('admin.catalog.attributes.column', $attribute?->id) }}" />
    </div>

    <x-admin::form v-slot="{ meta, errors, handleSubmit }" as="div" ref="addOptionForm">
        <form @submit.prevent="handleSubmit($event, storeOption)" enctype="multipart/form-data" ref="addOptionFormRef">
            <x-admin::modal style="z-index:10004;position:relative" ref="addOption" type="small">
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @lang('admin::app.catalog.attributes.create.add-option')
                    </p>
                </x-slot>

                <x-slot:content :class="'bg-white dark:bg-gray-900'">
                    <div class="grid grid-cols-1 gap-4">

                        <x-admin::form.control-group>

                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.catalog.attributes.edit.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                rules="required"
                                :label="trans('admin::app.catalog.attributes.edit.code')"
                                :placeholder="trans('admin::app.catalog.attributes.edit.code')"
                                v-code
                            />

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        @foreach ($locales as $locale)
                            <x-admin::form.control-group class="w-full mb-2.5">
                                <x-admin::form.control-group.label>
                                    {{ $locale->name }}
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $locale->code }}[label]"
                                    :label="$locale->name"
                                />
                                <x-admin::form.control-group.error control-name="{{ $locale->code }}[label]" />
                            </x-admin::form.control-group>
                        @endforeach
                    </div>
                </x-slot>

                <x-slot:footer>
                    <button type="submit" class="primary-button">
                        @lang('admin::app.catalog.attributes.edit.column.save-btn')
                    </button>
                </x-slot>
            </x-admin::modal>
        </form>
    </x-admin::form>

    <x-admin::form v-slot="{ meta, errors, handleSubmit }" as="div" ref="addColumnForm">
        <form @submit.prevent="handleSubmit($event, storeColumns)" enctype="multipart/form-data" ref="addColumnsForm">
            <x-admin::modal ref="addColumns" type="small">
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                    @lang('admin::app.catalog.attributes.edit.add-column')
                    </p>
                </x-slot>

                <x-slot:content :class="'bg-white dark:bg-gray-900'">
                    <div class="grid grid-cols-1 gap-4">

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.catalog.attributes.edit.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                rules="required"
                                :label="trans('admin::app.catalog.attributes.edit.code')"
                                :placeholder="trans('admin::app.catalog.attributes.edit.code')"
                            />
                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.catalog.attributes.edit.table-attribute.type')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="type"
                                rules="required"
                                :options="$columnTypesJson"
                                track-by="key"
                                label-by="label"
                                v-model="selectedType"
                            />

                            <x-admin::form.control-group.error control-name="type" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.catalog.attributes.edit.validation')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="multiselect"
                                name="validation"
                                :label="trans('admin::app.catalog.attributes.edit.code')"
                                :placeholder="trans('admin::app.catalog.attributes.edit.code')"
                                ref="validation"
                                ::disabled="true !== columnIsNew"
                                ::options="filteredValidationTypes"
                                track-by="id"
                                label-by="label"
                                v-model="selectedValidation"
                            />

                            <x-admin::form.control-group.error control-name="validation" />
                        </x-admin::form.control-group>

                    </div>
                </x-slot>

                <x-slot:footer>
                    <button type="submit" class="primary-button">
                        @lang('admin::app.catalog.attributes.edit.column.save-btn')
                    </button>
                </x-slot>
            </x-admin::modal>
        </form>
    </x-admin::form>

    <x-admin::form v-slot="{ meta, errors, handleSubmit }" as="div" ref="modelForm">
        <form @submit.prevent="handleSubmit($event, editColumn)" enctype="multipart/form-data" ref="editColumnsForm">
            <x-admin::modal ref="editColumns" type="large">
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @lang('admin::app.catalog.attributes.edit.edit-column')
                    </p>
                </x-slot>

                <x-slot:content :class="'bg-white dark:bg-gray-900'">
                    <!-- Column Details Title -->

                    <div class="bg-white dark:bg-gray-900 p-1">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Left Panel -->
                            <div class="">
                                <p class="text-md mb-2 text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.attributes.edit.column.title')
                                </p>
                                <!-- Code -->
                                <x-admin::form.control-group class="w-full mt-0">
                                    <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.edit.code')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        :placeholder="trans('Code')"
                                        ::disabled="true"
                                    />
                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.edit.table-attribute.type')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="type"
                                        rules="required"
                                        :options="$columnTypesJson"
                                        ::disabled="true"
                                        track-by="key"
                                        label-by="label"
                                    />
                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="w-full mb-2.5">
                                    <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attributes.edit.validation')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="multiselect"
                                        name="validation"
                                        ::options="filteredValidationTypes"
                                        ::disabled="true"
                                        track-by="id"
                                        label-by="label"
                                    />
                                    <x-admin::form.control-group.error control-name="validation" />
                                </x-admin::form.control-group>
                            </div>
                            <!-- Right Panel -->
                            <div class="overflow-auto" style="max-height:246px">
                                <p class="text-md mb-2 text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.attributes.edit.label')
                                </p>
                                @foreach ($locales as $index => $locale)
                                    <x-admin::form.control-group class="w-full mb-2.5">
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="translations[{{ $index }}]label"
                                            :label="$locale->name"
                                        />

                                        <x-admin::form.control-group.error control-name="translations[{{ $index }}]label" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>

                        <!-- Options Table -->
                        <div class="mt-8" v-if="selectedType === 'select' || selectedType === 'multiselect'">
                            
                            <div class="mt-1 pt-2 pb-2 text-gray-800 dark:text-white text-base font-semibold">
                            @lang('admin::app.catalog.attributes.edit.options')
                            </div>
                            <!-- Options Section -->
                            <div class="border border-slate-300 dark:border-gray-800 rounded p-2 grid grid-cols-2 gap-4 mt-8">
                                <div class="col-span-1 space-y-2 overflow-auto border-r pr-4" style="max-height:246px"   @scroll="onScroll" >
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="cursor-pointer w-[254px] text-violet-700 font-semibold text-md mr-1" >
                                            <div class="relative w-full flex items-center justify-center mb-3" >
                                                <input
                                                    type="text"
                                                    class="bg-white dark:bg-cherry-800 border dark:border-cherry-900 rounded-lg block w-full ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 py-1.5 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400"
                                                    placeholder="Search"
                                                    @keydown.enter.prevent="search($event.target.value)"
                                                />
                    
                                                <span class="icon-search text-2xl absolute ltr:right-5 rtl:left-3 top-1.5 flex items-center pointer-events-none"></span>
                                            </div>
                                        </div>

                                        <div class="cursor-pointer text-violet-700 font-semibold text-md mr-1" @click="$refs.addOption.toggle();">
                                        @lang('admin::app.catalog.attributes.create.add-option')
                                        </div>
                                    </div>

                                    <div v-if="optionData.length === 0" class="text-gray-500 italic text-center py-4">
                                    @lang('admin::app.components.datagrid.table.no-records-available')
                                    </div>
                                    <div
                                        v-for="option in optionData"
                                        :key="option.id"
                                        class="flex gap-1.5 hover:text-gray-800  w-full py-1.5 ltr:pr-1.5 rtl:pl-1.5 rounded text-gray-600 dark:text-gray-300 group cursor-pointer"
                                        @click="selectOption(option.id)"
                                    >
                                    <!-- text-violet-700 -->
                                        <span style="width:80%" class="w-[80%] flex gap-2.5 p-1.5 items-center cursor-pointer peer active"         :class="{ 'text-violet-700 font-semibold bg-violet-100 rounded-lg dark:bg-violet-200': activeOptionId === option.id }"
                                        >@{{ option.code }}</span>
                                        <span
                                            v-if="activeOptionId === option.id"
                                            class="w-[20%] icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center flex justify-center items-center"
                                            @click.stop="deleteOption(option.id)"
                                        >
                                        </span>
                    
                                    </div>
                                </div>

                                <div class="col-span-1 overflow-auto"  style="max-height:246px" v-if="optionData.length > 0">
                                    <div class="flex justify-between items-center pb-2 text-gray-800 dark:text-white text-base font-semibold">
                                        <span>@lang('admin::app.catalog.attribute-groups.edit.label')</span>
                                        <span 
                                            class="cursor-pointer text-violet-700 font-semibold text-md mr-1 p-2"
                                            @click="saveOption()"
                                        >
                                        @lang('admin::app.catalog.attributes.edit.table-attribute.save')
                                        </span>  
                                    </div>

                                    <div>
                                        <div v-if="optionData.length > 0" v-for="locale in locales" :key="locale.code">
                                            <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                                                @{{ locale.name }}
                                            </label>
                                            <input
                                                type="text"
                                                :value="getTranslationLabel(activeOptionId, locale.code)"
                                                name="locale.code"
                                                @blur="updateTranslationLabel(activeOptionId, locale.code, $event.target.value)"
                                                class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                                            />
                                        </div>
                        
                                    </div>
                                    <div class="">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </x-slot:content>

                <x-slot:footer>
                    <button type="submit" class="primary-button">
                        @lang('admin::app.catalog.attributes.edit.option.save-btn')
                    </button>
                </x-slot>
            </x-admin::modal>
        </form>
    </x-admin::form>
</script>
<script type="module">
app.component('v-edit-table-attribute', {
    template: '#v-edit-table-attribute-template',

    props: ['locales'],

    data: function () {
        return {
            selectedType: null,
            isSearching: false,
            selectedAttributeType: "table",
            columnsData: [],
            optionData: [],
            updatedOption: [],
            columnIsNew: true,
            validationType: '',
            columnValidationTypesJson: JSON.parse('{!! $columnValidationTypesJson !!}'),
            type: '',
            typeLabels: JSON.parse('{!! $columnTypesJson !!}'),
            filteredValidationTypes: null,
            selectedValidation: null,
            columnId: null,
            activeOptionId: null,
            attrId: "{{ $attribute?->id ?? null }}",
            loadingMore: false,
            loading: false,
            loadingOptions: false,
            hasMoreOptions: true,
            src: "{{ route('admin.catalog.attributes.columns.add', $attribute?->id ?? '') }}",
            addOptionRoute: "{{ route('admin.catalog.attributes.columns.options.add', ['id' => 'COLUMN_ID']) }}",
            updateColumnRoute: "{{ route('admin.catalog.attributes.columns.update',  ['columnId' => 'COLUMN_ID']) }}",
            getOptionsRoute: "{{route('admin.catalog.attributes.columns.option.get', ['id' => 'COLUMN_ID'])}}",
            updateOptionRoute: "{{route('admin.catalog.attributes.column.option.update', ['id' => 'OPTION_ID'] )}}",
            deleteOptionRoute: "{{route('admin.catalog.attributes.columns.options.delete', ['id' => 'OPTION_ID'])}}"
        };
    },

    created() {
        this.registerGlobalEvents();
    },

    watch: {
        selectedType(newType) {

            try {
                let type = JSON.parse(newType);
                this.selectedValidation = null;
                this.$refs.validation.selectedValue = null;
                this.updateValidationTypes(type['key']);
            } catch (e) {}
        }
    },

    methods: {
        updateValidationTypes(selectedType) {
            let filtered = this.columnValidationTypesJson.filter(item => item.supports.includes(selectedType));
            this.filteredValidationTypes = JSON.stringify(filtered);
        },

        registerGlobalEvents() {
            this.$emitter.on('open-v-large-modal', (url) => {
                this.fetchData(url);
            });
        },

        onScroll(event) {
            const bottom = event.target.scrollHeight === event.target.scrollTop + event.target.clientHeight;
            if (bottom && !this.loadingMore && this.hasMoreOptions) {
                this.loadMoreOptions();
            }
        },

        async loadMoreOptions(query = "") {
            if (this.loadingMore || !this.hasMoreOptions) return;

            this.loadingMore = true;

            const url = this.getOptionsRoute.replace('COLUMN_ID', this.columnId);

            try {
                const response = await axios.get(url, {
                    params: {
                        offset: this.optionData.length,
                        query : query
                    }
                });

                const newOptions = response.data.options;
                if (query) {
                    this.optionData = newOptions;
                }
                else {
                    if (newOptions.length === 0) {
                        this.hasMoreOptions = false;
                    } else if (this.isSearching) {
                        this.optionData = newOptions;
                        this.isSearching = false;
                    } 
                    else {
                        this.optionData = [...this.optionData, ...newOptions];
                    }
                }
            } catch (error) {
                console.error('Failed to load more options:', error);
            } finally {
                this.loadingMore = false;
            }
        },

        deleteOption(id) {
            const url = this.deleteOptionRoute.replace('OPTION_ID', id);

            this.$axios.delete(url)
                .then(response => {
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: "@lang('admin::app.catalog.attributes.edit.table.save-success')"
                    });
                    this.reloadOptions();
                })
                .catch(error => {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: "@lang('admin::app.catalog.attributes.edit.table.save-failed')"
                    });
                    console.error(error);
                });
        },

        getTranslationLabel(optionId, localeCode) {
            const option = this.optionData.find(opt => opt.id === optionId);
            if (!option || !option.translations) return '';
            const translation = option.translations.find(t => t.locale === localeCode);

            return translation ? translation.label : '';
        },

        updateTranslationLabel(optionId, localeCode, newValue) {
            if (!this.updatedOption || this.updatedOption.id !== optionId) {
                this.updatedOption = {
                    id: optionId
                };
            }

            this.updatedOption[localeCode] = { label: newValue };
        },

        search(value) {
            this.hasMoreOptions = true;
            this.isSearching = true;
            this.loadMoreOptions(value);
        },

        fetchData(url) {
            this.$axios.get(url.url)
            .then(response => {
               const data = response.data;

                this.selectedType = data.type;
                this.columnId = data.id;

                this.optionData = data.options || [];

                if (this.optionData.length > 0) {
                    this.activeOptionId = this.optionData[0].id;
                } else {
                    this.activeOptionId = null;
                }

                console.log(this.optionData);
                data.validation = typeof data.validation === 'string'
                    ? data.validation.split(',').map(v => v.trim())
                    : [];

                this.updateValidationTypes(this.selectedType);

                this.$refs.modelForm.setValues(data);
                this.$refs.editColumns.open();
            })
            .catch(error => {
                console.error("Fetch error:", error);
            });
        },

        selectOption(optionId) {
            this.activeOptionId = optionId;
            this.updatedOption = [];
        },

        reloadOptions() {
            const url = this.getOptionsRoute.replace('COLUMN_ID', this.columnId);

            this.$axios.get(url, {
                params: {
                    offset: 0,
                }
                })
                .then(response => {
                    const options = response.data.options || [];

                    this.optionData = options;

                    if (options.length > 0) {
                        this.activeOptionId = options[0].id;
                    } else {
                        this.activeOptionId = null;
                    }
                })
                .catch(error => {
                    console.error("Reload options error:", error);
                });
        },

        saveOption() {
            const url = this.updateOptionRoute.replace('OPTION_ID', this.activeOptionId);

            this.$axios.put(url, this.updatedOption,  {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
                })
                .then(response => {
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: "@lang('admin::app.catalog.attributes.edit.table-attribute.save-success')"
                    });

                    this.reloadOptions();
                })
                .catch(error => {
                    this.errors = error.response.data.errors;
                });

        },

        editColumn(params, { resetForm }) {
            const updateurl = this.updateColumnRoute.replace('COLUMN_ID', this.columnId);
            console.log(params);
            params.translations.forEach(item => {
                if (item.locale) {
                    params[item.locale] = { label: item.label };
                }
            });

            this.$axios.put(updateurl, params)
                .then(response => {
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: "@lang('admin::app.catalog.attributes.edit.table-attribute.save-success')"
                    });
                    this.$refs.columnDatagrid.get();
                    this.$refs.editColumns.toggle();
                })
                .catch(error => {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: "@lang('admin::app.catalog.attributes.edit.table-attribute.save-failed')"
                    });
                    console.error(error);
                });
        },

        removeColumn(id) {
            const index = this.columnsData.findIndex(item => item.id === id);
            if (index !== -1) {
                this.columnsData[index].isNew
                    ? this.columnsData.splice(index, 1)
                    : this.columnsData[index].isDelete = true;
            }
        },

        storeOption(params, { resetForm, setErrors }) {
            if(this.loadingOptions) return;
            const url = this.addOptionRoute.replace('COLUMN_ID', this.columnId);
            let data = new FormData(this.$refs.addOptionFormRef);

            this.loadingOptions = true;
            
            this.$axios.post(url, data)
                .then(response => {
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: "@lang('admin::app.catalog.attributes.edit.table-attribute.save-success')"
                    });
                    this.reloadOptions();
                    this.$refs.addOption.toggle();
                    resetForm();
                })
                .catch(error => {
                    setErrors(error.response.data.errors);
                })
                .finally(() => {
                    this.loadingOptions = false;
                });
        },


        storeColumns(params, { resetForm }) {
            if(this.loading) return;
            let data = new FormData(this.$refs.addColumnsForm);
            this.loading = true;
            this.$axios.post(this.src, data)
                .then(response => {
                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: "@lang('admin::app.catalog.attributes.edit.table-attribute.save-success')"
                    });
                    this.$refs.columnDatagrid.get();
                    this.$refs.addColumns.toggle();
                    resetForm();
                })
                .catch(error => {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: "@lang('admin::app.catalog.attributes.edit.table-attribute.save-failed')"
                    });
                    if (error.response.status === 422) {
                        this.$refs.addColumnForm.setErrors(error.response.data.errors);
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        parseValue(value) {
            try {
                return value ? JSON.parse(value) : null;
            } catch {
                return value;
            }
        },

        getTypeLabel(key) {
            const found = this.typeLabels.find(item => item.key === key);
            return found ? found.label : key;
        }
    }
});
</script>
@endPushOnce
