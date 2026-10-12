<?php

use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Jobs\SendBulkProductWebhook;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    WebhookLog::query()->delete();
    Webhook::query()->delete();

    Webhook::create([
        'name'      => 'Import Hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['product.created', 'product.updated'],
    ]);
});

function unauditedProduct(string $sku): Product
{
    $product = Product::factory()->simple()->create(['sku' => $sku]);

    $product->audits()->delete();

    return $product->fresh();
}

it('delivers an import update webhook for products that carry no audit row', function () {
    $product = unauditedProduct('IMPORT-UPD-1');

    expect($product->audits()->count())->toBe(0);

    app(WebhookService::class)->sendBatchByIds([$product->id]);

    Http::assertSent(fn ($request) => ($request->data()['event'] ?? null) === WebhookService::EVENT_PRODUCT_UPDATED
        && collect($request->data()['data'] ?? [])->pluck('sku')->contains('IMPORT-UPD-1'));

    expect(WebhookLog::where('sku', 'IMPORT-UPD-1')->where('event', WebhookService::EVENT_PRODUCT_UPDATED)->count())
        ->toBe(1);
});

it('delivers when the queued import job runs end to end', function () {
    $first = unauditedProduct('IMPORT-UPD-2');
    $second = unauditedProduct('IMPORT-UPD-3');

    (new SendBulkProductWebhook([$first->id, $second->id], null, WebhookService::EVENT_PRODUCT_UPDATED))
        ->handle(app(WebhookService::class));

    expect(WebhookLog::whereIn('sku', ['IMPORT-UPD-2', 'IMPORT-UPD-3'])
        ->where('event', WebhookService::EVENT_PRODUCT_UPDATED)
        ->count())->toBe(2);
});

it('still delivers the created event for imported rows', function () {
    $product = unauditedProduct('IMPORT-NEW-1');

    (new SendBulkProductWebhook([$product->id], null, WebhookService::EVENT_PRODUCT_CREATED))
        ->handle(app(WebhookService::class));

    expect(WebhookLog::where('sku', 'IMPORT-NEW-1')->where('event', WebhookService::EVENT_PRODUCT_CREATED)->count())
        ->toBe(1);
});
