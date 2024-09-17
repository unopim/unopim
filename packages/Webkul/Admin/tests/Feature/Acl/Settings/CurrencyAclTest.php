<?php

use Webkul\Core\Models\Currency;

it('should not display the currency list if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->get(route('admin.settings.currencies.index'));

    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the currency list if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.currencies']);

    $response = $this->get(route('admin.settings.currencies.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.currencies.index.title'));
});

it('should not display the currency edit if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $currency = Currency::first();

    $response = $this->get(route('admin.settings.currencies.edit', ['id' => $currency->id]));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the currency edit if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.currencies.edit']);
    $currency = Currency::first();

    $this->get(route('admin.settings.currencies.edit', ['id' => $currency->id]))
        ->assertOk()
        ->assertJsonFragment($currency->toArray());
});

it('should not be able to delete currency if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $currency = Currency::first();

    $response = $this->delete(route('admin.settings.currencies.delete', ['id' => $currency->id]));
    $this->assertStringContainsString('Unauthorized', $response->getContent());

    $this->assertDatabaseHas($this->getFullTableName(Currency::class),
        ['id' => $currency->id]
    );
});

it('should be able to delete currency if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.currencies.delete']);
    $currency = Currency::factory()->create();

    $response = $this->delete(route('admin.settings.currencies.delete', ['id' => $currency->id]));

    $response->assertok();

    $this->assertDatabaseMissing($this->getFullTableName(Currency::class), [
        'id' => $currency->id,
    ]);
});
