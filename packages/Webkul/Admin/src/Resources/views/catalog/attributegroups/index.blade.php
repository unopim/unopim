<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.attribute-groups.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <!-- Title -->
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.attribute-groups.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('catalog.attribute_groups.create'))
                <a href="{{ route('admin.catalog.attribute.groups.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.attribute-groups.index.create-btn')
                    </div>
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.catalog.attribute.groups.list.before') !!}

    <x-admin::datagrid :src="route('admin.catalog.attribute.groups.index')" />

    {!! view_render_event('unopim.admin.catalog.attribute.groups.list.after') !!}

</x-admin::layouts>
