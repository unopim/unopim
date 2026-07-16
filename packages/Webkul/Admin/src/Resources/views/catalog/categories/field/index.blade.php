@php
    $categoryFieldTypes = collect(config('category_field_types'))
        ->map(fn ($type) => [
            'id'    => $type['key'],
            'label' => trans($type['name']),
        ])
        ->values()
        ->toJson();

    $validations = collect(['none', 'number', 'decimal', 'email', 'url', 'regex'])
        ->map(fn ($validation) => [
            'id'    => $validation,
            'label' => $validation === 'none'
                ? trans('admin::app.catalog.category_fields.create.no')
                : trans('admin::app.catalog.category_fields.create.'.$validation),
        ])
        ->values()
        ->toJson();
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.category_fields.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('admin::app.catalog.category_fields.index.title')">
        <x-slot:actions>
            {!! view_render_event('unopim.admin.catalog.category_fields.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.category_fields.create'))
                <x-admin::catalog.quick-create-modal
                    id="categoryFieldCreateModal"
                    :action="route('admin.catalog.category_fields.store')"
                    :button-label="trans('admin::app.catalog.category_fields.index.add-btn')"
                    :title="trans('admin::app.catalog.category_fields.create.title')"
                    :name-label="trans('admin::app.catalog.category_fields.index.datagrid.name')"
                    :name-placeholder="trans('admin::app.catalog.category_fields.index.datagrid.name')"
                    :code-label="trans('admin::app.catalog.category_fields.create.code')"
                    :code-placeholder="trans('admin::app.catalog.category_fields.create.code')"
                    :type-label="trans('admin::app.catalog.category_fields.create.type')"
                    :type-placeholder="trans('admin::app.catalog.category_fields.create.select-type')"
                    :type-options="$categoryFieldTypes"
                    :validation-label="trans('admin::app.catalog.category_fields.create.input-validation')"
                    :validation-placeholder="trans('admin::app.catalog.category_fields.create.input-validation')"
                    :validation-options="$validations"
                    :save-label="trans('admin::app.catalog.category_fields.create.save-btn')"
                />
            @endif

            {!! view_render_event('unopim.admin.catalog.category_fields.index.create-button.after') !!}
        </x-slot>
    </x-admin::layouts.page-header>

    {!! view_render_event('unopim.admin.catalog.category_fields.list.before') !!}

    <x-admin::datagrid src="{{ route('admin.catalog.category_fields.index') }}" />

    {!! view_render_event('unopim.admin.catalog.category_fields.list.after') !!}

</x-admin::layouts>
