@props([
    'values'         => [],
    'exporterConfig' => '',
    'entityType'     => 'categories',
    'only'           => '',
    'except'         => '',
    'gridClass'      => '',
])

@php
    if (! empty($exporterConfig)) {
        $exporterConfig = json_decode($exporterConfig, true);
    }

    if (is_array($exporterConfig)) {
        foreach($exporterConfig as $name => $config) {
            if (! isset($config['filters']['fields'])) {
                continue;
            }

            foreach ($config['filters']['fields'] as $key => $filter) {
                $exporterConfig[$name]['filters']['fields'][$key]['title'] = trans($filter['title']);

                if (! empty($filter['info'])) {
                    $exporterConfig[$name]['filters']['fields'][$key]['info'] = trans($filter['info']);
                }

                if ($filter['type'] == 'select' || $filter['type'] == 'multiselect') {
                    if (($filter['async'] ?? false) == true && ! empty($filter['list_route'])) {
                        $exporterConfig[$name]['filters']['fields'][$key]['list_route'] = route($filter['list_route']);

                        continue;
                    }

                    if (! isset($filter['options'])) {
                        continue;
                    }

                    foreach ($filter['options'] as &$filterOption) {
                        $filterOption['label'] = trans($filterOption['label']);
                    }

                    $exporterConfig[$name]['filters']['fields'][$key]['options'] = $filter['options'];
                }
            }
        }

        $exporterConfig = json_encode($exporterConfig);
    }
@endphp

<x-admin::data-transfer.tags-input />

<v-filter-fields
    entityType="{{ $entityType }}"
    exporters="{{ $exporterConfig }}"
    values="{{ json_encode($values) }}"
    old="{{ json_encode(old()) }}"
    only="{{ $only }}"
    except="{{ $except }}"
    grid-class="{{ $gridClass }}"
    {{ $attributes }}
></v-filter-fields>

