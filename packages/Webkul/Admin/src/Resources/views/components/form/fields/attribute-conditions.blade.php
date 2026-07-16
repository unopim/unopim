@pushOnce('scripts')
    <script type="text/x-template" id="v-field-attribute-conditions-template">
        <div class="flex flex-col gap-3">
            <input type="hidden" :name="name" :value="serializedRows" />

            <span class="unsaved-badge" style="display: none" aria-hidden="true"></span>

            <div
                v-for="({ row, control, operatorsJson }, index) in decoratedRows"
                :key="row.uid"
                class="p-3 bg-gray-50 dark:bg-cherry-800 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
                <div v-if="! row.attribute" class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <v-async-select-handler
                            :track-by="'code'"
                            :label-by="'label'"
                            :list-route="attributeRoute"
                            :query-params="attributeQueryParams"
                            :value="row.attribute"
                            :placeholder="selectPlaceholder"
                            @input="setAttribute(index, $event)"
                        >
                        </v-async-select-handler>
                    </div>

                    <button
                        type="button"
                        @click="removeRow(index)"
                        :title="removeTitle"
                        :aria-label="removeTitle"
                        class="icon-delete shrink-0 self-center text-2xl text-gray-500 dark:text-gray-300 cursor-pointer rounded-md hover:bg-gray-100 dark:hover:bg-cherry-800 p-1"
                    >
                    </button>
                </div>
                <div v-else class="grid grid-cols-2 gap-4 items-center max-md:grid-cols-1">
                    <div class="flex items-center gap-3 min-w-0 max-sm:flex-col max-sm:items-stretch">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1 max-sm:w-full">
                            <span class="w-2 h-2 rounded-full bg-primary-600 shrink-0"></span>

                            <div class="min-w-0">
                                <div class="flex items-center min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white truncate" v-text="row.name || row.attribute"></p>

                                    <span
                                        v-if="isRowDirty(row)"
                                        class="unsaved-badge shrink-0"
                                    >@lang('admin::app.components.form.unsaved-changes.field-badge')</span>
                                </div>

                                <p class="text-xs text-gray-400 dark:text-gray-300" v-text="typeLabel(row)"></p>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0 max-sm:w-full">
                            <v-select-handler
                                :key="'op-' + row.uid + '-' + row.type"
                                :options="operatorsJson"
                                track-by="value"
                                label-by="label"
                                :value="row.operator"
                                :placeholder="operatorPlaceholder"
                                @input="setOperator(index, $event)"
                            >
                            </v-select-handler>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex-1 min-w-0">
                            <template v-if="control === 'none'">
                                <p class="py-2.5 text-sm text-gray-400 dark:text-gray-300 italic">
                                    @lang('admin::app.settings.data-transfer.exports.create.no-value-needed')
                                </p>
                            </template>

                            <template v-else-if="control === 'options'">
                                <v-async-select-handler
                                    :key="valueControlKey(row)"
                                    entity-name="attribute"
                                    :attribute-id="String(row.attributeId)"
                                    multiple="true"
                                    :onselect="false"
                                    :track-by="'code'"
                                    :label-by="'label'"
                                    :value="stringValue(row)"
                                    :placeholder="valueText"
                                    @input="setOptionValue(index, $event)"
                                >
                                </v-async-select-handler>
                            </template>

                            <template v-else-if="control === 'boolean'">
                                <v-select-handler
                                    :key="valueControlKey(row)"
                                    :options="booleanOptionsJson"
                                    track-by="value"
                                    label-by="label"
                                    :value="row.value"
                                    :placeholder="valueText"
                                    @input="setBooleanValue(index, $event)"
                                >
                                </v-select-handler>
                            </template>

                            <template v-else-if="control === 'number_range' || control === 'date_range'">
                                <div class="flex items-center gap-2">
                                    <input
                                        :type="control === 'date_range' ? 'date' : 'number'"
                                        :id="inputId + '-' + index + '-from'"
                                        v-model="row.value"
                                        :placeholder="rangeFromPlaceholder"
                                        :aria-label="rangeFromPlaceholder"
                                        class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                                    />

                                    <span class="text-gray-400">&ndash;</span>

                                    <input
                                        :type="control === 'date_range' ? 'date' : 'number'"
                                        :id="inputId + '-' + index + '-to'"
                                        v-model="row.value2"
                                        :placeholder="rangeToPlaceholder"
                                        :aria-label="rangeToPlaceholder"
                                        class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                                    />
                                </div>
                            </template>

                            <template v-else>
                                <input
                                    :type="inputType(control)"
                                    :id="inputId + '-' + index"
                                    v-model="row.value"
                                    :placeholder="valuePlaceholder(control)"
                                    :aria-label="valuePlaceholder(control)"
                                    class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                                />
                            </template>
                        </div>

                        <button
                            type="button"
                            @click="removeRow(index)"
                            :title="removeTitle"
                            :aria-label="removeTitle"
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
                    class="flex items-center justify-center gap-2 w-full py-3 border-2 border-dashed border-primary-300 rounded-lg bg-primary-50 dark:bg-cherry-800 text-primary-700 dark:text-primary-200 font-semibold cursor-pointer transition-all hover:bg-primary-50 hover:border-primary-500"
                >
                    <span class="text-lg leading-none">+</span>
                    @lang('admin::app.settings.data-transfer.exports.create.add-condition')
                </button>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-field-attribute-conditions', {
            template: '#v-field-attribute-conditions-template',

            mixins: [window.unopim.fieldBase],

            data() {
                return {
                    rows: [],
                    sequence: 0,
                    hydrated: false,
                    savedSignatures: {},
                    operatorsMap: @json(\Webkul\DataTransfer\Helpers\Sources\Export\Filters\AttributeConditionOperators::frontendMap()),
                    numberPlaceholder: @json(trans('admin::app.settings.data-transfer.exports.create.value-number-placeholder')),
                    valueText: @json(trans('admin::app.settings.data-transfer.exports.create.custom-attribute-value')),
                    selectPlaceholder: @json(trans('admin::app.settings.data-transfer.exports.create.custom-attribute-select')),
                    removeTitle: @json(trans('admin::app.settings.data-transfer.exports.create.custom-attribute-remove')),
                    operatorPlaceholder: @json(trans('admin::app.settings.data-transfer.exports.create.operator-select')),
                    rangeFromPlaceholder: @json(trans('admin::app.settings.data-transfer.exports.create.range-from')),
                    rangeToPlaceholder: @json(trans('admin::app.settings.data-transfer.exports.create.range-to')),
                    booleanOptionsJson: JSON.stringify([
                        { value: 'true',  label: @json(trans('admin::app.settings.data-transfer.exports.create.condition-yes')) },
                        { value: 'false', label: @json(trans('admin::app.settings.data-transfer.exports.create.condition-no')) },
                    ]),
                };
            },

            computed: {
                decoratedRows() {
                    return this.rows.map(row => {
                        const operators = this.operatorsForType(row.type);
                        const match = operators.find(operator => operator.value === row.operator);

                        return {
                            row,
                            control:      match ? match.control : 'text',
                            operatorsJson: JSON.stringify(operators),
                        };
                    });
                },

                serializedRows() {
                    const rows = this.decoratedRows
                        .filter(({ row, control }) => row.attribute && this.isComplete(row, control))
                        .map(({ row, control }) => {
                            const entry = { attribute: row.attribute, operator: row.operator };

                            if (control !== 'none') {
                                entry.value = row.value;
                            }

                            if (control === 'number_range' || control === 'date_range') {
                                entry.value2 = row.value2;
                            }

                            return entry;
                        });

                    return JSON.stringify(rows);
                },

                attributeRoute() {
                    return this.field.list_route ?? '';
                },

                attributeQueryParams() {
                    return this.field.query_params ?? {};
                },
            },

            watch: {
                rows: {
                    deep: true,
                    handler() {
                        if (! this.hydrated) {
                            return;
                        }

                        this.setValue(this.serializedRows);
                    },
                },
            },

            mounted() {
                this.buildRows();
            },

            methods: {
                decode(value, fallback) {
                    if (value && typeof value === 'object') {
                        return value;
                    }

                    try {
                        let parsed = JSON.parse(value);

                        if (typeof parsed === 'string') {
                            parsed = JSON.parse(parsed);
                        }

                        return parsed ?? fallback;
                    } catch (exception) {
                        return fallback;
                    }
                },

                markHydrated() {
                    this.$nextTick(() => {
                        this.savedSignatures = this.rows.reduce((signatures, row) => {
                            signatures[row.uid] = this.rowSignature(row);

                            return signatures;
                        }, {});

                        this.hydrated = true;
                    });
                },
                rowSignature(row) {
                    return JSON.stringify([row.attribute, row.operator, row.value ?? '', row.value2 ?? '']);
                },

                isRowDirty(row) {
                    if (! this.hydrated) {
                        return false;
                    }

                    return this.savedSignatures[row.uid] !== this.rowSignature(row);
                },

                operatorsForType(type) {
                    return this.operatorsMap[type] ?? this.operatorsMap['text'] ?? [];
                },

                valueControl(row) {
                    const match = this.operatorsForType(row.type).find(operator => operator.value === row.operator);

                    return match ? match.control : 'text';
                },

                inputType(control) {
                    if (control === 'number') {
                        return 'number';
                    }

                    if (control === 'date') {
                        return 'date';
                    }

                    return 'text';
                },

                valuePlaceholder(control) {
                    return control === 'number' ? this.numberPlaceholder : this.valueText;
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

                isComplete(row, control) {
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
                    const saved = this.decode(this.modelValue, []);

                    if (! Array.isArray(saved) || ! saved.length) {
                        this.markHydrated();

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
                        this.markHydrated();

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

                            if (this.control === 'options' && ! Array.isArray(row.value)) {
                                row.value = `${row.value ?? ''}`.split(',').map(code => code.trim()).filter(Boolean);
                            }
                        });
                    }).finally(() => {
                        this.markHydrated();
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
                    const option = this.decode(event, null);

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
                    const option = this.decode(event, null);

                    const row = this.rows[index];
                    const previous = this.valueControl(row);

                    row.operator = option?.value ?? '';

                    if (this.valueControl(row) !== previous) {
                        row.value = '';
                        row.value2 = '';
                    }
                },

                setOptionValue(index, event) {
                    const parsed = this.decode(event, null);

                    this.rows[index].value = Array.isArray(parsed)
                        ? parsed.map(option => option?.code ?? option).filter(Boolean)
                        : [];
                },

                setBooleanValue(index, event) {
                    const option = this.decode(event, null);

                    this.rows[index].value = option?.value ?? '';
                },
            },
        });
    </script>
@endPushOnce
