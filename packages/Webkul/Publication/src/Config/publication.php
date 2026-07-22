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
];
