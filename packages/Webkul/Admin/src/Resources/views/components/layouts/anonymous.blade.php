<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">

<head>
    <title>{{ $title ?? '' }}</title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="base-url" content="{{ url()->to('/') }}">
    <meta http-equiv="content-language" content="{{ app()->getLocale() }}">

    @stack('meta')

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

    {!! view_render_event('unopim.admin.layout.head') !!}
</head>

<body>
    {!! view_render_event('unopim.admin.layout.body.before') !!}

    <div id="app">
        <!-- Flash Message Blade Component -->
        <x-admin::flash-group />

        {!! view_render_event('unopim.admin.layout.content.before') !!}

                <!-- Page Content Blade Component -->
                {{ $slot }}

        {!! view_render_event('unopim.admin.layout.content.after') !!}
    </div>

    {!! view_render_event('unopim.admin.layout.body.after') !!}

    @stack('scripts')

    <script type="text/javascript">
        {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
    </script>
</body>

</html>
