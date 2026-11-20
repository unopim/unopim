<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function (): void {
    Webhook::query()->delete();
});

it('pins every delivery to the address its url validated to', function (): void {
    $sent = [];

    Http::fake(function (Request $request, array $options) use (&$sent) {
        $sent[] = $options;

        return Http::response(['ok' => true], 200);
    });

    Webhook::create(['name' => 'Pinned Hook', 'url' => 'https://8.8.8.8/hook', 'is_active' => true, 'events' => ['product.updated']]);

    app(WebhookService::class)->sendBatchByIds([Product::factory()->simple()->create()->id]);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['allow_redirects'])->toBeFalse()
        ->and($sent[0]['curl'][CURLOPT_RESOLVE])->toBe(['8.8.8.8:443:8.8.8.8']);
});

it('sends nothing to a webhook url that cannot be validated and pinned', function (string $url): void {
    Http::fake();

    Webhook::create(['name' => 'Unpinnable Hook', 'url' => $url, 'is_active' => true, 'events' => ['product.updated']]);

    app(WebhookService::class)->sendBatchByIds([Product::factory()->simple()->create()->id]);

    Http::assertNothingSent();
})->with([
    'unresolvable' => ['https://unresolvable.invalid/hook'],
    'metadata'     => ['http://169.254.169.254/latest'],
]);

it('refuses to probe a webhook url that cannot be validated and pinned', function (string $url): void {
    Http::fake();

    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');

    $this->postJson(route('webhook.test'), ['url' => $url])
        ->assertUnprocessable()
        ->assertJson(['success' => false, 'message' => trans('webhook::app.webhooks.validation.unsafe-url')]);

    Http::assertNothingSent();
})->with([
    'unresolvable' => ['https://unresolvable.invalid/hook'],
    'metadata'     => ['http://169.254.169.254/latest'],
]);

it('probes a webhook url pinned to the address it validated to', function (): void {
    $sent = [];

    Http::fake(function (Request $request, array $options) use (&$sent) {
        $sent[] = $options;

        return Http::response(['ok' => true], 200);
    });

    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');

    $this->postJson(route('webhook.test'), ['url' => 'https://8.8.8.8/hook'])->assertOk();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['allow_redirects'])->toBeFalse()
        ->and($sent[0]['curl'][CURLOPT_RESOLVE])->toBe(['8.8.8.8:443:8.8.8.8']);
});
