<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Webhook\Jobs\SendBulkProductWebhook;
use Webkul\Webhook\Listeners\Product as ProductListener;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Services\WebhookService;

function silenceExistingWebhooks(): void
{
    Webhook::query()->update(['is_active' => false]);
}

function subscribeWebhook(array $events): Webhook
{
    return Webhook::create([
        'name'      => 'Import Hook '.implode('-', $events),
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => $events,
    ]);
}

it('queues a product.created webhook for rows the import inserted', function () {
    Queue::fake();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_CREATED]);

    app(ProductListener::class)->afterBulkUpdate([1, 2], [1, 2], []);

    Queue::assertPushed(SendBulkProductWebhook::class, 1);
});

it('queues a product.updated webhook for rows the import updated', function () {
    Queue::fake();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_UPDATED]);

    app(ProductListener::class)->afterBulkUpdate([3], [], [3]);

    Queue::assertPushed(SendBulkProductWebhook::class, 1);
});

it('queues both events when one import batch creates and updates products', function () {
    Queue::fake();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_CREATED, WebhookService::EVENT_PRODUCT_UPDATED]);

    app(ProductListener::class)->afterBulkUpdate([1, 2, 3], [1, 2], [3]);

    Queue::assertPushed(SendBulkProductWebhook::class, 2);
});

it('sends import webhooks on the webhooks queue', function () {
    Queue::fake();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_CREATED, WebhookService::EVENT_PRODUCT_UPDATED]);

    app(ProductListener::class)->afterBulkUpdate([1, 2], [1], [2]);

    Queue::assertPushed(
        SendBulkProductWebhook::class,
        fn (SendBulkProductWebhook $job): bool => $job->queue === 'webhooks'
    );
});

it('does not queue a created webhook when nobody subscribes to product.created', function () {
    Queue::fake();

    silenceExistingWebhooks();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_UPDATED]);

    app(ProductListener::class)->afterBulkUpdate([1], [1], []);

    Queue::assertNothingPushed();
});

it('treats a legacy payload without the split ids as updates', function () {
    Queue::fake();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_UPDATED]);

    app(ProductListener::class)->afterBulkUpdate([7, 8]);

    Queue::assertPushed(SendBulkProductWebhook::class, 1);
});

it('does nothing when no webhook subscribes at all', function () {
    Queue::fake();

    silenceExistingWebhooks();

    app(ProductListener::class)->afterBulkUpdate([1, 2], [1], [2]);

    Queue::assertNothingPushed();
});

it('runs the import webhook job without an admin instead of fatalling', function () {
    silenceExistingWebhooks();

    subscribeWebhook([WebhookService::EVENT_PRODUCT_CREATED]);

    $job = new SendBulkProductWebhook([], null, WebhookService::EVENT_PRODUCT_CREATED);

    $job->handle(app(WebhookService::class));
})->throwsNoExceptions();
