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

    @if ($release ?? false)
        <p class="release">{{ trans('publication::app.public.release.banner', ['sequence' => $release['sequence']]) }}
            {{ $release['is_current'] ? trans('publication::app.public.release.current') : trans('publication::app.public.release.superseded') }}
            <a href="{{ $release['current_url'] }}">{{ trans('publication::app.public.release.view-current') }}</a></p>
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
