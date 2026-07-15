<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.attribute-groups.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.catalog.attribute-groups.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('catalog.attribute_groups.create'))
                <a href="{{ route('admin.catalog.attribute.groups.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.attribute-groups.index.create-btn')
                    </div>
                </a>
            @endif
        </x-slot>
    </x-admin::page-header>

    {!! view_render_event('unopim.admin.catalog.attribute.groups.list.before') !!}

    <x-admin::datagrid :src="route('admin.catalog.attribute.groups.index')" />

    {!! view_render_event('unopim.admin.catalog.attribute.groups.list.after') !!}

</x-admin::layouts>
