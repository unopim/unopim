@php
    $activeTab = match (true) {
        request()->has('history')                                                       => 'history',
        request()->has('logs') && bouncer()->hasPermission('configuration.webhook.logs') => 'logs',
        default                                                                          => 'general',
    };

    $tabItems = [
        [
            'key'   => 'general',
            'url'   => route('webhook.edit', $webhook->id),
            'label' => 'webhook::app.webhooks.form.general',
        ],
    ];

    if (bouncer()->hasPermission('configuration.webhook.logs')) {
        $tabItems[] = [
            'key'   => 'logs',
            'url'   => route('webhook.edit', ['id' => $webhook->id, 'logs' => 1]),
            'label' => 'webhook::app.webhooks.index.logs-btn',
        ];
    }
@endphp

<x-admin::layouts.with-history
    :active-tab="$activeTab"
    entity-name="webhooks"
    :history-id="$webhook->id"
    :tab-items="$tabItems"
>
    <x-slot:title>
        @lang('webhook::app.webhooks.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('webhook::app.webhooks.edit.title')"
            :back-url="route('webhook.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="$activeTab === 'general' ? trans('webhook::app.webhooks.edit.save-btn') : null"
            form="webhook-edit-form"
            :sticky="false"
        />
    </x-slot>

    <x-admin::form
        id="webhook-edit-form"
        :action="route('webhook.update', $webhook->id)"
        method="PUT"
    >
        @include('webhook::webhooks._fields')
    </x-admin::form>

    <x-slot:tabContents>
        @if ($activeTab === 'logs' && bouncer()->hasPermission('configuration.webhook.logs'))
            @include('webhook::logs._grid', ['logsSrc' => route('webhook.logs.for-webhook', $webhook->id)])
        @endif
    </x-slot>
</x-admin::layouts.with-history>
