<x-admin::layouts>
    <x-slot:title>
        @lang('webhook::app.webhooks.index.title')
    </x-slot>

    <div class="flex justify-between items-center max-sm:flex-wrap gap-2.5">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('webhook::app.webhooks.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            @if (bouncer()->hasPermission('configuration.webhook.logs'))
                <a
                    href="{{ route('webhook.logs.index') }}"
                    class="transparent-button"
                >
                    @lang('webhook::app.webhooks.index.logs-btn')
                </a>
            @endif

            @if (bouncer()->hasPermission('configuration.webhook.create'))
                <a
                    href="{{ route('webhook.create') }}"
                    class="primary-button"
                >
                    @lang('webhook::app.webhooks.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('webhook.index')" />
</x-admin::layouts>
