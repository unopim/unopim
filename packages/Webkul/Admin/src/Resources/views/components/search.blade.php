@props([
    'name' => 'search',
])

{{--
    Standard admin search input — one look everywhere (rounded input + trailing
    icon-search glyph). A pure shell: everything else (placeholder, value/`::value`,
    `@keydown`, `data-*` hooks) is passed through to the input, so both the
    server-side datagrid search and client-side filters use the same component.
--}}
@php
    $ownsAccessibleName = $attributes->hasAny(['aria-label', ':aria-label']);

    $ariaLabelBinding = $ownsAccessibleName ? null : $attributes->get(':placeholder');

    /** Resolved here because a `->` inside the tag ends it for static HTML analysers. */
    $ariaLabel = $ownsAccessibleName || $ariaLabelBinding
        ? null
        : $attributes->get('placeholder', trans('admin::app.components.search.placeholder'));
@endphp

<div class="relative w-full">
    <input
        type="text"
        name="{{ $name }}"
        autocomplete="off"
        @if ($ariaLabelBinding)
            :aria-label="{{ $ariaLabelBinding }}"
        @elseif ($ariaLabel)
            aria-label="{{ $ariaLabel }}"
        @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border dark:border-cherry-800 bg-white dark:bg-cherry-900 py-1.5 ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400']) }}
    />

    <div class="icon-search pointer-events-none absolute ltr:right-2.5 rtl:left-2.5 top-2 flex items-center text-2xl text-gray-400" aria-hidden="true"></div>
</div>
