@props([
    'href',
    'label' => null,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'transparent-button']) }}
>
    {{ $label ?? trans('admin::app.settings.system-settings.back') }}
</a>
