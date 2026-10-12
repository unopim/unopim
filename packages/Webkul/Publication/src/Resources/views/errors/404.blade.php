<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('publication::app.public.404.heading') }}</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #faf9ff; color: #1f1c30; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        }
        .box {
            background: #fff; border: 1px solid #ede9fe; border-radius: 16px;
            padding: 2.5rem 2rem; max-width: 26rem; text-align: center;
            box-shadow: 0 10px 30px rgba(31, 28, 48, .08);
        }
        .mark {
            width: 3rem; height: 3rem; margin: 0 auto 1rem; border-radius: 50%;
            background: #f5f3ff; color: #6d28d9; font-size: 1.4rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        h1 { font-size: 1.35rem; margin: 0 0 .5rem; }
        p { color: #6b7280; font-size: .9rem; margin: 0; }
    </style>
</head>
<body>
    <div class="box">
        <div class="mark">!</div>
        <h1>{{ trans('publication::app.public.404.heading') }}</h1>
        <p>{{ trans('publication::app.public.404.notice') }}</p>
    </div>
</body>
</html>
