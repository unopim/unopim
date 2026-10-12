<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function () {
    DB::table('webhooks')->delete();
    $this->loginAsAdmin();
    DB::table('webhook_logs')->delete();
});

afterEach(function () {
    DB::table('webhook_logs')->delete();
});

function insertWebhook(array $overrides = []): int
{
    return DB::table('webhooks')->insertGetId(array_merge([
        'name'       => 'Hook '.uniqid(),
        'url'        => 'https://1.1.1.1/hook',
        'is_active'  => 1,
        'events'     => json_encode(['product.created', 'product.updated']),
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

it('delivers to every active webhook subscribed to the event', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    insertWebhook(['url' => 'https://1.1.1.1/hook']);
    insertWebhook(['url' => 'https://8.8.8.8/hook']);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendCreatedToWebhook($product);

    Http::assertSent(fn ($request) => str_contains($request->url(), '1.1.1.1'));
    Http::assertSent(fn ($request) => str_contains($request->url(), '8.8.8.8'));
    Http::assertSentCount(2);
});

it('does not deliver to a webhook not subscribed to the event', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    insertWebhook(['url' => 'https://9.9.9.9/hook', 'events' => json_encode(['product.updated'])]);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendCreatedToWebhook($product);

    Http::assertNothingSent();
});

it('signs the payload with an HMAC header when a secret is set', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    insertWebhook(['url' => 'https://208.67.222.222/hook', 'secret' => 'topsecret']);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendCreatedToWebhook($product);

    Http::assertSent(function ($request) {
        $signature = $request->header('X-Unopim-Signature')[0] ?? '';
        $expected = 'sha256='.hash_hmac('sha256', $request->body(), 'topsecret');

        return $signature === $expected
            && ($request->header('X-Unopim-Event')[0] ?? '') === 'product.created';
    });
});

it('omits the signature header when no secret is set', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    insertWebhook(['url' => 'https://149.112.112.112/hook']);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendCreatedToWebhook($product);

    Http::assertSent(fn ($request) => empty($request->header('X-Unopim-Signature')));
});

it('attributes each delivery log to its webhook and event', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $webhookId = insertWebhook(['url' => 'https://1.0.0.1/hook']);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendCreatedToWebhook($product);

    $log = DB::table('webhook_logs')->where('sku', $product->sku)->first();

    expect($log)->not->toBeNull();
    expect((int) $log->webhook_id)->toBe($webhookId);
    expect($log->event)->toBe('product.created');
    expect((int) $log->http_code)->toBe(200);
});
