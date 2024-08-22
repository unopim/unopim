<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

it('should return the locale index datagrid page', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.locales.index'));

    $response->assertStatus(200)
        ->assertSeeTextInOrder([
            trans('admin::app.settings.locales.index.title'),
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

    $this->assertDatabaseHas('locales', [
        'code'   => 'zh_Hans_CN',
        'status' => 1,
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

    $this->assertDatabaseHas('locales', [
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

    $this->assertDatabaseHas('locales', [
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

    $this->assertDatabaseHas('locales', [
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

    $this->assertDatabaseHas('locales', [
        'id' => $localeId,
    ]);
});

it('should not delete the last locale', function () {
    $user = $this->loginAsAdmin();

    $localeId = $user->ui_locale_id;

    Locale::whereNot('id', $localeId)->delete();

    $response = $this->delete(route('admin.settings.locales.delete', ['id' => $localeId]));

    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.locales.index.last-delete-error'),
        ]);

    $this->assertDatabaseHas('locales', [
        'id' => $localeId,
    ]);
});
