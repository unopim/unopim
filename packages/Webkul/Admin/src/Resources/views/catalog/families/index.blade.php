<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.families.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.catalog.families.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('catalog.families.create'))
                <a href="{{ route('admin.catalog.families.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.families.index.add')
                    </div>
                </a>
            @endif
        </x-slot>
    </x-admin::page-header>

    {!! view_render_event('unopim.admin.catalog.families.list.before') !!}

    <x-admin::datagrid src="{{ route('admin.catalog.families.index') }}" />

    {!! view_render_event('unopim.admin.catalog.families.list.after') !!}

</x-admin::layouts>