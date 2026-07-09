@props([
    'title',
    'info'  => '',
    'icon'  => null,
    'href'  => '#',
])

<a
    href="{{ $href }}"
    data-settings-row
    data-search="{{ strtolower(trim($title.' '.$info)) }}"
    {{ $attributes->merge(['class' => 'flex items-center gap-4 p-4 border-b border-gray-200 dark:border-cherry-800 last:border-b-0 no-underline text-current hover:bg-gray-50 dark:hover:bg-cherry-800 transition-colors']) }}
>
    @if ($icon)
        <span class="{{ $icon }} text-2xl text-gray-500 dark:text-gray-300 shrink-0"></span>
    @endif

    <div class="flex flex-col min-w-0">
        <p class="font-semibold text-gray-800 dark:text-slate-50">{{ $title }}</p>

        @if ($info)
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $info }}</p>
        @endif
    </div>

    <span class="icon-chevron-right text-2xl text-gray-400 ltr:ml-auto rtl:mr-auto rtl:rotate-180 shrink-0"></span>
</a>
