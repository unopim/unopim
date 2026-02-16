<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.connectors.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.connectors.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('channel_connector.connectors.create'))
                <a
                    href="{{ route('admin.channel_connector.connectors.create') }}"
                    class="primary-button"
                >
                    @lang('channel_connector::app.connectors.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('channel_connector.connectors.list.before') !!}

    <x-admin::datagrid :src="route('admin.channel_connector.connectors.index')" />

    {!! view_render_event('channel_connector.connectors.list.after') !!}
</x-admin::layouts>
