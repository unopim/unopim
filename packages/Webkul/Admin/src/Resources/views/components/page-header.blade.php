@props([
    'title',
    'subtitle' => '',
    'back'     => null,
    'size'     => 'xl',
])

{{--
    Standard admin page header: title (with optional subtitle) on the left, an
    optional back link + an `actions` slot (buttons) grouped on the right.
    Reuses x-admin::heading for the title block so typography/spacing stay single-sourced.
--}}
<div {{ $attributes->merge(['class' => 'flex justify-between items-center gap-2.5 max-sm:flex-wrap']) }}>
    <x-admin::heading :title="$title" :subtitle="$subtitle" as="h1" :size="$size" class="max-w-[720px]" />

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
