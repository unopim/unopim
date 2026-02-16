<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.sync.index.title') - {{ $connector->name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.sync.index.title') - {{ $connector->name }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <form action="{{ route('admin.channel_connector.sync.trigger', $connector->code) }}" method="POST" class="flex gap-2">
                @csrf
                <select name="sync_type" class="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="incremental">@lang('channel_connector::app.sync.types.incremental')</option>
                    <option value="full">@lang('channel_connector::app.sync.types.full')</option>
                </select>
                <button type="submit" class="primary-button">@lang('channel_connector::app.sync.actions.trigger-sync')</button>
            </form>
        </div>
    </div>

    <div class="mt-3.5">
        <div class="box-shadow rounded bg-white dark:bg-gray-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3">@lang('channel_connector::app.sync.fields.sync-type')</th>
                        <th class="px-4 py-3">@lang('channel_connector::app.sync.fields.status')</th>
                        <th class="px-4 py-3">@lang('channel_connector::app.sync.fields.progress')</th>
                        <th class="px-4 py-3">@lang('channel_connector::app.sync.fields.started-at')</th>
                        <th class="px-4 py-3">@lang('channel_connector::app.general.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3">{{ trans("channel_connector::app.sync.types.{$job->sync_type}") }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded px-2 py-0.5 text-xs font-medium
                                    {{ $job->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $job->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : '' }}
                                    {{ $job->status === 'running' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $job->status === 'pending' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                    {{ $job->status === 'retrying' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                ">{{ trans("channel_connector::app.sync.status.{$job->status}") }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $job->synced_products }}/{{ $job->total_products }} ({{ $job->failed_products }} @lang('channel_connector::app.sync.fields.failed-products'))</td>
                            <td class="px-4 py-3">{{ $job->started_at?->diffForHumans() ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.channel_connector.sync.show', [$connector->code, $job->job_id]) }}" class="text-blue-600 hover:underline dark:text-blue-400">@lang('channel_connector::app.general.view')</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">@lang('channel_connector::app.sync.index.empty')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
            <div class="mt-4">{{ $jobs->links() }}</div>
        @endif
    </div>
</x-admin::layouts>
