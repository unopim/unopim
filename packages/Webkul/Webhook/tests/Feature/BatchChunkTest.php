<?php

use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Services\WebhookService;

/*
 * A large product selection must be delivered in bounded chunks, not one
 * oversized request with every product loaded into memory.
 */
class ChunkedWebhookService extends WebhookService
{
    protected function batchChunkSize(): int
    {
        return 2;
    }
}

it('delivers a large batch in bounded chunks', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    Webhook::create([
        'name'      => 'Hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    $ids = collect(range(1, 5))
        ->map(fn (int $i): int => Product::factory()->simple()->create(['sku' => "CHUNK-{$i}"])->id)
        ->all();

    app(ChunkedWebhookService::class)->sendBatchForBulkEdit($ids);

    // 5 products / chunk size 2 => 3 chunks => 3 delivery requests to the webhook.
    Http::assertSentCount(3);
});
