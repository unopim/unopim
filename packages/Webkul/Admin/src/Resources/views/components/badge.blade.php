@props(['variant' => 'neutral'])

@php
    $variants = [
        'success' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
        'danger'  => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
        'info'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
        'neutral' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium leading-none {$classes}"]) }}>
    {{ $slot }}
</span>
