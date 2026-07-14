@props(['types' => []])

@pushOnce('scripts')
    <script type="module">
        window.unopim = window.unopim || {};

        window.unopim.fieldBase = {
            props: {
                field:      { type: Object, required: true },
                name:       { type: String, required: true },
                modelValue: { default: null },
                errors:     { type: [Array, Object], default: () => ([]) },
                disabled:   { type: Boolean, default: false },
                context:    { type: String, default: 'form' },
            },

            emits: ['update:modelValue'],

            computed: {
                hasErrors() {
                    return Array.isArray(this.errors)
                        ? this.errors.length > 0
                        : Object.keys(this.errors ?? {}).length > 0;
                },

                inputId() {
                    return (this.field?.name || this.name || '').replace(/[^\w-]+/g, '-');
                },

                inputClass() {
                    return [
                        'w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600',
                        this.hasErrors ? 'border !border-red-600 hover:border-red-600' : '',
                    ];
                },
            },

            methods: {
                setValue(value) {
                    this.$emit('update:modelValue', value);

                    const element = (this.$el instanceof Element) ? this.$el : this.$el?.parentElement;

                    element?.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                        detail: { name: this.name },
                        bubbles: true,
                    }));
                },

                parseJson(value) {
                    try {
                        return JSON.parse(value);
                    } catch (exception) {
                        return value;
                    }
                },
            },
        };
    </script>

    <script type="text/x-template" id="v-form-field-template">
        <x-admin::form.control-group ::class="field.full_width ? 'col-span-2' : ''">
            <x-admin::form.control-group.label
                v-if="showLabel"
                ::class="field.required ? 'required' : ''"
                ::for="inputId"
            >
                <span v-text="field.label"></span>

                <button
                    v-if="field.info"
                    type="button"
                    class="icon-information text-base text-gray-500 dark:text-gray-300 cursor-pointer"
                    :id="inputId + '-info'"
                    :title="field.info"
                    :aria-label="field.info"
                ></button>
            </x-admin::form.control-group.label>

            <v-field
                v-slot="{ field: veeField, errors }"
                :rules="field.validation"
                :name="qualifiedName"
                :label="field.label"
                :model-value="modelValue"
            >
                <component
                    v-bind="$attrs"
                    :is="componentFor(field.type)"
                    :field="field"
                    :name="qualifiedName"
                    :model-value="modelValue"
                    :errors="errors"
                    :disabled="disabled"
                    :context="context"
                    :aria-describedby="field.info ? inputId + '-info' : null"
                    @update:model-value="onInput($event, veeField)"
                />
            </v-field>

            <v-error-message :name="qualifiedName" v-slot="{ message }">
                <p class="mt-1 text-red-600 text-xs italic" v-text="message"></p>
            </v-error-message>
        </x-admin::form.control-group>
    </script>

    <script type="text/x-template" id="v-field-set-template">
        <div :class="gridClass">
            <v-form-field
                v-for="field in visibleFields"
                :key="fieldKey(field)"
                :field="scopedField(field)"
                :name-prefix="namePrefix"
                :model-value="values[field.name]"
                :context="mode"
                @update:model-value="setValue(field, $event)"
            />
        </div>
    </script>

    <script type="text/x-template" id="v-field-text-template">
        <input
            type="text"
            :id="inputId"
            :name="name"
            :value="modelValue"
            :placeholder="field.placeholder"
            :disabled="disabled"
            :class="inputClass"
            :aria-invalid="hasErrors"
            autocomplete="off"
            @change="setValue($event.target.value)"
        />
    </script>

    <script type="module">
        app.component('v-form-field', {
            template: '#v-form-field-template',

            inheritAttrs: false,

            props: {
                field:      { type: Object, required: true },
                modelValue: { default: null },
                namePrefix: { type: String, default: '' },
                disabled:   { type: Boolean, default: false },
                context:    { type: String, default: 'form' },
            },

            emits: ['update:modelValue'],

            computed: {
                qualifiedName() {
                    return this.namePrefix
                        ? `${this.namePrefix}[${this.field.name}]`
                        : this.field.name;
                },

                inputId() {
                    return (this.field.name || '').replace(/[^\w-]+/g, '-');
                },

                showLabel() {
                    return this.context !== 'filter' && !! this.field.label;
                },
            },

            methods: {
                componentFor(type) {
                    const name = 'v-field-' + type;

                    return this.$.appContext.components[name] ? name : 'v-field-text';
                },

                onInput(value, veeField) {
                    veeField?.onChange?.(value);

                    this.$emit('update:modelValue', value);
                },
            },
        });

        app.component('v-field-set', {
            template: '#v-field-set-template',

            props: {
                fields:        { type: [Array, String], default: () => ([]) },
                initialValues: { type: [Object, String], default: () => ({}) },
                namePrefix:    { type: String, default: 'filters' },
                mode:          { type: String, default: 'form' },
                gridClass:     { type: String, default: '' },
                only:          { type: String, default: '' },
                except:        { type: String, default: '' },
            },

            emits: ['change'],

            data() {
                return {
                    values: this.decode(this.initialValues) || {},
                };
            },

            computed: {
                fieldList() {
                    const only = this.toList(this.only);
                    const except = this.toList(this.except);

                    return (this.decode(this.fields) || []).filter(field => {
                        if (only.length && ! only.includes(field.name)) {
                            return false;
                        }

                        return ! except.includes(field.name);
                    });
                },

                visibleFields() {
                    return this.fieldList.filter(field => {
                        const rule = field.visible_when;

                        return ! rule || rule.values.includes(this.values[rule.field]);
                    });
                },
            },

            watch: {
                initialValues: {
                    deep: true,
                    handler(updated) {
                        this.values = this.decode(updated) || {};
                    },
                },
            },

            methods: {
                decode(value) {
                    if (typeof value !== 'string') {
                        return value;
                    }

                    try {
                        return JSON.parse(value);
                    } catch (exception) {
                        return value;
                    }
                },

                toList(value) {
                    return (value || '').split(',').map(item => item.trim()).filter(Boolean);
                },

                fieldKey(field) {
                    if (! field.depends_on) {
                        return field.name;
                    }

                    const value = this.values[field.depends_on.field];

                    return `${field.name}::${Array.isArray(value) ? value.join(',') : (value ?? '')}`;
                },

                scopedField(field) {
                    if (! field.depends_on) {
                        return field;
                    }

                    return {
                        ...field,
                        query_params: {
                            ...(field.query_params || {}),
                            [field.depends_on.as]: this.toCodes(this.values[field.depends_on.field]),
                        },
                    };
                },

                toCodes(value) {
                    if (! value) {
                        return [];
                    }

                    let parsed = typeof value === 'string' ? this.decode(value) : value;

                    if (typeof parsed === 'string') {
                        parsed = parsed.split(',');
                    }

                    if (! Array.isArray(parsed)) {
                        parsed = [parsed];
                    }

                    return parsed
                        .map(item => (item && typeof item === 'object') ? (item.code ?? item.value) : `${item ?? ''}`.trim())
                        .filter(Boolean);
                },

                setValue(field, value) {
                    this.values[field.name] = value;

                    this.fieldList
                        .filter(candidate => candidate.depends_on?.field === field.name)
                        .forEach(candidate => { this.values[candidate.name] = null; });

                    this.$emit('change', { name: field.name, value });

                    this.$emitter.emit('filter-value-changed', { filterName: field.name, value });
                },
            },
        });

        app.component('v-field-text', {
            template: '#v-field-text-template',
            mixins: [window.unopim.fieldBase],
        });
    </script>
