<?php

use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Services\WebhookService;

/*
 * Each batch delivery log row must carry only its own product's slice, not the
 * whole batch payload — storing the full payload per row was O(N²).
 */
it('stores only the per-product slice in each batch log row', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    Webhook::create([
        'name'      => 'Hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    $first = Product::factory()->simple()->create(['sku' => 'BATCH-A']);
    $second = Product::factory()->simple()->create(['sku' => 'BATCH-B']);

    app(WebhookService::class)->sendBatchForBulkEdit([$first->id, $second->id]);

    $logs = WebhookLog::all();

    expect($logs)->toHaveCount(2);

    $logs->each(fn (WebhookLog $log) => expect($log->extra['payload']['data'] ?? [])->toHaveCount(1));
});
