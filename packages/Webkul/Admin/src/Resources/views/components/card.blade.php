@props([
    'icon'     => '',
    'title'    => '',
    'url'      => '#',
    'host'     => '',
    'target'   => '_blank',
    'external' => false,
])

<a
    href="{{ $url }}"
    target="{{ $target }}"
    @if ($external || $target === '_blank') rel="noopener noreferrer" @endif
    {{ $attributes->merge(['class' => 'group flex flex-col bg-white dark:bg-cherry-800 border border-gray-200 dark:border-cherry-700 rounded-xl p-5 no-underline text-current transition-all hover:border-violet-200 dark:hover:border-violet-500 hover:shadow-lg hover:-translate-y-0.5']) }}
>
    <div class="flex items-start justify-between mb-4">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-violet-50 dark:bg-cherry-900 text-violet-600 transition-all group-hover:bg-violet-600 group-hover:text-white">
            {{-- $icon is sourced from trusted server-side config, never user input; raw output is safe. --}}
            @if (str_starts_with(trim($icon), '<svg'))
                {!! $icon !!}
            @else
                <span class="text-2xl {{ $icon }}"></span>
            @endif
        </span>

        @if ($external)
            <span class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-300 transition-all group-hover:text-violet-600 group-hover:bg-violet-50 dark:group-hover:bg-cherry-900">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17 17 7 M9 7h8v8"></path>
                </svg>
            </span>
        @endif
    </div>

    <h3 class="text-base font-bold leading-tight mb-1.5 text-gray-900 dark:text-white">
        {{ $title }}
    </h3>

    <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-300 m-0 flex-1">
        {{ $slot }}
    </p>

    @if ($host)
        <div class="flex items-center gap-1.5 mt-4 pt-3.5 border-t border-gray-100 dark:border-cherry-700 text-xs font-semibold text-violet-600">
            <span class="text-gray-400 font-medium">{{ $host }}</span>

            <svg class="opacity-0 -translate-x-1 transition-all group-hover:opacity-100 group-hover:translate-x-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14 M13 6l6 6-6 6"></path>
            </svg>
        </div>
    @endif
</a>
