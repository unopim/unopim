<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Jobs\SendBulkEditProductWebhook;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Listeners\Product as ProductListener;
use Webkul\Webhook\Models\Webhook;

it('does not send the per-product webhook for a row updated via bulk edit', function () {
    Queue::fake();

    Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    $product = Product::factory()->create();

    app(ProductListener::class)->afterUpdate($product, true);

    Queue::assertNotPushed(SendProductWebhook::class);
});

it('still sends the per-product webhook for a normal single-product edit', function () {
    Queue::fake();

    Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    $product = Product::factory()->create();
    $product->update(['sku' => $product->sku.'-changed']);

    app(ProductListener::class)->afterUpdate($product);

    Queue::assertPushed(SendProductWebhook::class);
});

it('sends exactly one batched webhook job for a bulk-edited product', function () {
    Queue::fake();

    Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    $product = Product::factory()->create();

    app(ProductListener::class)->afterUpdate($product, true);
    app(ProductListener::class)->afterBulkEdit([$product->id]);

    Queue::assertNotPushed(SendProductWebhook::class);
    Queue::assertPushed(SendBulkEditProductWebhook::class, 1);
});
