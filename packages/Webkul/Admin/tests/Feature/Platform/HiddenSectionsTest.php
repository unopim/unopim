<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);

    CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->delete();
});

function platformSettingsPayload(array $values): array
{
    $configData = collect(include __DIR__.'/../../../src/Config/system.php')
        ->first(fn ($item) => ($item['key'] ?? '') === 'general.platform.settings');

    return [
        'general' => ['platform' => ['settings' => $values]],
        'keys'    => [json_encode($configData)],
    ];
}

it('rejects hiding a section that is not flagged hideable', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->post(route('admin.configuration.store', ['general', 'platform']), platformSettingsPayload([
        'modules'         => 'records',
        'hidden_sections' => ['dashboard', 'settings.users'],
    ]))->assertSessionHasErrors(ModuleGate::HIDDEN_SECTIONS_KEY);

    expect(CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->exists())->toBeFalse();
});

it('saves a hideable section', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->post(route('admin.configuration.store', ['general', 'platform']), platformSettingsPayload([
        'modules'         => 'records',
        'hidden_sections' => ['settings.channels'],
    ]))->assertSessionHasNoErrors();

    app()->forgetScopedInstances();

    expect(app(ModuleGate::class)->hiddenSections())->toBe(['settings.channels']);
});

it('persists an emptied selection so every section can be shown again', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->post(route('admin.configuration.store', ['general', 'platform']), platformSettingsPayload([
        'modules'         => 'records',
        'hidden_sections' => '',
    ]))->assertSessionHasNoErrors();

    app()->forgetScopedInstances();

    expect(app(ModuleGate::class)->hiddenSections())->toBe([]);
});

it('defaults to the hideable menu keys flagged hidden by default', function () {
    expect(ModuleGate::parseSections(app(ModuleGate::class)->defaultHiddenSections()))->toEqualCanonicalizing([
        'dashboard',
        'catalog.attributes',
        'catalog.attribute_groups',
        'catalog.measurements',
        'settings.currencies',
        'ai-agent',
        'configuration.webhook',
    ]);
});

it('leaves out a hidden-by-default menu key that is not hideable', function () {
    config(['menu.admin' => [
        ...config('menu.admin'),
        ['key' => 'records', 'name' => 'records', 'route' => 'admin.dashboard.index', 'hidden_by_default' => true],
        ['key' => 'records.tagged', 'name' => 'records', 'route' => 'admin.dashboard.index', 'module' => 'records', 'hideable' => true, 'hidden_by_default' => true],
    ]]);

    expect(ModuleGate::parseSections(app(ModuleGate::class)->defaultHiddenSections()))
        ->not->toContain('records')
        ->not->toContain('records.tagged');
});

it('hides the default sections while pim is off and nothing is stored', function () {
    $gate = $this->setPlatformModules('records');

    expect($gate->hiddenSections())->toEqualCanonicalizing(ModuleGate::parseSections($gate->defaultHiddenSections()))
        ->and($gate->hiddenSections())->not->toBeEmpty();
});

it('hides nothing while pim is on', function () {
    expect($this->setPlatformModules('pim')->hiddenSections())->toBe([]);
});

it('drops a stored section that is not hideable', function () {
    CoreConfig::query()->create([
        'code'  => ModuleGate::HIDDEN_SECTIONS_KEY,
        'value' => 'settings.users,settings.channels',
    ]);

    expect($this->setPlatformModules('records')->hiddenSections())->toBe(['settings.channels']);
});
