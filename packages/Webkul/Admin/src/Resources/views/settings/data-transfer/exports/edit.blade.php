<x-admin::layouts.with-history>
    <x-slot:entityName>
        job_instance
    </x-slot>
    
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.edit.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.before') !!}

    <v-export-profile-edit></v-export-profile-edit>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-export-profile-edit-template">
            <x-admin::form
                :action="route('admin.settings.data_transfer.exports.update', $export->id)"
                method="PUT"
                enctype="multipart/form-data"
            >
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.edit_form_controls.before') !!}

                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.settings.data-transfer.exports.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a href="{{ route('admin.settings.data_transfer.exports.index') }}" class="transparent-button">
                            @lang('admin::app.settings.data-transfer.exports.edit.back-btn')
                        </a>

                        <button type="submit" class="primary-button">
                            @lang('admin::app.settings.data-transfer.exports.edit.save-btn')
                        </button>
                    </div>
                </div>

                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.edit.general')
                            </p>
                            
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.exports.create.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    :disabled="true"
                                    :value="old('code') ?? $export->code"
                                    rules="required"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="code"
                                    :value="old('code') ?? $export->code"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.exports.edit.type')
                                </x-admin::form.control-group.label>

                                @php
                                    $options = [];
                                    foreach(config('exporters') as $index => $exporter) {
                                        $options[] = ['id' => $index, 'label' => trans($exporter['title'])];
                                    }
                                    $optionsJson = json_encode($options);                            
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="entity_type_disabled"
                                    :disabled="true"
                                    :value="$export->entity_type"
                                    :options="$optionsJson"
                                    track-by="id"
                                    label-by="label"
                                />

                                <input type="hidden" 
                                       name="entity_type" 
                                       v-model="entityType" 
                                       value="{{ old('entity_type', $export->entity_type) }}"
                                       >
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        <x-admin::accordion v-if="selectedFileFormat == 'Csv'">
                            <x-slot:header>
                                <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.settings.data-transfer.exports.edit.settings')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.exports.edit.field-separator')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="field_separator"
                                        rules="required"
                                        :value="old('field_separator') ?? $export->field_separator"
                                    />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.settings.after') !!}

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.filters.befor') !!}

                        <!-- Filters Panel -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.settings.data-transfer.exports.create.filters')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                @php
                                    $filters = $export->filters ?? [];
                                @endphp

                                <x-admin::form.control-group v-if="!isCategory()">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.channels.create.channels')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="multiselect"
                                        name="filters[channel][]"
                                        v-model="filters.channel"
                                        ::options="JSON.stringify(channels)"
                                        track-by="id"
                                        label-by="label"
                                        rules="required"
                                        label="Channel"
                                    />
                                    <x-admin::form.control-group.error control-name="filters[channel][]" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mt-4">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.channels.edit.locales')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="multiselect"
                                        name="filters[locale][]"
                                        v-model="filters.locale"
                                        ::options="JSON.stringify(locales)"
                                        track-by="id"
                                        label-by="label"
                                        rules="required"
                                        label="Locale"
                                    />
                                    <x-admin::form.control-group.error control-name="filters[locale][]" />
                                </x-admin::form.control-group>

                                <x-admin::data-transfer.filter-fields
                                    :entity-type="$export->entity_type"
                                    :values="$filters"
                                    :exporter-config="json_encode($exporterConfig)"
                                >
                                </x-admin::data-transfer.filter-fields>

                                {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.filters.fields.after') !!}
                            </x-slot>
                        </x-admin::accordion>
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.filters.after') !!}
                    </div>
                </div>

                {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.create_form_controls.after') !!}
             </x-admin::form>
        </script>

        <script type="module">
            app.component('v-export-profile-edit', {
                template: '#v-export-profile-edit-template',

                data() {
                    return {
                        entityType: @json($export->entity_type),
                        selectedFileFormat: @json($export->filters['file_format'] ?? 'Csv'),

                        filters: {
                            channel: @json($export->filters['channel'] ?? []),
                            locale: @json($export->filters['locale'] ?? [])
                        },

                        channels: @json($channels).map(c => ({
                            id: c.code,
                            label: c.name
                        })),
                        locales: @json($locales).map(l => ({
                            id: l.code,
                            label: l.name
                        })),
                    };
                },

                mounted() {
                    this.$emitter.on('filter-value-changed', this.handleFilterValues);
                },

                methods: {
                    isCategory() {
                        return this.entityType === 'categories';
                    },

                    handleFilterValues(changed) {
                        if ('file_format' == changed.filterName) {
                            this.selectedFileFormat = changed.value;
                        }
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts.with-history>