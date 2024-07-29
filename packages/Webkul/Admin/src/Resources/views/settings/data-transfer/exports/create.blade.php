<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.create.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.before') !!}

    <x-admin::form
        :action="route('admin.settings.data_transfer.exports.store')"
        enctype="multipart/form-data"
    >
        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.create_form_controls.before') !!}

        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.settings.data-transfer.exports.create.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Cancel Button -->
                <a
                    href="{{ route('admin.settings.data_transfer.exports.index') }}"
                    class="transparent-button "
                >
                    @lang('admin::app.settings.data-transfer.exports.create.back-btn')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.settings.data-transfer.exports.create.save-btn')
                </button>
            </div>
        </div>

        <!-- Body Content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Container -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.general.before') !!}
                <!-- Setup Import Panel -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.settings.data-transfer.exports.create.general')
                    </p>
                    <!-- Code -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.data-transfer.exports.create.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code"
                            :value="old('code')"
                            rules="required"
                            :label="trans('admin::app.settings.data-transfer.exports.create.code')"
                            :placeholder="trans('admin::app.settings.data-transfer.exports.create.code')"
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <!-- Type -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.data-transfer.exports.create.type')
                        </x-admin::form.control-group.label>
 
                        @php
                            $options = [];
                            foreach(config('exporters') as $index => $export) {
                                    $options[] = [
                                        'id'    => $index,
                                        'label' => trans($export['title'])
                                    ];
                                }

                            $optionsJson = json_encode($options);
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            name="entity_type"
                            id="export-type"
                            :value="old('entity_type')"
                            ref="exportType"
                            rules="required"
                            :label="trans('admin::app.settings.data-transfer.exports.create.type')"
                            :options="$optionsJson"
                            track-by="id"
                            label-by="label"
                        >   
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="type" /> 
                    </x-admin::form.control-group>
                </div>
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.general.after') !!}
            </div>

            <!-- Right Container -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.accordion.settings.before') !!}
                <!-- Settings Panel -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.settings.data-transfer.exports.create.settings')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>                        
                        <!-- CSV Field Separator -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.data-transfer.exports.create.field-separator')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="field_separator"
                                :value="old('field_separator') ?? ','"
                                rules="required"
                                :label="trans('admin::app.settings.data-transfer.exports.create.field-separator')"
                                :placeholder="trans('admin::app.settings.data-transfer.exports.create.field-separator')"
                            />
                            
                            <x-admin::form.control-group.error control-name="field_separator" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

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
                        @php
                                $fields = $exporterConfig['categories']['filters']['fields'];
                        @endphp
                        @foreach($fields as $field)
                               @php 
                                $fieldName = $field['name'];
                                $fieldLabel = trans($field['title']);
                                $validation = $field['validation'] ?? '';
                               @endphp
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    {{ $field['title'] }}

                                    @if ($field['required'])
                                        <span class="required"></span>
                                    @endif
                                </x-admin::form.control-group.label>
                                @switch($field['type'])
                                    @case('boolean')
                                        <input type="hidden" name="filters[{{$fieldName}}]" value="0" />
                                        <x-admin::form.control-group.control
                                            type="switch"
                                            :id="$fieldName"
                                            name="filters[{{$fieldName}}]"
                                            ::rules="$validation"
                                            :label="$fieldLabel"
                                            :checked="(bool) ! empty($value)"
                                            value="1"
                                        />
                                        @break

                                    @case('select')
                                        @php
                                            $optionsInJson = json_encode($field['options']);
                                        @endphp
                                        <x-admin::form.control-group.control
                                            type="select"
                                            id="$fieldName"
                                            ::rules="$validation"
                                            name="filters[{{$fieldName}}]"
                                            value="{{old($fieldName) ?? 'Csv'}}"
                                            v-model="$fieldName"
                                            :options="$optionsInJson"
                                            track-by="value"
                                            label-by="label" 
                                        />
                                        @break

                                    @default
                                        <x-admin::form.control-group.control
                                            :type="$field['type']"
                                            :id="$fieldName"
                                            :name="filters['$fieldName']"
                                            ::rules="$validation"
                                            :options="json_encode([])"
                                            :label="$fieldLabel"
                                            :value="$value"
                                            async="true"
                                            entity-name="export_filter_field"
                                        />
                                @endswitch
                                <x-admin::form.control-group.error :control-name="$fieldName" />
                            </x-admin::form.control-group>
                        @endforeach
                    </x-slot>
                </x-admin::accordion>
                {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.card.accordion.settings.after') !!}
            </div>
        </div>

        {!! view_render_event('unopim.admin.settings.data_transfer.exports.create.create_form_controls.after') !!}
    </x-admin::form>
</x-admin::layouts>
