<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Services\WebhookService;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'webhook-test',
        'name'         => 'Webhook Test Connector',
        'channel_type' => 'salla',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [],
        'status'       => 'connected',
    ]);

    $this->webhookService = app(WebhookService::class);
});

it('generates a 64-character hex webhook token', function () {
    $token = $this->webhookService->generateWebhookToken();

    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    expect(ctype_xdigit($token))->toBeTrue();
});

it('generates unique tokens on each call', function () {
    $token1 = $this->webhookService->generateWebhookToken();
    $token2 = $this->webhookService->generateWebhookToken();

    expect($token1)->not->toBe($token2);
});

it('returns null callback URL when no webhook token in settings', function () {
    $url = $this->webhookService->getCallbackUrl($this->connector);

    expect($url)->toBeNull();
});

it('returns callback URL when webhook token exists in settings', function () {
    $this->connector->update([
        'settings' => ['webhook_token' => 'test_token_123'],
    ]);

    $url = $this->webhookService->getCallbackUrl($this->connector->fresh());

    expect($url)->not->toBeNull();
    expect($url)->toContain('test_token_123');
});

it('ensures webhook token creates and persists token if missing', function () {
    expect($this->connector->settings['webhook_token'] ?? null)->toBeNull();

    $token = $this->webhookService->ensureWebhookToken($this->connector);

    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);

    $this->connector->refresh();
    expect($this->connector->settings['webhook_token'])->toBe($token);
});

it('ensures webhook token returns existing token if present', function () {
    $this->connector->update([
        'settings' => ['webhook_token' => 'existing_token_abc'],
    ]);

    $token = $this->webhookService->ensureWebhookToken($this->connector->fresh());

    expect($token)->toBe('existing_token_abc');
});

it('registerWebhooks returns false when webhook_events missing from settings', function () {
    $this->connector->update([
        'settings' => ['webhook_token' => 'some_token'],
    ]);

    $result = $this->webhookService->registerWebhooks($this->connector->fresh());

    expect($result)->toBeFalse();
});

it('registerWebhooks returns false when webhook_token missing from settings', function () {
    $this->connector->update([
        'settings' => ['webhook_events' => ['product.created']],
    ]);

    $result = $this->webhookService->registerWebhooks($this->connector->fresh());

    expect($result)->toBeFalse();
});
