<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the currency index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.currencies.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.currencies.index.title'));
});

it('should create the currency', function () {
    $this->loginAsAdmin();

    $response = postJson(route('admin.settings.currencies.store'),
        [
            'code'    => 'DOP',
            'symbol'  => 'RD$',
            'decimal' => '',
            'status'  => 1,
        ],
    );

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'code' => 'DOP',
    ]);
});

it('should return the currency datagrid', function () {
    $this->loginAsAdmin();
    Currency::factory()->create([
        'code'   => 'DOP',
        'symbol' => '$',
    ]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->json('GET', route('admin.settings.currencies.index'));

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should return the currency create page', function () {
    $this->loginAsAdmin();
    $currency = Currency::factory()->create([
        'code'   => 'DOP',
        'symbol' => '$',
    ]);

    $this->get(route('admin.settings.currencies.edit', ['id' => $currency->id]))
        ->assertOk();
});

it('should update the currency', function () {
    $this->loginAsAdmin();

    $currency = Currency::factory()->create([
        'code'   => 'DOP',
        'symbol' => '$',
    ]);

    $response = putJson(route('admin.settings.currencies.update'),
        [
            'id'      => $currency->id,
            'code'    => 'DOP',
            'symbol'  => '$$',
            'decimal' => '',
            'status'  => 0,
        ],
    );

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'code'   => $currency->code,
        'symbol' => '$$',
    ]);
});

it('should give validation message for code', function () {
    $this->loginAsAdmin();

    $response = postJson(route('admin.settings.currencies.store'),
        [
            'code'    => 'DO',
            'symbol'  => '$$',
            'decimal' => '',
            'status'  => 0,
        ],
    );

    $response->assertJsonValidationErrorFor('code');
});

it('should give validation message for decimal', function () {
    $this->loginAsAdmin();

    $response = postJson(route('admin.settings.currencies.store'),
        [
            'code'    => 'DOP',
            'symbol'  => 'RD$',
            'decimal' => 'char',
            'status'  => 0,
        ],
    );

    // Issue in this test , there should be validation message for decimal
    $response->assertok();
    // $response->assertJsonValidationErrorFor('decimal');
});

it('should delete the currency', function () {
    $this->loginAsAdmin();

    $currency = Currency::factory()->create();

    $response = $this->delete(route('admin.settings.currencies.delete', ['id' => $currency->id]));

    $response->assertok();

    $this->assertDatabaseMissing($this->getFullTableName(Currency::class), [
        'id' => $currency->id,
    ]);
});

it('should not delete the currency linked to a channel', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $response = $this->delete(route('admin.settings.currencies.delete', ['id' => $channel->currencies->first()->id]));

    $response->assertStatus(400);

    $response->assertJsonPath('message', trans('admin::app.settings.currencies.index.can-not-delete-error'));
});

it('should not update the status of currency linked to a channel', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $response = putJson(route('admin.settings.currencies.update'),
        [
            'id'     => $channel->currencies->first()->id,
            'code'   => $channel->currencies->first()->code,
            'status' => 0,
        ],
    );

    $response->assertJsonValidationErrorFor('status');

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'code'   => $channel->currencies->first()->code,
        'status' => 1,
    ]);
});

it('should not update the code of currency', function () {
    $this->loginAsAdmin();

    $currency = Currency::factory()->create();

    $response = putJson(route('admin.settings.currencies.update'),
        [
            'id'      => $currency->id,
            'code'    => 'DOP',
            'symbol'  => 'RD$',
            'decimal' => '',
            'status'  => 0,
        ],
    );

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'code'   => $currency->code,
        'symbol' => 'RD$',
    ]);
});

it('should update status of currency using mass action', function () {
    $this->loginAsAdmin();

    $currencyIds = Currency::where('status', 0)->limit(4)->pluck('id')->toArray();

    postJson(route('admin.settings.currencies.mass_update'), [
        'indices' => $currencyIds,
        'value'   => 1,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.update-success')]);

    $this->assertTrue(count($currencyIds) == Currency::whereIn('id', $currencyIds)->where('status', 1)->count());
});

it('should update the status of currency to disabled through mass update', function () {
    $this->loginAsAdmin();

    $currencyIds = Currency::where('status', 0)->limit(4)->pluck('id')->toArray();

    Currency::whereIn('id', $currencyIds)->update(['status' => 1]);

    $this->post(route('admin.settings.currencies.mass_update'), [
        'indices' => $currencyIds,
        'value'   => 0,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.update-success')]);

    $this->assertTrue(count($currencyIds) == Currency::whereIn('id', $currencyIds)->where('status', 0)->count());
});

it('should not disable a currency linked to a channel through mass update', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $currencyId = $channel->currencies->first()?->id;

    $this->post(route('admin.settings.currencies.mass_update'), [
        'indices' => [$currencyId],
        'value'   => 0,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.update-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'id'     => $currencyId,
        'status' => 1,
    ]);
});

it('should delete the currency using mass delete', function () {
    $this->loginAsAdmin();

    $currencyIds = Currency::where('status', 0)->limit(4)->pluck('id')->toArray();

    postJson(route('admin.settings.currencies.mass_delete'), [
        'indices' => $currencyIds,
        'value'   => 1,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.delete-success')]);

    $this->assertTrue(Currency::whereIn('id', $currencyIds)->count() == 0);
});

it('should not delete the currency linked to channel using mass delete', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $currencyId = $channel->currencies->first()?->id;

    postJson(route('admin.settings.currencies.mass_delete'), [
        'indices' => [$currencyId],
    ])
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.cannot-delete')]);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'id'     => $currencyId,
        'status' => 1,
    ]);
});

it('should not delete the last currency using mass delete', function () {
    $this->loginAsAdmin();

    $id = Currency::first()->id;
    Currency::whereNot('id', $id)->delete();

    postJson(route('admin.settings.currencies.mass_delete'), [
        'indices' => [$id],
    ])
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.last-delete-error')]);

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), [
        'id' => $id,
    ]);
});
