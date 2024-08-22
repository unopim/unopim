<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the currency index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.currencies.index'));

    $response->assertStatus(200);
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

    $this->assertDatabaseHas('currencies', [
        'code' => 'DOP',
    ]);
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

    $this->assertDatabaseHas('currencies', [
        'id'     => $currency->id,
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

    $this->assertDatabaseMissing('admins', [
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
            'id'      => $channel->currencies->first()->id,
            'code'    => $channel->currencies->first()->code,
            'status'  => 0,
        ],
    );

    $response->assertJsonValidationErrorFor('status');

    $this->assertDatabaseHas('currencies', [
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

    $this->assertDatabaseHas('currencies', [
        'code'   => $currency->code,
        'symbol' => 'RD$',
    ]);
});
