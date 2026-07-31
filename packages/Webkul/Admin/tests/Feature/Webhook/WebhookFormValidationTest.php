<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Webhook\Http\Requests\WebhookForm;

function webhookRules(): array
{
    $request = WebhookForm::create('/admin/configuration/webhook/create', 'POST');
    $request->setContainer(app());

    return $request->rules();
}

function validateWebhook(array $payload): Illuminate\Contracts\Validation\Validator
{
    return Validator::make($payload, webhookRules());
}

$valid = [
    'name'   => 'My Hook',
    'url'    => 'https://1.1.1.1/hook',
    'events' => ['product.created'],
];

it('accepts a valid webhook payload', function () use ($valid) {
    expect(validateWebhook($valid)->fails())->toBeFalse();
});

it('requires a name', function () use ($valid) {
    $payload = $valid;
    unset($payload['name']);

    expect(validateWebhook($payload)->fails())->toBeTrue();
});

it('rejects a non-http(s) url scheme', function () use ($valid) {
    expect(validateWebhook([...$valid, 'url' => 'ftp://example.com/hook'])->fails())->toBeTrue();
});

it('rejects a url that resolves to a private address (SSRF)', function () use ($valid) {
    expect(validateWebhook([...$valid, 'url' => 'https://127.0.0.1/hook'])->fails())->toBeTrue();
});

it('requires at least one event', function () use ($valid) {
    expect(validateWebhook([...$valid, 'events' => []])->fails())->toBeTrue();
});

it('rejects an event that is not in the registry', function () use ($valid) {
    expect(validateWebhook([...$valid, 'events' => ['order.exploded']])->fails())->toBeTrue();
});
