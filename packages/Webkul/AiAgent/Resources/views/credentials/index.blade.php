<x-admin::layouts>
    <x-slot:title>
        @lang('ai-agent::app.credentials.title')
    </x-slot:title>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('ai-agent::app.credentials.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            @if (bouncer()->hasPermission('ai-agent.credentials.create'))
                <a href="{{ route('ai-agent.credentials.create') }}" class="primary-button">
                    @lang('ai-agent::app.credentials.create-btn')
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.ai-agent.credentials.list.before') !!}

    <x-admin::datagrid :src="route('ai-agent.credentials.index')" />

    {!! view_render_event('unopim.admin.ai-agent.credentials.list.after') !!}
</x-admin::layouts>
