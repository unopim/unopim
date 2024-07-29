<v-datagrid-export {{ $attributes }}>
    <div class="transparent-button ">
        @lang('admin::app.export.export')
    </div>
</v-datagrid-export>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-export-template"
    >
        <div>
            <!-- Modal Component -->
            <x-admin::modal ref="exportModal">
                <!-- Modal Toggle -->
                <x-slot:toggle>
                    <button class="transparent-button">
                        <span class="icon-export  text-2xl text-violet-700"></span>
                        @lang('admin::app.export.export')
                    </button>
                </x-slot>

                <!-- Modal Header -->
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @lang('admin::app.export.download')
                    </p>
                </x-slot>

                @php
                    $supportedType = ['csv', 'xls', 'xlsx'];

                    $options = [];

                    foreach($supportedType as $type) {
                        $options[] = [
                            'id'    => $type,
                            'label' => trans('admin::app.export.' . $type)
                        ];
                    }

                    $optionsInJson = json_encode($options);

                @endphp

                <!-- Modal Content -->
                <x-slot:content>
                    <x-admin::form action="">
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.control
                                type="select"
                                id="format"
                                name="format"
                                :value="old('format')"
                                v-model="format"
                                :options="$optionsInJson"
                                track-by="id"
                                label-by="label" 
                            >
                            </x-admin::form.control-group.control>
                        </x-admin::form.control-group>
                    </x-admin::form>
                </x-slot>

                <!-- Modal Footer -->
                <x-slot:footer>
                    <button
                        type="button"
                        class="primary-button"
                        @click="download"
                    >
                        @lang('admin::app.export.export')
                    </button>
                </x-slot>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid-export', {
            template: '#v-datagrid-export-template',

            props: ['src'],

            data() {
                return {
                    format: 'xls',

                    available: null,

                    applied: null,
                };
            },

            mounted() {
                this.registerEvents();
            },

            watch: {
                format(value) {
                    this.format = this.parseValue(value)?.id ?? this.format;
                }
            },

            methods: {
                registerEvents() {
                    this.$emitter.on('change-datagrid', this.updateProperties);
                },

                updateProperties({available, applied }) {
                    this.available = available;

                    this.applied = applied;
                },

                download() {
                    if (! this.available?.records?.length) {                        
                        this.$emitter.emit('add-flash', { type: 'warning', message: '@lang('admin::app.export.no-records')' });

                        this.$refs.exportModal.toggle();
                    } else {
                        let params = {
                            export: 1,
    
                            format: this.format,
    
                            sort: {},
    
                            filters: {},

                            pagination: {
                                page: this?.applied?.pagination?.page ?? 1,
                                per_page: this?.applied?.pagination?.perPage ?? 10,
                            },
                        };
    
                        if (
                            this.applied.sort.column &&
                            this.applied.sort.order
                        ) {
                            params.sort = this.applied.sort;
                        }
    
                        this.applied.filters.columns.forEach(column => {
                            params.filters[column.index] = column.value;
                        });
    
                        this.$axios
                            .get(this.src, {
                                params,
                                responseType: 'blob',
                            })
                            .then((response) => {
                                const url = window.URL.createObjectURL(new Blob([response.data]));

                                /**
                                 * Link generation.
                                 */
                                const link = document.createElement('a');
                                link.href = url;
                                link.setAttribute('download', `${(Math.random() + 1).toString(36).substring(7)}.${this.format}`);

                                /**
                                 * Adding a link to a document, clicking on the link, and then removing the link.
                                 */
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);

                                this.$refs.exportModal.toggle();
                            });
                    }
                },

                parseValue(value) {
                    try {
                        return value ? JSON.parse(value) : null;
                    } catch (error) {
                        return null;
                    }
                }
            },
        });
    </script>
@endPushOnce
