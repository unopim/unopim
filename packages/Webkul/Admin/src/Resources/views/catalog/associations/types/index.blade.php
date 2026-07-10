<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.association_types.index.title')
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.association_types.index.before') !!}

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.catalog.association_types.index.title')
        </p>

        @if (bouncer()->hasPermission('catalog.association_types.create'))
            <a
                href="{{ route('admin.catalog.association_types.create') }}"
                class="primary-button"
            >
                @lang('admin::app.catalog.association_types.index.create-btn')
            </a>
        @endif
    </div>

    <x-admin::datagrid :src="route('admin.catalog.association_types.index')" />

    {!! view_render_event('unopim.admin.catalog.association_types.index.after') !!}
</x-admin::layouts>
