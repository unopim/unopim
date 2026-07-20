<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Loopback Opt-In
    |--------------------------------------------------------------------------
    |
    | Allows webhook delivery to loopback addresses for local E2E testing
    | (e.g. Playwright delivery tests). Off in production by default.
    |
    */

    'allow_loopback' => env('WEBHOOK_ALLOW_LOOPBACK', false),
];
