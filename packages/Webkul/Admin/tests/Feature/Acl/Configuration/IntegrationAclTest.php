<?php

use Webkul\AdminApi\Models\Apikey;

it('should display the magic ai tab if has permission', function () {
    $this->loginWithPermissions('custom', [
        'configuration',
        'configuration.magic-ai',
        'configuration.magic-ai.general',
    ]);

    $this->get(route('admin.configuration.edit', ['general', 'magic_ai']))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.index.general.magic-ai.title'))
        ->assertSeeText(trans('admin::app.configuration.index.general.magic-ai.settings.title'));
});

it('should not display the magic ai tab if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.configuration.edit', ['general', 'magic_ai']))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.configuration.index.general.magic-ai.settings.title'));
});

it('should display the integration index page if has permission', function () {
    $this->loginWithPermissions('custom', ['configuration', 'configuration.integrations']);

    $this->get(route('admin.configuration.integrations.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.integrations.index.title'));
});

it('should not display the integration index page if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.configuration.integrations.index'))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.configuration.integrations.index.title'));
});

it('should display the create integration page if has permission', function () {
    $this->loginWithPermissions('custom', ['configuration', 'configuration.integrations.create']);

    $this->get(route('admin.configuration.integrations.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.integrations.create.title'));
});

it('should not display the create integration page if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.configuration.integrations.create'))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.configuration.integrations.create.title'));
});

it('should display the edit integration page if has permission', function () {
    $userId = $this->loginWithPermissions('custom', ['configuration', 'configuration.integrations.edit'])?->id;

    $apiKey = Apikey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->get(route('admin.configuration.integrations.edit', $apiKey->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.integrations.edit.title'));
});

it('should not display the edit integration page if does not have permission', function () {
    $userId = $this->loginWithPermissions()?->id;

    $apiKey = Apikey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->get(route('admin.configuration.integrations.edit', $apiKey->id))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.configuration.integrations.edit.title'));
});

it('should delete an integration if has permission', function () {
    $userId = $this->loginWithPermissions('custom', ['configuration', 'configuration.integrations', 'configuration.integrations.delete'])?->id;

    $apiKey = Apikey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->delete(route('admin.configuration.integrations.delete', $apiKey->id))
        ->assertOk()
        ->assertDontSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(ApiKey::class), [
        'id'      => $apiKey->id,
        'revoked' => 1,
    ]);
});

it('should not delete an integration if does not have permission', function () {
    $userId = $this->loginWithPermissions('custom', ['configuration'])?->id;

    $apiKey = Apikey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->delete(route('admin.configuration.integrations.delete', $apiKey))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Apikey::class), [
        'id'      => $apiKey->id,
        'revoked' => 0,
    ]);
});