@pushOnce('scripts')
    <script type="text/x-template" id="v-filter-fields-template">
      <div :class="gridClass">
        <x-admin::form.control-group
            v-for="filterField in visibleFields"
            ::key="fieldKey(filterField)"
            ::class="filterField.full_width ? 'col-span-2' : ''"
        >
            <x-admin::form.control-group.label
                ::class="filterField.required ? 'required' : ''"
                ::for="filterField.name"
            >
                <span v-text="filterField.title"></span>

                <span
                    v-if="filterField.info"
                    class="icon-information text-base text-gray-500 dark:text-gray-300 cursor-pointer"
                    :title="filterField.info"
                >
                </span>
            </x-admin::form.control-group.label>

            <template v-if="filterField?.type == 'boolean'">
                <input type="hidden" :name="'filters[' + filterField.name + ']'" value="0" />

                <label class="relative inline-flex items-center cursor-pointer">
                    <input  
                        type="checkbox"
                        :name="'filters[' + filterField.name + ']'"
                        value="1"
                        :id="filterField.name"
                        class="sr-only peer"
                        :checked="'1' == (this.getFilterValue(filterField.name) ?? filterField.default)"
                        @change="emitChangeEvent(filterField.name, $event.target.checked ? '1' : '0')"
                    />

                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-900 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-700"></div>
                </label>
            </template>

            <template v-else-if="filterField.type == 'select'">
                    <v-field
                        v-slot="{ field, errors }"
                        :rules="filterField.validation"
                        :name="'filters[' + filterField.name + ']'"
                        :label="filterField.title"
                        :value="this.getFilterValue(filterField.name)"
                    >
                    <template v-if="filterField?.async">
                        <v-async-select-handler
                            :name="'filters[' + filterField.name + ']'"
                            v-bind="field"
                            :class="[errors.length ? 'border border-red-500' : JSON.stringify(errors)]"
                            :track-by="filterField.track_by"
                            :label-by="filterField.label_by"
                            :list-route="filterField.list_route"
                            :queryParams="fieldQueryParams(filterField)"
                            @input="emitChangeEvent(filterField.name, this.parseJson($event, true)[filterField.track_by] ?? '')"
                        >
                        </v-async-select-handler>
                    </template>
                    <template v-else>
                        <v-select-handler
                            :name="'filters[' + filterField.name + ']'"
                            v-bind="field"
                            :options="JSON.stringify(filterField.options)"
                            :class="[errors.length ? 'border border-red-500' : JSON.stringify(errors)]"
                            track-by="value"
                            label-by="label"
                            @input="emitChangeEvent(filterField.name, this.parseJson($event, true)?.value ?? '')"
                        >
                        </v-select-handler>
                    </template>
                </v-field>
            </template>

            <template v-else-if="filterField.type == 'multiselect'">
                    <v-field
                        v-slot="{ field, errors }"
                        :rules="filterField.validation"
                        :name="'filters[' + filterField.name + ']'"
                        :label="filterField.title"
                        :value="resolveFieldValue(filterField)"
                    >
                    <template v-if="filterField?.async">
                        <v-async-select-handler
                            :name="'filters[' + filterField.name + ']'"
                            multiple='true'
                            v-bind="field"
                            :class="[errors.length ? 'border border-red-500' : JSON.stringify(errors)]"
                            :track-by="filterField.track_by"
                            :label-by="filterField.label_by"
                            :list-route="filterField.list_route"
                            :queryParams="fieldQueryParams(filterField)"
                            @input="emitChangeEvent(filterField.name, this.parseJson($event, true) ?? '')"
                        >
                        </v-async-select-handler>
                    </template>
                    <template v-else>
                        <v-multiselect-handler
                            :name="'filters[' + filterField.name + ']'"
                            v-bind="field"
                            :options="JSON.stringify(filterField.options)"
                            :class="[errors.length ? 'border border-red-500' : JSON.stringify(errors)]"
                            track-by="value"
                            label-by="label"
                            @input="emitChangeEvent(filterField.name, this.parseJson($event, true)?.value ?? '')"
                        >
                        </v-multiselect-handler>
                    </template>
                </v-field>
            </template>

            <template v-else-if="filterField.type == 'date'">
                <v-field
                    v-slot="{ field, errors }"
                    :rules="filterField.validation"
                    :name="'filters[' + filterField.name + ']'"
                    :label="filterField.title"
                    :value="this.getFilterValue(filterField.name)"
                >
                    <x-admin::flat-picker.date>
                        <input
                            :type="filterField.type"
                            :name="filterField.name"
                            v-bind="field"
                            :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                            autocomplete="off"
                            @change="emitChangeEvent(filterField.name, $event.target.value)"
                        />
                    </x-admin::flat-picker.date>
                </v-field>
            </template>

            <template v-else-if="filterField.type == 'datetime'">
                <v-field
                    v-slot="{ field, errors }"
                    :rules="filterField.validation"
                    :name="'filters[' + filterField.name + ']'"
                    :label="filterField.title"
                    :value="this.getFilterValue(filterField.name)"
                >
                    <x-admin::flat-picker.datetime>
                        <input
                            :type="filterField.type"
                            :name="filterField.name"
                            v-bind="field"
                            :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                            autocomplete="off"
                            @change="emitChangeEvent(filterField.name, $event.target.value)"
                        />
                    </x-admin::flat-picker.datetime>
                </v-field>
            </template>

            <template v-else-if="filterField.type == 'textarea'">
                <v-field
                    v-slot="{ field, errors }"
                    :rules="filterField.validation"
                    :name="'filters[' + filterField.name + ']'"
                    :label="filterField.title"
                    :value="this.getFilterValue(filterField.name)"
                >
                    <textarea
                        :type="filterField.type"
                        :name="filterField.name"
                        v-bind="field"
                        :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                        class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                        @change="emitChangeEvent(filterField.name, $event.target.value)"
                    >
                    </textarea>
                </v-field>
            </template>

            <template v-else-if="filterField.type == 'tags'">
                <v-data-transfer-tags-input
                    :name="'filters[' + filterField.name + ']'"
                    :value="this.getFilterValue(filterField.name)"
                    :placeholder="filterField.title"
                >
                </v-data-transfer-tags-input>
            </template>

            <template v-else>
                <v-field
                    v-slot="{ field, errors }"
                    :rules="filterField.validation"
                    :name="'filters[' + filterField.name + ']'"
                    :label="filterField.title"
                    :value="this.getFilterValue(filterField.name)"
                >
                    <input
                        :type="filterField.type"
                        :name="filterField.name"
                        v-bind="field"
                        :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                        class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                        @change="emitChangeEvent(filterField.name, $event.target.value)"
                    />
                </v-field>
            </template>

            <v-error-message
                :name="'filters[' + filterField.name + ']'"
                v-slot="{ message }"
            >
                <p
                    class="mt-1 text-red-600 text-xs italic"
                    v-text="message"
                >
                </p>
            </v-error-message>
        </x-admin::form.control-group>
      </div>
    </script>

    <script type="module">
        const CHANNEL_FIELD = 'channels';
        const SCOPE_CHILD_FIELDS = ['locales', 'currencies'];

        app.component('v-filter-fields', {
            template: '#v-filter-fields-template',

            props: [
                'entityType',
                'exporters',
                'values',
                'old',
                'only',
                'except',
                'gridClass'
            ],
            data() {
                const entity = this.resolveEntity(this.entityType);
                const exportersConfig = this.parseJson(this.exporters);
                const filterValues = this.parseJson(this.values);
                const oldValues = this.parseJson(this.old);

                const channelValue = (oldValues?.filters ? oldValues.filters['channels'] ?? null : null) ?? filterValues['channels'];

                return {
                    exportersConfig: exportersConfig,
                    entity: entity,
                    fields: this.applyFieldScope(exportersConfig[entity]?.filters?.fields ?? []),
                    filterValues: filterValues,
                    oldValues: oldValues,
                    selectedChannelCodes: this.extractCodes(channelValue),
                    liveScopeValues: {},
                    currentValues: {},
                };
            },

            mounted() {
                this.$emitter.on('entity-type-changed', this.changeEntityType);
                this.$emitter.on('filter-value-changed', this.handleScopeChange);
            },

            computed: {
                visibleFields() {
                    return this.fields.filter(field => this.isVisible(field));
                },
            },

            methods: {
                parseJson(value, silent = false) {
                    try {
                        return JSON.parse(value);
                    } catch (e) {
                        if (! silent) {
                            console.error(e);
                        }

                        return value;
                    }
                },

                emitChangeEvent(fieldName, value) {
                    this.$emitter.emit('filter-value-changed', { filterName: fieldName, value: value });
                },

                getFilterValue(fieldName) {
                    return (this.oldValues?.filters ? this.oldValues.filters[fieldName] ?? null : null) ?? this.filterValues[fieldName];
                },
                resolveEntity(value) {
                    const parsed = this.parseJson(value, true);

                    if (parsed && typeof parsed === 'object' && parsed.id) {
                        return parsed.id;
                    }

                    return value;
                },
                applyFieldScope(fields) {
                    const toList = (value) => (value ? value.split(',').map(item => item.trim()).filter(Boolean) : []);

                    const only = toList(this.only);
                    const except = toList(this.except);

                    if (only.length) {
                        fields = fields.filter(field => only.includes(field.name));
                    }

                    if (except.length) {
                        fields = fields.filter(field => ! except.includes(field.name));
                    }

                    return fields;
                },
                changeEntityType(jsonValue) {
                    this.entity = this.resolveEntity(jsonValue);

                    this.fields = this.applyFieldScope(this.exportersConfig[this.entity]?.filters?.fields ?? []);
                },

                isScopeChild(name) {
                    return SCOPE_CHILD_FIELDS.includes(name);
                },

                extractCodes(value) {
                    if (! value) {
                        return [];
                    }

                    let parsed = value;

                    if (typeof value === 'string') {
                        parsed = this.parseJson(value, true);

                        if (typeof parsed === 'string') {
                            parsed = value.split(',');
                        }
                    }

                    if (! Array.isArray(parsed)) {
                        parsed = [parsed];
                    }

                    return parsed
                        .map(item => (item && typeof item === 'object') ? item.code : `${item ?? ''}`.trim())
                        .filter(Boolean);
                },

                handleScopeChange(changed) {
                    this.currentValues[changed.filterName] = changed.value;

                    if (changed.filterName === CHANNEL_FIELD) {
                        this.selectedChannelCodes = this.extractCodes(changed.value);

                        return;
                    }

                    if (this.isScopeChild(changed.filterName)) {
                        this.liveScopeValues[changed.filterName] = this.extractCodes(changed.value).join(',');
                    }
                },

                isVisible(filterField) {
                    const rule = filterField.visible_when;

                    if (! rule) {
                        return true;
                    }

                    const value = this.currentValues[rule.field] ?? this.getFilterValue(rule.field);

                    return rule.values.includes(value);
                },

                fieldKey(filterField) {
                    if (this.isScopeChild(filterField.name)) {
                        return `${filterField.name}::${this.selectedChannelCodes.join(',')}`;
                    }

                    return filterField.name;
                },

                fieldQueryParams(filterField) {
                    if (this.isScopeChild(filterField.name)) {
                        return { [CHANNEL_FIELD]: this.selectedChannelCodes };
                    }

                    return filterField.query_params;
                },

                resolveFieldValue(filterField) {
                    if (! this.isScopeChild(filterField.name)) {
                        return this.getFilterValue(filterField.name);
                    }

                    if (this.liveScopeValues[filterField.name] !== undefined) {
                        return this.liveScopeValues[filterField.name];
                    }

                    return this.extractCodes(this.getFilterValue(filterField.name)).join(',');
                },
            },
        });
    </script>
@endPushOnce
