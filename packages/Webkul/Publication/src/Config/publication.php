<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Publication Queue
    |--------------------------------------------------------------------------
    |
    | Dedicated queue for publish jobs, so bulk publishing never competes with
    | the import pipeline. Run a worker with:
    |
    |   php artisan queue:work --queue=publication
    |
    */
    'queue' => env('PUBLICATION_QUEUE', 'publication'),

    /*
    |--------------------------------------------------------------------------
    | Publication Types
    |--------------------------------------------------------------------------
    |
    | Types are registered by consuming packages via mergeConfigFrom. Each entry
    | is keyed by type code and declares its payload builder, view, the attribute
    | group whose members are the only values allowed into a public payload, and
    | the URL prefix its routes are registered under.
    |
    */
    'types' => [],

    /*
    |--------------------------------------------------------------------------
    | Global Kill Switch
    |--------------------------------------------------------------------------
    |
    | A pre-routing, env-only, non-channel-scoped emergency switch for the
    | entire public tier. Deliberately NOT a core_config field: it must never
    | require a DB round trip or a channel to resolve, so it works even if the
    | channel/locale tables themselves are the thing being firefought.
    |
    */
    'enabled' => (bool) env('PUBLICATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Global Rate Limit Ceiling
    |--------------------------------------------------------------------------
    |
    | An unkeyed ceiling shared by every client, on top of the per-IP limit
    | below. TRUSTED_PROXIES must be the real proxy CIDR in production — never
    | "*" — or every request presents its own X-Forwarded-For and defeats the
    | per-IP limiter, at which point this global ceiling is the only thing
    | standing between one bad actor and every other visitor.
    |
    */
    'global_rate_limit' => (int) env('PUBLICATION_GLOBAL_RATE_LIMIT', 6000),

    /*
    |--------------------------------------------------------------------------
    | Asset Disk
    |--------------------------------------------------------------------------
    |
    | Never `filesystems.default` — that disk is `public`, symlinked into
    | `public/storage` and served directly by nginx with no publication check
    | at all. Documents referenced by a published payload are copied here
    | (Task 10, at build time) and served exclusively through the proxy below.
    |
    */
    'asset_disk' => env('PUBLICATION_ASSET_DISK', 'private'),
];
