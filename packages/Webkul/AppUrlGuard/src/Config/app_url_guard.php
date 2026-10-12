<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Guard Override
    |--------------------------------------------------------------------------
    |
    | Explicit override for the guard. Null keeps the automatic behaviour
    | (disabled during unit tests and CI runs); tests may opt in with true.
    |
    */

    'enabled' => env('APP_URL_GUARD_ENABLED'),

    /*
    |--------------------------------------------------------------------------
    | CI Detection
    |--------------------------------------------------------------------------
    |
    | The guard stays completely inactive on CI runners, where APP_URL
    | routinely differs from the request host.
    |
    */

    'ci' => env('CI', false),
];
