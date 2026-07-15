@props([
    'activeTab' => 'general',
    'historyId' => null,
    'generalUrl' => '?',
    'historyUrl' => '?history',
    'tabItems' => [],
])

@php
    $darkModePreference = request()->cookie('dark_mode', 'auto');
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr" class="{{ $darkModePreference === 'dark' || $darkModePreference === '1' ? 'dark' : '' }}">
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

        <!-- <link rel="preload" as="image" href="{{ url('cache/logo/pim.png') }}"> -->

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

        {!! view_render_event('unopim.admin.layout.head') !!}
    </head>

    <body class="h-full dark:bg-cherry-800">
        {!! view_render_event('unopim.admin.layout.body.before') !!}

        <div id="app" class="h-full">
            <!-- Flash Message Blade Component -->
            <x-admin::flash-group />

            <!-- Confirm Modal Blade Component -->
            <x-admin::modal.history />

            <!-- Confirm Modal Blade Component -->
            <x-admin::modal.confirm />

            {!! view_render_event('unopim.admin.layout.content.before') !!}

            <!-- Page Header Blade Component -->
            <x-admin::layouts.header />

            <div
                class="flex gap-4 group/container {{ (request()->cookie('sidebar_collapsed') ?? 0) ? 'sidebar-collapsed' : 'sidebar-not-collapsed' }}"
                ref="appLayout"
            >
                <!-- Page Sidebar Blade Component -->
                <x-admin::layouts.sidebar />

                <div class="flex-1 max-w-full px-4 pt-3 pb-6 bg-transparent dark:bg-cherry-800 ltr:pl-[286px] rtl:pr-[286px] max-lg:!px-4 transition-all duration-300 group-[.sidebar-collapsed]/container:ltr:pl-[85px] group-[.sidebar-collapsed]/container:rtl:pr-[85px]">
                    @php
                        $hasPermission = bouncer()->hasPermission('history');

                        $activeTab = $hasPermission
                            ? (request()->has('history') ? 'history' : $activeTab)
                            : ($activeTab ?? 'general');

                        $currentQuery = request()->query();
                        $generalQuery = $currentQuery;
                        unset($generalQuery['history']);

                        $defaultGeneralUrl = request()->url().(count($generalQuery) ? '?'.http_build_query($generalQuery) : '?');
                        $defaultHistoryUrl = request()->url().'?'.http_build_query(array_merge($generalQuery, ['history' => 1]));

                        $generalUrl = $generalUrl === '?' ? $defaultGeneralUrl : $generalUrl;
                        $historyUrl = $historyUrl === '?history' ? $defaultHistoryUrl : $historyUrl;

                        $tabItems = count($tabItems)
                            ? $tabItems
                            : [
                                [
                                    'key'   => 'general',
                                    'url'   => $generalUrl,
                                    'label' => 'admin::app.components.layouts.sidebar.general',
                                ],
                            ];
                    @endphp

                    <div class="js-sticky-header sticky top-[57px] z-20 -mx-4 -mt-3 border-b border-gray-200 bg-unopim-primary-page px-4 pt-3 transition-shadow dark:border-gray-800 dark:bg-cherry-800">
                        {{ $pageHeader ?? '' }}

                        <x-admin::layouts.edit-tabs
                            :items="$tabItems"
                            :active="$activeTab"
                            :history-url="$historyUrl"
                            :show-history="$hasPermission"
                        >
                            {{ $tabs ?? '' }}
                        </x-admin::layouts.edit-tabs>
                    </div>

                    <div class="pt-4">
                        @if ($activeTab === 'general')
                            {{ $slot }}
                        @endif

                        {{ $tabContents ?? '' }}

                        @if ($activeTab === 'history')
                            {!! view_render_event('unopim.admin.layout.history.before') !!}

                            <x-admin::history src="{{ route('admin.history.index',[$entityName, ($historyId ?? request()->id)]) }}" >

                            </x-admin::history>

                            {!! view_render_event('unopim.admin.layout.history.after') !!}
                        @endif
                    </div>
                </div>
            </div>

            {!! view_render_event('unopim.admin.layout.content.after') !!}
        </div>

        {!! view_render_event('unopim.admin.layout.body.after') !!}

        @stack('scripts')
    </body>
</html>
