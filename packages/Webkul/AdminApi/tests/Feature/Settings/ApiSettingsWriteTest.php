<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/** Currencies */
it('creates a currency', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.currencies.store'), ['code' => 'ZQX', 'symbol' => 'Z', 'decimal' => 2])
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), ['code' => 'ZQX']);
});

it('updates a currency', function () {
    $currency = Currency::create(['code' => 'ZQY', 'symbol' => 'Y', 'decimal' => 2, 'status' => 1]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.currencies.update', $currency->code), ['symbol' => 'YY'])
        ->assertOk();

    expect($currency->fresh()->symbol)->toBe('YY');
});

it('deletes a currency', function () {
    $currency = Currency::create(['code' => 'ZQZ', 'symbol' => 'Z', 'decimal' => 2, 'status' => 1]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.currencies.delete', $currency->code))
        ->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(Currency::class), ['id' => $currency->id]);
});

it('returns 404 updating an unknown currency', function () {
    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.currencies.update', 'ZZZ'), ['symbol' => 'X'])
        ->assertNotFound();
});

/** Locales */
it('creates a locale', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.locales.store'), ['code' => 'zz_ZZ', 'status' => 1])
        ->assertStatus(201);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), ['code' => 'zz_ZZ']);
});

it('deletes a locale', function () {
    $locale = Locale::create(['code' => 'zy_ZY', 'status' => 1]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.locales.delete', $locale->code))
        ->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(Locale::class), ['id' => $locale->id]);
});

/** Channels */
it('creates and deletes a channel', function () {
    $existingChannel = Channel::with(['locales', 'currencies'])->first();
    $category = Category::find($existingChannel->root_category_id) ?? Category::first();
    $locale = $existingChannel->locales->first() ?? Locale::where('status', 1)->first();
    $currency = $existingChannel->currencies->first() ?? Currency::where('status', 1)->first();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.channels.store'), [
            'code'          => 'apichan',
            'root_category' => $category->code,
            'locales'       => [$locale->code],
            'currencies'    => [$currency->code],
            'labels'        => [$locale->code => 'API Channel'],
        ])
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Channel::class), ['code' => 'apichan']);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.channels.delete', 'apichan'))
        ->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(Channel::class), ['code' => 'apichan']);
});

it('validates channel creation', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.channels.store'), ['code' => 'x'])
        ->assertStatus(422);
});

/** ACL + auth */
it('forbids currency create without permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.currencies']);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.currencies.store'), ['code' => 'ZQW'])
        ->assertForbidden();
});

it('rejects unauthenticated channel create', function () {
    $this->json('POST', route('admin.api.channels.store'), [], ['Accept' => 'application/json'])
        ->assertUnauthorized();
});
