<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Webhook\Jobs\SendBulkEditProductWebhook;
use Webkul\Webhook\Listeners\Product as ProductListener;
use Webkul\Webhook\Models\Webhook;

/*
 * Bulk-edit webhook delivery must be queued like every other product webhook
 * path, never sent inline — a slow receiver would otherwise block the admin's
 * bulk-edit request.
 */
it('queues bulk-edit webhook delivery instead of sending inline', function () {
    Queue::fake();

    Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    app(ProductListener::class)->afterBulkEdit([1, 2, 3]);

    Queue::assertPushed(SendBulkEditProductWebhook::class);
});

it('does nothing when no webhook subscribes to the event', function () {
    Queue::fake();

    app(ProductListener::class)->afterBulkEdit([1, 2, 3]);

    Queue::assertNothingPushed();
});
