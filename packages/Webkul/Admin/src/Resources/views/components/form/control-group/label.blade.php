@php
    $isLocalizable = 'true' == $attributes->get('localizable') || 1 == $attributes->get('localizable');
    $class = $attributes->get('class', '');
    $isRequired = preg_match('/(^|\s)required(\s|$)/', $class);
    $labelClass = $isLocalizable && $isRequired ? trim(preg_replace('/\brequired\b/', '', $class)) : $class;
    $currentLocaleCode = $attributes->get('currentLocaleCode') ?? $attributes->get('current-locale-code') ?? core()->getRequestedLocaleCode();
@endphp

<label {{ $attributes->except(['class', 'localizable', 'currentLocaleCode', 'current-locale-code'])->merge(['class' => trim('flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium '.$labelClass)]) }}>
    <span class="inline-flex items-center gap-1">
        <span @class(['required' => $isLocalizable && $isRequired])>{{ $slot }}</span>

        <span class="unsaved-badge hidden">@lang('admin::app.components.form.unsaved-changes.field-badge')</span>
    </span>

@if($isLocalizable)
    <span class="icon-language uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200 text-gray-600 dark:!text-gray-600 ltr:ml-auto rtl:mr-auto">
        {{ $currentLocaleCode }}
    </span>
@endif
</label>
