@props([
    'placeholder'  => trans('admin::app.components.search.placeholder'),
    'clearWhen'    => null,
    'clearAction'  => null,
    'iconPosition' => 'right',
])

<div class="relative w-full">
    <input
        type="text"
        placeholder="{{ $placeholder }}"
        aria-label="{{ $placeholder }}"
        {{ $attributes->merge(['class' => $iconPosition === 'left'
            ? 'block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all ltr:pl-10 ltr:pr-10 rtl:pl-10 rtl:pr-10 hover:border-gray-400 focus:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400'
            : 'block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3 hover:border-gray-400 focus:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400'
        ]) }}
    />

    <span @class([
        'icon-search pointer-events-none absolute top-1.5 flex items-center text-2xl',
        'ltr:left-3 rtl:right-3' => $iconPosition === 'left',
        'ltr:right-3 rtl:left-3' => $iconPosition !== 'left' && ! $clearWhen,
        'ltr:right-8 rtl:left-8' => $iconPosition !== 'left' && $clearWhen,
    ])></span>

    @if ($clearWhen && $clearAction)
        <button
            type="button"
            class="icon-cancel absolute top-2 flex items-center text-lg ltr:right-3 rtl:left-3"
            v-if="{{ $clearWhen }}"
            @click="{{ $clearAction }}"
        >
        </button>
    @endif
</div>
