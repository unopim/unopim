<?php

use Webkul\Admin\SystemSettings;

it('builds an ACL-filtered tree from config(system_settings)', function () {
    config(['system_settings' => [
        ['key' => 'system', 'name' => 'system.title', 'info' => 'system.info', 'sort' => 1],
        ['key' => 'system.appearance', 'name' => 'a.title', 'info' => 'a.info', 'route' => 'admin.settings.appearance.index', 'sort' => 1],
    ]]);

    $tree = app(SystemSettings::class)->tree();

    // Tree nests children by the last dot segment of the key.
    expect($tree->items)->toHaveKey('system')
        ->and($tree->items['system']['children'])->toHaveKey('appearance')
        ->and($tree->items['system']['children']['appearance']['key'])->toBe('system.appearance');
});

it('finds a raw entry by key', function () {
    config(['system_settings' => [
        ['key' => 'system.debug', 'name' => 'd.title', 'info' => 'd.info', 'fields' => [['name' => 'enabled', 'type' => 'boolean']]],
    ]]);

    expect(app(SystemSettings::class)->find('system.debug')['fields'][0]['name'])->toBe('enabled');
});

it('drops a row whose acl permission the admin lacks', function () {
    config(['system_settings' => [
        ['key' => 'system', 'name' => 'system.title', 'sort' => 1],
        ['key' => 'system.secret', 'name' => 's.title', 'route' => 'admin.settings.appearance.index', 'acl' => 'settings.system.never', 'sort' => 1],
    ]]);

    $tree = app(SystemSettings::class)->tree();

    expect($tree->items['system']['children'] ?? [])->not->toHaveKey('secret');
});
