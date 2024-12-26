<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr" class="{{ (request()->cookie('dark_mode') ?? 0) ? 'dark' : '' }}">
    <head>
        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="base-url" content="{{ url()->to('/') }}">
        <meta name="currency-code" content="{{ core()->getBaseCurrencyCode() }}">
        <meta http-equiv="content-language" content="{{ app()->getLocale() }}">

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
                    <div class="tabs">    
                        @php
                            $hasPermission = bouncer()->hasPermission('history');

                            $activeTab = $hasPermission ?
                                request()->getQueryString() === 'history=' ? "history" : "general"
                                : 'general';

                            $items = [
                                [
                                    'url'    => '?',
                                    'name'   => 'admin::app.components.layouts.sidebar.general',
                                    'active' => $activeTab === 'general' ? true : false,
                                ],
                            ];

                            if ($hasPermission) {
                                $items[] = [
                                    'url'    => '?history',
                                    'name'   => 'admin::app.components.layouts.sidebar.history',
                                    'active' => $activeTab === 'history' ? true : false,
                                ];
                            }
                        @endphp

                        <div class="flex gap-4 mb-4 pt-2 border-b-2 max-sm:hidden dark:border-gray-800">
                            @foreach ($items as $key => $item)
                                <a href="{{ $item['url'] }}">
                                    <div class="{{  $item['active'] ? "-mb-px border-violet-700  border-b-2 transition" : '' }} pb-3.5 px-2.5 text-base  font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                        @lang($item['name'])
                                    </div>
                                </a>
                            @endforeach
                        </div>    
                    </div>
                    @if ($activeTab === 'general')    
                        {{ $slot }}
                    @endif

                    @if ($activeTab === 'history')
                        {!! view_render_event('unopim.settings.channels.list.before') !!}
                        
                        <x-admin::history src="{{ route('admin.history.index',[$entityName, request()->id]) }}" >
                            
                        </x-admin::history>

                        {!! view_render_event('unopim.settings.channels.list.after') !!}        
                    @endif
                </div>
            </div>

            {!! view_render_event('unopim.admin.layout.content.after') !!}
        </div>

        {!! view_render_event('unopim.admin.layout.body.after') !!}

        @stack('scripts')
    </body>
</html>
