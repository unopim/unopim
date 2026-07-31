<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.roles.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.settings.roles.index.title')">
        <x-slot:actions>
            <!-- Add Role Button -->
            @if (bouncer()->hasPermission('settings.roles.create'))
                <a
                    href="{{ route('admin.settings.roles.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.settings.roles.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::page-header>

    {!! view_render_event('unopim.admin.settings.roles.list.before') !!}
    
    <x-admin::datagrid src="{{ route('admin.settings.roles.index') }}" />

    {!! view_render_event('unopim.admin.settings.roles.list.after') !!}

</x-admin::layouts>