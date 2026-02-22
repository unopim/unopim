<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.profitability.index.title')
    </x-slot>

    {!! view_render_event('unopim.order.profitability.index.before') !!}

    <div class="flex justify-between items-center mb-4">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.profitability.index.page-title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Export Button -->
            @if (bouncer()->hasPermission('order.profitability.export'))
                <a
                    href="{{ route('admin.order.profitability.export') }}"
                    class="secondary-button"
                >
                    @lang('order::app.admin.profitability.index.export')
                </a>
            @endif

            <!-- By Channel Button -->
            <a
                href="{{ route('admin.order.profitability.by-channel') }}"
                class="secondary-button"
            >
                @lang('order::app.admin.profitability.index.by-channel')
            </a>

            <!-- By Product Button -->
            <a
                href="{{ route('admin.order.profitability.by-product') }}"
                class="secondary-button"
            >
                @lang('order::app.admin.profitability.index.by-product')
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    {!! view_render_event('unopim.order.profitability.index.summary.before') !!}

    <div class="grid grid-cols-4 gap-4 mb-4">
        <!-- Total Revenue -->
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.index.total-revenue')
                    </p>
                    <p class="text-2xl text-gray-800 dark:text-white font-bold mt-1">
                        {{ $metrics['total_revenue_formatted'] ?? '$0.00' }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.index.total-profit')
                    </p>
                    <p class="text-2xl font-bold mt-1 {{ ($metrics['total_profit'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $metrics['total_profit_formatted'] ?? '$0.00' }}
                    </p>
                </div>
                <div class="p-3 {{ ($metrics['total_profit'] ?? 0) >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }} rounded-full">
                    <svg class="w-6 h-6 {{ ($metrics['total_profit'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Average Margin -->
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.index.avg-margin')
                    </p>
                    <p class="text-2xl text-gray-800 dark:text-white font-bold mt-1">
                        {{ number_format($metrics['avg_margin'] ?? 0, 1) }}%
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Order Count -->
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('order::app.admin.profitability.index.order-count')
                    </p>
                    <p class="text-2xl text-gray-800 dark:text-white font-bold mt-1">
                        {{ number_format($metrics['order_count'] ?? 0) }}
                    </p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {!! view_render_event('unopim.order.profitability.index.summary.after') !!}

    <!-- Charts Section -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <!-- Revenue vs Profit Trend -->
        {!! view_render_event('unopim.order.profitability.index.trend-chart.before') !!}

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('order::app.admin.profitability.index.revenue-profit-trend')
            </p>
            <canvas id="revenueProfitChart" height="250"></canvas>
        </div>

        {!! view_render_event('unopim.order.profitability.index.trend-chart.after') !!}

        <!-- Profit by Channel -->
        {!! view_render_event('unopim.order.profitability.index.channel-chart.before') !!}

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('order::app.admin.profitability.index.profit-by-channel')
            </p>
            <canvas id="profitByChannelChart" height="250"></canvas>
        </div>

        {!! view_render_event('unopim.order.profitability.index.channel-chart.after') !!}
    </div>

    <!-- Top Profitable Products -->
    {!! view_render_event('unopim.order.profitability.index.top-products.before') !!}

    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow mb-4">
        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
            @lang('order::app.admin.profitability.index.top-products')
        </p>
        <canvas id="topProductsChart" height="100"></canvas>
    </div>

    {!! view_render_event('unopim.order.profitability.index.top-products.after') !!}

    <!-- Detailed Data Table -->
    {!! view_render_event('unopim.order.profitability.index.datagrid.before') !!}

    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
            @lang('order::app.admin.profitability.index.detailed-data')
        </p>
        <x-admin::datagrid src="{{ route('admin.order.profitability.index') }}" />
    </div>

    {!! view_render_event('unopim.order.profitability.index.datagrid.after') !!}

    {!! view_render_event('unopim.order.profitability.index.after') !!}

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Revenue vs Profit Trend Chart
        const revenueProfitCtx = document.getElementById('revenueProfitChart').getContext('2d');
        new Chart(revenueProfitCtx, {
            type: 'line',
            data: {
                labels: @json($chartData['trend']['labels'] ?? []),
                datasets: [
                    {
                        label: '@lang('order::app.admin.profitability.index.revenue')',
                        data: @json($chartData['trend']['revenue'] ?? []),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: '@lang('order::app.admin.profitability.index.profit')',
                        data: @json($chartData['trend']['profit'] ?? []),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });

        // Profit by Channel Chart
        const profitByChannelCtx = document.getElementById('profitByChannelChart').getContext('2d');
        new Chart(profitByChannelCtx, {
            type: 'bar',
            data: {
                labels: @json($chartData['byChannel']['labels'] ?? []),
                datasets: [{
                    label: '@lang('order::app.admin.profitability.index.profit')',
                    data: @json($chartData['byChannel']['profit'] ?? []),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Top Products Chart
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: @json($chartData['topProducts']['labels'] ?? []),
                datasets: [{
                    label: '@lang('order::app.admin.profitability.index.profit')',
                    data: @json($chartData['topProducts']['profit'] ?? []),
                    backgroundColor: 'rgba(139, 92, 246, 0.8)'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    @endpush

</x-admin::layouts>
