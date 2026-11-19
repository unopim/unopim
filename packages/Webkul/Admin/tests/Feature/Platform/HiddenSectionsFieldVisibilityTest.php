<?php

use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;

uses(InteractsWithPlatformModules::class);

it('hides the hidden-sections field when only PIM is registered', function () {
    config(['platform_modules' => [
        'pim' => ['name' => 'core::app.platform-modules.pim', 'requires' => [], 'route_prefixes' => []],
    ]]);

    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.system.edit', 'system.platform'));

    $response->assertOk();
    $response->assertSee('modules');
    $response->assertDontSee('hidden_sections');
});

it('shows the hidden-sections field when an optional module (DAM) is registered', function () {
    config(['platform_modules' => [
        'pim' => ['name' => 'core::app.platform-modules.pim', 'requires' => [], 'route_prefixes' => []],
        'dam' => ['name' => 'dam::app.platform-module.name', 'requires' => [], 'route_prefixes' => ['admin.dam.']],
    ]]);

    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.system.edit', 'system.platform'));

    $response->assertOk();
    $response->assertSee('hidden_sections');
});
