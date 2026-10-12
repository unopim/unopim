<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.association_types.index.title')
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.association_types.index.before') !!}

    <x-admin::layouts.page-header
        :title="trans('admin::app.catalog.association_types.index.title')"
    >
        <x-slot:actions>
            @include('admin::catalog.associations.types.create')
        </x-slot>
    </x-admin::layouts.page-header>

    <x-admin::datagrid :src="route('admin.catalog.association_types.index')" />

    {!! view_render_event('unopim.admin.catalog.association_types.index.after') !!}
</x-admin::layouts>
