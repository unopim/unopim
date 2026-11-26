<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\User\Tests\Concerns\UserAssertions;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Services\WebhookService;

uses(UserAssertions::class);

/** Creates a webhook with the given custom headers. */
function createAuditedWebhook(array $headers): Webhook
{
    return Webhook::create([
        'name'      => 'Audited hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['product.created'],
        'headers'   => $headers,
    ]);
}

/** The raw audit rows written for a webhook, oldest first. */
function webhookAuditRows(Webhook $webhook): array
{
    return DB::table('audits')
        ->where('auditable_type', Webhook::class)
        ->where('auditable_id', $webhook->id)
        ->orderBy('id')
        ->get()
        ->all();
}

/** The decoded headers stored in one audit column. */
function auditedHeaders(object $audit, string $column): mixed
{
    $headers = json_decode((string) $audit->{$column}, true)['headers'] ?? null;

    return is_string($headers) ? json_decode($headers, true) : $headers;
}

/** The header value backfill migration. */
function maskWebhookHeadersMigration(): Migration
{
    return require base_path(
        'packages/Webkul/Webhook/src/Database/Migrations/2026_11_26_120000_mask_webhook_header_values_in_audits.php'
    );
}

/** Inserts an audit row with raw old and new values. */
function insertRawAudit(string $auditableType, array $oldValues, array $newValues): int
{
    return DB::table('audits')->insertGetId([
        'event'          => 'updated',
        'auditable_type' => $auditableType,
        'auditable_id'   => 1,
        'old_values'     => json_encode($oldValues),
        'new_values'     => json_encode($newValues),
        'tags'           => 'webhooks',
        'history_id'     => 1,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
}

it('masks header values in the audit written on create and keeps the header names', function (): void {
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer create-secret', 'X-Shop' => 'acme']);

    [$created] = webhookAuditRows($webhook);

    expect(auditedHeaders($created, 'new_values'))->toBe([
        'Authorization' => Webhook::MASKED_HEADER_VALUE,
        'X-Shop'        => Webhook::MASKED_HEADER_VALUE,
    ])
        ->and($created->new_values)->not->toContain('create-secret')
        ->and(json_decode($created->new_values, true)['url'])->toBe('https://8.8.8.8/hook');
});

it('masks both the old and the new header values in the audit written on update', function (): void {
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer old-secret']);

    $webhook->update(['headers' => ['Authorization' => 'Bearer new-secret', 'X-Api-Key' => 'key-secret']]);

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect(auditedHeaders($updated, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_HEADER_VALUE])
        ->and(auditedHeaders($updated, 'new_values'))->toBe([
            'Authorization' => Webhook::MASKED_HEADER_VALUE,
            'X-Api-Key'     => Webhook::MASKED_HEADER_VALUE,
        ])
        ->and($updated->old_values.$updated->new_values)->not->toContain('secret');
});

it('still stores the real header values on the webhook itself', function (): void {
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer kept-secret']);

    expect($webhook->fresh()->headers)->toBe(['Authorization' => 'Bearer kept-secret']);
});

it('leaves an empty header list unchanged in the audit', function (): void {
    $webhook = createAuditedWebhook([]);

    [$created] = webhookAuditRows($webhook);

    expect(auditedHeaders($created, 'new_values'))->toBe([]);
});

it('masks every kind of credential header a user may add', function (string $name, string $value): void {
    $webhook = createAuditedWebhook([$name => $value]);

    [$created] = webhookAuditRows($webhook);

    expect(auditedHeaders($created, 'new_values'))->toBe([$name => Webhook::MASKED_HEADER_VALUE])
        ->and(json_decode($created->new_values, true)['headers'])->not->toContain($value);
})->with([
    'bearer token'          => ['Authorization', 'Bearer eyJhbGciOiJIUzI1NiJ9.payload.signature'],
    'basic credentials'     => ['Authorization', 'Basic dXNlcjpwYXNzd29yZA=='],
    'api key'               => ['X-Api-Key', 'sk_live_51HxYzAbCdEf'],
    'cookie'                => ['Cookie', 'session=abc123; remember=xyz789'],
    'quotes and unicode'    => ['X-Signature', 'tök"en\\with\'quötes☃'],
    'non credential header' => ['X-Shop-Region', 'eu-west-1'],
]);

it('masks the old value when a user removes a header', function (): void {
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer removed-secret']);

    $webhook->update(['headers' => []]);

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect(auditedHeaders($updated, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_HEADER_VALUE])
        ->and(auditedHeaders($updated, 'new_values'))->toBe([])
        ->and($updated->old_values)->not->toContain('removed-secret');
});

it('masks the header values in the audit written when a user deletes the webhook', function (): void {
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer deleted-secret']);
    $id = $webhook->id;

    $webhook->delete();

    $deleted = DB::table('audits')
        ->where('auditable_type', Webhook::class)
        ->where('auditable_id', $id)
        ->where('event', 'deleted')
        ->sole();

    expect(auditedHeaders($deleted, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_HEADER_VALUE])
        ->and($deleted->old_values)->not->toContain('deleted-secret');
});

it('keeps other fields readable in the audit when only the name changes', function (): void {
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer untouched-secret']);

    $webhook->update(['name' => 'Renamed hook']);

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect(json_decode($updated->new_values, true))->toBe(['name' => 'Renamed hook'])
        ->and(json_decode($updated->old_values, true))->toBe(['name' => 'Audited hook']);
});

it('keeps the signing secret out of the audit', function (): void {
    $webhook = Webhook::create([
        'name'      => 'Signed hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['product.created'],
        'secret'    => 'signing-secret',
    ]);

    [$created] = webhookAuditRows($webhook);

    expect(json_decode($created->new_values, true))->not->toHaveKey('secret')
        ->and($created->new_values)->not->toContain('signing-secret');
});

it('still sends the real header values when delivering and keeps them out of the delivery log', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $webhook = createAuditedWebhook(['Authorization' => 'Bearer delivered-secret']);

    app(WebhookService::class)->deliverEvent($webhook, 'product.created', ['sku' => 'header-check'], 'header-check', 'Robot');

    Http::assertSent(fn (Request $request): bool => $request->header('Authorization') === ['Bearer delivered-secret']);

    $log = WebhookLog::query()->where('webhook_id', $webhook->id)->sole();

    expect(json_encode($log->getAttributes()))->not->toContain('delivered-secret');
});

it('masks the header value an admin saves through the webhook edit form', function (): void {
    $this->loginAsAdmin();
    $webhook = createAuditedWebhook([]);

    $this->put(route('webhook.update', $webhook->id), [
        'name'      => 'Audited hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => 1,
        'events'    => ['product.created'],
        'headers'   => [['key' => 'Authorization', 'value' => 'Bearer form-secret']],
    ])->assertRedirect(route('webhook.edit', $webhook->id));

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect($webhook->fresh()->headers)->toBe(['Authorization' => 'Bearer form-secret'])
        ->and(auditedHeaders($updated, 'new_values'))->toBe(['Authorization' => Webhook::MASKED_HEADER_VALUE])
        ->and($updated->new_values)->not->toContain('form-secret');
});

it('masks header values already stored in webhook audits and leaves other audits alone', function (): void {
    $webhookAudit = insertRawAudit(
        Webhook::class,
        ['headers' => json_encode(['Authorization' => 'Bearer stored-old'])],
        ['headers' => json_encode(['Authorization' => 'Bearer stored-new']), 'name' => 'Stored hook'],
    );
    $otherAudit = insertRawAudit('Webkul\\Core\\Models\\Channel', [], ['headers' => 'not a webhook']);

    maskWebhookHeadersMigration()->up();

    $masked = DB::table('audits')->find($webhookAudit);

    expect(auditedHeaders($masked, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_HEADER_VALUE])
        ->and(auditedHeaders($masked, 'new_values'))->toBe(['Authorization' => Webhook::MASKED_HEADER_VALUE])
        ->and(json_decode($masked->new_values, true)['name'])->toBe('Stored hook')
        ->and($masked->old_values.$masked->new_values)->not->toContain('stored-')
        ->and(json_decode(DB::table('audits')->find($otherAudit)->new_values, true))->toBe(['headers' => 'not a webhook']);
});

it('is safe to run the header value backfill twice', function (): void {
    $auditId = insertRawAudit(Webhook::class, [], ['headers' => json_encode(['Authorization' => 'Bearer twice'])]);
    $migration = maskWebhookHeadersMigration();

    $migration->up();
    $afterFirstRun = DB::table('audits')->find($auditId)->new_values;

    $migration->up();

    expect(DB::table('audits')->find($auditId)->new_values)->toBe($afterFirstRun);
});

it('leaves webhook audits without headers untouched', function (): void {
    $auditId = insertRawAudit(Webhook::class, ['name' => 'Old'], ['name' => 'New']);
    $before = DB::table('audits')->find($auditId);

    maskWebhookHeadersMigration()->up();

    $after = DB::table('audits')->find($auditId);

    expect($after->old_values)->toBe($before->old_values)
        ->and($after->new_values)->toBe($before->new_values);
});

it('leaves stored header values it cannot read as a header map untouched', function (mixed $headers): void {
    $auditId = insertRawAudit(Webhook::class, [], ['headers' => $headers]);
    $before = DB::table('audits')->find($auditId)->new_values;

    maskWebhookHeadersMigration()->up();

    expect(DB::table('audits')->find($auditId)->new_values)->toBe($before);
})->with([
    'null'         => [null],
    'invalid json' => ['{not json'],
    'plain string' => ['"Bearer"'],
]);
