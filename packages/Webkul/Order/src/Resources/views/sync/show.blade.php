<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.sync.show.title')
    </x-slot>

    {!! view_render_event('unopim.order.sync.show.before', ['syncLog' => $syncLog]) !!}

    <div class="flex justify-between items-center mb-4">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.sync.show.page-title', ['id' => $syncLog->id])
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Back Button -->
            <a
                href="{{ route('admin.order.sync.index') }}"
                class="transparent-button"
            >
                @lang('order::app.admin.sync.show.back')
            </a>

            <!-- Retry Button (only for failed syncs) -->
            @if ($syncLog->status === 'failed' && bouncer()->hasPermission('order.sync.create'))
                <form
                    action="{{ route('admin.order.sync.retry', $syncLog->id) }}"
                    method="POST"
                >
                    @csrf
                    <button type="submit" class="primary-button">
                        @lang('order::app.admin.sync.show.retry')
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <!-- Left Column -->
        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

            <!-- Sync Summary -->
            {!! view_render_event('unopim.order.sync.show.summary.before', ['syncLog' => $syncLog]) !!}

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.sync.show.summary')
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.channel')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $syncLog->channel->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.status')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            <span class="px-2 py-1 rounded text-xs
                                {{ $syncLog->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $syncLog->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $syncLog->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                            ">
                                {{ ucfirst($syncLog->status) }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.started-at')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $syncLog->started_at->format('M d, Y H:i:s') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.completed-at')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $syncLog->completed_at ? $syncLog->completed_at->format('M d, Y H:i:s') : 'N/A' }}
                        </p>
                    </div>

                    @if($syncLog->completed_at && $syncLog->started_at)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.duration')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $syncLog->started_at->diffForHumans($syncLog->completed_at, true) }}
                        </p>
                    </div>
                    @endif

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.orders-synced')
                        </p>
                        <p class="text-base text-gray-800 dark:text-white font-medium">
                            {{ $syncLog->orders_synced }}
                        </p>
                    </div>
                </div>
            </div>

            {!! view_render_event('unopim.order.sync.show.summary.after', ['syncLog' => $syncLog]) !!}

            <!-- Error Details (if failed) -->
            @if($syncLog->status === 'failed' && $syncLog->error_message)
            {!! view_render_event('unopim.order.sync.show.error.before', ['syncLog' => $syncLog]) !!}

            <div class="p-4 bg-red-50 dark:bg-red-900 rounded box-shadow border border-red-200 dark:border-red-700">
                <p class="mb-2 text-base text-red-800 dark:text-red-200 font-semibold">
                    @lang('order::app.admin.sync.show.error-details')
                </p>
                <p class="text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap">
                    {{ $syncLog->error_message }}
                </p>
            </div>

            {!! view_render_event('unopim.order.sync.show.error.after', ['syncLog' => $syncLog]) !!}
            @endif

            <!-- Metadata -->
            @if($syncLog->metadata)
            {!! view_render_event('unopim.order.sync.show.metadata.before', ['syncLog' => $syncLog]) !!}

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.sync.show.metadata')
                </p>

                <div class="space-y-2">
                    @foreach($syncLog->metadata as $key => $value)
                    <div class="flex justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">
                            {{ Str::title(str_replace('_', ' ', $key)) }}
                        </p>
                        <p class="text-sm text-gray-800 dark:text-white">
                            {{ is_array($value) ? json_encode($value) : $value }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            {!! view_render_event('unopim.order.sync.show.metadata.after', ['syncLog' => $syncLog]) !!}
            @endif
        </div>

        <!-- Right Column -->
        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

            <!-- Sync Statistics -->
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.sync.show.statistics')
                </p>

                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.total-orders')
                        </p>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            {{ $syncLog->orders_synced }}
                        </p>
                    </div>

                    @if(isset($syncLog->metadata['orders_created']))
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.created')
                        </p>
                        <p class="text-lg text-green-600 dark:text-green-400 font-bold">
                            {{ $syncLog->metadata['orders_created'] }}
                        </p>
                    </div>
                    @endif

                    @if(isset($syncLog->metadata['orders_updated']))
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.updated')
                        </p>
                        <p class="text-lg text-blue-600 dark:text-blue-400 font-bold">
                            {{ $syncLog->metadata['orders_updated'] }}
                        </p>
                    </div>
                    @endif

                    @if(isset($syncLog->metadata['orders_failed']))
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('order::app.admin.sync.show.failed')
                        </p>
                        <p class="text-lg text-red-600 dark:text-red-400 font-bold">
                            {{ $syncLog->metadata['orders_failed'] }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Related Orders -->
            @if($syncLog->orders_synced > 0)
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('order::app.admin.sync.show.related-orders')
                </p>

                <a
                    href="{{ route('admin.order.orders.index', ['channel_id' => $syncLog->channel_id, 'synced_at_from' => $syncLog->started_at->format('Y-m-d H:i:s')]) }}"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                >
                    @lang('order::app.admin.sync.show.view-orders')
                </a>
            </div>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.order.sync.show.after', ['syncLog' => $syncLog]) !!}

</x-admin::layouts>
