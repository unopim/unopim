@props(['provider'])

<a
    href="{{ $provider->getRedirectUrl() }}"
    class="flex items-center justify-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-cherry-600 bg-white dark:bg-cherry-800 text-sm font-semibold text-gray-700 dark:text-gray-100 shadow-sm hover:bg-gray-50 hover:border-gray-400 hover:shadow-md dark:hover:bg-cherry-700 dark:hover:border-cherry-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-cherry-800 active:scale-[0.99] transition-all duration-150"
    aria-label="{{ $provider->getLabel() }}"
>
    @if ($icon = $provider->getIconView())
        @include($icon)
    @endif

    {{ $provider->getLabel() }}
</a>
