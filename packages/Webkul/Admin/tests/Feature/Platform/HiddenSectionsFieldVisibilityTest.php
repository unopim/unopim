<?php

use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;

uses(InteractsWithPlatformModules::class);

it('hides the hidden-sections field when only PIM is registered', function (): void {
    config(['platform_modules' => [
        'pim' => ['name' => 'core::app.platform-modules.pim', 'requires' => [], 'route_prefixes' => []],
    ]]);

    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.system.edit', 'system.platform'));

    $response->assertOk();
    $response->assertSee('modules');
    $response->assertDontSee('hidden_sections');
});

it('shows the hidden-sections field when an optional module (DAM) is registered', function (): void {
    config(['platform_modules' => [
        'pim' => ['name' => 'core::app.platform-modules.pim', 'requires' => [], 'route_prefixes' => []],
        'dam' => ['name' => 'dam::app.platform-module.name', 'requires' => [], 'route_prefixes' => ['admin.dam.']],
    ]]);

    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.system.edit', 'system.platform'));

    $response->assertOk();
    $response->assertSee('hidden_sections');
});

it('hides the hidden-sections field on the configuration page when only PIM is registered', function (): void {
    config(['platform_modules' => [
        'pim' => ['name' => 'core::app.platform-modules.pim', 'requires' => [], 'route_prefixes' => []],
    ]]);

    $this->loginAsAdmin();

    $response = $this->get(route('admin.configuration.edit', ['slug' => 'general', 'slug2' => 'platform']));

    $response->assertOk();
    $response->assertSee('modules');
    $response->assertDontSee('hidden_sections');
});

it('skips a field whose visible callback is false in the Magic AI translation group', function (): void {
    app()->instance('tests.field-visibility', new class
    {
        public function hidden(): bool
        {
            return false;
        }
    });

    config(['core' => collect(config('core'))
        ->map(function (array $item): array {
            if (($item['key'] ?? '') === 'general.magic_ai.translation') {
                $item['fields'][] = [
                    'name'    => 'hidden_probe_field',
                    'title'   => 'hidden_probe_field',
                    'type'    => 'text',
                    'visible' => 'tests.field-visibility@hidden',
                ];
            }

            return $item;
        })
        ->all()]);

    $this->loginAsAdmin();

    $this->get(route('admin.magic_ai.settings.index'))
        ->assertOk()
        ->assertSee('source_channel')
        ->assertDontSee('hidden_probe_field');
});
