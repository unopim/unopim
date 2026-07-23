<?php

beforeEach(fn () => $this->loginAsAdmin());

it('renders a fields edit page and persists to core config', function () {
    config(['system_settings' => [
        ['key' => 'system', 'name' => 'admin::app.settings.system-settings.system.title', 'info' => 'admin::app.settings.system-settings.system.info', 'sort' => 1],
        ['key'       => 'system.debug', 'name' => 'admin::app.settings.system-settings.debug.title', 'info' => 'admin::app.settings.system-settings.debug.info', 'sort' => 1,
            'fields' => [['name' => 'enabled', 'title' => 'admin::app.settings.system-settings.debug.title', 'type' => 'boolean']]],
    ]]);

    $this->get(route('admin.settings.system.edit', 'system.debug'))->assertOk();

    $this->put(route('admin.settings.system.update', 'system.debug'), [
        'system' => ['debug' => ['enabled' => '1']],
    ])->assertRedirect(route('admin.settings.system.edit', 'system.debug'));

    expect(core()->getConfigData('system.debug.enabled'))->toBe('1');
});

it('redirects to the hub when the key has no fields', function () {
    config(['system_settings' => [
        ['key' => 'system.appearance', 'name' => 'admin::app.settings.appearance.title', 'route' => 'admin.settings.appearance.index', 'sort' => 1],
    ]]);

    $this->get(route('admin.settings.system.edit', 'system.appearance'))
        ->assertRedirect(route('admin.settings.system.index'));
});
