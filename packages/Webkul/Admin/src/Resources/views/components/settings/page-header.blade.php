@props([
    'title',
    'info' => '',
    'back' => null,
])

<div class="flex gap-4 justify-between items-center mt-3.5 max-sm:flex-wrap">
    <div class="flex items-center gap-3">
        @if ($back)
            <x-admin::back-button :href="$back" />
        @endif

        <div class="flex flex-col gap-1">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">{{ $title }}</p>

            @if ($info)
                <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">{{ $info }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
