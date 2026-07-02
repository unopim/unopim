@props([
    'values'            => [],
    'attributeRoute'    => '',
    'excludeAttributes' => [],
    'operators'         => [],
])

<v-attribute-conditions
    values="{{ json_encode($values) }}"
    old="{{ json_encode(old('filters.custom_attributes')) }}"
    attribute-route="{{ $attributeRoute }}"
    exclude-attributes="{{ json_encode($excludeAttributes) }}"
    operators="{{ json_encode($operators) }}"
    {{ $attributes }}
></v-attribute-conditions>

@pushOnce('scripts')
    <script type="text/x-template" id="v-attribute-conditions-template">
        <div class="flex flex-col gap-3">
            <input type="hidden" name="filters[custom_attributes]" :value="serializedRows" />

            <div
                v-for="(row, index) in rows"
                :key="row.uid"
                class="p-3 bg-gray-50 dark:bg-cherry-800 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
                <!-- Pick the attribute (shown until one is chosen) -->
                <div v-if="! row.attribute" class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <v-async-select-handler
                            :track-by="'code'"
                            :label-by="'label'"
                            :list-route="attributeRoute"
                            :query-params="attributeQueryParams"
                            :value="row.attribute"
                            :placeholder="'@lang('admin::app.settings.data-transfer.exports.create.custom-attribute-select')'"
                            @input="setAttribute(index, $event)"
                        >
                        </v-async-select-handler>
                    </div>

                    <button
                        type="button"
                        @click="removeRow(index)"
                        :title="'@lang('admin::app.settings.data-transfer.exports.create.custom-attribute-remove')'"
                        class="icon-delete shrink-0 self-center text-2xl text-gray-500 dark:text-gray-300 cursor-pointer rounded-md hover:bg-gray-100 dark:hover:bg-cherry-800 p-1"
                    >
                    </button>
                </div>

                <!-- Configured condition -->
                <div
                    v-else
                    class="grid grid-cols-2 gap-4 items-center max-md:grid-cols-1"
                >
                    <!-- Left 50%: Attribute + condition -->
                    <div class="flex items-center gap-3 min-w-0 max-sm:flex-col max-sm:items-stretch">
                        <!-- Attribute label (name + type) -->
                        <div class="flex items-center gap-2.5 min-w-0 flex-1 max-sm:w-full">
                            <span class="w-2 h-2 rounded-full bg-violet-600 shrink-0"></span>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white truncate" v-text="row.name || row.attribute"></p>
                                <p class="text-xs text-gray-400 dark:text-gray-300" v-text="typeLabel(row)"></p>
                            </div>
                        </div>

                        <!-- Condition -->
                        <div class="flex-1 min-w-0 max-sm:w-full">
                        <v-select-handler
                            :key="'op-' + row.uid + '-' + row.type"
                            :options="operatorOptionsJson(row)"
                            track-by="value"
                            label-by="label"
                            :value="row.operator"
                            :placeholder="'@lang('admin::app.settings.data-transfer.exports.create.operator-select')'"
                            @input="setOperator(index, $event)"
                        >
                        </v-select-handler>
                        </div>
                    </div>

                    <!-- Right 50%: Value + delete -->
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Value (switches with attribute type + operator) -->
                        <div class="flex-1 min-w-0">
                        <template v-if="valueControl(row) === 'none'">
                            <p class="py-2.5 text-sm text-gray-400 dark:text-gray-300 italic">
                                @lang('admin::app.settings.data-transfer.exports.create.no-value-needed')
                            </p>
                        </template>

                        <template v-else-if="valueControl(row) === 'options'">
                            <v-async-select-handler
                                :key="valueControlKey(row)"
                                entity-name="attribute"
                                :attribute-id="String(row.attributeId)"
                                multiple="true"
                                :onselect="false"
                                :track-by="'code'"
                                :label-by="'label'"
                                :value="stringValue(row)"
                                :placeholder="'@lang('admin::app.settings.data-transfer.exports.create.custom-attribute-value')'"
                                @input="setOptionValue(index, $event)"
                            >
                            </v-async-select-handler>
                        </template>

                        <template v-else-if="valueControl(row) === 'boolean'">
                            <v-select-handler
                                :key="valueControlKey(row)"
                                :options="booleanOptionsJson"
                                track-by="value"
                                label-by="label"
                                :value="row.value"
                                :placeholder="'@lang('admin::app.settings.data-transfer.exports.create.custom-attribute-value')'"
                                @input="setBooleanValue(index, $event)"
                            >
                            </v-select-handler>
                        </template>

                        <template v-else-if="valueControl(row) === 'number_range' || valueControl(row) === 'date_range'">
                            <div class="flex items-center gap-2">
                                <input
                                    :type="valueControl(row) === 'date_range' ? 'date' : 'number'"
                                    v-model="row.value"
                                    :placeholder="'@lang('admin::app.settings.data-transfer.exports.create.range-from')'"
                                    class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                                />
                                <span class="text-gray-400">&ndash;</span>
                                <input
                                    :type="valueControl(row) === 'date_range' ? 'date' : 'number'"
                                    v-model="row.value2"
                                    :placeholder="'@lang('admin::app.settings.data-transfer.exports.create.range-to')'"
                                    class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                                />
                            </div>
                        </template>

                        <template v-else>
                            <input
                                :type="inputType(row)"
                                v-model="row.value"
                                :placeholder="valuePlaceholder(row)"
                                class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                            />
                        </template>
                    </div>

                    <!-- Delete -->
                    <button
                        type="button"
                        @click="removeRow(index)"
                        :title="'@lang('admin::app.settings.data-transfer.exports.create.custom-attribute-remove')'"
                        class="icon-delete shrink-0 self-center text-2xl text-gray-400 dark:text-gray-300 cursor-pointer rounded-md hover:bg-gray-100 hover:text-red-500 dark:hover:bg-cherry-700 p-1"
                    >
                    </button>
                    </div>
                </div>
            </div>

            <div>
                <button
                    type="button"
                    @click="addRow"
                    class="flex items-center justify-center gap-2 w-full py-3 border-2 border-dashed border-violet-300 rounded-lg bg-violet-50 dark:bg-cherry-800 text-violet-700 dark:text-violet-200 font-semibold cursor-pointer transition-all hover:bg-violet-50 hover:border-violet-500"
                >
                    <span class="text-lg leading-none">+</span>
                    @lang('admin::app.settings.data-transfer.exports.create.add-condition')
                </button>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-attribute-conditions', {
            template: '#v-attribute-conditions-template',

            props: ['values', 'old', 'attributeRoute', 'excludeAttributes', 'operators'],

            data() {
                return {
                    rows: [],
                    sequence: 0,
                    operatorsMap: this.parseJson(this.operators, {}),
                    numberPlaceholder: "@lang('admin::app.settings.data-transfer.exports.create.value-number-placeholder')",
                    valueText: "@lang('admin::app.settings.data-transfer.exports.create.custom-attribute-value')",
                    booleanOptionsJson: JSON.stringify([
                        { value: 'true',  label: "@lang('admin::app.settings.data-transfer.exports.create.condition-yes')" },
                        { value: 'false', label: "@lang('admin::app.settings.data-transfer.exports.create.condition-no')" },
                    ]),
                };
            },

            computed: {
                serializedRows() {
                    const rows = this.rows
                        .filter(row => row.attribute && this.isComplete(row))
                        .map(row => {
                            const entry = { attribute: row.attribute, operator: row.operator };

                            if (this.valueControl(row) !== 'none') {
                                entry.value = row.value;
                            }

                            if (this.valueControl(row) === 'number_range' || this.valueControl(row) === 'date_range') {
                                entry.value2 = row.value2;
                            }

                            return entry;
                        });

                    return JSON.stringify(rows);
                },

                attributeQueryParams() {
                    const exclude = this.parseJson(this.excludeAttributes, []);

                    return Array.isArray(exclude) && exclude.length ? { exclude } : {};
                },
            },

            mounted() {
                this.buildRows();
            },

            methods: {
                parseJson(value, fallback) {
                    try {
                        let parsed = JSON.parse(value);

                        if (typeof parsed === 'string') {
                            parsed = JSON.parse(parsed);
                        }

                        return parsed ?? fallback;
                    } catch (e) {
                        return fallback;
                    }
                },

                operatorsForType(type) {
                    return this.operatorsMap[type] ?? this.operatorsMap['text'] ?? [];
                },

                operatorOptionsJson(row) {
                    return JSON.stringify(this.operatorsForType(row.type));
                },

                valueControl(row) {
                    const match = this.operatorsForType(row.type).find(operator => operator.value === row.operator);

                    return match ? match.control : 'text';
                },

                inputType(row) {
                    const control = this.valueControl(row);

                    if (control === 'number') {
                        return 'number';
                    }

                    if (control === 'date') {
                        return 'date';
                    }

                    return 'text';
                },

                valuePlaceholder(row) {
                    return this.valueControl(row) === 'number' ? this.numberPlaceholder : this.valueText;
                },

                typeLabel(row) {
                    if (! row.type) {
                        return '';
                    }

                    return row.type.charAt(0).toUpperCase() + row.type.slice(1);
                },

                valueControlKey(row) {
                    return `value-${row.uid}-${row.attributeId}-${row.operator}`;
                },

                stringValue(row) {
                    return Array.isArray(row.value) ? row.value.join(',') : `${row.value ?? ''}`;
                },

                isComplete(row) {
                    const control = this.valueControl(row);

                    if (control === 'none') {
                        return true;
                    }

                    if (control === 'number_range' || control === 'date_range') {
                        return this.hasValue(row.value) && this.hasValue(row.value2);
                    }

                    return this.hasValue(row.value);
                },

                hasValue(value) {
                    return Array.isArray(value) ? value.length > 0 : `${value ?? ''}`.length > 0;
                },

                buildRows() {
                    const saved = this.parseJson(this.old, null) ?? this.parseJson(this.values, []);

                    if (! Array.isArray(saved) || ! saved.length) {
                        return;
                    }

                    this.rows = saved.map((row, index) => ({
                        uid: index,
                        attribute: row.attribute ?? '',
                        attributeId: null,
                        name: row.attribute ?? '',
                        type: 'text',
                        operator: row.operator ?? 'in',
                        value: row.value ?? '',
                        value2: row.value2 ?? '',
                    }));

                    this.hydrateTypes();
                },

                hydrateTypes() {
                    const codes = this.rows.map(row => row.attribute).filter(Boolean);

                    if (! codes.length) {
                        return;
                    }

                    this.$axios.get(this.attributeRoute, {
                        params: { identifiers: { columnName: 'code', values: codes } },
                    }).then(result => {
                        const meta = {};
                        (result.data.options || []).forEach(option => { meta[option.code] = option; });

                        this.rows.forEach(row => {
                            const option = meta[row.attribute];

                            if (! option) {
                                return;
                            }

                            row.attributeId = option.id;
                            row.type = option.type;
                            row.name = option.label || row.attribute;

                            if (this.valueControl(row) === 'options' && ! Array.isArray(row.value)) {
                                row.value = `${row.value ?? ''}`.split(',').map(code => code.trim()).filter(Boolean);
                            }
                        });
                    });
                },

                addRow() {
                    this.rows.push({
                        uid: `new-${this.sequence++}`,
                        attribute: '',
                        attributeId: null,
                        name: '',
                        type: 'text',
                        operator: '',
                        value: '',
                        value2: '',
                    });
                },

                removeRow(index) {
                    this.rows.splice(index, 1);
                },

                setAttribute(index, event) {
                    let option = null;

                    try {
                        option = JSON.parse(event);
                    } catch (e) {
                        option = null;
                    }

                    const row = this.rows[index];
                    const code = option?.code ?? '';
                    const changed = code !== row.attribute;

                    row.attribute = code;
                    row.attributeId = option?.id ?? null;
                    row.type = option?.type ?? 'text';
                    row.name = option?.label ?? code;

                    if (changed) {
                        const operators = this.operatorsForType(row.type);
                        row.operator = operators.length ? operators[0].value : '';
                        row.value = '';
                        row.value2 = '';
                    }
                },

                setOperator(index, event) {
                    let option = null;

                    try {
                        option = JSON.parse(event);
                    } catch (e) {
                        option = null;
                    }

                    const row = this.rows[index];
                    const previous = this.valueControl(row);

                    row.operator = option?.value ?? '';

                    if (this.valueControl(row) !== previous) {
                        row.value = '';
                        row.value2 = '';
                    }
                },

                setOptionValue(index, event) {
                    let parsed = event;

                    if (typeof event === 'string') {
                        try {
                            parsed = JSON.parse(event);
                        } catch (e) {
                            parsed = null;
                        }
                    }

                    this.rows[index].value = Array.isArray(parsed)
                        ? parsed.map(option => option?.code ?? option).filter(Boolean)
                        : [];
                },

                setBooleanValue(index, event) {
                    let option = null;

                    try {
                        option = JSON.parse(event);
                    } catch (e) {
                        option = null;
                    }

                    this.rows[index].value = option?.value ?? '';
                },
            },
        });
    </script>
@endPushOnce
