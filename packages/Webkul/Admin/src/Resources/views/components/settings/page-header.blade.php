@props([
    'title',
    'info' => '',
    'back' => null,
])

{{-- Standard admin edit-page header: title (with optional info) on the left,
     the back link + page actions grouped on the right. --}}
<div class="flex justify-between items-center gap-2.5 mt-3.5 max-sm:flex-wrap">
    <div class="flex flex-col gap-1">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">{{ $title }}</p>

        @if ($info)
            <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">{{ $info }}</p>
        @endif
    </div>

    @if ($back || isset($actions))
        <div class="flex gap-x-2.5 items-center shrink-0">
            @if ($back)
                <x-admin::back-button :href="$back" />
            @endif

            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    @endif
</div>
