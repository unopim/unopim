<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\DataGrids\LogsDataGrid;
use Webkul\Webhook\Services\WebhookService;

/**
 * Regression coverage for issue #498 — "Webhook Always Shows 'Success'".
 *
 * The webhook_logs.status column must reflect the actual HTTP outcome:
 *   - 200/2xx     → status = 1 (success)
 *   - 4xx / 5xx   → status = 0 (failed)
 *   - connection error / timeout → status = 0 (failed) with the error in extra
 */
beforeEach(function () {
    $this->loginAsAdmin();

    DB::table('webhook_settings')->updateOrInsert(
        ['field' => 'webhook_url'],
        ['value' => 'https://1.1.1.1/hook', 'updated_at' => now(), 'created_at' => now()]
    );
    DB::table('webhook_settings')->updateOrInsert(
        ['field' => 'webhook_active'],
        ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
    );

    DB::table('webhook_logs')->delete();
});

afterEach(function () {
    DB::table('webhook_logs')->delete();
    DB::table('webhook_settings')->whereIn('field', ['webhook_url', 'webhook_active'])->delete();
});

it('logs status=1 with HTTP 200 in extra when the endpoint succeeds', function () {
    Http::fake([
        '1.1.1.1/*' => Http::response(['ok' => true], 200),
    ]);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendDataToWebhook($product);

    $log = DB::table('webhook_logs')->where('sku', $product->sku)->first();

    expect($log)->not->toBeNull();
    expect((int) $log->status)->toBe(1);

    $extra = json_decode((string) $log->extra, true);
    expect($extra['response']['status'] ?? null)->toBe(200);
});

it('logs status=0 when the endpoint returns 404', function () {
    Http::fake([
        '1.1.1.1/*' => Http::response(['error' => 'not found'], 404),
    ]);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendDataToWebhook($product);

    $log = DB::table('webhook_logs')->where('sku', $product->sku)->first();

    expect($log)->not->toBeNull();
    expect((int) $log->status)->toBe(0);

    $extra = json_decode((string) $log->extra, true);
    expect($extra['response']['status'] ?? null)->toBe(404);
});

it('logs status=0 when the endpoint returns 500', function () {
    Http::fake([
        '1.1.1.1/*' => Http::response(['error' => 'server'], 500),
    ]);

    $product = Product::factory()->create();

    app(WebhookService::class)->sendCreatedToWebhook($product);

    $log = DB::table('webhook_logs')->where('sku', $product->sku)->first();

    expect($log)->not->toBeNull();
    expect((int) $log->status)->toBe(0);

    $extra = json_decode((string) $log->extra, true);
    expect($extra['response']['status'] ?? null)->toBe(500);
});

it('logs status=0 with the error message when the connection cannot be made', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 6: Could not resolve host');
    });

    $product = Product::factory()->create();

    app(WebhookService::class)->sendDataToWebhook($product);

    $log = DB::table('webhook_logs')->where('sku', $product->sku)->first();

    expect($log)->not->toBeNull();
    expect((int) $log->status)->toBe(0);

    $extra = json_decode((string) $log->extra, true);
    expect($extra['response']['error'] ?? null)
        ->toContain('Could not resolve host');
});

/**
 * @param  int|null  $code  HTTP code to embed in extra.response.status, or null to leave it out.
 * @param  string|null  $error  Error message to embed in extra.response.error.
 */
function insertWebhookLogRow(int $status, ?int $code = null, ?string $error = null): void
{
    $extra = [];

    if ($code !== null) {
        $extra['response']['status'] = $code;
    }

    if ($error !== null) {
        $extra['response']['error'] = $error;
    }

    DB::table('webhook_logs')->insert([
        'sku'        => 'DISPLAY-'.uniqid(),
        'user'       => 'tester',
        'status'     => $status,
        'extra'      => $extra !== [] ? json_encode($extra) : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('renders the status column with the HTTP code for successful and failed rows', function () {
    DB::table('webhook_logs')->delete();

    insertWebhookLogRow(status: 1, code: 200);
    insertWebhookLogRow(status: 0, code: 404);
    insertWebhookLogRow(status: 0, code: 500);
    insertWebhookLogRow(status: 0, code: null, error: 'cURL error 6: Could not resolve host');

    // Render the datagrid in-process so we do not depend on the test client's
    // ability to resolve routes through a subdirectory-prefixed APP_URL.
    $payload = app(LogsDataGrid::class)->toJson();

    $statuses = collect(json_decode((string) $payload->getContent(), true)['records'] ?? [])
        ->pluck('status')
        ->map(fn (mixed $html) => strip_tags((string) $html))
        ->all();

    expect($statuses)->toContain('Success (200)');
    expect($statuses)->toContain('Failed (404)');
    expect($statuses)->toContain('Server Error (500)');
    expect($statuses)->toContain('Timeout/Error');
});
