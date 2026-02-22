{!! view_render_event('unopim.order.orders.show.items.before', ['order' => $order]) !!}

<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
        @lang('order::app.admin.orders.partials.items.title')
    </p>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                        @lang('order::app.admin.orders.partials.items.sku')
                    </th>
                    <th class="text-left text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                        @lang('order::app.admin.orders.partials.items.product')
                    </th>
                    <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                        @lang('order::app.admin.orders.partials.items.quantity')
                    </th>
                    <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                        @lang('order::app.admin.orders.partials.items.price')
                    </th>
                    <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                        @lang('order::app.admin.orders.partials.items.total')
                    </th>
                    <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                        @lang('order::app.admin.orders.partials.items.profit')
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="py-3 text-sm text-gray-800 dark:text-white">
                        {{ $item->sku }}
                    </td>
                    <td class="py-3">
                        <div>
                            <p class="text-sm text-gray-800 dark:text-white font-medium">
                                {{ $item->product_name }}
                            </p>
                            @if($item->product)
                            <a
                                href="{{ route('admin.catalog.products.edit', $item->product_id) }}"
                                class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                @lang('order::app.admin.orders.partials.items.view-product')
                            </a>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 text-sm text-gray-800 dark:text-white text-right">
                        {{ $item->quantity }}
                    </td>
                    <td class="py-3 text-sm text-gray-800 dark:text-white text-right">
                        {{ $item->formatted_unit_price }}
                    </td>
                    <td class="py-3 text-sm text-gray-800 dark:text-white text-right font-medium">
                        {{ $item->formatted_total_price }}
                    </td>
                    <td class="py-3 text-sm text-right">
                        @if($item->profit !== null)
                            <span class="{{ $item->profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                                {{ $item->formatted_profit }}
                                <span class="text-xs">
                                    ({{ number_format($item->profit_margin, 1) }}%)
                                </span>
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500 text-xs">
                                @lang('order::app.admin.orders.partials.items.no-cost-data')
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 dark:border-gray-700">
                    <td colspan="4" class="py-3 text-sm text-gray-800 dark:text-white font-semibold text-right">
                        @lang('order::app.admin.orders.partials.items.subtotal')
                    </td>
                    <td class="py-3 text-sm text-gray-800 dark:text-white font-semibold text-right">
                        {{ $order->formatted_subtotal_amount }}
                    </td>
                    <td class="py-3 text-sm text-right">
                        @if($order->total_profit !== null)
                            <span class="{{ $order->total_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-semibold">
                                {{ $order->formatted_total_profit }}
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500 text-xs">N/A</span>
                        @endif
                    </td>
                </tr>
                @if($order->shipping_amount > 0)
                <tr>
                    <td colspan="4" class="py-2 text-sm text-gray-600 dark:text-gray-300 text-right">
                        @lang('order::app.admin.orders.partials.items.shipping')
                    </td>
                    <td class="py-2 text-sm text-gray-800 dark:text-white text-right">
                        {{ $order->formatted_shipping_amount }}
                    </td>
                    <td></td>
                </tr>
                @endif
                @if($order->tax_amount > 0)
                <tr>
                    <td colspan="4" class="py-2 text-sm text-gray-600 dark:text-gray-300 text-right">
                        @lang('order::app.admin.orders.partials.items.tax')
                    </td>
                    <td class="py-2 text-sm text-gray-800 dark:text-white text-right">
                        {{ $order->formatted_tax_amount }}
                    </td>
                    <td></td>
                </tr>
                @endif
                @if($order->discount_amount > 0)
                <tr>
                    <td colspan="4" class="py-2 text-sm text-gray-600 dark:text-gray-300 text-right">
                        @lang('order::app.admin.orders.partials.items.discount')
                    </td>
                    <td class="py-2 text-sm text-red-600 dark:text-red-400 text-right">
                        -{{ $order->formatted_discount_amount }}
                    </td>
                    <td></td>
                </tr>
                @endif
                <tr class="border-t-2 border-gray-200 dark:border-gray-700">
                    <td colspan="4" class="py-3 text-base text-gray-800 dark:text-white font-bold text-right">
                        @lang('order::app.admin.orders.partials.items.grand-total')
                    </td>
                    <td class="py-3 text-base text-gray-800 dark:text-white font-bold text-right">
                        {{ $order->formatted_total_amount }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{!! view_render_event('unopim.order.orders.show.items.after', ['order' => $order]) !!}
