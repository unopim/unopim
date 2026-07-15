@php
    $darkModePreference = request()->cookie('dark_mode', 'auto');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar_AE']) ? 'rtl' : 'ltr' }}" class="{{ $darkModePreference === 'dark' || $darkModePreference === '1' ? 'dark' : '' }}">
    <head>
        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="base-url" content="{{ rtrim(config('app.url'), '/') }}">
        <meta name="admin-url" content="{{ config('app.admin_url') }}">
        <meta name="currency-code" content="{{ core()->getBaseCurrencyCode() }}">
        <meta http-equiv="content-language" content="{{ app()->getLocale() }}">

        <script>
            (() => {
                const getCookie = (name) => {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);

                    return parts.length === 2 ? parts.pop().split(';').shift() : null;
                };

                const preference = getCookie('dark_mode') || 'auto';
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const shouldUseDark = preference === 'dark' || preference === '1' || (preference === 'auto' && prefersDark);

                document.documentElement.classList.toggle('dark', shouldUseDark);
            })();
        </script>

        @stack('meta')

        {!! view_render_event('unopim.admin.layout.head.before') !!}

        @unoPimVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        />

        <link
            href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap"
            rel="stylesheet"
        />

        <link
            href="https://fonts.googleapis.com/css2?family=Inter&display=swap"
            rel="stylesheet"
        />
        

        @if ($favicon = core()->getConfigData('general.design.admin_logo.favicon'))
            <link
                type="image/x-icon"
                href="{{ Storage::url($favicon) }}"
                rel="shortcut icon"
                sizes="16x16"
            >
        @else
            <link
                type="image/x-icon"
                href="{{ unopim_asset('images/favicon.svg') }}"
                rel="shortcut icon"
                sizes="16x16"
            />
        @endif

        @stack('styles')

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
        </style>

        {{-- When the promo bar is present, offset the fixed header/sidebar by its height so the left menu is not cut. --}}
        <style>
            body:has(#unopim-promo-bar) .unopim-header { top: 3rem; }
            body:has(#unopim-promo-bar) #unopim-sidebar { top: 6.5rem; }
            body:has(#unopim-promo-bar) #unopim-sidebar-scroll { height: calc(100vh - 148px); }
        </style>

        {!! view_render_event('unopim.admin.layout.head') !!}
    </head>

    <body class="h-full bg-unopim-primary-soft bg-primary-50 bg-opacity-30 dark:bg-cherry-800 font-inter" style="font-family: inter;">
        {!! view_render_event('unopim.admin.layout.body.before') !!}

        <div id="app" class="h-full">
            <x-admin::flash-group />

            <x-admin::modal.confirm />

            {!! view_render_event('unopim.admin.layout.content.before') !!}

            <x-admin::layouts.header />

            <div
                class="flex gap-4 group/container {{ (request()->cookie('sidebar_collapsed') ?? 0) ? 'sidebar-collapsed' : 'sidebar-not-collapsed' }}"
                ref="appLayout"
            >
                <x-admin::layouts.sidebar />

                <div class="flex-1 max-w-full px-4 pt-3 pb-6 bg-transparent dark:bg-cherry-800 ltr:pl-[286px] rtl:pr-[286px] max-lg:!px-4 transition-all duration-300 group-[.sidebar-collapsed]/container:ltr:pl-[85px] group-[.sidebar-collapsed]/container:rtl:pr-[85px]">
                    @if (! request()->routeIs('admin.configuration.index'))
                        <x-admin::layouts.tabs />
                    @endif

                    {{ $slot }}
                </div>
            </div>

            {!! view_render_event('unopim.admin.layout.content.after') !!}
        </div>

        {!! view_render_event('unopim.admin.layout.body.after') !!}

        @stack('scripts')
    </body>
</html>
