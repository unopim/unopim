<x-admin::layouts>
    <x-slot:title>
        @lang('webhook::app.configuration.webhook.logs.index.title')
    </x-slot>

    <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap pt-3">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('webhook::app.configuration.webhook.logs.index.title')
        </p>
    </div>

    <x-admin::datagrid :src="route('webhook.logs.index')">
    </x-admin::datagrid>
</x-admin::layouts>
