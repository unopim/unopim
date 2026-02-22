<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.profitability.by-channel.title')
    </x-slot>

    {!! view_render_event('unopim.order.profitability.by-channel.before') !!}

    <div class="flex justify-between items-center mb-4">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.profitability.by-channel.page-title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Back Button -->
            <a
                href="{{ route('admin.order.profitability.index') }}"
                class="transparent-button"
            >
                @lang('order::app.admin.profitability.by-channel.back')
            </a>

            <!-- Export Button -->
            @if (bouncer()->hasPermission('order.profitability.export'))
                <a
                    href="{{ route('admin.order.profitability.export', ['type' => 'by-channel']) }}"
                    class="secondary-button"
                >
                    @lang('order::app.admin.profitability.by-channel.export')
                </a>
            @endif
        </div>
    </div>

    <!-- Channel Comparison Cards -->
    {!! view_render_event('unopim.order.profitability.by-channel.cards.before') !!}

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
        @foreach($channelMetrics as $channelMetric)
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ $channelMetric['channel_name'] }}
                </p>
                <span class="px-2 py-1 text-xs rounded {{ $channelMetric['profit'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ number_format($channelMetric['margin'], 1) }}%
                </span>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.by-channel.revenue')
                    </p>
                    <p class="text-sm text-gray-800 dark:text-white font-medium">
                        {{ $channelMetric['revenue_formatted'] }}
                    </p>
                </div>

                <div class="flex justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.by-channel.profit')
                    </p>
                    <p class="text-sm font-medium {{ $channelMetric['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $channelMetric['profit_formatted'] }}
                    </p>
                </div>

                <div class="flex justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.by-channel.orders')
                    </p>
                    <p class="text-sm text-gray-800 dark:text-white font-medium">
                        {{ number_format($channelMetric['order_count']) }}
                    </p>
                </div>

                <div class="flex justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.by-channel.avg-order-value')
                    </p>
                    <p class="text-sm text-gray-800 dark:text-white font-medium">
                        {{ $channelMetric['avg_order_value_formatted'] }}
                    </p>
                </div>
            </div>

            <!-- Profit Margin Progress Bar -->
            <div class="mt-3">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        class="h-2 rounded-full {{ $channelMetric['profit'] >= 0 ? 'bg-green-500' : 'bg-red-500' }}"
                        style="width: {{ min(abs($channelMetric['margin']), 100) }}%"
                    ></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {!! view_render_event('unopim.order.profitability.by-channel.cards.after') !!}

    <!-- Detailed Comparison Table -->
    {!! view_render_event('unopim.order.profitability.by-channel.table.before') !!}

    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
            @lang('order::app.admin.profitability.by-channel.comparison-table')
        </p>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                            @lang('order::app.admin.profitability.by-channel.channel')
                        </th>
                        <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                            @lang('order::app.admin.profitability.by-channel.orders')
                        </th>
                        <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                            @lang('order::app.admin.profitability.by-channel.revenue')
                        </th>
                        <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                            @lang('order::app.admin.profitability.by-channel.cost')
                        </th>
                        <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                            @lang('order::app.admin.profitability.by-channel.profit')
                        </th>
                        <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300 pb-2">
                            @lang('order::app.admin.profitability.by-channel.margin')
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($channelMetrics as $metric)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-3 text-sm text-gray-800 dark:text-white font-medium">
                            {{ $metric['channel_name'] }}
                        </td>
                        <td class="py-3 text-sm text-gray-800 dark:text-white text-right">
                            {{ number_format($metric['order_count']) }}
                        </td>
                        <td class="py-3 text-sm text-gray-800 dark:text-white text-right">
                            {{ $metric['revenue_formatted'] }}
                        </td>
                        <td class="py-3 text-sm text-gray-800 dark:text-white text-right">
                            {{ $metric['cost_formatted'] }}
                        </td>
                        <td class="py-3 text-sm text-right font-medium {{ $metric['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $metric['profit_formatted'] }}
                        </td>
                        <td class="py-3 text-sm text-right font-medium {{ $metric['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($metric['margin'], 1) }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-700">
                        <td class="py-3 text-sm text-gray-800 dark:text-white font-bold">
                            @lang('order::app.admin.profitability.by-channel.total')
                        </td>
                        <td class="py-3 text-sm text-gray-800 dark:text-white text-right font-bold">
                            {{ number_format($totals['order_count']) }}
                        </td>
                        <td class="py-3 text-sm text-gray-800 dark:text-white text-right font-bold">
                            {{ $totals['revenue_formatted'] }}
                        </td>
                        <td class="py-3 text-sm text-gray-800 dark:text-white text-right font-bold">
                            {{ $totals['cost_formatted'] }}
                        </td>
                        <td class="py-3 text-sm text-right font-bold {{ $totals['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $totals['profit_formatted'] }}
                        </td>
                        <td class="py-3 text-sm text-right font-bold {{ $totals['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($totals['margin'], 1) }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {!! view_render_event('unopim.order.profitability.by-channel.table.after') !!}

    {!! view_render_event('unopim.order.profitability.by-channel.after') !!}

</x-admin::layouts>
