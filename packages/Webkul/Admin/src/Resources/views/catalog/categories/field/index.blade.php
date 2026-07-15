<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.category_fields.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.catalog.category_fields.index.title')">
        <x-slot:actions>
            {!! view_render_event('unopim.admin.catalog.category_fields.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.category_fields.create'))
                <a href="{{ route('admin.catalog.category_fields.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.category_fields.index.add-btn')
                    </div>
                </a>
            @endif

            {!! view_render_event('unopim.admin.catalog.category_fields.index.create-button.after') !!}
        </x-slot>
    </x-admin::page-header>

    {!! view_render_event('unopim.admin.catalog.category_fields.list.before') !!}

    <x-admin::datagrid src="{{ route('admin.catalog.category_fields.index') }}" />

    {!! view_render_event('unopim.admin.catalog.category_fields.list.after') !!}

</x-admin::layouts>
