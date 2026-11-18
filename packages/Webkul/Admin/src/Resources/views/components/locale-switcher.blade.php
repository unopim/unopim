@props([
    'locales' => [],
    'current' => null,
    'param'   => 'locale',
])

@php
    $currentName = collect($locales)->firstWhere('code', $current)['name'] ?? $current;
@endphp

<x-admin::dropdown>
    <x-slot:toggle>
        <button
            type="button"
            class="flex gap-x-1 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-primary-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
        >
            <span class="icon-language text-2xl"></span>

            {{ $currentName }}

            <span class="icon-chevron-down text-2xl"></span>
        </button>
    </x-slot>

    <x-slot:content class="!p-0">
        @foreach ($locales as $locale)
            <a
                href="{{ request()->fullUrlWithQuery([$param => $locale['code']]) }}"
                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale['code'] === $current ? 'bg-gray-100 dark:bg-cherry-800' : '' }}"
            >
                {{ $locale['name'] }}
            </a>
        @endforeach
    </x-slot>
</x-admin::dropdown>
