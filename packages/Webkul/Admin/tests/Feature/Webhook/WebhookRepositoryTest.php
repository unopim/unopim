<?php

use Illuminate\Support\Facades\DB;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Repositories\WebhookRepository;

beforeEach(function () {
    DB::table('webhooks')->delete();
});

function makeWebhook(array $overrides = []): Webhook
{
    return app(WebhookRepository::class)->create(array_merge([
        'name'      => 'Endpoint '.uniqid(),
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['product.created', 'product.updated'],
    ], $overrides));
}

it('returns only active webhooks subscribed to the event', function () {
    makeWebhook(['events' => ['product.created']]);
    makeWebhook(['events' => ['product.updated']]);
    makeWebhook(['events' => ['product.created'], 'is_active' => false]);

    $matches = app(WebhookRepository::class)->getActiveForEvent('product.created');

    expect($matches)->toHaveCount(1);
    expect($matches->first()->subscribesTo('product.created'))->toBeTrue();
});

it('reports whether any active webhook subscribes to the event', function () {
    makeWebhook(['events' => ['product.updated']]);

    $repo = app(WebhookRepository::class);

    expect($repo->hasActiveForEvent('product.updated'))->toBeTrue();
    expect($repo->hasActiveForEvent('product.created'))->toBeFalse();
});

it('casts events, headers and is_active', function () {
    $webhook = makeWebhook([
        'secret'  => 's3cr3t',
        'headers' => ['Authorization' => 'Bearer x'],
    ]);

    $fresh = Webhook::find($webhook->id);

    expect($fresh->events)->toBeArray();
    expect($fresh->headers)->toBe(['Authorization' => 'Bearer x']);
    expect($fresh->is_active)->toBeTrue();
    expect($fresh->secret)->toBe('s3cr3t');
});
