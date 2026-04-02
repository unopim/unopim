<x-admin::layouts>
    <x-slot:title>
        @lang('ai-agent::app.agents.title')
    </x-slot:title>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('ai-agent::app.agents.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            @if (bouncer()->hasPermission('ai-agent.agents.create'))
                <a href="{{ route('ai-agent.agents.create') }}" class="primary-button">
                    @lang('ai-agent::app.agents.create-btn')
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.ai-agent.agents.list.before') !!}

    <x-admin::datagrid :src="route('ai-agent.agents.index')" />

    {!! view_render_event('unopim.admin.ai-agent.agents.list.after') !!}
</x-admin::layouts>
