<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.association_types.index.title')
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.association_types.index.before') !!}

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.catalog.association_types.index.title')
        </p>

        @include('admin::catalog.associations.types.create')
    </div>

    <x-admin::datagrid :src="route('admin.catalog.association_types.index')" />

    {!! view_render_event('unopim.admin.catalog.association_types.index.after') !!}
</x-admin::layouts>
