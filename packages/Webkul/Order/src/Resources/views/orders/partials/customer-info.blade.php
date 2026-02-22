{!! view_render_event('unopim.order.orders.show.customer.before', ['order' => $order]) !!}

<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
        @lang('order::app.admin.orders.partials.customer.title')
    </p>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                @lang('order::app.admin.orders.partials.customer.name')
            </p>
            <p class="text-base text-gray-800 dark:text-white font-medium">
                {{ $order->customer_name ?: 'N/A' }}
            </p>
        </div>

        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                @lang('order::app.admin.orders.partials.customer.email')
            </p>
            <p class="text-base text-gray-800 dark:text-white font-medium">
                {{ $order->customer_email ?: 'N/A' }}
            </p>
        </div>

        @if($order->customer_phone)
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                @lang('order::app.admin.orders.partials.customer.phone')
            </p>
            <p class="text-base text-gray-800 dark:text-white font-medium">
                {{ $order->customer_phone }}
            </p>
        </div>
        @endif

        @if($order->customer_channel_id)
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                @lang('order::app.admin.orders.partials.customer.channel-id')
            </p>
            <p class="text-base text-gray-800 dark:text-white font-medium">
                {{ $order->customer_channel_id }}
            </p>
        </div>
        @endif
    </div>
</div>

{!! view_render_event('unopim.order.orders.show.customer.after', ['order' => $order]) !!}
