@props([
    'items' => [],
    'active' => 'general',
    'historyUrl' => '?history',
    'showHistory' => false,
])

<div class="tabs">
    <div class="flex gap-4 pt-1 border-b max-sm:hidden dark:border-gray-800">
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}">
                <div class="{{ ($item['key'] ?? null) === $active ? '-mb-px border-unopim-primary border-b-2 text-unopim-primary transition dark:text-unopim-primary-400' : '' }} pb-3 ltr:mr-5 rtl:ml-5 text-base font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                    @lang($item['label'])
                </div>
            </a>
        @endforeach

        {{ $slot }}

        @if ($showHistory)
            <a href="{{ $historyUrl }}">
                <div class="{{ $active === 'history' ? '-mb-px border-unopim-primary border-b-2 text-unopim-primary transition dark:text-unopim-primary-400' : '' }} pb-3 ltr:mr-5 rtl:ml-5 text-base font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                    @lang('admin::app.components.layouts.sidebar.history')
                </div>
            </a>
        @endif
    </div>
</div>
