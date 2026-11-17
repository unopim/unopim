<?php

use Webkul\AdminApi\Models\Apikey;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\User\Tests\Concerns\UserAssertions;

uses(InteractsWithPlatformModules::class, UserAssertions::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);
});

it('responds 404 for an api route of a switched-off module, even for a key with full access', function () {
    $headers = $this->getAuthenticationHeaders('all');

    $this->setPlatformModules('records');

    $this->withHeaders($headers)->getJson('/api/v1/rest/products')->assertNotFound();
});

it('responds 404 for an api route of a switched-off module for a key granted that permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);

    $this->setPlatformModules('records');

    $this->withHeaders($headers)->getJson('/api/v1/rest/products')->assertNotFound();
});

it('serves api routes outside the switched-off module', function () {
    $headers = $this->getAuthenticationHeaders('all');

    $this->setPlatformModules('records');

    $this->withHeaders($headers)->getJson('/api/v1/rest/locales')->assertOk();
});

it('serves the api route while its module is on', function () {
    $headers = $this->getAuthenticationHeaders('all');

    $this->setPlatformModules('pim');

    $this->withHeaders($headers)->getJson('/api/v1/rest/products')->assertOk();
});

it('drops a crafted permission of a switched-off module when an api key is created', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $name = 'gated-key-'.uniqid();

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => $name,
        'permission_type' => 'custom',
        'permissions'     => ['api.settings.locales', 'api.catalog.products'],
    ])->assertRedirect();

    expect(Apikey::query()->where('name', $name)->firstOrFail()->permissions)->toBe(['api.settings.locales']);
});

it('creates an api key without permissions when none are submitted', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $name = 'gated-key-'.uniqid();

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => $name,
        'permission_type' => 'all',
    ])->assertRedirect();

    expect(Apikey::query()->where('name', $name)->firstOrFail()->permissions)->toBe([]);
});

it('keeps stored permissions of a switched-off module and drops crafted ones on api key update', function () {
    $this->setPlatformModules('pim');

    $this->loginAsAdmin();

    $name = 'gated-key-'.uniqid();

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => $name,
        'permission_type' => 'custom',
        'permissions'     => ['api.settings.locales', 'api.catalog.products'],
    ])->assertRedirect();

    $apiKey = Apikey::query()->where('name', $name)->firstOrFail();

    $this->setPlatformModules('records');

    $this->put(route('admin.configuration.integrations.update', $apiKey->id), [
        'name'            => $name,
        'permission_type' => 'custom',
        'permissions'     => ['api.settings.channels', 'api.catalog.categories'],
    ])->assertRedirect();

    expect($apiKey->refresh()->permissions)->toEqualCanonicalizing(['api.settings.channels', 'api.catalog.products']);
});
