<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    WebhookLog::query()->delete();
    Webhook::query()->delete();
});

it('delivers any event signed and logged with the given code and actor', function () {
    $webhook = Webhook::create([
        'name'      => 'Generic Hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['vendor.thing.created'],
        'secret'    => 's3cret',
    ]);

    app(WebhookService::class)->deliverEvent($webhook, 'vendor.thing.created', ['id' => 7], 'THING-7', 'Robot');

    Http::assertSent(function (Request $request): bool {
        return $request->data()['event'] === 'vendor.thing.created'
            && $request->data()['data'] === [['id' => 7]]
            && $request->header('X-Unopim-Event')[0] === 'vendor.thing.created'
            && $request->header('X-Unopim-Signature')[0] === 'sha256='.hash_hmac('sha256', $request->body(), 's3cret');
    });

    $log = WebhookLog::query()->where('webhook_id', $webhook->id)->sole();

    expect($log->sku)->toBe('THING-7')
        ->and($log->user)->toBe('Robot')
        ->and($log->event)->toBe('vendor.thing.created')
        ->and((int) $log->status)->toBe(1);
});

it('fires save events around a webhook update so packages can extend the form', function () {
    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');

    $webhook = Webhook::create(['name' => 'Hook', 'url' => 'https://8.8.8.8/hook', 'is_active' => true, 'events' => ['product.created']]);

    Event::fake(['webhook.update.before', 'webhook.update.after']);

    $this->put(route('webhook.update', $webhook->id), [
        'name'   => 'Hook',
        'url'    => 'https://8.8.8.8/hook',
        'events' => ['product.created'],
    ])->assertRedirect();

    Event::assertDispatched('webhook.update.before');
    Event::assertDispatched('webhook.update.after', fn (string $name, mixed $saved): bool => $saved instanceof Webhook && $saved->is($webhook));
});

it('delivers several entities of one event in a single request with a log row per entity', function () {
    $webhook = Webhook::create([
        'name'      => 'Batch Hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['vendor.thing.updated'],
    ]);

    app(WebhookService::class)->deliverEvents($webhook, 'vendor.thing.updated', [
        ['code' => 'THING-1', 'data' => ['id' => 1], 'actor' => 'Robot'],
        ['code' => 'THING-2', 'data' => ['id' => 2], 'actor' => 'Robot'],
    ]);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->data()['data'] === [['id' => 1], ['id' => 2]]);

    $logs = WebhookLog::query()->where('webhook_id', $webhook->id)->orderBy('id')->get();

    expect($logs->pluck('sku')->all())->toBe(['THING-1', 'THING-2'])
        ->and($logs->map(fn (WebhookLog $log): array => $log->extra['payload']['data'])->all())->toBe([[['id' => 1]], [['id' => 2]]]);
});

it('sends nothing when there are no entities to deliver', function () {
    $webhook = Webhook::create(['name' => 'Idle Hook', 'url' => 'https://8.8.8.8/hook', 'is_active' => true, 'events' => ['vendor.thing.updated']]);

    expect(app(WebhookService::class)->deliverEvents($webhook, 'vendor.thing.updated', []))->toBeNull();

    Http::assertNothingSent();

    expect(WebhookLog::query()->where('webhook_id', $webhook->id)->exists())->toBeFalse();
});

it('fires save events around a webhook create', function () {
    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');

    Event::fake(['webhook.create.before', 'webhook.create.after']);

    $this->postJson(route('webhook.store'), [
        'name'   => 'Created Hook',
        'url'    => 'https://8.8.8.8/hook',
        'events' => ['product.created'],
    ])->assertOk();

    Event::assertDispatched('webhook.create.before');
    Event::assertDispatched('webhook.create.after', fn (string $name, mixed $saved): bool => $saved instanceof Webhook && $saved->name === 'Created Hook');
});

it('renders an extension point after the webhook settings on the edit form', function () {
    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');

    $webhook = Webhook::create(['name' => 'Hook', 'url' => 'https://8.8.8.8/hook', 'is_active' => true, 'events' => ['product.created']]);

    $rendered = null;

    Event::listen('unopim.admin.webhook.form.settings.after', function ($viewRenderEventManager) use (&$rendered): void {
        $rendered = $viewRenderEventManager->getParam('webhook');
    });

    $this->get(route('webhook.edit', $webhook->id))->assertOk();

    expect($rendered)->toBeInstanceOf(Webhook::class)
        ->and($rendered->is($webhook))->toBeTrue();
});

it('pins every delivery to the address its url validated to', function () {
    $sent = [];

    Http::fake(function (Request $request, array $options) use (&$sent) {
        $sent[] = $options;

        return Http::response(['ok' => true], 200);
    });

    $webhook = Webhook::create(['name' => 'Pinned Hook', 'url' => 'https://8.8.8.8/hook', 'is_active' => true, 'events' => ['vendor.thing.created']]);

    app(WebhookService::class)->deliverEvent($webhook, 'vendor.thing.created', ['id' => 7], 'THING-7', 'Robot');

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['allow_redirects'])->toBeFalse()
        ->and($sent[0]['curl'][CURLOPT_RESOLVE])->toBe(['8.8.8.8:443:8.8.8.8']);
});

it('sends nothing to a webhook url that cannot be validated and pinned', function (string $url) {
    $webhook = Webhook::create(['name' => 'Unpinnable Hook', 'url' => $url, 'is_active' => true, 'events' => ['vendor.thing.created']]);

    expect(app(WebhookService::class)->deliverEvent($webhook, 'vendor.thing.created', ['id' => 7]))->toBeNull();

    Http::assertNothingSent();
})->with([
    'unresolvable' => ['https://unresolvable.invalid/hook'],
    'metadata'     => ['http://169.254.169.254/latest'],
]);

it('refuses to probe a webhook url that cannot be validated and pinned', function (string $url) {
    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');

    $this->postJson(route('webhook.test'), ['url' => $url])
        ->assertUnprocessable()
        ->assertJson(['success' => false, 'message' => trans('webhook::app.webhooks.validation.unsafe-url')]);

    Http::assertNothingSent();
})->with([
    'unresolvable' => ['https://unresolvable.invalid/hook'],
    'metadata'     => ['http://169.254.169.254/latest'],
]);

it('probes a webhook url pinned to the address it validated to', function () {
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
