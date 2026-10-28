@php
    $favicon = core()->getConfigData('general.design.admin_logo.favicon');

    $darkFavicon = core()->getConfigData('general.design.admin_logo.favicon_dark');
@endphp

<link
    type="image/x-icon"
    href="{{ $favicon ? Storage::url($favicon) : unopim_asset('images/favicon.svg') }}"
    rel="shortcut icon"
    sizes="16x16"
    @if ($darkFavicon) media="(prefers-color-scheme: light)" @endif
/>

@if ($darkFavicon)
    <link
        type="image/x-icon"
        href="{{ Storage::url($darkFavicon) }}"
        rel="shortcut icon"
        sizes="16x16"
        media="(prefers-color-scheme: dark)"
    />
@endif
