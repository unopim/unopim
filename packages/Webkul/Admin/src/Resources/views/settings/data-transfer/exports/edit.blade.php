<x-admin::layouts.with-history>
    <x-slot:entityName>
        job_instance
    </x-slot>
    
    <!-- Page Title -->
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

                <!-- Page Header -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.settings.data-transfer.exports.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Cancel Button -->
                        <a
                            href="{{ route('admin.settings.data_transfer.exports.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.settings.data-transfer.exports.edit.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.settings.data-transfer.exports.edit.save-btn')
                        </button>
                    </div>
                </div>

                <!-- Body Content -->
                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <!-- Left Container -->
                    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.general.before') !!}

                        <!-- Setup Import Panel -->
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.data-transfer.exports.edit.general')
                            </p>
                            <!-- Code -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.exports.create.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    :disabled="(boolean) $export->code"
                                    :value="old('code') ?? $export->code"
                                    rules="required"
                                    :label="trans('admin::app.settings.data-transfer.exports.create.code')"
                                    :placeholder="trans('admin::app.settings.data-transfer.exports.create.code')"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="code"
                                    :value="old('code') ?? $export->code"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <!-- Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.data-transfer.exports.edit.type')
                                </x-admin::form.control-group.label>

                                @php
                                    $options = [];
                                    foreach(config('exporters') as $index => $exporter) {
                                            $options[] = [
                                                'id'    => $index,
                                                'label' => trans($exporter['title'])
                                            ];
                                        }

                                    $optionsJson = json_encode($options);                            
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="entity_type"
                                    id="export-type"
                                    :disabled="(boolean) $export->entity_type"
                                    :value="old('entity_type') ?? $export->entity_type"
                                    ref="exportType"
                                    rules="required"
                                    :label="trans('admin::app.settings.data-transfer.exports.edit.type')"
                                    :options="$optionsJson"
                                    track-by="id"
                                    label-by="label"
                                >
                                    
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>
                        </div>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.general.after') !!}
                    </div>

                    <!-- Right Container -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.settings.before') !!}

                        <!-- Settings Panel -->
                        <x-admin::accordion v-if="selectedFileFormat == 'Csv'">
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.settings.data-transfer.exports.edit.settings')
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot:content>
                                <!-- CSV Field Separator -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.data-transfer.exports.edit.field-separator')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="field_separator"
                                        rules="required"
                                        :value="old('field_separator') ?? $export->field_separator"
                                        :label="trans('admin::app.settings.data-transfer.exports.edit.field-separator')"
                                        :placeholder="trans('admin::app.settings.data-transfer.exports.edit.field-separator')"
                                    />

                                    <x-admin::form.control-group.error control-name="field_separator" />
                                </x-admin::form.control-group>

                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.settings.after') !!}

                        {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.card.accordion.filters.befor') !!}

                        <!-- Filters Panel -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.settings.data-transfer.exports.create.filters')
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot:content>                        
                                <!-- Filter Fields -->
                                {!! view_render_event('unopim.admin.settings.data_transfer.exports.edit.filters.fields.befor') !!}

                                @php
                                    $fields = $exporterConfig[$export->entity_type]['filters']['fields'];
                                    $filters = $export->filters ?? [];
                                @endphp
                                <x-admin::data-transfer.filter-fields
                                    :fields="$fields"
                                    :fieldValues="$filters"
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
                        fileFormat: @json($export->filters['file_format']),
                        selectedFileFormat: @json($export->filters['file_format']),
                    };
                },

                watch: {
                    fileFormat(value) {
                        this.selectedFileFormat = JSON.parse(value).value;
                    },
                },
            })
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
