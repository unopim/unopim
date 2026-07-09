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
    {{ $attributes->merge(['class' => 'group flex items-center gap-4 p-4 border-b border-gray-200 dark:border-cherry-700 last:border-b-0 no-underline text-current hover:bg-gray-50 dark:hover:bg-cherry-900 transition-colors']) }}
>
    @if ($icon)
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-violet-50 dark:bg-cherry-900 shrink-0 transition-all duration-150 group-hover:bg-violet-600">
            {{-- Colour is on the glyph itself so group-hover reliably flips it to white. --}}
            <span class="{{ $icon }} text-2xl text-violet-600 dark:text-violet-300 group-hover:text-white"></span>
        </span>
    @endif

    <div class="flex flex-col min-w-0">
        <p class="font-semibold text-gray-800 dark:text-slate-50">{{ $title }}</p>

        @if ($info)
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $info }}</p>
        @endif
    </div>

    <span class="icon-chevron-right text-2xl text-gray-400 dark:text-gray-500 ltr:ml-auto rtl:mr-auto rtl:rotate-180 shrink-0 transition-transform duration-150 ltr:group-hover:translate-x-1 rtl:group-hover:-translate-x-1 group-hover:text-violet-600 dark:group-hover:text-violet-300"></span>
</a>
