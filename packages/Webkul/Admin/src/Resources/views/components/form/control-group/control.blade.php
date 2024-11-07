@props([
    'type' => 'text',
    'name' => '', 
    'info' => '',
])

@switch($type)
    @case('hidden')
    @case('text')
    @case('email')
    @case('password')
    @case('number')
        <v-field
            v-slot="{ field, errors }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <input
                type="{{ $type }}"
                name="{{ $name }}"
                v-bind="field"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600']) }}
            />
        </v-field>

        @break

    @case('price')
        <v-field
            v-slot="{ field, errors }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <div
                class="flex items-center w-full border rounded-md overflow-hidden text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400  focus-within:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
            >
                @if (isset($currency))
                    <span {{ $currency->attributes->merge(['class' => 'ltr:pl-4 rtl:pr-4 py-2.5 text-gray-500']) }}>
                        {{ $currency }}
                    </span>
                @else
                    <span class="ltr:pl-4 rtl:pr-4 py-2.5 text-gray-500">
                        {{ core()->currencySymbol(core()->getBaseCurrencyCode()) }}
                    </span>
                @endif

                <input
                    type="text"
                    name="{{ $name }}"
                    v-bind="field"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full p-2.5 text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900']) }}
                />
            </div>
        </v-field>

        @break

    @case('file')
        <v-field
            v-slot="{ field = {}, errors = [], handleChange, handleBlur }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label', 'info', ':info']) }}
            name="{{ $name }}"
        >
            <v-file-uploader
                name="{{ $name }}"
                :field="field"
                :class="[errors.length ? 'border border-red-500' : '']"
                 {{ $attributes }}
            >

            </v-file-uploader>
        </v-field>

        @break

    @case('color')
        <v-field
            name="{{ $name }}"
            v-slot="{ field, errors }"
            {{ $attributes->except('class') }}
        >
            <input
                type="{{ $type }}"
                :class="[errors.length ? 'border border-red-500' : '']"
                v-bind="field"
                {{ $attributes->except(['value'])->merge(['class' => 'w-full appearance-none border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400']) }}
            >
        </v-field>
        @break

    @case('textarea')
        <v-field
            v-slot="{ field, errors }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <textarea
                type="{{ $type }}"
                name="{{ $name }}"
                v-bind="field"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600']) }}
            >
            </textarea>

            @if ($attributes->get('tinymce', false) || $attributes->get(':tinymce', false))
                <x-admin::tinymce 
                    :selector="'textarea#' . $attributes->get('id')"
                    :prompt="stripcslashes($attributes->get('prompt', ''))"
                    ::field="field"
                >
                </x-admin::tinymce>
            @endif
        </v-field>

        @break

    @case('date')
        <v-field
            v-slot="{ field, errors }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <x-admin::flat-picker.date>
                <input
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600']) }}
                    autocomplete="off"
                />
            </x-admin::flat-picker.date>
        </v-field>

        @break

    @case('datetime')
        <v-field
            v-slot="{ field, errors }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <x-admin::flat-picker.datetime>
                <input
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600']) }}
                    autocomplete="off"
                >
            </x-admin::flat-picker.datetime>
        </v-field>
        @break

    @case('select')
        <v-field
            v-slot="{ field, errors }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            @if ('true' == $attributes->get('async'))
                <v-async-select-handler
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border border-red-500' : '']"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
                >
                </v-async-select-handler>
            @else
                <v-select-handler
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border border-red-500' : '']"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
                >
                </v-select-handler>
            @endIf
        </v-field>

        @break

    @case('multiselect')
        <v-field
            v-slot="{ field, errors }"
            :class="[errors && errors['{{ $name }}'] ? 'border !border-red-600 hover:border-red-600' : '']"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            @if ('true' == $attributes->get('async'))
                <v-async-select-handler
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border border-red-500' : '']"
                    multiple="true"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
                >
                </v-async-select-handler>
            @else
                <v-multiselect-handler
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border border-red-500' : '']"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
                >
                </v-multiselect-handler>
            @endIf
        </v-field>

        @break

    @case('tagging')
        <v-field
            v-slot="{ field, errors }"
            :class="[errors && errors['{{ $name }}'] ? 'border !border-red-600 hover:border-red-600' : '']"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            
                <v-tagging-handler
                    :taggable=true
                    name="{{ $name }}"
                    v-bind="field"
                    :class="[errors.length ? 'border border-red-500' : '']"
                    {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
                >
                </v-tagging-handler>
            
        </v-field>

        @break

    @case('checkbox')
        <v-field
            v-slot="{ field }"
            type="checkbox"
            class="hidden"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <input
                type="checkbox"
                name="{{ $name }}"
                v-bind="field"
                class="sr-only peer"
                {{ $attributes->except(['rules', 'label', ':label']) }}
            />

            <v-checkbox-handler
                :field="field"
                checked="{{ $attributes->get('checked') }}"
            >
            </v-checkbox-handler>
        </v-field>

        <label
             {{ 
                $attributes
                    ->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])
                    ->merge(['class' => 'icon-checkbox-normal text-2xl peer-checked:icon-checkbox-check peer-checked:text-violet-700'])
                    ->merge(['class' => $attributes->get('disabled') ? 'opacity-70 cursor-not-allowed' : 'cursor-pointer'])
            }}
        >
        </label>

        @break

    @case('radio')
        <v-field
            type="radio"
            class="hidden"
            v-slot="{ field }"
            {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'v-model', 'rules', ':rules', 'label', ':label']) }}
            name="{{ $name }}"
        >
            <input
                type="radio"
                name="{{ $name }}"
                v-bind="field"
                class="sr-only peer"
                {{ $attributes->except(['rules', 'label', ':label']) }}
            />
        </v-field>

        <label
            class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-violet-700 cursor-pointer"
            {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
        >
        </label>

        @break

    @case('switch')
        <label class="relative inline-flex items-center cursor-pointer">
            <v-field
                type="checkbox"
                class="hidden"
                v-slot="{ field }"
                {{ $attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
                name="{{ $name }}"
            >
                <input
                    type="checkbox"
                    name="{{ $name }}"
                    id="{{ $name }}"
                    class="sr-only peer"
                    v-bind="field"
                    {{ $attributes->except(['v-model', 'rules', ':rules', 'label', ':label']) }}
                />
                
                <v-checkbox-handler
                    class="hidden"
                    :field="field"
                    checked="{{ $attributes->get('checked') }}"
                >
                </v-checkbox-handler>
            </v-field>

            <label
                class="rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-violet-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-violet-700 dark:peer-checked:bg-violet-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800"
                for="{{ $name }}"
            ></label>
        </label>

        @break

    @case('image')
        <x-admin::media.images
            name="{{ $name }}"
            ::class="[errors && errors['{{ $name }}'] ? 'border !border-red-600 hover:border-red-600' : '']"
            {{ $attributes }}
        />

        @break

    @case('custom')
        <v-field {{ $attributes }}>
            {{ $slot }}
        </v-field>
@endswitch

@pushOnce('scripts')
    <script type="text/x-template" id="v-checkbox-handler-template">        
    </script>

    <script type="module">
        app.component('v-checkbox-handler', {
            template: '#v-checkbox-handler-template',

            props: ['field', 'checked'],

            mounted() {
                if (this.checked == '') {
                    return;
                }

                this.field.checked = true;

                this.field.onChange();
            },
        });

    </script>

    <script type="text/x-template" id="v-select-handler-template">
        <div>
            <v-multiselect
                :track-by="trackBy ?? 'id'"
                :label="labelBy ?? 'label'"
                :options="formattedOptions"
                :preserve-search="false"
                :searchable="true"
                :placeholder="placeholder"
                :close-on-select="true"
                :clear-on-select="true"
                :show-no-results="true"
                :hide-selected="false"
                :disabled="disabled ?? false"
                :name="name"
                v-model="selectedValue"
                v-bind="field"
            >

            </v-multiselect>   
            <input
                v-model="selectedOption"
                v-validate="'required'"
                :name="name"
                type="hidden"
            >
        </div>
         
    </script>

    <script type="module">
        app.component('v-select-handler', {
            template: '#v-select-handler-template',

            props: {
                trackBy: {
                    type: String,
                    default: 'id'
                },
                labelBy: {
                    type: String,
                    default: 'label'
                },
                options: Array,
                label: String,
                name: String,
                value: String,
                field: Array,
                placeholder: String,
                disabled: Boolean
            },
            
            data() {
                return {
                    selectedValue: this.parseValue() ? this.parseOptions().find(option => option[this.trackBy] === this.parseValue()) : null,
                }
            },

            computed: {
                formattedOptions() {
                    return this.parseOptions();
                },
                selectedOption() {
                    return this.selectedValue instanceof Object ? this.selectedValue[this.trackBy] : null;
                }
            },

            watch: {
                selectedValue(newValue) {
                    if (! (newValue instanceof Object)) {
                        this.$emit('input', '');

                        return;
                    }

                    this.$emit('input', JSON.stringify(newValue));
                },
            },
            
            methods: {
                parseOptions() {
                    try {
                        return JSON.parse(this.options);
                    } catch (error) {
                        if (this.options !== null && Array.isArray(this.options)) {
                            return this.options;
                        }
                        
                        console.error('Error parsing options JSON:', error);

                        return [];
                    }
                },
                parseValue() {
                    try {
                        return this.value ? JSON.parse(this.value) : null;
                    } catch (error) {
                        return this.value;
                    }
                }
            }
        });
    </script>

    <script type="text/x-template" id="v-multiselect-handler-template">
        <div>
            <v-multiselect
                :track-by="trackBy"
                :label="labelBy"
                :options="formattedOptions"
                :preserve-search="false"
                :multiple="true"
                :searchable="true"
                :placeholder="placeholder"
                :close-on-select="true"
                :clear-on-select="true"
                :show-no-results="true"
                :hide-selected="true"
                :name="name"
                v-model="selectedValue"
                v-bind="field"
            >

            </v-multiselect>   
            <input
                v-model="selectedOption"
                v-validate="'required'"
                :name="name"
                type="hidden"
            >
        </div>
    </script>

    <script type="module">
        app.component('v-multiselect-handler', {
            template: '#v-multiselect-handler-template',

            props: {
                trackBy: {
                    type: String,
                    default: 'id'
                },
                labelBy: {
                    type: String,
                    default: 'label'
                },
                options: Array,
                label: String,
                name: String,
                value: String,
                field: Array,
                placeholder: String,
            },
            
            data() {
                return {
                    selectedValue: this.parseValue() ? this.parseOptions().filter(option =>  this.parseValue() instanceof Array && this.parseValue()?.some(valueItem => option[this.trackBy] === valueItem)) : [],
                }
            },

            computed: {
                formattedOptions() {
                    return this.parseOptions();
                },
                selectedOption() {
                    let selectedOptions = [];

                    this.selectedValue instanceof Array ? this.selectedValue.forEach((item) => {
                        selectedOptions.push(item[this.trackBy]);
                    }) : null;

                    return selectedOptions.length > 0 ? selectedOptions : null;
                }
            },

            watch: {
                selectedValue(newValue) {
                    if (
                        (newValue instanceof Array && newValue.length < 1) || null == newValue
                    ) {
                        this.$emit('input', newValue);

                        return;
                    }

                    this.$emit('input', JSON.stringify(newValue));
                },
            },

            methods: {
                parseOptions() {
                    try {
                        return this.options ? JSON.parse(this.options) : [];
                    } catch (error) {
                        console.error('Error parsing options JSON:', error);
                        return [];
                    }
                },
                parseValue() {
                    try {
                        return this.value ? JSON.parse(this.value) : [];
                    } catch (error) {
                        return this.value;
                    }
                }
            }
        });
    </script>


    <script type="text/x-template" id="v-tagging-handler-template">
        <div>
            <v-multiselect
                :track-by="trackBy"
                :label="labelBy"
                :taggable="true"
                @tag="addTag"
                @select="selectOption"
                @remove="removeOption"
                :options="formattedOptions"
                :preserve-search="false"
                :multiple="true"
                :searchable="true"
                :placeholder="placeholder"
                :close-on-select="false"
                :clear-on-select="false"
                :show-no-results="true"
                :hide-selected="true"
                :name="name"
                v-model="selectedValue"
                v-bind="field"
            >

            </v-multiselect>   
            <input
                v-model="selectedOption"
                v-validate="'required'"
                :name="name"
                type="hidden"
            >
        </div>
    </script>

    <script type="module">
        app.component('v-tagging-handler', {
            template: '#v-tagging-handler-template',

            props: {
                trackBy: {
                    type: String,
                    default: 'id'
                },
                labelBy: {
                    type: String,
                    default: 'label'
                },
                options: Array,
                label: String,
                name: String,
                value: String,
                field: Array,
                placeholder: String,
            },
            
            data() {
                return {
                    selectedValue: this.parseValue() ? this.parseOptions().filter(option =>  this.parseValue() instanceof Array && this.parseValue()?.some(valueItem => option[this.trackBy] === valueItem)) : [],
                }
            },

            computed: {
                formattedOptions() {
                    return this.parseOptions();
                },
                selectedOption() {
                    let selectedOptions = [];

                    this.selectedValue instanceof Array ? this.selectedValue.forEach((item) => {
                        selectedOptions.push(item[this.trackBy]);
                    }) : null;

                    return selectedOptions.length > 0 ? selectedOptions : null;
                },
                
            },

            watch: {
                selectedValue(newValue) {
                    if (
                        (newValue instanceof Array && newValue.length < 1) || null == newValue
                    ) {
                        this.$emit('input', newValue);

                        return;
                    }

                    this.$emit('input', JSON.stringify(newValue));
                },
            },

            methods: {
                parseOptions() {
                    try {
                        return this.options ? JSON.parse(this.options) : [];
                    } catch (error) {
                        console.error('Error parsing options JSON:', error);
                        return [];
                    }
                },
                parseValue() {
                    try {
                        return this.value ? JSON.parse(this.value) : [];
                    } catch (error) {
                        return this.value;
                    }
                },
                addTag (newTag) {
                    const tag = {
                        name: newTag,
                        code: newTag
                    };
                    this.formattedOptions.push(tag);
                    this.selectedValue.push(tag);
                    this.$emit('add-option', {
                        target: {
                            value: tag
                        }
                    });
                },
                selectOption(tag) {
                    this.$emit('select-option', {
                        target: {
                            value: tag
                        }
                    });
                },
                removeOption(tag) {
                    this.$emit('remove-option', {
                        target: {
                            value: tag
                        }
                    });
                },                
            }
        });
    </script>

    <script type="text/x-template" id="v-async-select-handler-template">
        <div>
            <v-multiselect
                id="ajax"
                :track-by="trackBy"
                :label="labelBy"
                :options="formattedOptions"
                :preserve-search="true"
                :searchable="true"
                :placeholder="placeholder"
                :loading="isLoading ?? false"
                :max-height="600"
                :internal-search="false"
                :close-on-select="true"
                :clear-on-select="false"
                :show-no-results="true"
                :hide-selected="false"
                :disabled="disabled ?? false"
                :multiple="multiple ?? false"
                :name="name"
                @search-change="handleSearch"
                @open="openedSelect"
                @scroll="onScroll"
                ref="multiselect__handler__"
                v-model="selectedValue"
                v-bind="field"
            >
            </v-multiselect>   
            <input
                v-model="selectedOption"
                v-validate="'required'"
                :name="name"
                type="hidden"
            >
        </div>
         
    </script>

    <script type="module">
        app.component('v-async-select-handler', {
            template: '#v-async-select-handler-template',

            props: {
                trackBy: {
                    type: String,
                    default: 'id'
                },
                labelBy: {
                    type: String,
                    default: 'label'
                },
                options: Array,
                label: String,
                name: String,
                value: String,
                field: Array,
                placeholder: String,
                disabled: Boolean,
                isLoading: Boolean,
                entityName: String,
                attributeId: String,
                multiple: Boolean,
                listRoute: {
                    type: String,
                    default: '{{ route('admin.catalog.options.fetch-all')}}'
                },
                queryParams: Array,
            },

            data() {
                return {
                    selectedValue: this.parseValue() ? this.parseValue() : null,

                    isLoading: false,
                    optionsList: [],
                    timeout: null,
                    delayTime: 500,
                    lastPage: 1,

                    params: {
                        entityName: this.entityName,
                        attributeId: this.attributeId,
                        page: 1,
                        locale: "{{ core()->getRequestedLocaleCode() }}",
                        ...this.queryParams
                    }
                }
            },

            computed: {
                formattedOptions() {
                    return this.optionsList;
                },
                selectedOption() {
                    if (this.multiple) {
                        return this.getMultiSelectedOption();
                    }

                    return this.getSingleSelectedOption();
                }
            },

            mounted() {
                this.$refs['multiselect__handler__']._.refs.list.addEventListener('scroll', this.onScroll);

                if (this.selectedValue && typeof this.selectedValue != 'object') {
                    this.initializeValue();
                }
            },

            watch: {
                selectedValue(newValue) {
                    if (this.multiple && this.isInvalidMultipleValue(newValue)) {
                        this.$emit('input', newValue);

                        return;
                    } else if (! this.multiple && this.isInvalidSingleValue(newValue)) {
                        this.$emit('input', '');

                        return;
                    }

                    this.$emit('input', JSON.stringify(newValue));
                },
            },
            
            methods: {
                parseValue() {
                    try {
                        return this.value ? JSON.parse(this.value) : null;
                    } catch (error) {
                        return this.value;
                    }
                },
                getMultiSelectedOption() {
                    let selectedOptions = [];

                    this.selectedValue instanceof Array ? this.selectedValue.forEach((item) => {
                        selectedOptions.push(item[this.trackBy]);
                    }) : null;

                    return selectedOptions.length > 0 ? selectedOptions : null
                },
                getSingleSelectedOption() {
                    return this.selectedValue instanceof Object ? this.selectedValue[this.trackBy] : null;
                },
                isInvalidMultipleValue(newValue) {
                    if ((newValue instanceof Array && newValue.length < 1) || null == newValue) {
                        return true;
                    }

                    return false;
                },
                isInvalidSingleValue(newValue) {
                    if (! (newValue instanceof Object)) {
                        return true;
                    }

                    return false;
                },

                handleSearch(query) {
                    if (null !== this.timer) {
                        clearTimeout(this.timer);
                    }

                    this.timer = setTimeout(this.search(query), this.delayTime);
                },

                search(query) {
                    this.isLoading = true;
                    this.params.query = query;
                    this.params.page = 1;

                    this.$axios.get(this.listRoute, {params: this.params})
                        .then((result) => {
                            this.optionsList = result.data.options;
                            this.lastPage = result.data.lastPage;
                            this.params.page = result.data.page;

                            this.isLoading = false;
                        })
                },
                openedSelect(id) {
                    if (this.optionsList.length < 1) {
                        this.isLoading = true;

                        this.$axios.get(this.listRoute, {params: this.params})
                            .then((result) => {
                                this.optionsList = result.data.options;
                                this.lastPage = result.data.lastPage;
                                this.params.page = result.data.page;

                                this.isLoading = false;
                            });
                    }
                },
                onScroll(e) {
                    const element = this.$refs['multiselect__handler__']._.refs.list;

                    if (
                        (element.scrollHeight - element.scrollTop) == element.clientHeight
                        && this.lastPage > this.params.page
                    ) {
                        this.fetchMore();
                    }
                },
                fetchMore() {
                    this.isLoading = true;

                    this.params.page++;

                    this.$axios.get(this.listRoute, {params: this.params})
                        .then((result) => {
                            this.optionsList = [...this.optionsList, ...result.data.options];
                            this.lastPage = result.data.lastPage;
                            this.params.page = result.data.page;

                            this.isLoading = false;
                        });
                },

                initializeValue() {
                    this.isLoading = true;
                    
                    this.params.identifiers = {
                        columnName: this.trackBy,
                        values: 'string' == typeof this.selectedValue ? this.selectedValue?.split(',') : this.selectedValue
                    };

                    this.$axios.get(this.listRoute, {params: this.params})
                        .then((result) => {
                            this.selectedValue = this.multiple ? result.data.options : result.data.options[0];

                            this.params.identifiers = {};

                            this.isLoading = false;
                        })
                }
            }
        });
    </script>
 
    <script type="text/x-template" id="v-file-uploader-template">
        <div :class="[errors.length ? 'flex items-center justify-center w-full border !border-red-600 hover:border-red-600' : 'flex items-center justify-center w-full']">
            <label :for="$.uid + '_dropzone-file'" class="flex flex-col items-center justify-center w-full h-full border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-violet-50 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                <div class="flex flex-col items-center justify-center py-6">
                    <template v-if="fieldData.value && (fieldData.value.name || field.value)">
                        <span class="icon-product text-4xl mb-4 mr-4"></span>
                        <div class="flex justify-between items-center mb-2 text-sm text-gray-500 dark:text-gray-400">
                            <p class="text-sm text-gray-500 dark:text-gray-400" v-html="fieldData.value.name || field.value"></p>
                            <button
                                type="button"
                                @click="clearFile"
                                class="icon-cancel text-3xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md"
                            >
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <span class="icon-export text-gray-500 dark:text-gray-400 text-4xl"></span>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" v-html="info"></p>
                    </template>
                </div>
                            
                <input
                    :id="$.uid + '_dropzone-file'"
                    type="file"
                    :name="name"
                    class="hidden"
                    :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                    @change="handleChange"
                    @blur="handleBlur"
                    ref="fileInput"
                />
            </label>
        </div>
    </script>
    
    <script type="module">
        app.component('v-file-uploader', {
            template: '#v-file-uploader-template',

            props: {
                field: Object,
                info: String,
                name: String,
                value: String,
                errors: {
                    type: Array,
                    default: () => []
                }
            },

            data() {
                return {
                    fieldData: this.field,
                }
            },
            watch: {
                field: {
                    handler(newValue) {
                        this.fieldData = newValue;
                    },
                    deep: true
                },
                fieldData: {
                    handler(newValue) {
                        this.$forceUpdate();
                    },
                    deep: true
                }
                },
            methods: {
                handleChange(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.fieldData.value = file;
                    }
                    this.$nextTick(() => {
                        this.$forceUpdate();
                    });
                },
                 
                clearFile(event) {
                    this.fieldData.value = null;
                    // this.$refs.fileInput.value = null;
                    this.$nextTick(() => {
                        // Force update to refresh any related UI without reopening the upload dialog
                        this.$forceUpdate();
                    });
                    event.preventDefault();
                }
            }
        });
    </script>
@endpushOnce
