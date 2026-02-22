{!! view_render_event('unopim.order.orders.show.profitability.before', ['order' => $order]) !!}

<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
        @lang('order::app.admin.orders.partials.profitability.title')
    </p>

    @if($order->total_profit !== null)
        <div class="space-y-4">
            <!-- Total Profit -->
            <div class="p-3 rounded {{ $order->total_profit >= 0 ? 'bg-green-50 dark:bg-green-900' : 'bg-red-50 dark:bg-red-900' }}">
                <p class="text-sm {{ $order->total_profit >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                    @lang('order::app.admin.orders.partials.profitability.total-profit')
                </p>
                <p class="text-2xl font-bold {{ $order->total_profit >= 0 ? 'text-green-700 dark:text-green-200' : 'text-red-700 dark:text-red-200' }}">
                    {{ $order->formatted_total_profit }}
                </p>
            </div>

            <!-- Profit Margin -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.orders.partials.profitability.margin')
                    </p>
                    <p class="text-sm font-semibold {{ $order->profit_margin >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($order->profit_margin, 1) }}%
                    </p>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        class="h-2 rounded-full {{ $order->profit_margin >= 0 ? 'bg-green-500' : 'bg-red-500' }}"
                        style="width: {{ min(abs($order->profit_margin), 100) }}%"
                    ></div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.orders.partials.profitability.revenue')
                    </p>
                    <p class="text-sm text-gray-800 dark:text-white font-medium">
                        {{ $order->formatted_total_amount }}
                    </p>
                </div>
                <div class="flex justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.orders.partials.profitability.cost')
                    </p>
                    <p class="text-sm text-gray-800 dark:text-white font-medium">
                        {{ $order->formatted_total_cost }}
                    </p>
                </div>
            </div>

            <!-- Per Item Breakdown -->
            @if($order->items->where('profit', '!==', null)->count() > 0)
            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-2">
                    @lang('order::app.admin.orders.partials.profitability.by-item')
                </p>
                <div class="space-y-1 max-h-32 overflow-y-auto">
                    @foreach($order->items->where('profit', '!==', null) as $item)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-300 truncate mr-2">
                            {{ Str::limit($item->product_name, 20) }}
                        </span>
                        <span class="{{ $item->profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                            {{ $item->formatted_profit }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    @else
        <div class="text-center py-4">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                @lang('order::app.admin.orders.partials.profitability.no-cost-data')
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                @lang('order::app.admin.orders.partials.profitability.cost-hint')
            </p>
        </div>
    @endif
</div>

{!! view_render_event('unopim.order.orders.show.profitability.after', ['order' => $order]) !!}
