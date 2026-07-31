@props([
    'currentPage' => 'currentPage',
    'totalPages'  => 'totalPages',
    'change'      => 'changePage',
    'showEdges'   => false,
])

<div
    v-if="{{ $totalPages }} > 1"
    {{ $attributes->merge(['class' => 'flex items-center justify-end border-t border-gray-200 pt-3 dark:border-cherry-800']) }}
>
    <div class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white p-1 shadow-sm dark:border-cherry-800 dark:bg-cherry-900">
        @if ($showEdges)
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-600 transition-all hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-300 dark:hover:bg-cherry-800"
                :disabled="{{ $currentPage }} <= 1"
                title="@lang('admin::app.components.datagrid.toolbar.pagination.first-page')"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.first-page')"
                @click="{{ $change }}(1)"
            >
                <span class="icon-chevron-left -mr-3 text-xl"></span>
                <span class="icon-chevron-left text-xl"></span>
            </button>
        @endif

        <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-600 transition-all hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-300 dark:hover:bg-cherry-800"
            :disabled="{{ $currentPage }} <= 1"
            title="@lang('admin::app.components.datagrid.toolbar.pagination.previous-page')"
            aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.previous-page')"
            @click="{{ $change }}({{ $currentPage }} - 1)"
        >
            <span class="icon-chevron-left text-xl"></span>
        </button>

        <div class="flex items-center gap-1.5 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
            <span>@lang('admin::app.components.pagination.page')</span>

            <input
                type="text"
                class="h-8 w-12 rounded-md border border-gray-200 bg-white px-2 text-center text-sm font-medium text-gray-800 outline-none transition-all hover:border-gray-300 focus:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-white"
                :value="{{ $currentPage }}"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.page-number')"
                @change="{{ $change }}(parseInt($event.target.value))"
                @keydown.enter.prevent="{{ $change }}(parseInt($event.target.value))"
            />

            <span>@lang('admin::app.components.datagrid.toolbar.of')</span>

            <span class="min-w-5 text-gray-800 dark:text-white" v-text="{{ $totalPages }}"></span>
        </div>

        <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-600 transition-all hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-300 dark:hover:bg-cherry-800"
            :disabled="{{ $currentPage }} >= {{ $totalPages }}"
            title="@lang('admin::app.components.datagrid.toolbar.pagination.next-page')"
            aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.next-page')"
            @click="{{ $change }}({{ $currentPage }} + 1)"
        >
            <span class="icon-chevron-right text-xl"></span>
        </button>

        @if ($showEdges)
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-600 transition-all hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-300 dark:hover:bg-cherry-800"
                :disabled="{{ $currentPage }} >= {{ $totalPages }}"
                title="@lang('admin::app.components.datagrid.toolbar.pagination.last-page')"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.last-page')"
                @click="{{ $change }}({{ $totalPages }})"
            >
                <span class="icon-chevron-right text-xl"></span>
                <span class="icon-chevron-right -ml-3 text-xl"></span>
            </button>
        @endif
    </div>
</div>
