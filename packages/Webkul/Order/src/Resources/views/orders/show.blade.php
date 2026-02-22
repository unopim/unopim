<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.orders.show.title', ['order_number' => $order->channel_order_id])
    </x-slot>

    {!! view_render_event('unopim.order.orders.show.before', ['order' => $order]) !!}

    <div class="flex justify-between items-center mb-4">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.orders.show.page-title', ['order_number' => $order->channel_order_id])
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Back Button -->
            <a
                href="{{ route('admin.order.orders.index') }}"
                class="transparent-button"
            >
                @lang('order::app.admin.orders.show.back')
            </a>

            <!-- Edit Button -->
            @if (bouncer()->hasPermission('order.orders.edit'))
                <a
                    href="{{ route('admin.order.orders.edit', $order->id) }}"
                    class="secondary-button"
                >
                    @lang('order::app.admin.orders.show.edit')
                </a>
            @endif

            <!-- Sync Button -->
            @if (bouncer()->hasPermission('order.sync.create'))
                <form
                    action="{{ route('admin.order.sync.order', $order->id) }}"
                    method="POST"
                >
                    @csrf
                    <button type="submit" class="primary-button">
                        @lang('order::app.admin.orders.show.sync')
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <!-- Left Column -->
        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

            <!-- Order Summary Card -->
            {!! view_render_event('unopim.order.orders.show.summary.before', ['order' => $order]) !!}

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.orders.show.summary')
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.orders.show.channel')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $order->channel->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.orders.show.status')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            <span class="px-2 py-1 rounded text-xs
                                {{ $order->status->value === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->status->value === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $order->status->value === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $order->status->value === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                            ">
                                {{ $order->status->label() }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.orders.show.order-date')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $order->order_date->format('M d, Y H:i') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.orders.show.total')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $order->formatted_total_amount }}
                        </p>
                    </div>

                    @if($order->tracking_number)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.orders.show.tracking-number')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $order->tracking_number }}
                        </p>
                    </div>
                    @endif

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.orders.show.synced-at')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $order->synced_at ? $order->synced_at->format('M d, Y H:i') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            {!! view_render_event('unopim.order.orders.show.summary.after', ['order' => $order]) !!}

            <!-- Customer Information -->
            @include('order::orders.partials.customer-info', ['order' => $order])

            <!-- Addresses -->
            @include('order::orders.partials.addresses', ['order' => $order])

            <!-- Order Items -->
            @include('order::orders.partials.order-items', ['order' => $order])

            <!-- Internal Notes -->
            @if($order->internal_notes)
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.orders.show.internal-notes')
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">
                    {{ $order->internal_notes }}
                </p>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

            <!-- Profitability Metrics -->
            @include('order::orders.partials.profitability', ['order' => $order])

            <!-- Order Timeline -->
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.orders.show.timeline')
                </p>

                <div class="space-y-3">
                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-blue-500"></div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-white">
                                @lang('order::app.admin.orders.show.order-created')
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                {{ $order->created_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>

                    @if($order->synced_at)
                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-green-500"></div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-white">
                                @lang('order::app.admin.orders.show.order-synced')
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                {{ $order->synced_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-gray-500"></div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-white">
                                @lang('order::app.admin.orders.show.last-updated')
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                {{ $order->updated_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Raw Data Accordion -->
            <x-admin::accordion>
                <x-slot:header>
                    <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                        @lang('order::app.admin.orders.show.raw-data')
                    </p>
                </x-slot>

                <x-slot:content>
                    <pre class="text-xs text-gray-600 dark:text-gray-300 overflow-auto max-h-64">{{ json_encode($order->raw_data, JSON_PRETTY_PRINT) }}</pre>
                </x-slot>
            </x-admin::accordion>
        </div>
    </div>

    {!! view_render_event('unopim.order.orders.show.after', ['order' => $order]) !!}

</x-admin::layouts>
