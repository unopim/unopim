<x-admin::layouts>
    <x-slot:title>
        @lang('webhook::app.configuration.webhook.logs.index.title')
    </x-slot>

    <x-admin::layouts.page-header
        :title="trans('webhook::app.configuration.webhook.logs.index.title')"
        class="mb-1"
    >
        <x-slot:actions>
            <a
                href="{{ route('webhook.index') }}"
                class="transparent-button"
            >
                @lang('webhook::app.webhooks.index.back-btn')
            </a>
        </x-slot>
    </x-admin::layouts.page-header>

    @include('webhook::logs._grid')
</x-admin::layouts>
