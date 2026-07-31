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
    {{ $attributes->merge(['class' => 'group flex flex-col bg-white dark:bg-cherry-800 border border-primary-100 dark:border-cherry-700 rounded-2xl px-[22px] pt-[22px] pb-5 no-underline text-current transition-all duration-150 hover:border-primary-200 dark:hover:border-primary-500 hover:-translate-y-[3px] hover:shadow-xl']) }}
>
    <div class="flex items-start justify-between mb-4">
        <span class="flex items-center justify-center w-[46px] h-[46px] rounded-xl bg-primary-50 dark:bg-cherry-900 text-primary-600 transition-all duration-150 group-hover:bg-primary-600 group-hover:text-white">
            {{-- $icon is sourced from trusted server-side config, never user input; raw output is safe. --}}
            @if (str_starts_with(trim($icon), '<svg'))
                {!! $icon !!}
            @else
                <span class="text-2xl {{ $icon }}"></span>
            @endif
        </span>

        @if ($external)
            <span class="flex items-center justify-center w-[30px] h-[30px] rounded-lg text-gray-400 transition-all duration-150 group-hover:bg-primary-50 group-hover:text-primary-600 dark:group-hover:bg-cherry-900 group-hover:translate-x-0.5 group-hover:-translate-y-0.5">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17 17 7 M9 7h8v8"></path>
                </svg>
            </span>
        @endif
    </div>

    <h3 class="text-[16.5px] font-bold leading-tight tracking-[-0.015em] mb-1 text-gray-900 dark:text-white">
        {{ $title }}
    </h3>

    <p class="flex-1 m-0 text-[13.5px] !leading-[1.55] text-gray-500 dark:text-gray-300">
        {{ $slot }}
    </p>

    @if ($host)
        <div class="flex items-center gap-1.5 mt-[18px] pt-[15px] border-t border-primary-50 dark:border-cherry-700 text-[12.5px] font-semibold text-primary-600">
            <span class="font-medium text-gray-500">{{ $host }}</span>

            <svg class="opacity-0 -translate-x-1 transition-all duration-150 group-hover:opacity-100 group-hover:translate-x-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14 M13 6l6 6-6 6"></path>
            </svg>
        </div>
    @endif
</a>
