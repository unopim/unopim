{!! view_render_event('unopim.order.orders.show.addresses.before', ['order' => $order]) !!}

<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
        @lang('order::app.admin.orders.partials.addresses.title')
    </p>

    <div class="grid grid-cols-2 gap-4">
        <!-- Shipping Address -->
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300 font-semibold mb-2">
                @lang('order::app.admin.orders.partials.addresses.shipping')
            </p>
            @if($order->shipping_address)
                <div class="text-sm text-gray-800 dark:text-white space-y-1">
                    @if(!empty($order->shipping_address['name']))
                        <p class="font-medium">{{ $order->shipping_address['name'] }}</p>
                    @endif
                    @if(!empty($order->shipping_address['address_line_1']))
                        <p>{{ $order->shipping_address['address_line_1'] }}</p>
                    @endif
                    @if(!empty($order->shipping_address['address_line_2']))
                        <p>{{ $order->shipping_address['address_line_2'] }}</p>
                    @endif
                    <p>
                        @if(!empty($order->shipping_address['city']))
                            {{ $order->shipping_address['city'] }},
                        @endif
                        @if(!empty($order->shipping_address['state']))
                            {{ $order->shipping_address['state'] }}
                        @endif
                        @if(!empty($order->shipping_address['postal_code']))
                            {{ $order->shipping_address['postal_code'] }}
                        @endif
                    </p>
                    @if(!empty($order->shipping_address['country']))
                        <p>{{ $order->shipping_address['country'] }}</p>
                    @endif
                    @if(!empty($order->shipping_address['phone']))
                        <p class="mt-2">{{ $order->shipping_address['phone'] }}</p>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @lang('order::app.admin.orders.partials.addresses.no-shipping')
                </p>
            @endif
        </div>

        <!-- Billing Address -->
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300 font-semibold mb-2">
                @lang('order::app.admin.orders.partials.addresses.billing')
            </p>
            @if($order->billing_address)
                <div class="text-sm text-gray-800 dark:text-white space-y-1">
                    @if(!empty($order->billing_address['name']))
                        <p class="font-medium">{{ $order->billing_address['name'] }}</p>
                    @endif
                    @if(!empty($order->billing_address['address_line_1']))
                        <p>{{ $order->billing_address['address_line_1'] }}</p>
                    @endif
                    @if(!empty($order->billing_address['address_line_2']))
                        <p>{{ $order->billing_address['address_line_2'] }}</p>
                    @endif
                    <p>
                        @if(!empty($order->billing_address['city']))
                            {{ $order->billing_address['city'] }},
                        @endif
                        @if(!empty($order->billing_address['state']))
                            {{ $order->billing_address['state'] }}
                        @endif
                        @if(!empty($order->billing_address['postal_code']))
                            {{ $order->billing_address['postal_code'] }}
                        @endif
                    </p>
                    @if(!empty($order->billing_address['country']))
                        <p>{{ $order->billing_address['country'] }}</p>
                    @endif
                    @if(!empty($order->billing_address['phone']))
                        <p class="mt-2">{{ $order->billing_address['phone'] }}</p>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @lang('order::app.admin.orders.partials.addresses.no-billing')
                </p>
            @endif
        </div>
    </div>
</div>

{!! view_render_event('unopim.order.orders.show.addresses.after', ['order' => $order]) !!}
