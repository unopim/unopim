<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('webhook::app.configuration.webhook.index.history.title')
    </x-slot>

    <x-admin::modal.history />

    <x-admin::history src="{{ route('admin.history.index',['webhook_history', 1]) }}">
    </x-admin::history>
</x-admin::layouts>
