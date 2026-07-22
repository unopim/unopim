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

    /*
    |--------------------------------------------------------------------------
    | Delivery-log Retention
    |--------------------------------------------------------------------------
    |
    | Days to keep webhook_logs rows before webhook:logs:prune deletes them.
    | Set to 0 to disable pruning (keep every log indefinitely).
    |
    */

    'retention_days' => env('WEBHOOK_LOG_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Subscribable Events
    |--------------------------------------------------------------------------
    |
    | The catalog of events a webhook may subscribe to, grouped by entity.
    | Adding a new entity's events here plus an EventServiceProvider listener
    | is all that is needed — the delivery core iterates subscriptions and
    | needs no change.
    |
    */

    'events' => [
        'product' => [
            'product.created' => 'webhook::app.events.product.created',
            'product.updated' => 'webhook::app.events.product.updated',
        ],
    ],
];
