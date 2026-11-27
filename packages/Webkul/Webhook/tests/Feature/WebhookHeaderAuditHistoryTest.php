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

/**
 * Create a webhook with the given custom headers and URL.
 *
 * @param  array<string, string>  $headers
 */
function webhookAuditCreateWebhook(array $headers, string $url = 'https://8.8.8.8/hook'): Webhook
{
    return Webhook::create([
        'name'      => 'Audited hook',
        'url'       => $url,
        'is_active' => true,
        'events'    => ['product.created'],
        'headers'   => $headers,
    ]);
}

/**
 * Get the raw audit rows written for a webhook, oldest first.
 *
 * @return array<int, object>
 */
function webhookAuditRows(Webhook $webhook): array
{
    return DB::table('audits')
        ->where('auditable_type', Webhook::class)
        ->where('auditable_id', $webhook->id)
        ->orderBy('id')
        ->get()
        ->all();
}

/** Decode the headers stored in one audit column. */
function webhookAuditHeaders(object $audit, string $column): mixed
{
    $headers = json_decode((string) $audit->{$column}, true)['headers'] ?? null;

    return is_string($headers) ? json_decode($headers, true) : $headers;
}

/** Load the webhook audit credential backfill migration. */
function webhookAuditMaskMigration(): Migration
{
    return require base_path(
        'packages/Webkul/Webhook/src/Database/Migrations/2026_11_26_120000_mask_webhook_credentials_in_audits.php'
    );
}

/**
 * Insert an audit row with raw old and new values.
 *
 * @param  array<string, mixed>  $oldValues
 * @param  array<string, mixed>  $newValues
 */
function webhookAuditInsertRaw(string $auditableType, array $oldValues, array $newValues): int
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
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer create-secret', 'X-Shop' => 'acme']);

    [$created] = webhookAuditRows($webhook);

    expect(webhookAuditHeaders($created, 'new_values'))->toBe([
        'Authorization' => Webhook::MASKED_VALUE,
        'X-Shop'        => Webhook::MASKED_VALUE,
    ])
        ->and($created->new_values)->not->toContain('create-secret')
        ->and(json_decode($created->new_values, true)['url'])->toBe('https://8.8.8.8/'.Webhook::MASKED_VALUE);
});

it('masks both the old and the new header values in the audit written on update', function (): void {
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer old-secret']);

    $webhook->update(['headers' => ['Authorization' => 'Bearer new-secret', 'X-Api-Key' => 'key-secret']]);

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect(webhookAuditHeaders($updated, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_VALUE])
        ->and(webhookAuditHeaders($updated, 'new_values'))->toBe([
            'Authorization' => Webhook::MASKED_VALUE,
            'X-Api-Key'     => Webhook::MASKED_VALUE,
        ])
        ->and($updated->old_values.$updated->new_values)->not->toContain('secret');
});

it('still stores the real header values on the webhook itself', function (): void {
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer kept-secret']);

    expect($webhook->fresh()->headers)->toBe(['Authorization' => 'Bearer kept-secret']);
});

it('leaves an empty header list unchanged in the audit', function (): void {
    $webhook = webhookAuditCreateWebhook([]);

    [$created] = webhookAuditRows($webhook);

    expect(webhookAuditHeaders($created, 'new_values'))->toBe([]);
});

it('masks every kind of credential header a user may add', function (string $name, string $value): void {
    $webhook = webhookAuditCreateWebhook([$name => $value]);

    [$created] = webhookAuditRows($webhook);

    expect(webhookAuditHeaders($created, 'new_values'))->toBe([$name => Webhook::MASKED_VALUE])
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
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer removed-secret']);

    $webhook->update(['headers' => []]);

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect(webhookAuditHeaders($updated, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_VALUE])
        ->and(webhookAuditHeaders($updated, 'new_values'))->toBe([])
        ->and($updated->old_values)->not->toContain('removed-secret');
});

it('masks the header values in the audit written when a user deletes the webhook', function (): void {
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer deleted-secret']);
    $id = $webhook->id;

    $webhook->delete();

    $deleted = DB::table('audits')
        ->where('auditable_type', Webhook::class)
        ->where('auditable_id', $id)
        ->where('event', 'deleted')
        ->sole();

    expect(webhookAuditHeaders($deleted, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_VALUE])
        ->and($deleted->old_values)->not->toContain('deleted-secret');
});

