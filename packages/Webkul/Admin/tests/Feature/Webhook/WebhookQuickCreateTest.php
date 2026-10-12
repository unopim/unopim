<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * The webhook create flow follows the UnoPim convention: a quick-create modal on
 * the index page collects only the required fields, then hands off to the edit
 * page for the optional ones (secret, custom headers, active toggle).
 */
beforeEach(function () {
    $this->loginAsAdmin();

    $this->withoutMiddleware(PreventRequestForgery::class);
});

afterEach(function () {
    DB::table('webhooks')->where('url', 'https://example.com/quick-create')->delete();
});

it('no longer exposes a standalone create page', function () {
    expect(Route::has('webhook.create'))->toBeFalse();
    expect(Route::has('webhook.store'))->toBeTrue();
});

it('renders the quick-create modal and breadcrumbs on the index page', function () {
    $response = $this->get(route('webhook.index'));

    $response->assertOk();
    $response->assertSee('v-create-webhook-form', false);
    $response->assertSee('webhookCreateModal', false);
    $response->assertSee(trans('admin::app.components.layouts.breadcrumbs.label'), false);
});

it('creates a webhook from the required fields and returns the edit redirect', function () {
    $response = $this->postJson(route('webhook.store'), [
        'name'   => 'Quick Created',
        'url'    => 'https://example.com/quick-create',
        'events' => ['product.created'],
    ]);

    $response->assertOk();

    $webhook = DB::table('webhooks')->where('url', 'https://example.com/quick-create')->first();

    expect($webhook)->not->toBeNull();
    expect($response->json('data.redirect_url'))->toBe(route('webhook.edit', $webhook->id));
});

it('defaults a quick-created webhook to active', function () {
    $this->postJson(route('webhook.store'), [
        'name'   => 'Quick Created',
        'url'    => 'https://example.com/quick-create',
        'events' => ['product.created'],
    ])->assertOk();

    $webhook = DB::table('webhooks')->where('url', 'https://example.com/quick-create')->first();

    expect((int) $webhook->is_active)->toBe(1);
});

it('rejects a quick-create without the required fields', function () {
    $this->postJson(route('webhook.store'), ['name' => 'Missing the rest'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['url', 'events']);
});

it('hides the quick-create modal from an admin without the create permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook']);

    $this->get(route('webhook.index'))
        ->assertOk()
        ->assertDontSee('v-create-webhook-form', false);
});

it('denies a quick-create from an admin without the create permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook']);

    $this->postJson(route('webhook.store'), [
        'name'   => 'Not Allowed',
        'url'    => 'https://example.com/quick-create',
        'events' => ['product.created'],
    ])->assertForbidden();
});
