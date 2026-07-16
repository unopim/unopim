@php
    $attributeTypes = collect(config('attribute_types'))
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
                ? trans('admin::app.catalog.attributes.create.no')
                : trans('admin::app.catalog.attributes.create.'.$validation),
        ])
        ->values()
        ->toJson();
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.attributes.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('admin::app.catalog.attributes.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('catalog.attributes.create'))
                <x-admin::catalog.quick-create-modal
                    id="attributeCreateModal"
                    :action="route('admin.catalog.attributes.store')"
                    :button-label="trans('admin::app.catalog.attributes.index.create-btn')"
                    :title="trans('admin::app.catalog.attributes.create.title')"
                    :name-label="trans('admin::app.catalog.attributes.index.datagrid.name')"
                    :name-placeholder="trans('admin::app.catalog.attributes.index.datagrid.name')"
                    :code-label="trans('admin::app.catalog.attributes.create.code')"
                    :code-placeholder="trans('admin::app.catalog.attributes.create.code')"
                    :type-label="trans('admin::app.catalog.attributes.create.type')"
                    :type-placeholder="trans('admin::app.catalog.attributes.create.select-type')"
                    :type-options="$attributeTypes"
                    :validation-label="trans('admin::app.catalog.attributes.create.input-validation')"
                    :validation-placeholder="trans('admin::app.catalog.attributes.create.input-validation')"
                    :validation-options="$validations"
                    :save-label="trans('admin::app.catalog.attributes.create.save-btn')"
                />
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    {!! view_render_event('unopim.admin.catalog.attributes.list.before') !!}

    <x-admin::datagrid :src="route('admin.catalog.attributes.index')" />

    {!! view_render_event('unopim.admin.catalog.attributes.list.after') !!}

</x-admin::layouts>