it('keeps other fields readable in the audit when only the name changes', function (): void {
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer untouched-secret']);

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
    $webhook = webhookAuditCreateWebhook(['Authorization' => 'Bearer delivered-secret']);

    app(WebhookService::class)->deliverEvent($webhook, 'product.created', ['sku' => 'header-check'], 'header-check', 'Robot');

    Http::assertSent(fn (Request $request): bool => $request->header('Authorization') === ['Bearer delivered-secret']);

    $log = WebhookLog::query()->where('webhook_id', $webhook->id)->sole();

    expect(json_encode($log->getAttributes()))->not->toContain('delivered-secret');
});

it('masks the header value an admin saves through the webhook edit form', function (): void {
    $this->loginAsAdmin();
    $webhook = webhookAuditCreateWebhook([]);

    $this->put(route('webhook.update', $webhook->id), [
        'name'      => 'Audited hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => 1,
        'events'    => ['product.created'],
        'headers'   => [['key' => 'Authorization', 'value' => 'Bearer form-secret']],
    ])->assertRedirect(route('webhook.edit', $webhook->id));

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect($webhook->fresh()->headers)->toBe(['Authorization' => 'Bearer form-secret'])
        ->and(webhookAuditHeaders($updated, 'new_values'))->toBe(['Authorization' => Webhook::MASKED_VALUE])
        ->and($updated->new_values)->not->toContain('form-secret');
});

it('masks header values already stored in webhook audits and leaves other audits alone', function (): void {
    $webhookAudit = webhookAuditInsertRaw(
        Webhook::class,
        ['headers' => json_encode(['Authorization' => 'Bearer stored-old'])],
        ['headers' => json_encode(['Authorization' => 'Bearer stored-new']), 'name' => 'Stored hook'],
    );
    $otherAudit = webhookAuditInsertRaw('Webkul\\Core\\Models\\Channel', [], ['headers' => 'not a webhook']);

    webhookAuditMaskMigration()->up();

    $masked = DB::table('audits')->find($webhookAudit);

    expect(webhookAuditHeaders($masked, 'old_values'))->toBe(['Authorization' => Webhook::MASKED_VALUE])
        ->and(webhookAuditHeaders($masked, 'new_values'))->toBe(['Authorization' => Webhook::MASKED_VALUE])
        ->and(json_decode($masked->new_values, true)['name'])->toBe('Stored hook')
        ->and($masked->old_values.$masked->new_values)->not->toContain('stored-')
        ->and(json_decode(DB::table('audits')->find($otherAudit)->new_values, true))->toBe(['headers' => 'not a webhook']);
});

it('is safe to run the header value backfill twice', function (): void {
    $auditId = webhookAuditInsertRaw(Webhook::class, [], ['headers' => json_encode(['Authorization' => 'Bearer twice'])]);
    $migration = webhookAuditMaskMigration();

    $migration->up();
    $afterFirstRun = DB::table('audits')->find($auditId)->new_values;

    $migration->up();

    expect(DB::table('audits')->find($auditId)->new_values)->toBe($afterFirstRun);
});

it('leaves webhook audits without headers untouched', function (): void {
    $auditId = webhookAuditInsertRaw(Webhook::class, ['name' => 'Old'], ['name' => 'New']);
    $before = DB::table('audits')->find($auditId);

    webhookAuditMaskMigration()->up();

    $after = DB::table('audits')->find($auditId);

    expect($after->old_values)->toBe($before->old_values)
        ->and($after->new_values)->toBe($before->new_values);
});

it('leaves a stored null header value untouched', function (): void {
    $auditId = webhookAuditInsertRaw(Webhook::class, [], ['headers' => null]);
    $before = DB::table('audits')->find($auditId)->new_values;

    webhookAuditMaskMigration()->up();

    expect(DB::table('audits')->find($auditId)->new_values)->toBe($before);
});

it('masks stored header values it cannot read as a header map whole', function (string $headers): void {
    $auditId = webhookAuditInsertRaw(Webhook::class, [], ['headers' => $headers]);

    webhookAuditMaskMigration()->up();

    expect(json_decode(DB::table('audits')->find($auditId)->new_values, true)['headers'])->toBe(Webhook::MASKED_VALUE);
})->with([
    'invalid json' => ['{not json'],
    'plain string' => ['"Bearer"'],
]);

