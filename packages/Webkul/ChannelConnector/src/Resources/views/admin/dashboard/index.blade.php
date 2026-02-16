<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.dashboard.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.dashboard.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.channel_connector.connectors.index') }}"
                class="transparent-button"
            >
                @lang('channel_connector::app.dashboard.back-to-connectors')
            </a>
        </div>
    </div>

    {!! view_render_event('channel_connector.dashboard.list.before') !!}

    <x-admin::datagrid :src="route('admin.channel_connector.dashboard.index')" />

    {!! view_render_event('channel_connector.dashboard.list.after') !!}
</x-admin::layouts>
