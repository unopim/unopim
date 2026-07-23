<x-admin::layouts>
    <x-slot:title>
        {{ $mode === 'edit' ? trans('resource::app.save') : trans('resource::app.create') }}
    </x-slot>

    {!! view_render_event('unopim.resource.edit.before') !!}

    <x-admin::form
        ajax
        :action="$mode === 'edit' ? route($resource['routePrefix'].'.update', $record['id']) : route($resource['routePrefix'].'.store')"
    >
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        {!! view_render_event('unopim.resource.edit.form_controls.before') !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                {{ $mode === 'edit' ? trans('resource::app.save') : trans('resource::app.create') }}
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Back Button -->
                <a
                    href="{{ route($resource['routePrefix'].'.index') }}"
                    class="transparent-button"
                >
                    @lang('resource::app.back')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('resource::app.save')
                </button>
            </div>
        </div>

        <!-- Fields -->
        <div class="p-4 mt-3.5 bg-white dark:bg-cherry-900 rounded box-shadow">
            @foreach ($resource['schema'] as $field)
                @php
                    $fieldValue = old($field['name']) ?? ($record[$field['name']] ?? $field['default']);
                @endphp

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label :class="$field['required'] ? 'required' : ''">
                        {{ $field['label'] }}
                    </x-admin::form.control-group.label>

                    @switch($field['type'])
                        @case('select')
                            <x-admin::form.control-group.control
                                type="select"
                                :id="$field['name']"
                                :name="$field['name']"
                                :rules="$field['rules']"
                                :options="json_encode($field['options'])"
                                :value="$fieldValue"
                                :label="$field['label']"
                                :placeholder="$field['label']"
                                track-by="id"
                                label-by="label"
                            />

                            @break

                        @case('textarea')
                            <x-admin::form.control-group.control
                                type="textarea"
                                :id="$field['name']"
                                :name="$field['name']"
                                :rules="$field['rules']"
                                :value="$fieldValue"
                                :label="$field['label']"
                                :placeholder="$field['label']"
                            />

                            @break

                        @default
                            <x-admin::form.control-group.control
                                type="text"
                                :id="$field['name']"
                                :name="$field['name']"
                                :rules="$field['rules']"
                                :value="$fieldValue"
                                :label="$field['label']"
                                :placeholder="$field['label']"
                            />
                    @endswitch

                    <x-admin::form.control-group.error :control-name="$field['name']" />
                </x-admin::form.control-group>
            @endforeach
        </div>

        {!! view_render_event('unopim.resource.edit.form_controls.after') !!}
    </x-admin::form>

    {!! view_render_event('unopim.resource.edit.after') !!}
</x-admin::layouts>
