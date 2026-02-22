<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.orders.index.title')
    </x-slot>

    {!! view_render_event('unopim.order.orders.list.before') !!}

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.orders.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Manual Sync Button -->
            @if (bouncer()->hasPermission('order.sync.create'))
                <a
                    href="{{ route('admin.order.sync.manual') }}"
                    class="secondary-button"
                >
                    @lang('order::app.admin.orders.index.sync-now')
                </a>
            @endif

            <!-- Export Orders Button -->
            @if (bouncer()->hasPermission('order.orders.export'))
                <a
                    href="{{ route('admin.order.orders.export') }}"
                    class="secondary-button"
                >
                    @lang('order::app.admin.orders.index.export')
                </a>
            @endif

            <!-- Profitability Dashboard Button -->
            @if (bouncer()->hasPermission('order.profitability.view'))
                <a
                    href="{{ route('admin.order.profitability.index') }}"
                    class="primary-button"
                >
                    @lang('order::app.admin.orders.index.profitability')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid src="{{ route('admin.order.orders.index') }}" />

    {!! view_render_event('unopim.order.orders.list.after') !!}

</x-admin::layouts>
