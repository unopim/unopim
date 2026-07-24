<x-admin::page
    :title="trans('passport::app.mapping.title')"
    :subtitle="trans('passport::app.mapping.info')"
    :back="route('admin.catalog.passports.index')"
    :action="route('admin.catalog.passports.mapping.update')"
    method="PUT"
    :ajax="true"
>
    <x-slot:actions>
        <button type="submit" class="primary-button">
            @lang('passport::app.mapping.save-btn')
        </button>
    </x-slot>

    <div class="mt-6 grid gap-4 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        @foreach ($passportFields as $field)
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    {{ $field->getTranslatedValueWithFallback('name') ?: $field->code }}
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    :name="'mapping[' . $field->code . ']'"
                    :value="$mapping[$field->code] ?? ''"
                    :label="$field->getTranslatedValueWithFallback('name') ?: $field->code"
                    :placeholder="trans('passport::app.mapping.select-source')"
                    :options="json_encode($sourceOptions[$field->code] ?? [])"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.error :control-name="'mapping[' . $field->code . ']'" />
            </x-admin::form.control-group>
        @endforeach
    </div>
</x-admin::page>
