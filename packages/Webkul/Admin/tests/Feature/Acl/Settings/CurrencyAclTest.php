<?php

use Webkul\Core\Models\Currency;

it('should not display the currency list if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.settings.currencies.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the currency list if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.currencies']);

    $response = $this->get(route('admin.settings.currencies.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.currencies.index.title'));
});

it('should not return the currency json for edit if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $currency = Currency::first();

    $this->get(route('admin.settings.currencies.edit', ['id' => $currency->id]))
        ->assertSeeText('Unauthorized');
});

it('should return the currency json for edit if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.currencies.edit']);
    $currency = Currency::first();

    $this->get(route('admin.settings.currencies.edit', ['id' => $currency->id]))
        ->assertOk()
        ->assertJsonFragment($currency->toArray());
});

it('should not be able to delete currency if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $currency = Currency::first();

    $this->delete(route('admin.settings.currencies.delete', ['id' => $currency->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Currency::class), ['id' => $currency->id]);
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

it('should not be able to mass update currencies if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['settings', 'settings.currencies']);

    $currencyIds = Currency::where('status', 0)->limit(2)->pluck('id')->toArray();

    $this->post(route('admin.settings.currencies.mass_update'), [
        'indices' => $currencyIds,
        'value'   => 1,
    ])
        ->assertSeeText('Unauthorized');

    foreach ($currencyIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(Currency::class), ['id' => $id, 'status' => 0]);
    }
});

it('should be able to mass update currencies if has permission', function () {
    $this->loginWithPermissions(permissions: ['settings', 'settings.currencies', 'settings.currencies.mass_update']);

    $currencyIds = Currency::where('status', 0)->limit(2)->pluck('id')->toArray();

    $this->post(route('admin.settings.currencies.mass_update'), [
        'indices' => $currencyIds,
        'value'   => 1,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.update-success')]);

    foreach ($currencyIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(Currency::class), ['id' => $id, 'status' => 1]);
    }
});

it('should not be able to mass delete currencies if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['settings', 'settings.currencies', 'settings.currencies.delete']);

    $currencyIds = Currency::where('status', 0)->limit(2)->pluck('id')->toArray();

    $this->post(route('admin.settings.currencies.mass_delete'), [
        'indices' => $currencyIds,
    ])
        ->assertSeeText('Unauthorized');

    foreach ($currencyIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(Currency::class), ['id' => $id]);
    }
});

it('should be able to mass delete currencies if has permission', function () {
    $this->loginWithPermissions(permissions: ['settings', 'settings.currencies', 'settings.currencies.mass_delete']);

    $currencyIds = Currency::where('status', 0)->limit(2)->pluck('id')->toArray();

    $this->post(route('admin.settings.currencies.mass_delete'), [
        'indices' => $currencyIds,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.currencies.index.delete-success')]);

    foreach ($currencyIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(Currency::class), ['id' => $id]);
    }
});
