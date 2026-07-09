<?php

beforeEach(fn () => $this->loginAsAdmin());

it('renders the system settings hub with registered rows', function () {
    config(['system_settings' => [
        ['key' => 'system', 'name' => 'admin::app.settings.system-settings.system.title', 'info' => 'admin::app.settings.system-settings.system.info', 'sort' => 1],
        ['key' => 'system.appearance', 'name' => 'admin::app.settings.appearance.title', 'info' => 'admin::app.settings.appearance.info', 'route' => 'admin.settings.appearance.index', 'sort' => 1],
    ]]);

    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.settings.appearance.title'));
});

it('hides a row the admin lacks permission for and shows the search box', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    config(['system_settings' => [
        ['key' => 'system', 'name' => 'admin::app.settings.system-settings.system.title', 'info' => 'admin::app.settings.system-settings.system.info', 'sort' => 1],
        ['key' => 'system.secret', 'name' => 'admin::app.settings.appearance.title', 'info' => 'admin::app.settings.appearance.info', 'route' => 'admin.settings.appearance.index', 'acl' => 'settings.system.never', 'sort' => 1],
    ]]);

    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee('data-settings-search', false)
        ->assertDontSee(route('admin.settings.appearance.index'));
});

