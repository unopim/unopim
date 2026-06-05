@pushOnce('scripts')
    {{--
        Spreadsheet cell editor for `measurement` attributes in the product Bulk
        Edit grid. Mirrors the two-field (value + unit) UI used on the product edit
        page and the datagrid filter, so measurement attributes are editable in
        bulk just like everywhere else.

        It emits the raw `{ value, unit }` shape; the Measurement ProductObserver
        converts it into the stored `{ unit, amount, family, base_data, base_unit }`
        structure when the BulkProductUpdate job saves each product.
    --}}
    <script type="text/x-template" id="v-spreadsheet-measurement-template">
        <div ref="wrapper" class="flex w-full h-full items-stretch">
            <!-- Value Field -->
            <input
                ref="input"
                type="text"
                :name="`${entityId}_${column.code}_value`"
                v-model="amount"
                class="w-1/2 min-w-0 h-full px-1 py-1 text-sm bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none border-r border-gray-200 dark:border-cherry-700"
                @input="onAmountInput"
                @blur="commit"
            />

            <!-- Unit Field -->
            <div ref="unitTrigger" class="relative w-1/2 flex items-center">
                <input
                    ref="unitInput"
                    type="text"
                    readonly
                    :value="unitLabel"
                    :placeholder="''"
                    class="w-full min-w-0 h-full px-1 py-1 text-sm bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none cursor-pointer"
                    @focus="openDropdown"
                    @click="openDropdown"
                />

                <span
                    class="flex-shrink-0 px-0.5 cursor-pointer text-gray-400 hover:text-violet-600"
                    @mousedown.prevent="openDropdown"
                >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </span>

                <div
                    v-if="open"
                    class="absolute left-0 top-full z-20 bg-white dark:bg-cherry-900 border border-gray-300 dark:border-gray-600 rounded shadow-lg max-h-[120px] w-full overflow-auto"
                    @scroll.passive="onScroll"
                >
                    <div
                        v-if="options.length === 0"
                        class="px-2 py-1 text-gray-500 dark:text-gray-300"
                    >
                        @lang('admin::app.catalog.products.bulk-edit.no-option')
                    </div>

                    <div
                        v-for="option in options"
                        :key="option.code"
                        class="flex items-center justify-between px-2 py-1 hover:bg-gray-100 dark:hover:bg-cherry-700 cursor-pointer"
                        :class="{ 'font-semibold text-violet-700': selectedUnit === option.code }"
                        :title="option.label"
                        @mousedown.prevent="selectUnit(option)"
                    >
                        <span class="truncate w-full dark:text-white text-gray-600">
                            @{{ option.label ? option.label : option.code }}
                        </span>

                        <button
                            v-if="selectedUnit === option.code"
                            class="ml-2 text-red-500 hover:text-red-700"
                            @mousedown.stop.prevent="clearUnit"
                        >
                            &times;
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-spreadsheet-measurement', {
            template: '#v-spreadsheet-measurement-template',

            props: {
                isActive: {
                    type: Boolean,
                    default: false,
                },

                modelValue: {
                    default: null,
                },

                entityId: {
                    type: Number,
                },

                column: {
                    type: Object,
                },

                attribute: {
                    type: Object,
                },
            },

            data() {
                return {
                    amount: '',
                    selectedUnit: null,
                    selectedUnitLabel: '',
                    open: false,
                    loading: false,
                    options: [],
                    page: 1,
                    hasMore: true,
                    unitsRoute: "{{ route('admin.measurement.attribute.units') }}",
                };
            },

            computed: {
                unitLabel() {
                    return this.selectedUnitLabel || this.selectedUnit || '';
                },
            },

            watch: {
                modelValue(newVal) {
                    this.hydrate(newVal);
                },
            },

            mounted() {
                this.hydrate(this.modelValue);

                document.addEventListener('mousedown', this.handleClickOutside);
            },

            beforeUnmount() {
                document.removeEventListener('mousedown', this.handleClickOutside);
            },

            methods: {
                focus() {
                    this.$refs.input?.focus();
                },

                hydrate(value) {
                    if (value && typeof value === 'object') {
                        this.amount = value.amount ?? value.value ?? '';
                        this.selectedUnit = value.unit ?? null;
                    } else {
                        this.amount = '';
                        this.selectedUnit = null;
                    }

                    this.selectedUnitLabel = '';

                    if (this.selectedUnit) {
                        this.resolveUnitLabel();
                    }
                },

                onAmountInput() {
                    this.amount = String(this.amount ?? '')
                        .replace(/[^0-9.]/g, '')
                        .replace(/(\..*?)\..*/g, '$1');
                },

                queryParams() {
                    return {
                        attribute_id: this.attribute?.id,
                        page: this.page,
                    };
                },

                loadOptions(reset = false) {
                    if (! this.attribute?.id) {
                        return;
                    }

                    if (this.loading || (! this.hasMore && ! reset)) {
                        return;
                    }

                    if (reset) {
                        this.page = 1;
                        this.options = [];
                        this.hasMore = true;
                    }

                    this.loading = true;

                    this.$axios
                        .get(this.unitsRoute, { params: this.queryParams() })
                        .then((response) => {
                            const data = response.data;

                            if (! data.options || data.options.length === 0) {
                                this.hasMore = false;
                            } else {
                                this.options.push(...data.options);
                                this.page++;

                                if (this.page > (data.lastPage ?? 1)) {
                                    this.hasMore = false;
                                }

                                this.resolveUnitLabel();
                            }
                        })
                        .catch((err) => {
                            console.error('Error fetching measurement units:', err);
                            this.hasMore = false;
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                resolveUnitLabel() {
                    if (! this.selectedUnit) {
                        return;
                    }

                    const matched = this.options.find((option) => option.code === this.selectedUnit);

                    if (matched) {
                        this.selectedUnitLabel = matched.label || matched.code;
                    }
                },

                openDropdown() {
                    this.open = true;

                    if (! this.options.length) {
                        this.loadOptions(true);
                    }
                },

                onScroll(e) {
                    const el = e.target;

                    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
                        this.loadOptions();
                    }
                },

                selectUnit(option) {
                    this.selectedUnit = option.code;
                    this.selectedUnitLabel = option.label || option.code;
                    this.open = false;

                    this.commit();
                },

                clearUnit() {
                    this.selectedUnit = null;
                    this.selectedUnitLabel = '';
                    this.open = false;

                    this.commit();
                },

                commit() {
                    const amount = String(this.amount ?? '').trim();

                    const payload = (amount === '' && ! this.selectedUnit)
                        ? ''
                        : { value: amount, unit: this.selectedUnit };

                    this.$emit('update:modelValue', payload);

                    this.$emitter.emit('update-spreadsheet-data', {
                        value: payload,
                        entityId: this.entityId,
                        column: this.column,
                    });
                },

                handleClickOutside(event) {
                    if (this.$refs.unitTrigger && ! this.$refs.unitTrigger.contains(event.target)) {
                        this.open = false;
                    }
                },
            },
        });
    </script>
@endPushOnce
