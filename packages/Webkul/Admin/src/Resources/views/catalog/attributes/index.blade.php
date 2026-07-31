@php
    $attributeTypes = collect(config('attribute_types'))
        ->map(fn ($type) => [
            'id'    => $type['key'],
            'label' => trans($type['name']),
        ])
        ->values()
        ->toJson();

    $swatchOptions = collect($swatchTypes)
        ->map(fn ($swatchType) => [
            'id'    => $swatchType,
            'label' => trans('admin::app.catalog.attributes.edit.option.'.$swatchType),
        ])
        ->values()
        ->toJson();

    $creationToggles = collect([
        [
            'name'  => 'is_unique',
            'label' => trans('admin::app.catalog.attributes.edit.is-unique'),
            'hint'  => trans('admin::app.catalog.attributes.create.is-unique-hint'),
            'types' => ['text'],
        ],
        [
            'name'  => 'value_per_locale',
            'label' => trans('admin::app.catalog.attributes.edit.value-per-locale'),
            'hint'  => trans('admin::app.catalog.attributes.create.value-per-locale-hint'),
        ],
        [
            'name'  => 'value_per_channel',
            'label' => trans('admin::app.catalog.attributes.edit.value-per-channel'),
            'hint'  => trans('admin::app.catalog.attributes.create.value-per-channel-hint'),
        ],
    ])->toJson();

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
                    :code-hint="trans('admin::app.catalog.attributes.create.code-hint')"
                    :type-label="trans('admin::app.catalog.attributes.create.type')"
                    :type-placeholder="trans('admin::app.catalog.attributes.create.select-type')"
                    :type-options="$attributeTypes"
                    :type-hint="trans('admin::app.catalog.attributes.create.type-hint')"
                    :swatch-label="trans('admin::app.catalog.attributes.create.swatch')"
                    :swatch-placeholder="trans('admin::app.catalog.attributes.create.swatch')"
                    :swatch-options="$swatchOptions"
                    :toggles="$creationToggles"
                    :save-label="trans('admin::app.catalog.attributes.create.save-btn')"
                />
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    {!! view_render_event('unopim.admin.catalog.attributes.list.before') !!}

    <x-admin::datagrid :src="route('admin.catalog.attributes.index')" />

    {!! view_render_event('unopim.admin.catalog.attributes.list.after') !!}

</x-admin::layouts>
