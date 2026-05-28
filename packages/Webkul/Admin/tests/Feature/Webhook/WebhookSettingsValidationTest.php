<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Webkul\Webhook\Http\Controllers\WebhookSettingsController;

/**
 * Validation coverage for the Webhook → Settings save endpoint.
 * Exercises WebhookSettingsController::store directly so the assertions
 * are environment-independent (APP_URL prefix has no bearing on routing).
 */
beforeEach(function () {
    $this->loginAsAdmin();

    DB::table('webhook_settings')->whereIn('field', ['webhook_url', 'webhook_active'])->delete();
});

afterEach(function () {
    DB::table('webhook_settings')->whereIn('field', ['webhook_url', 'webhook_active'])->delete();
});

function storeWebhookSettings(array $payload): mixed
{
    $controller = app(WebhookSettingsController::class);
    $request = Request::create('/admin/webhook/settings', 'POST', $payload);

    return $controller->store($request);
}

it('rejects a webhook_url that is not a real URL', function () {
    storeWebhookSettings(['webhook_active' => 1, 'webhook_url' => 'not-a-url']);
})->throws(ValidationException::class);

it('rejects a webhook_url that uses a non-http(s) scheme', function () {
    storeWebhookSettings(['webhook_active' => 1, 'webhook_url' => 'ftp://example.com/hook']);
})->throws(ValidationException::class);

it('requires a webhook_url when the webhook is being activated', function () {
    try {
        storeWebhookSettings(['webhook_active' => 1, 'webhook_url' => '']);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('webhook_url');
    }
});

it('allows an empty webhook_url when the webhook is inactive', function () {
    $response = storeWebhookSettings(['webhook_active' => 0, 'webhook_url' => '']);

    $payload = json_decode($response->getContent(), true);
    expect($payload['success'] ?? null)->toBeTrue();
});

it('saves a valid https webhook URL when the probe succeeds', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $response = storeWebhookSettings([
        'webhook_active' => 1,
        'webhook_url'    => 'https://wh58b8f638f57c37d003.free.beeceptor.com',
    ]);

    $payload = json_decode($response->getContent(), true);
    expect($payload['success'] ?? null)->toBeTrue();

    $stored = DB::table('webhook_settings')->where('field', 'webhook_url')->value('value');
    expect($stored)->toBe('https://wh58b8f638f57c37d003.free.beeceptor.com');
});

it('rejects the save when the probe returns a non-2xx response', function () {
    Http::fake([
        '*' => Http::response('not found', 404),
    ]);

    $response = storeWebhookSettings([
        'webhook_active' => 1,
        'webhook_url'    => 'https://example.test/not-a-hook',
    ]);

    expect($response->getStatusCode())->toBe(422);

    $payload = json_decode($response->getContent(), true);
    expect($payload['success'] ?? null)->toBeFalse();
    expect($payload['errors']['webhook_url'][0] ?? '')->toContain('404');

    $stored = DB::table('webhook_settings')->where('field', 'webhook_url')->value('value');
    expect($stored)->toBeNull();
});

it('rejects the save when the probe cannot reach the host', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 6: Could not resolve host');
    });

    $response = storeWebhookSettings([
        'webhook_active' => 1,
        'webhook_url'    => 'https://does-not-exist.invalid/hook',
    ]);

    expect($response->getStatusCode())->toBe(422);

    $payload = json_decode($response->getContent(), true);
    expect($payload['errors']['webhook_url'][0] ?? '')->toContain('could not be reached');

    $stored = DB::table('webhook_settings')->where('field', 'webhook_url')->value('value');
    expect($stored)->toBeNull();
});

it('skips the probe when the webhook is being deactivated', function () {
    Http::fake();

    $response = storeWebhookSettings([
        'webhook_active' => 0,
        'webhook_url'    => 'https://anything.test/hook',
    ]);

    $payload = json_decode($response->getContent(), true);
    expect($payload['success'] ?? null)->toBeTrue();

    Http::assertNothingSent();
});
