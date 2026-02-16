<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.conflicts.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.conflicts.index.title')
        </p>
    </div>

    {!! view_render_event('channel_connector.conflicts.list.before') !!}

    <x-admin::datagrid :src="route('admin.channel_connector.conflicts.index')" />

    {!! view_render_event('channel_connector.conflicts.list.after') !!}
</x-admin::layouts>
