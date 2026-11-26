@props(['variant' => 'neutral'])

@php
    $classes = \Webkul\Admin\Helpers\BadgeVariants::classesFor($variant);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium leading-none {$classes}"]) }}>
    {{ $slot }}
</span>
