<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.webhooks.index.title')
    </x-slot>

    {!! view_render_event('unopim.order.webhooks.list.before') !!}

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.webhooks.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Create Webhook Button -->
            @if (bouncer()->hasPermission('order.webhooks.create'))
                <a
                    href="{{ route('admin.order.webhooks.create') }}"
                    class="primary-button"
                >
                    @lang('order::app.admin.webhooks.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid src="{{ route('admin.order.webhooks.index') }}" />

    {!! view_render_event('unopim.order.webhooks.list.after') !!}

</x-admin::layouts>
