@props([
    'values'         => [],
    'importerConfig' => '',
    'entityType'     => 'categories'
])

@php
    if (! empty($importerConfig)) {
        $importerConfig = json_decode($importerConfig, true);
    }

    if (is_array($importerConfig)) {
        foreach($importerConfig as $name => $config) {
            if (! isset($config['filters']['fields'])) {
                continue;
            }

            foreach ($config['filters']['fields'] as $key => $filter) {
                if (
                    ($filter['type'] == 'select' || $filter['type'] == 'multiselect')
                    && ($filter['async'] ?? false) == true
                ) {
                    $importerConfig[$name]['filters']['fields'][$key]['list_route'] = route($filter['list_route']);
                }
            }
        }
        $importerConfig = json_encode($importerConfig);
        
    }

@endphp

<v-import-filter-fields
    entity-Type="{{ $entityType }}"
    importers="{{ $importerConfig }}"
    values="{{ json_encode($values) }}"
    old="{{ json_encode(old()) }}"
    {{ $attributes }}
></v-import-filter-fields>

@pushOnce('scripts')
    <script type="text/x-template" id="v-import-filter-fields-template">
        <x-admin::form.control-group v-for="filterField in fields" ::key="filterField.name">
            <x-admin::form.control-group.label
                v-text="filterField.title"
                ::class="filterField.required ? 'required' : ''"
                ::for="filterField.name"
            >
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
                        :checked="'1' == this.getFilterValue(filterField.name)"
                        @change="emitChangeEvent(filterField.name, $event.target.checked ? '1' : '0')"
                    />

                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-900 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-700"></div>
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
                            :queryParams="filterField.query_params"
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
                        :value="this.getFilterValue(filterField.name)"
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
                            :queryParams="filterField.query_params"
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
    </script>

    <script type="module">
        app.component('v-import-filter-fields', {
            template: '#v-import-filter-fields-template',

            props: [
                'entityType',
                'importers',
                'values',
                'old'
            ],
            data() {
                return {
                    importersConfig: this.parseJson(this.importers),
                    entity: this.entityType,
                    fields: this.parseJson(this.importers)[this.entityType]?.filters?.fields ?? [],
                    filterValues: this.parseJson(this.values),
                    oldValues: this.parseJson(this.old),
                };
            },

            mounted() {
                this.$emitter.on('entity-type-changed', this.changeEntityType);
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

                changeEntityType(jsonValue) {
                    this.entity = this.parseJson(jsonValue)?.id;
                    this.fields = this.importersConfig[this.entity]?.filters?.fields ?? [];
                },
            },
        });
    </script>
@endPushOnce
