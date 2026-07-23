<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->webhookId = DB::table('webhooks')->insertGetId([
        'name'       => 'Logs Acl',
        'url'        => 'https://example.com/logs-acl',
        'is_active'  => 1,
        'events'     => json_encode(['product.created']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

afterEach(function () {
    DB::table('webhooks')->where('id', $this->webhookId)->delete();
});

it('should not display the webhook logs tab if user does not have logs permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook', 'configuration.webhook.edit']);

    $this->get(route('webhook.edit', $this->webhookId))
        ->assertOk()
        ->assertDontSee(route('webhook.edit', ['id' => $this->webhookId, 'logs' => 1]), false);
});

it('should display the webhook logs tab if user has logs permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook', 'configuration.webhook.edit', 'configuration.webhook.logs']);

    $this->get(route('webhook.edit', $this->webhookId))
        ->assertOk()
        ->assertSee(route('webhook.edit', ['id' => $this->webhookId, 'logs' => 1]), false);
});

it('should not render webhook logs content when user lacks logs permission even if logs query param is present', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook', 'configuration.webhook.edit']);

    $this->get(route('webhook.edit', ['id' => $this->webhookId, 'logs' => 1]))
        ->assertOk()
        ->assertDontSee(route('webhook.logs.for-webhook', $this->webhookId), false);
});
