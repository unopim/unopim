<?php

use Illuminate\Support\Carbon;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Services\RecentProductAudits;
use Webkul\Webhook\Services\WebhookService;

it('reports the update rather than the creation when both audits share a timestamp', function () {
    $product = Product::factory()->create();

    $product->update(['sku' => $product->sku.'-changed']);

    $changes = app(WebhookService::class)->getProductChangesForWebhook($product);

    expect($changes)->not->toBeEmpty()
        ->and($changes['changed'] ?? [])->toHaveKey('sku');
});

it('still reports the change when the write took longer than the old two second window', function () {
    $product = Product::factory()->create();

    $product->update(['sku' => $product->sku.'-changed']);

    Carbon::setTestNow(now()->addSeconds(30));

    $changes = app(WebhookService::class)->getProductChangesForWebhook($product);

    Carbon::setTestNow();

    expect($changes)->not->toBeEmpty();
});

it('reports nothing for a product that was not written in this request', function () {
    $product = Product::factory()->create();

    $product->update(['sku' => $product->sku.'-changed']);

    Carbon::setTestNow(now()->addHours(3));

    $changes = app(WebhookService::class)->getProductChangesForWebhook($product);

    Carbon::setTestNow();

    expect($changes)->toBe([]);
});

it('remembers the audit written for a product during this request', function () {
    $product = Product::factory()->create();

    $product->update(['sku' => $product->sku.'-changed']);

    $audit = app(RecentProductAudits::class)->for($product->id);

    expect($audit)->not->toBeNull()
        ->and($audit->event)->toBe('updated');
});
