@props([
    'href',
    'label' => null,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 dark:border-cherry-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 transition-all shrink-0']) }}
    aria-label="{{ $label ?? trans('admin::app.settings.system-settings.back') }}"
>
    <span class="icon-left rtl:rotate-180 text-xl"></span>
</a>
