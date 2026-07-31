<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Publication\Models\Publication;

function enablePassportFeature(): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.product_passport.settings.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );
}

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('lists publications when the feature is enabled', function () {
    enablePassportFeature();
    $publication = Publication::factory()->create();

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.passports.index'))
        ->assertOk()
        ->assertJsonFragment(['uuid' => $publication->uuid]);
});

it('returns 404 for the list when the feature is disabled', function () {
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.product_passport.settings.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '0'],
    );

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.passports.index'))
        ->assertNotFound();
});

it('exposes the gtin and gs1 link identifiers on the list', function () {
    enablePassportFeature();
    $publication = Publication::factory()->create([
        'gtin'             => '04006381333931',
        'alias_identifier' => 'https://dpp.example.test/01/04006381333931',
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.passports.index'))
        ->assertOk()
        ->assertJsonFragment(['gtin' => '04006381333931'])
        ->assertJsonFragment(['gs1_link' => 'https://dpp.example.test/01/04006381333931']);
});

it('gets publications for a product by sku', function () {
    enablePassportFeature();
    $publication = Publication::factory()->create();

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.passports.get', $publication->product->sku))
        ->assertOk()
        ->assertJsonFragment(['uuid' => $publication->uuid]);
});

it('returns 404 getting passports for an unknown sku', function () {
    enablePassportFeature();

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.passports.get', 'no-such-sku'))
        ->assertNotFound();
});

it('forbids passport list without permission', function () {
    enablePassportFeature();
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);

    $this->withHeaders($headers)
        ->json('GET', route('admin.api.passports.index'))
        ->assertForbidden();
});

it('rejects unauthenticated passport list', function () {
    enablePassportFeature();

    $this->json('GET', route('admin.api.passports.index'), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
