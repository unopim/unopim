<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.configuration.integrations.index.title')
    </x-slot>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.configuration.integrations.index.title')
        </p>
        
        <div class="flex gap-x-2.5 items-center">
            <!-- Add Role Button -->
            @if (bouncer()->hasPermission('configuration.integrations.create')) 
                <a 
                    href="{{ route('admin.configuration.integrations.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.configuration.integrations.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.configuration.integrations.list.before') !!}
    
    <x-admin::datagrid src="{{ route('admin.configuration.integrations.index') }}" />

    {!! view_render_event('unopim.admin.configuration.integrations.list.after') !!}

</x-admin::layouts>