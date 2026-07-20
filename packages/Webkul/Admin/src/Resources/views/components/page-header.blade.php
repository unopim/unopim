@props([
    'title',
    'subtitle'   => '',
    'back'       => null,
    'size'       => 'xl',
    'breadcrumb' => true,
])

<div {{ $attributes->merge(['class' => 'flex justify-between items-center gap-2.5 max-sm:flex-wrap']) }}>
    <x-admin::page-title :title="$title" :subtitle="$subtitle" :size="$size" :breadcrumb="$breadcrumb" class="max-w-[720px]" />

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
