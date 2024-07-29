<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.category_fields.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.catalog.category_fields.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            {!! view_render_event('unopim.admin.catalog.category_fields.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.category_fields.create'))
                <a href="{{ route('admin.catalog.category_fields.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.category_fields.index.add-btn')
                    </div>
                </a>
            @endif

            {!! view_render_event('unopim.admin.catalog.category_fields.index.create-button.after') !!}
        </div>        
    </div>

    {!! view_render_event('unopim.admin.catalog.category_fields.list.before') !!}

    <x-admin::datagrid src="{{ route('admin.catalog.category_fields.index') }}" />

    {!! view_render_event('unopim.admin.catalog.category_fields.list.after') !!}

</x-admin::layouts>
