@props(['defaultClass' => ''])

@php
    $logo = core()->getConfigData('general.design.admin_logo.logo_image');

    $darkLogo = core()->getConfigData('general.design.admin_logo.logo_image_dark');
@endphp

@if ($logo && ! $darkLogo)
    <img
        class="h-10"
        src="{{ Storage::url($logo) }}"
        alt="{{ config('app.name') }}"
    />
@else
    <img
        class="{{ trim('theme-logo-light block dark:hidden '.($logo ? 'h-10' : $defaultClass)) }}"
        src="{{ $logo ? Storage::url($logo) : unopim_asset('images/logo.svg') }}"
        alt="{{ config('app.name') }}"
    />

    <img
        class="{{ trim('theme-logo-dark hidden dark:block '.($darkLogo ? 'h-10' : $defaultClass)) }}"
        src="{{ $darkLogo ? Storage::url($darkLogo) : unopim_asset('images/dark_logo.svg') }}"
        alt="{{ config('app.name') }}"
    />
@endif