it('masks a header map that reaches the audit already decoded', function (): void {
    expect(Webhook::maskHeaders(['Authorization' => 'Bearer decoded-secret']))
        ->toBe(['Authorization' => Webhook::MASKED_VALUE]);
});

it('masks the credentials and query values of the URL in the audit written on create', function (): void {
    $webhook = webhookAuditCreateWebhook([], 'https://hook-user:hook-pass@8.8.8.8/hook?token=url-secret');

    [$created] = webhookAuditRows($webhook);
    $mask = Webhook::MASKED_VALUE;

    expect(json_decode($created->new_values, true)['url'])->toBe("https://{$mask}@8.8.8.8/{$mask}?token={$mask}")
        ->and($created->new_values)->not->toContain('hook-user')
        ->and($created->new_values)->not->toContain('hook-pass')
        ->and($created->new_values)->not->toContain('url-secret');
});

it('masks both the old and the new URL in the audit written on update', function (): void {
    $webhook = webhookAuditCreateWebhook([], 'https://8.8.8.8/hook?token=old-url-secret');

    $webhook->update(['url' => 'https://8.8.8.8/hook?token=new-url-secret']);

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');
    $masked = 'https://8.8.8.8/'.Webhook::MASKED_VALUE.'?token='.Webhook::MASKED_VALUE;

    expect(json_decode($updated->old_values, true)['url'])->toBe($masked)
        ->and(json_decode($updated->new_values, true)['url'])->toBe($masked)
        ->and($updated->old_values.$updated->new_values)->not->toContain('url-secret');
});

it('masks every credential a URL may carry', function (string $url, string $expected): void {
    expect(Webhook::maskUrl($url))->toBe(str_replace('MASK', Webhook::MASKED_VALUE, $expected));
})->with([
    'user and password'      => ['https://user:pass@example.com/hook', 'https://MASK@example.com/MASK'],
    'user only'              => ['https://token@example.com/hook', 'https://MASK@example.com/MASK'],
    'at sign in password'    => ['https://user:p@ss@example.com/hook', 'https://MASK@example.com/MASK'],
    'query token'            => ['https://example.com/hook?token=abc', 'https://example.com/MASK?token=MASK'],
    'several values'         => ['https://example.com/hook?a=1&secret=2', 'https://example.com/MASK?a=MASK&secret=MASK'],
    'value without key'      => ['https://example.com/hook?=abc', 'https://example.com/MASK?=MASK'],
    'item without value'     => ['https://example.com/?abc123secret&debug', 'https://example.com/?MASK&MASK'],
    'everything'             => ['http://u:p@example.com:8080/a/b?key=v#part', 'http://MASK@example.com:8080/MASK/MASK?key=MASK#part'],
    'chat path token'        => ['https://chat.example.com/services/T0A1B2C3D/B0E4F5G6H/Xy12AbCdEf34GhIjKl56MnOp', 'https://chat.example.com/MASK/MASK/MASK/MASK'],
    'id and token path'      => ['https://example.com/api/webhooks/123456789012345678/AbCdEf_12-GhIjKlMnOpQrStUvWxYz0123456789', 'https://example.com/MASK/MASK/MASK/MASK'],
    'uuid path segment'      => ['https://example.com/hook/0f8fad5b-d9cb-469f-a165-70867728950e/', 'https://example.com/MASK/MASK/'],
    'word-like path secret'  => ['https://example.com/hooks/plainlowercasesecret', 'https://example.com/MASK/MASK'],
    'short path secret'      => ['https://example.com/k9', 'https://example.com/MASK'],
    'at sign in path'        => ['https://example.com/p@th/hook', 'https://example.com/MASK/MASK'],
    'not an absolute url'    => ['example.com/hook/secret', 'MASK'],
]);

it('leaves the scheme, host, port and fragment of a URL readable', function (mixed $url): void {
    expect(Webhook::maskUrl($url))->toBe($url);
})->with([
    'host only'         => ['https://8.8.8.8'],
    'root path'         => ['https://8.8.8.8/'],
    'port and fragment' => ['https://example.com:8443/#part'],
    'empty query'       => ['https://example.com/?'],
    'token-like host'   => ['https://wh824cd96eb956450f94.free.beeceptor.com/'],
    'null'              => [null],
    'not a string'      => [42],
]);

