<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.dashboard.show.title') - #{{ $job->id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.dashboard.show.title') - #{{ $job->id }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.channel_connector.dashboard.index') }}" class="transparent-button">
                @lang('channel_connector::app.general.back')
            </a>

            @if ($job->status === 'failed' && bouncer()->hasPermission('channel_connector.sync.create'))
                <form action="{{ route('admin.channel_connector.dashboard.retry', $job->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="primary-button">
                        @lang('channel_connector::app.sync.actions.retry-failed')
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Job Info Cards --}}
    <div class="mt-3.5 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.connector')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ $job->connector?->name ?? '-' }}</p>
            @if ($job->connector)
                <p class="text-xs text-gray-400 dark:text-gray-500">{{ trans("channel_connector::app.connectors.channel-types.{$job->connector->channel_type}") }}</p>
            @endif
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.sync-type')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ trans("channel_connector::app.sync.types.{$job->sync_type}") }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.status')</p>
            <span class="inline-block rounded px-2 py-0.5 text-xs font-medium
                {{ $job->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : '' }}
                {{ $job->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : '' }}
                {{ $job->status === 'running' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : '' }}
                {{ $job->status === 'pending' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : '' }}
                {{ $job->status === 'retrying' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
            ">{{ trans("channel_connector::app.sync.status.{$job->status}") }}</span>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.started-at')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ $job->started_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.duration')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">
                @if ($job->started_at && $job->completed_at)
                    @php
                        $diff = $job->started_at->diff($job->completed_at);
                    @endphp
                    @if ($diff->h > 0)
                        {{ $diff->format('%hh %im %ss') }}
                    @elseif ($diff->i > 0)
                        {{ $diff->format('%im %ss') }}
                    @else
                        {{ $diff->format('%ss') }}
                    @endif
                @else
                    -
                @endif
            </p>
        </div>
    </div>

    {{-- Progress Section --}}
    <div class="mt-4">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('channel_connector::app.sync.fields.progress')</p>

            @include('channel_connector::admin.dashboard.components.sync-progress', ['job' => $job])
        </div>
    </div>

    {{-- Product Counters --}}
    <div class="mt-4 grid grid-cols-3 gap-4">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.total-products')</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $job->total_products ?? 0 }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.synced-products')</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $job->synced_products ?? 0 }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.sync.fields.failed-products')</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $job->failed_products ?? 0 }}</p>
        </div>
    </div>

    {{-- Error Details --}}
    @if (! empty($job->error_summary))
        <div class="mt-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('channel_connector::app.sync.errors.title')</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.sync.errors.product')</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.sync.errors.message')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($job->error_summary as $error)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 font-mono text-sm text-gray-800 dark:text-white">{{ $error['product_sku'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-red-600 dark:text-red-400">{{ implode(', ', $error['errors'] ?? []) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Retry History --}}
    @if ($job->retries && $job->retries->count() > 0)
        <div class="mt-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('channel_connector::app.dashboard.show.retry-history')</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">#</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.sync.fields.status')</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.sync.fields.synced-products')</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.sync.fields.failed-products')</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.sync.fields.started-at')</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.general.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($job->retries as $retry)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 text-gray-800 dark:text-white">{{ $retry->id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded px-2 py-0.5 text-xs font-medium
                                            {{ $retry->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : '' }}
                                            {{ $retry->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : '' }}
                                            {{ $retry->status === 'running' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                            {{ $retry->status === 'pending' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                        ">{{ trans("channel_connector::app.sync.status.{$retry->status}") }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-green-600 dark:text-green-400">{{ $retry->synced_products ?? 0 }}</td>
                                    <td class="px-4 py-3 text-red-600 dark:text-red-400">{{ $retry->failed_products ?? 0 }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-white">{{ $retry->started_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.channel_connector.dashboard.show', $retry->id) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                            @lang('channel_connector::app.acl.view')
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-admin::layouts>
