<v-modal-bulk-edit ref="bulkEditModal"></v-modal-bulk-edit>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-modal-bulk-edit-template"
    >
        <div>
            <transition
                tag="div"
                name="modal-overlay"
                enter-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity z-[10002]"
                    v-show="isOpen"
                ></div>
            </transition>

            <transition
                tag="div"
                name="modal-content"
                enter-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                enter-to-class="opacity-100 translate-y-0 md:scale-100"
                leave-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 md:scale-100"
                leave-to-class="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
            >
                <div
                    class="fixed inset-0 z-[10002] transform transition overflow-y-auto"
                    v-if="isOpen"
                >
                    <div class="flex min-h-full items-end justify-center p-5 sm:items-center sm:p-0">
                        <div class="w-full max-w-[400px] z-[999] absolute left-1/2 top-1/2 rounded-lg bg-white dark:bg-cherry-800 box-shadow max-md:w-[90%] -translate-x-1/2 -translate-y-1/2">
                            <div class="flex justify-between items-center gap-2.5 px-4 py-3 border-b dark:border-cherry-800 text-lg text-gray-800 dark:text-white font-bold">
                                @{{ title }}
                            </div>

                            <div class="px-4 py-3 text-gray-600 dark:text-gray-300 text-left space-y-4">
                                <div>
                                    <label
                                        for="filtered_attributes"
                                        class="block text-sm font-medium mb-1 cursor-pointer "
                                    >
                                        @lang('admin::app.catalog.families.edit.select-variant')
                                    </label>

                                    <v-async-select-handler
                                        name="filtered_attributes"
                                        multiple="true"
                                        v-bind="field"
                                        :onselect="false"
                                        track-by="code"
                                        label-by="name"
                                        :value="selectedValues"
                                        list-route="{{ route('admin.catalog.bulkedit.attributes.fetch-all') }}"
                                        @input="filtersUpdate('filtered_attributes', parseJson($event, true) ?? '')"
                                    />
                                </div>
                                <p v-if="validationError" class="text-red-500 text-sm mt-2">
                                    @{{ validationError }}
                                </p>
                            </div>

                            <div class="flex gap-2.5 justify-end px-4 py-2.5">
                                <button type="button" class="transparent-button" @click="cancel">
                                    @{{ options.btnCancel }}
                                </button>

                                <button
                                    type="button"
                                    class="primary-button"
                                    @click="proceed"
                                >
                                    @{{ options.btnProceed }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </script>

    <script type="module">
        app.component('v-modal-bulk-edit', {
            template: '#v-modal-bulk-edit-template',

            data() {
                return {
                    isOpen: false,
                    title: '',
                    attributes: [],
                    filters: {
                        filtered_attributes: [],
                    },
                    validationError: '',
                    options: {
                        btnCancel: "{{ trans('admin::app.catalog.products.bulk-edit.modal.btn-cancel') }}",
                        btnProceed: "{{ trans('admin::app.catalog.products.bulk-edit.modal.btn-proceed') }}",
                    },
                    agreeCallback: null,
                    query_params: {
                        limit: 50,
                        offset: 0,
                        search: '',
                    },
                    selectedValues: [],
                };
            },

            created() {
                this.registerGlobalEvents();
                this.getOldValue();
            },

            methods: {
                open({
                    title = "{{ trans('admin::app.catalog.products.bulk-edit.modal.title') }}",
                    attributes = [],
                    options = {
                        btnCancel: "{{ trans('admin::app.catalog.products.bulk-edit.modal.btn-cancel') }}",
                        btnProceed: "{{ trans('admin::app.catalog.products.bulk-edit.modal.btn-proceed') }}",
                    },
                    agree = () => {},
                } ) {
                    this.title = title;
                    this.options = options;
                    this.agreeCallback = agree;

                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                },

                cancel() {
                    this.isOpen = false;
                    document.body.style.overflow = 'auto';
                },

                proceed() {
                    const hasAttributes = Array.isArray(this.filters.filtered_attributes) && this.filters.filtered_attributes.length > 0;

                    if (!hasAttributes) {
                        this.validationError = "{{ trans('admin::app.catalog.products.bulk-edit.validation.select-attribute-or-family') }}";
                        return;
                    } else {
                        this.validationError = '';
                    }

                    this.isOpen = false;
                    document.body.style.overflow = 'auto';
                    this.agreeCallback(this.filters);
                },

                filtersUpdate(name, value) {
                    this.filters[name] = value;
                },

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

                registerGlobalEvents() {
                    this.$emitter.on('open-bulk-edit-modal', this.open);
                },

                getOldValue() {
                    const selectedIds = {!! json_encode(session('bulk_edit_attribute_ids', [])) !!};
                    
                    if (selectedIds.length) {
                        axios.get("{{ route('admin.catalog.bulkedit.attributes.fetch-all') }}", {
                            params: {ids: selectedIds}
                        }).then(({ data }) => {
                            this.filters.filtered_attributes = data.options;
                            this.selectedValues = data.options;
                        }).catch((error) => {
                            console.error(error);
                        });
                    }
                }
            },
        });
    </script>
@endPushOnce
