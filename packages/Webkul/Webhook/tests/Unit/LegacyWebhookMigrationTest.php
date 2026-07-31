<?php

use Illuminate\Support\Facades\DB;

function legacyWebhookMigration(): object
{
    return require base_path('packages/Webkul/Webhook/src/Database/Migrations/2026_07_20_120200_seed_default_webhook.php');
}

function seedLegacySettings(string $url, int $active): void
{
    DB::table('webhook_settings')->whereIn('field', ['webhook_url', 'webhook_active'])->delete();

    DB::table('webhook_settings')->insert([
        ['field' => 'webhook_url', 'value' => $url, 'created_at' => now(), 'updated_at' => now()],
        ['field' => 'webhook_active', 'value' => (string) $active, 'created_at' => now(), 'updated_at' => now()],
    ]);
}

beforeEach(function () {
    $this->legacyUrl = 'https://legacy.example.com/hook';

    DB::table('webhooks')->where('url', $this->legacyUrl)->delete();
});

afterEach(function () {
    DB::table('webhook_settings')->whereIn('field', ['webhook_url', 'webhook_active'])->delete();
    DB::table('webhook_logs')->where('sku', 'LEGACY-MIGRATION')->delete();
    DB::table('webhooks')->where('url', $this->legacyUrl)->delete();
});

it('carries an active legacy webhook into the webhooks table', function () {
    seedLegacySettings($this->legacyUrl, 1);

    legacyWebhookMigration()->up();

    $webhook = DB::table('webhooks')->where('url', $this->legacyUrl)->first();

    expect($webhook)->not->toBeNull();
    expect((int) $webhook->is_active)->toBe(1);
    expect(json_decode($webhook->events, true))->toBe(['product.created', 'product.updated']);
});

it('keeps a disabled legacy webhook disabled', function () {
    seedLegacySettings($this->legacyUrl, 0);

    legacyWebhookMigration()->up();

    expect((int) DB::table('webhooks')->where('url', $this->legacyUrl)->value('is_active'))->toBe(0);
});

it('adopts orphaned delivery logs into the migrated webhook', function () {
    seedLegacySettings($this->legacyUrl, 1);

    DB::table('webhook_logs')->insert([
        'sku'        => 'LEGACY-MIGRATION',
        'user'       => 'tester',
        'status'     => 1,
        'webhook_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    legacyWebhookMigration()->up();

    $webhookId = DB::table('webhooks')->where('url', $this->legacyUrl)->value('id');

    expect((int) DB::table('webhook_logs')->where('sku', 'LEGACY-MIGRATION')->value('webhook_id'))->toBe((int) $webhookId);
});

it('does not duplicate a webhook that already points at the legacy url', function () {
    seedLegacySettings($this->legacyUrl, 1);

    legacyWebhookMigration()->up();
    legacyWebhookMigration()->up();

    expect(DB::table('webhooks')->where('url', $this->legacyUrl)->count())->toBe(1);
});

it('skips customers that never configured a legacy webhook', function () {
    seedLegacySettings('', 0);

    $before = DB::table('webhooks')->count();

    legacyWebhookMigration()->up();

    expect(DB::table('webhooks')->count())->toBe($before);
});

it('rolls back only the migrated webhook', function () {
    seedLegacySettings($this->legacyUrl, 1);

    $keptId = DB::table('webhooks')->insertGetId([
        'name'       => 'Added After Upgrade',
        'url'        => 'https://example.com/added-after-upgrade',
        'is_active'  => 1,
        'events'     => json_encode(['product.created']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    legacyWebhookMigration()->up();
    legacyWebhookMigration()->down();

    expect(DB::table('webhooks')->where('url', $this->legacyUrl)->exists())->toBeFalse();
    expect(DB::table('webhooks')->where('id', $keptId)->exists())->toBeTrue();

    DB::table('webhooks')->where('id', $keptId)->delete();
});
