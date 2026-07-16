@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex min-h-10 items-center justify-between gap-4 max-sm:flex-wrap']) }}>
    <div class="grid gap-1">
        <p class="text-xl font-bold leading-6 text-gray-800 dark:text-slate-50">
            {{ $title }}
        </p>

        @if ($description)
            <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                {{ $description }}
            </p>
        @endif

        {{ $content ?? '' }}
    </div>

    @isset($actions)
        <div class="flex items-center gap-2.5">
            {{ $actions }}
        </div>
    @endisset
</div>