@endPushOnce

@foreach (array_unique((array) $types) as $type)
    @pushOnce('scripts', 'v-field-'.$type)
        @switch($type)
            @case('number')
                <script type="text/x-template" id="v-field-number-template">
                    <input
                        type="number"
                        :id="inputId"
                        :name="name"
                        :value="modelValue"
                        :placeholder="field.placeholder"
                        :disabled="disabled"
                        :class="inputClass"
                        :aria-invalid="hasErrors"
                        autocomplete="off"
                        @change="setValue($event.target.value)"
                    />
                </script>

                <script type="module">
                    app.component('v-field-number', {
                        template: '#v-field-number-template',
                        mixins: [window.unopim.fieldBase],
                    });
                </script>
                @break

            @case('textarea')
                <script type="text/x-template" id="v-field-textarea-template">
                    <textarea
                        :id="inputId"
                        :name="name"
                        :placeholder="field.placeholder"
                        :disabled="disabled"
                        :class="inputClass"
                        :aria-invalid="hasErrors"
                        @change="setValue($event.target.value)"
                    >@{{ modelValue }}</textarea>
                </script>

                <script type="module">
                    app.component('v-field-textarea', {
                        template: '#v-field-textarea-template',
                        mixins: [window.unopim.fieldBase],
                    });
                </script>
                @break

            @case('boolean')
                <script type="text/x-template" id="v-field-boolean-template">
                    <div>
                        <input type="hidden" :name="name" value="0" />

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                :id="inputId"
                                :name="name"
                                value="1"
                                class="sr-only peer"
                                :checked="isChecked"
                                :disabled="disabled"
                                @change="setValue($event.target.checked ? '1' : '0')"
                            />

                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-violet-500 rounded-full peer dark:bg-gray-900 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-700"></div>
                        </label>
                    </div>
                </script>

                <script type="module">
                    app.component('v-field-boolean', {
                        template: '#v-field-boolean-template',

                        mixins: [window.unopim.fieldBase],

                        computed: {
                            isChecked() {
                                return '1' == (this.modelValue ?? this.field.default);
                            },
                        },
                    });
                </script>
                @break

            @case('select')
                <script type="text/x-template" id="v-field-select-template">
                    <div>
                        <v-async-select-handler
                            v-if="field.async"
                            :name="name"
                            :class="hasErrors ? 'border border-red-500' : ''"
                            :track-by="field.track_by"
                            :label-by="field.label_by"
                            :list-route="field.list_route"
                            :query-params="field.query_params"
                            :value="modelValue"
                            :placeholder="field.placeholder || undefined"
                            :disabled="disabled"
                            @input="setValue(parseJson($event)?.[field.track_by] ?? '')"
                        ></v-async-select-handler>

                        <v-select-handler
                            v-else
                            :name="name"
                            :options="JSON.stringify(field.options ?? [])"
                            :class="hasErrors ? 'border border-red-500' : ''"
                            track-by="value"
                            label-by="label"
                            :value="modelValue"
                            :placeholder="field.placeholder || undefined"
                            :disabled="disabled"
                            @input="setValue(parseJson($event)?.value ?? '')"
                        ></v-select-handler>
                    </div>
                </script>

                <script type="module">
                    app.component('v-field-select', {
                        template: '#v-field-select-template',
                        mixins: [window.unopim.fieldBase],
                    });
                </script>
                @break

            @case('multiselect')
                <script type="text/x-template" id="v-field-multiselect-template">
                    <div>
                        <v-async-select-handler
                            v-if="field.async"
                            :name="name"
                            multiple="true"
                            :class="hasErrors ? 'border border-red-500' : ''"
                            :track-by="field.track_by"
                            :label-by="field.label_by"
                            :list-route="field.list_route"
                            :query-params="field.query_params"
                            :value="modelValue"
                            :placeholder="field.placeholder || undefined"
                            :disabled="disabled"
                            @input="setValue(parseJson($event) ?? '')"
                        ></v-async-select-handler>

                        <v-multiselect-handler
                            v-else
                            :name="name"
                            :options="JSON.stringify(field.options ?? [])"
                            :class="hasErrors ? 'border border-red-500' : ''"
                            track-by="value"
                            label-by="label"
                            :value="modelValue"
                            :placeholder="field.placeholder || undefined"
                            :disabled="disabled"
                            @input="setValue(parseJson($event) ?? '')"
                        ></v-multiselect-handler>
                    </div>
                </script>

                <script type="module">
                    app.component('v-field-multiselect', {
                        template: '#v-field-multiselect-template',
                        mixins: [window.unopim.fieldBase],
                    });
                </script>
                @break

            @case('date')
                <script type="text/x-template" id="v-field-date-template">
                    <x-admin::flat-picker.date>
                        <input
                            type="date"
                            :id="inputId"
                            :name="name"
                            :value="modelValue"
                            :placeholder="field.placeholder"
                            :disabled="disabled"
                            :class="inputClass"
                            :aria-invalid="hasErrors"
                            autocomplete="off"
                            @change="setValue($event.target.value)"
                        />
                    </x-admin::flat-picker.date>
                </script>

                <script type="module">
                    app.component('v-field-date', {
                        template: '#v-field-date-template',
                        mixins: [window.unopim.fieldBase],
                    });
                </script>
                @break

            @case('datetime')
                <script type="text/x-template" id="v-field-datetime-template">
                    <x-admin::flat-picker.datetime>
                        <input
                            type="datetime-local"
                            :id="inputId"
                            :name="name"
                            :value="modelValue"
                            :placeholder="field.placeholder"
                            :disabled="disabled"
                            :class="inputClass"
                            :aria-invalid="hasErrors"
                            autocomplete="off"
                            @change="setValue($event.target.value)"
                        />
                    </x-admin::flat-picker.datetime>
                </script>

                <script type="module">
                    app.component('v-field-datetime', {
                        template: '#v-field-datetime-template',
                        mixins: [window.unopim.fieldBase],
                    });
                </script>
                @break

            @case('date-range')
            @case('datetime-range')
                @php($html = $type === 'date-range' ? 'date' : 'datetime-local')

                <script type="text/x-template" id="v-field-{{ $type }}-template">
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap gap-1.5" v-if="field.options?.length">
                            <button
                                v-for="option in field.options"
                                :key="option.name"
                                type="button"
                                class="cursor-pointer rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600 hover:bg-violet-100 hover:text-violet-700 dark:bg-cherry-800 dark:text-gray-300"
                                @click="setRange(option.from, option.to)"
                                v-text="option.label"
                            ></button>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($type === 'date-range')
                                <x-admin::flat-picker.date ::allow-input="false">
                                    <input
                                        type="date"
                                        :id="inputId + '-from'"
                                        :name="name + '[from]'"
                                        :value="range.from"
                                        :disabled="disabled"
                                        :class="inputClass"
                                        aria-label="From"
                                        autocomplete="off"
                                        @change="setRange($event.target.value, range.to)"
                                    />
                                </x-admin::flat-picker.date>

                                <span class="text-gray-400">&ndash;</span>

                                <x-admin::flat-picker.date ::allow-input="false">
                                    <input
                                        type="date"
                                        :id="inputId + '-to'"
                                        :name="name + '[to]'"
                                        :value="range.to"
                                        :disabled="disabled"
                                        :class="inputClass"
                                        aria-label="To"
                                        autocomplete="off"
                                        @change="setRange(range.from, $event.target.value)"
                                    />
                                </x-admin::flat-picker.date>
                            @else
                                <x-admin::flat-picker.datetime ::allow-input="false">
                                    <input
                                        type="datetime-local"
                                        :id="inputId + '-from'"
                                        :name="name + '[from]'"
                                        :value="range.from"
                                        :disabled="disabled"
                                        :class="inputClass"
                                        aria-label="From"
                                        autocomplete="off"
                                        @change="setRange($event.target.value, range.to)"
                                    />
                                </x-admin::flat-picker.datetime>

                                <span class="text-gray-400">&ndash;</span>

                                <x-admin::flat-picker.datetime ::allow-input="false">
                                    <input
                                        type="datetime-local"
                                        :id="inputId + '-to'"
                                        :name="name + '[to]'"
                                        :value="range.to"
                                        :disabled="disabled"
                                        :class="inputClass"
                                        aria-label="To"
                                        autocomplete="off"
                                        @change="setRange(range.from, $event.target.value)"
                                    />
                                </x-admin::flat-picker.datetime>
                            @endif
                        </div>
                    </div>
                </script>

                <script type="module">
                    app.component('v-field-{{ $type }}', {
                        template: '#v-field-{{ $type }}-template',

                        mixins: [window.unopim.fieldBase],

                        data() {
                            return {
                                range: {
                                    from: this.modelValue?.from ?? '',
                                    to: this.modelValue?.to ?? '',
                                },
                            };
                        },

                        methods: {
                            setRange(from, to) {
                                this.range = { from: from ?? '', to: to ?? '' };

                                this.setValue({ ...this.range });
                            },
                        },
                    });
                </script>
                @break

            @case('price')
                <script type="text/x-template" id="v-field-price-template">
                    <div class="flex items-center gap-2">
                        <input
                            type="number"
                            step="any"
                            :id="inputId"
                            :name="name + '[amount]'"
                            :value="price.amount"
                            :placeholder="field.placeholder"
                            :disabled="disabled"
                            :class="inputClass"
                            :aria-invalid="hasErrors"
                            autocomplete="off"
                            @change="setPrice($event.target.value, price.currency)"
                        />

                        <select
                            :name="name + '[currency]'"
                            :value="price.currency"
                            :disabled="disabled"
                            class="py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                            aria-label="Currency"
                            @change="setPrice(price.amount, $event.target.value)"
                        >
                            <option
                                v-for="option in (field.options ?? [])"
                                :key="option.value ?? option"
                                :value="option.value ?? option"
                                v-text="option.label ?? option"
                            ></option>
                        </select>
                    </div>
                </script>

                <script type="module">
                    app.component('v-field-price', {
                        template: '#v-field-price-template',

                        mixins: [window.unopim.fieldBase],

                        data() {
                            return {
                                price: {
                                    amount: this.modelValue?.amount ?? '',
                                    currency: this.modelValue?.currency ?? (this.field.options?.[0]?.value ?? ''),
                                },
                            };
                        },

                        methods: {
                            setPrice(amount, currency) {
                                this.price = { amount: amount ?? '', currency: currency ?? '' };

                                this.setValue({ ...this.price });
                            },
                        },
                    });
                </script>
                @break
        @endswitch
    @endPushOnce
@endforeach
