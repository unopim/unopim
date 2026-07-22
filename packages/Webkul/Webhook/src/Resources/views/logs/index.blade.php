<x-admin::layouts>
    <x-slot:title>
        @lang('webhook::app.configuration.webhook.logs.index.title')
    </x-slot>

    <div class="flex justify-between items-center max-sm:flex-wrap gap-2.5 mb-1">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('webhook::app.configuration.webhook.logs.index.title')
        </p>

        <a
            href="{{ route('webhook.index') }}"
            class="transparent-button"
        >
            @lang('webhook::app.webhooks.index.back-btn')
        </a>
    </div>

    @include('webhook::logs._grid')
</x-admin::layouts>