it('masks a URL that is already masked to the same value', function (string $url): void {
    $masked = Webhook::maskUrl($url);

    expect(Webhook::maskUrl($masked))->toBe($masked);
})->with([
    'userinfo, path and query' => ['https://user:p@ss@example.com/a/b?token=abc&flag#part'],
    'not an absolute url'      => ['example.com/hook'],
]);

it('still stores and calls the real URL with its credentials', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $webhook = webhookAuditCreateWebhook([], 'https://8.8.8.8/hook?token=delivered-url-secret');

    app(WebhookService::class)->deliverEvent($webhook, 'product.created', ['sku' => 'url-check'], 'url-check', 'Robot');

    expect($webhook->fresh()->url)->toBe('https://8.8.8.8/hook?token=delivered-url-secret');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://8.8.8.8/hook?token=delivered-url-secret');
});

it('masks the URL token an admin saves through the webhook edit form', function (): void {
    $this->loginAsAdmin();
    $webhook = webhookAuditCreateWebhook([]);

    $this->put(route('webhook.update', $webhook->id), [
        'name'      => 'Audited hook',
        'url'       => 'https://8.8.8.8/hook?token=form-url-secret',
        'is_active' => 1,
        'events'    => ['product.created'],
    ])->assertRedirect(route('webhook.edit', $webhook->id));

    $updated = collect(webhookAuditRows($webhook))->firstWhere('event', 'updated');

    expect($webhook->fresh()->url)->toBe('https://8.8.8.8/hook?token=form-url-secret')
        ->and(json_decode($updated->new_values, true)['url'])->toBe('https://8.8.8.8/'.Webhook::MASKED_VALUE.'?token='.Webhook::MASKED_VALUE)
        ->and($updated->old_values.$updated->new_values)->not->toContain('form-url-secret');
});

it('masks URL credentials already stored in webhook audits and is safe to run twice', function (): void {
    $auditId = webhookAuditInsertRaw(
        Webhook::class,
        ['url' => 'https://user:stored-pass@8.8.8.8/hook'],
        ['url' => 'https://8.8.8.8/hook?token=stored-url-secret', 'name' => 'Stored hook'],
    );
    $migration = webhookAuditMaskMigration();
    $mask = Webhook::MASKED_VALUE;

    $migration->up();
    $afterFirstRun = DB::table('audits')->find($auditId);

    $migration->up();
    $afterSecondRun = DB::table('audits')->find($auditId);

    expect(json_decode($afterFirstRun->old_values, true)['url'])->toBe("https://{$mask}@8.8.8.8/{$mask}")
        ->and(json_decode($afterFirstRun->new_values, true)['url'])->toBe("https://8.8.8.8/{$mask}?token={$mask}")
        ->and(json_decode($afterFirstRun->new_values, true)['name'])->toBe('Stored hook')
        ->and($afterFirstRun->old_values.$afterFirstRun->new_values)->not->toContain('stored-')
        ->and($afterSecondRun->new_values)->toBe($afterFirstRun->new_values)
        ->and($afterSecondRun->old_values)->toBe($afterFirstRun->old_values);
});

it('leaves stored URLs without credentials untouched', function (): void {
    $auditId = webhookAuditInsertRaw(Webhook::class, ['url' => 'https://8.8.8.8'], ['url' => 'https://8.8.4.4/']);
    $before = DB::table('audits')->find($auditId);

    webhookAuditMaskMigration()->up();

    $after = DB::table('audits')->find($auditId);

    expect($after->old_values)->toBe($before->old_values)
        ->and($after->new_values)->toBe($before->new_values);
});

it('masks every path segment and an at sign password in the audit written on create', function (): void {
    $webhook = webhookAuditCreateWebhook([], 'https://hook-user:p@ss-secret@8.8.8.8/services/plainpathsecret/PathToken12Secret34Value');
    $mask = Webhook::MASKED_VALUE;

    [$created] = webhookAuditRows($webhook);

    expect(json_decode($created->new_values, true)['url'])->toBe("https://{$mask}@8.8.8.8/{$mask}/{$mask}/{$mask}")
        ->and($created->new_values)->not->toContain('plainpathsecret')
        ->and($created->new_values)->not->toContain('ss-secret')
        ->and($created->new_values)->not->toContain('PathToken12Secret34Value')
        ->and($webhook->fresh()->url)->toBe('https://hook-user:p@ss-secret@8.8.8.8/services/plainpathsecret/PathToken12Secret34Value');
});
