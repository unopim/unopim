<x-admin::layouts>

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.channels.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.settings.channels.index.title')">
        @if (bouncer()->hasPermission('settings.channels.create'))
            <x-slot:actions>
                <a
                    href="{{ route('admin.settings.channels.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.settings.channels.index.create-btn')
                </a>
            </x-slot>
        @endif
    </x-admin::page-header>

    {!! view_render_event('unopim.settings.channels.list.before') !!}
    
    <x-admin::datagrid src="{{ route('admin.settings.channels.index') }}" />

    {!! view_render_event('unopim.settings.channels.list.after') !!}

</x-admin::layouts>