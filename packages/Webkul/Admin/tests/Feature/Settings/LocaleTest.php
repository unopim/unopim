<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;

it('should return the locale index datagrid page', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.locales.index'));

    $response->assertStatus(200)
        ->assertSeeTextInOrder([
            trans('admin::app.settings.locales.index.title'),
        ]);
});

it('should return the locale datagrid', function () {
    $this->loginAsAdmin();
    Locale::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->json('GET', route('admin.settings.locales.index'));

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should create the locale succesfully', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.settings.locales.store', [
        'code'   => 'zh_Hans_CN',
        'status' => 1,
    ]));

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.create-success'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'code'   => 'zh_Hans_CN',
        'status' => 1,
    ]);
});

it('should return the locale as json for edit modal', function () {
    $this->loginAsAdmin();

    $locale = Locale::factory()->create();

    $this->get(route('admin.settings.locales.edit', $locale->id))
        ->assertOk()
        ->assertJsonFragment([
            ...$locale->toArray(),
            'status' => $locale->status ? true : false,
        ]);
});

it('should update an existing locale status', function () {
    $this->loginAsAdmin();

    $locale = Locale::factory()->create([
        'status' => 0,
    ]);

    $response = $this->put(route('admin.settings.locales.update'), [
        'id'     => $locale->id,
        'status' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.update-success'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $locale->id,
        'code'   => $locale->code,
        'status' => 1,
    ]);
});

it('should not disable locale when the locale is linked to a user', function () {
    $user = $this->loginAsAdmin();

    $response = $this->put(route('admin.settings.locales.update'), [
        'id'     => $user->ui_locale_id,
        'status' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'status' => trans('admin::app.settings.locales.index.can-not-disable-error'),
            ],
        ]);
});

it('should not disable locale when the locale is linked to a channel', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $localeId = $channel->locales->first()->id;

    $response = $this->put(route('admin.settings.locales.update'), [
        'id'     => $localeId,
        'status' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'status' => trans('admin::app.settings.locales.index.can-not-disable-error'),
            ],
        ]);
});

it('should not update the locale code and only update status', function () {
    $this->loginAsAdmin();

    $locale = Locale::factory()->create([
        'code'   => 'zh_Hans_CN',
        'status' => 0,
    ]);

    $response = $this->put(route('admin.settings.locales.update'), [
        'id'     => $locale->id,
        'code'   => 'sn',
        'status' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.update-success'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $locale->id,
        'code'   => 'zh_Hans_CN',
        'status' => 1,
    ]);
});

it('should delete a locale successfully', function () {
    $this->loginAsAdmin();

    $locale = Locale::factory()->create([
        'code'   => 'zh_Hans_CN',
        'status' => 0,
    ]);

    $response = $this->delete(route('admin.settings.locales.delete', ['id' => $locale->id]));

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.delete-success'),
        ]);

    $this->assertDatabaseMissing('locales', [
        'id'     => $locale->id,
        'code'   => 'zh_Hans_CN',
        'status' => 0,
    ]);
});

it('should not delete the locale when the locale is linked to a user', function () {
    $user = $this->loginAsAdmin();

    $localeId = $user->ui_locale_id;

    $response = $this->delete(route('admin.settings.locales.delete', ['id' => $localeId]));

    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.can-not-delete-error'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id' => $localeId,
    ]);
});

it('should not delete the locale when the locale is linked to a channel', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $localeId = $channel->locales->first()->id;

    $response = $this->delete(route('admin.settings.locales.delete', ['id' => $localeId]));

    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.can-not-delete-error'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id' => $localeId,
    ]);
});

it('should not delete the last locale', function () {
    $user = $this->loginAsAdmin();

    $localeId = $user->ui_locale_id;

    Admin::whereNot('id', $user->id)->delete();

    Locale::whereNot('id', $localeId)->delete();

    $response = $this->delete(route('admin.settings.locales.delete', ['id' => $localeId]));

    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.last-delete-error'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id' => $localeId,
    ]);
});

it('should update the status of locales to enabled through mass update', function () {
    $this->loginAsAdmin();

    $localeIds = Locale::where('status', 0)->limit(4)->pluck('id')->toArray();

    $this->post(route('admin.settings.locales.mass_update'), [
        'indices' => $localeIds,
        'value'   => 1,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.update-success')]);

    $this->assertTrue(count($localeIds) == Locale::whereIn('id', $localeIds)->where('status', 1)->count());
});

it('should update the status of locales to disabled through mass update', function () {
    $this->loginAsAdmin();

    $localeIds = Locale::where('status', 0)->limit(4)->pluck('id')->toArray();

    Locale::whereIn('id', $localeIds)->update(['status' => 1]);

    $this->post(route('admin.settings.locales.mass_update'), [
        'indices' => $localeIds,
        'value'   => 0,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.update-success')]);

    $this->assertTrue(count($localeIds) == Locale::whereIn('id', $localeIds)->where('status', 0)->count());
});

it('should not disable a locale linked to a channel through mass update', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $localeId = $channel->locales->first()?->id;

    $this->post(route('admin.settings.locales.mass_update'), [
        'indices' => [$localeId],
        'value'   => 0,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.update-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $localeId,
        'status' => 1,
    ]);
});

it('should not disable a locale linked to a user through mass update', function () {
    $user = $this->loginAsAdmin();

    $localeId = $user->ui_locale_id;

    $this->post(route('admin.settings.locales.mass_update'), [
        'indices' => [$localeId],
        'value'   => 0,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.update-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $localeId,
        'status' => 1,
    ]);
});

it('should delete locales through mass delete', function () {
    $this->loginAsAdmin();

    $localeIds = Locale::where('status', 0)->limit(4)->pluck('id')->toArray();

    $this->post(route('admin.settings.locales.mass_delete'), [
        'indices' => $localeIds,
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.delete-success')]);

    $this->assertTrue(Locale::whereIn('id', $localeIds)->count() == 0);
});

it('should not delete a locale linked to a channel through mass delete', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $localeId = $channel->locales->first()?->id;

    $this->post(route('admin.settings.locales.mass_delete'), [
        'indices' => [$localeId],
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.delete-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $localeId,
        'status' => 1,
    ]);
});

it('should not delete a locale linked to a user through mass delete', function () {
    $user = $this->loginAsAdmin();

    $localeId = $user->ui_locale_id;

    $this->post(route('admin.settings.locales.mass_delete'), [
        'indices' => [$localeId],
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.delete-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $localeId,
        'status' => 1,
    ]);
});

it('should return error when deleting the last locale through mass delete', function () {
    $user = $this->loginAsAdmin();

    $localeId = $user->ui_locale_id;

    Admin::whereNot('id', $user->id)->delete();

    Locale::whereNot('id', $localeId)->delete();

    $response = $this->post(route('admin.settings.locales.mass_delete'), [
        'indices' => [$localeId],
    ])
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.settings.locales.index.last-delete-error')]);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'id'     => $localeId,
        'status' => 1,
    ]);
});
