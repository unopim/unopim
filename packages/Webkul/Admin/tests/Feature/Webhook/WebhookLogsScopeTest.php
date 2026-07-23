<?php

use Illuminate\Support\Facades\DB;
use Webkul\Webhook\DataGrids\LogsDataGrid;

function insertScopedWebhook(string $name): int
{
    return DB::table('webhooks')->insertGetId([
        'name'       => $name,
        'url'        => 'https://example.com/'.strtolower($name),
        'is_active'  => 1,
        'events'     => json_encode(['product.created']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function insertScopedLog(?int $webhookId, string $sku): void
{
    DB::table('webhook_logs')->insert([
        'webhook_id' => $webhookId,
        'sku'        => $sku,
        'user'       => 'tester',
        'event'      => 'product.created',
        'status'     => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function () {
    $this->loginAsAdmin();

    DB::table('webhook_logs')->delete();
    DB::table('webhooks')->delete();

    $this->mine = insertScopedWebhook('Mine');
    $this->other = insertScopedWebhook('Other');

    insertScopedLog($this->mine, 'SKU-MINE');
    insertScopedLog($this->other, 'SKU-OTHER-1');
    insertScopedLog($this->other, 'SKU-OTHER-2');
});

it('returns only the logs of the requested webhook', function () {
    $records = json_decode(resolve(LogsDataGrid::class)->forWebhook($this->mine)->toJson()->getContent(), true)['records'];

    expect($records)->toHaveCount(1);
    expect($records[0]['sku'])->toBe('SKU-MINE');
});

it('ignores a conflicting webhook_id sent by the client', function () {
    request()->merge(['webhook_id' => $this->other]);

    $records = json_decode(resolve(LogsDataGrid::class)->forWebhook($this->mine)->toJson()->getContent(), true)['records'];

    expect($records)->toHaveCount(1);
    expect($records[0]['sku'])->toBe('SKU-MINE');
});

it('lists every webhook log on the unscoped grid', function () {
    $records = json_decode(resolve(LogsDataGrid::class)->toJson()->getContent(), true)['records'];

    expect($records)->toHaveCount(3);
});

it('serves the per-webhook feed over its own route', function () {
    $response = $this->getJson(route('webhook.logs.for-webhook', $this->mine));

    $response->assertOk();

    expect($response->json('records'))->toHaveCount(1);
    expect($response->json('records.0.sku'))->toBe('SKU-MINE');
});

it('denies the per-webhook feed without the logs permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook']);

    $this->getJson(route('webhook.logs.for-webhook', $this->mine))->assertForbidden();
});
