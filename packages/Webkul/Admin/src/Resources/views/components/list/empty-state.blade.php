@props([
    'icon'        => null,
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex min-h-[140px] flex-col items-center justify-center rounded-md border border-dashed border-gray-300 px-6 py-8 text-center dark:border-cherry-800']) }}>
    @if ($icon)
        <span class="{{ $icon }} inline-flex text-3xl text-gray-400 dark:text-gray-500"></span>
    @endif

    <p class="{{ $icon ? 'mt-2 ' : '' }}text-sm font-semibold text-gray-700 dark:text-gray-200">
        {{ $title }}
    </p>

    @if ($description)
        <p class="mt-2 max-w-md text-xs leading-5 text-gray-500 dark:text-gray-300">
            {{ $description }}
        </p>
    @endif
</div>
