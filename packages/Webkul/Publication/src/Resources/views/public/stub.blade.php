<!doctype html>
<html lang="{{ $locale ?? '' }}">
<head>
    <meta charset="utf-8">
</head>
<body>
    @if ($withdrawn || $payload === null)
        <h1>{{ trans('publication::app.public.withdrawn.heading') }}</h1>
    @else
        <pre>{{ json_encode($payload) }}</pre>
    @endif

    @if (isset($locales))
        <ul>
            @foreach ($locales as $availableLocale)
                <li>{{ $availableLocale->code }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
