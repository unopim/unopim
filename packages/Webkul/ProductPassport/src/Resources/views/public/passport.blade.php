@php
    $documentUrl = fn (array $document): string => route('publication.public.dpp.asset', ['uuid' => $uuid, 'path' => $document['path']]);
    $operator = $payload['operator'] ?? [];
    $hasOperator = trim(($operator['name'] ?? '').($operator['address'] ?? '').($operator['eu_representative'] ?? '')) !== '';
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('passport::app.public.title') }}</title>
    @if (! $withdrawn && (! empty($payload['identifier']['gtin']) || ! empty($payload['sections'])))
        {{-- Emitted only for a live (Published) passport: a withdrawn/redacted
             page renders a tombstone, so its frozen payload must never surface
             as machine-readable JSON-LD — matching the controller's Published
             gate on the content-negotiated branch.
             JSON-LD is data, not executable JS, so CSP script-src does not gate
             it and no nonce is required. JSON_HEX_TAG neutralises any </script>
             breakout from field values, so raw @json output stays XSS-safe. --}}
        <script type="application/ld+json">@json((new \Webkul\ProductPassport\Http\Resources\PassportJsonLdResource($payload))->toArray(request()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)</script>
    @endif
    <style>
        /* UnoPim brand palette (violet primary), light by design: a passport
           is an official document, so it does not follow the viewer's OS dark
           mode the way the admin panel does. */
        :root {
            --bg: #faf9ff; --card: #ffffff; --ink: #1f1c30; --muted: #6b7280;
            --line: #ede9fe; --border: #ddd6fe;
            --accent: #7c3aed; --accent-strong: #6d28d9; --accent-soft: #f5f3ff;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--bg); color: var(--ink); margin: 0;
            padding: 1rem; line-height: 1.5;
        }
        .wrap { max-width: 44rem; margin: 0 auto; }
        .switcher { margin-bottom: 1rem; position: relative; max-width: max-content; margin-left: auto; }
        .switcher summary {
            list-style: none; cursor: pointer; font-size: .8rem; color: var(--ink);
            border: 1px solid var(--border); border-radius: 999px; padding: .2rem .85rem; background: var(--card);
            text-transform: uppercase; letter-spacing: .03em;
        }
        .switcher summary::-webkit-details-marker { display: none; }
        .switcher summary::after { content: " \25BE"; color: var(--muted); }
        .switcher .menu {
            position: absolute; right: 0; margin-top: .35rem; z-index: 10; width: 15rem;
            background: var(--card); border: 1px solid var(--border); border-radius: 12px;
            padding: .5rem; box-shadow: 0 8px 24px rgba(31, 28, 48, .12);
        }
        .switcher .locale-search {
            width: 100%; font-size: .8rem; padding: .35rem .55rem; margin-bottom: .4rem;
            border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--ink);
        }
        .switcher .locale-list {
            max-height: 13rem; overflow-y: auto;
            display: grid; grid-template-columns: repeat(2, minmax(4.5rem, 1fr)); gap: .2rem;
        }
        .switcher .locale-list a {
            font-size: .78rem; text-decoration: none; color: var(--muted);
            border-radius: 8px; padding: .25rem .5rem; text-align: center;
        }
        .switcher .locale-list a:hover { background: var(--accent-soft); color: var(--accent-strong); }
        .switcher .locale-list a.active { color: #fff; background: var(--accent); }
        .switcher .locale-list a.is-hidden { display: none; }
        header.hero {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
            color: #fff; border-radius: var(--radius); padding: 1.5rem 1.25rem; margin-bottom: 1.25rem;
        }
        .badge {
            display: inline-block; font-size: .68rem; font-weight: 600; letter-spacing: .05em;
            text-transform: uppercase; color: #fff; background: rgba(255, 255, 255, .18);
            border-radius: 999px; padding: .2rem .65rem; margin-bottom: .55rem;
        }
        header.hero h1 { font-size: 1.55rem; margin: 0 0 .3rem; }
        .uuid { font-size: .72rem; color: rgba(255, 255, 255, .8); word-break: break-all; }
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: var(--radius);
            padding: 1rem 1.15rem; margin-bottom: 1rem;
        }
        .card h2 { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: var(--accent-strong); margin: 0 0 .75rem; }
        .ids { display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: .75rem; }
        .ids .k { font-size: .72rem; color: var(--muted); }
        .ids .v { font-size: 1rem; font-weight: 600; word-break: break-word; }
        dl.fields { margin: 0; }
        dl.fields > div { padding: .6rem 0; border-top: 1px solid var(--line); }
        dl.fields > div:first-child { border-top: 0; }
        dl.fields dt { font-size: .78rem; color: var(--muted); margin-bottom: .15rem; }
        dl.fields dd { margin: 0; }
        .docs { list-style: none; margin: 0; padding: 0; }
        .docs li { padding: .5rem 0; border-top: 1px solid var(--line); }
        .docs li:first-child { border-top: 0; }
        .docs a { color: var(--accent); text-decoration: none; font-weight: 600; }
        footer { color: var(--muted); font-size: .72rem; text-align: center; margin-top: 1.5rem; }
        .tombstone {
            background: var(--accent-soft); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 1rem 1.15rem; margin-bottom: 1rem;
        }
        @media print {
            body { background: #fff; }
            .switcher { display: none; }
            header.hero { background: var(--accent); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .card { break-inside: avoid; border-color: #ccc; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <details class="switcher">
        <summary>{{ $locale }}</summary>
        <div class="menu">
            <input type="search" class="locale-search" autocomplete="off"
                   placeholder="{{ trans('passport::app.public.search-locale') }}"
                   aria-label="{{ trans('passport::app.public.search-locale') }}">
            <div class="locale-list">
                @foreach ($locales as $availableLocale)
                    <a @class(['active' => $availableLocale->code === $locale])
                       data-code="{{ strtolower($availableLocale->code) }}"
                       href="{{ route('publication.public.dpp.show.locale', ['uuid' => $uuid, 'locale' => $availableLocale->code]) }}">{{ $availableLocale->code }}</a>
                @endforeach
            </div>
        </div>
    </details>

    @if ($withdrawn)
        <div class="tombstone">@include('passport::public.partials.tombstone')</div>
    @endif

    <header class="hero">
        <span class="badge">{{ trans('passport::app.public.badge') }}</span>
        <h1>{{ trans('passport::app.public.title') }}</h1>
        <div class="uuid">{{ $uuid }}</div>
    </header>

    <section class="card">
        <h2>{{ trans('passport::app.public.identifier.title') }}</h2>
        <div class="ids">
            <div>
                <div class="k">{{ trans('passport::app.public.identifier.gtin') }}</div>
                <div class="v">{{ $payload['identifier']['gtin'] ?? trans('passport::app.public.identifier.not-provided') }}</div>
            </div>
            <div>
                <div class="k">{{ trans('passport::app.public.identifier.model') }}</div>
                <div class="v">{{ $payload['identifier']['model'] ?? trans('passport::app.public.identifier.not-provided') }}</div>
            </div>
            <div>
                <div class="k">{{ trans('passport::app.public.identifier.batch') }}</div>
                <div class="v">{{ $payload['identifier']['batch'] ?? trans('passport::app.public.identifier.not-provided') }}</div>
            </div>
        </div>
    </section>

    @foreach ($payload['sections'] as $section)
        @if (! empty($section['fields']))
            <section class="card">
                <h2>{{ $section['label'] }}</h2>
                <dl class="fields">
                    @foreach ($section['fields'] as $field)
                        <div>
                            <dt>{{ $field['label'] }}</dt>
                            <dd>{{ $field['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
    @endforeach

    @if (! empty($payload['documents']))
        <section class="card">
            <h2>{{ trans('passport::app.public.documents.title') }}</h2>
            <ul class="docs">
                @foreach ($payload['documents'] as $document)
                    <li><a href="{{ $documentUrl($document) }}">{{ $document['label'] }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($hasOperator)
        <section class="card">
            <h2>{{ trans('passport::app.public.operator.title') }}</h2>
            @if (! empty($operator['name']))<div>{{ $operator['name'] }}</div>@endif
            @if (! empty($operator['address']))<div>{{ $operator['address'] }}</div>@endif
            @if (! empty($operator['eu_representative']))<div>{{ $operator['eu_representative'] }}</div>@endif
        </section>
    @endif

    <footer>{{ trans('passport::app.public.badge') }}</footer>
</div>
@if (! empty($cspNonce))
    <script nonce="{{ $cspNonce }}">
        (function () {
            var search = document.querySelector('.locale-search');
            if (!search) { return; }
            var links = Array.prototype.slice.call(document.querySelectorAll('.locale-list a'));
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                links.forEach(function (link) {
                    link.classList.toggle('is-hidden', q !== '' && link.getAttribute('data-code').indexOf(q) === -1);
                });
            });
        })();
    </script>
@endif
</body>
</html>
