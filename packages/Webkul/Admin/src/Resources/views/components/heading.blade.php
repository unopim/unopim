@props([
    'title'    => null,
    'subtitle' => '',
    'as'       => 'p',
    'size'     => 'md',
])

@php
    $titleClass = match ($size) {
        'sm'    => 'text-sm font-semibold',
        'lg'    => 'text-[16.5px] font-bold leading-tight',
        'xl'    => 'text-xl font-bold',
        default => 'text-base font-semibold',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    <{{ $as }} class="{{ $titleClass }} text-gray-800 dark:text-slate-50">{{ $title ?? $slot }}</{{ $as }}>

    @if ($subtitle)
        <p class="text-sm text-gray-600 dark:text-gray-300 leading-[140%]">{{ $subtitle }}</p>
    @endif
</div>
