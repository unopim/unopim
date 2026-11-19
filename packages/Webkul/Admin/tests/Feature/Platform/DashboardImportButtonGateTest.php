<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);

    CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->delete();
});

it('hides the dashboard Import Data button when the imports section is hidden', function () {
    $this->setPlatformModules('records');

    CoreConfig::query()->create([
        'code'         => ModuleGate::HIDDEN_SECTIONS_KEY,
        'value'        => 'data_transfer.imports',
        'channel_code' => null,
        'locale_code'  => null,
    ]);

    $this->loginAsAdmin();

    app()->forgetScopedInstances();

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertDontSee(route('admin.settings.data_transfer.imports.index'), false);
});

it('shows the dashboard Import Data button when the imports section is visible', function () {
    $this->setPlatformModules('records');

    CoreConfig::query()->create([
        'code'         => ModuleGate::HIDDEN_SECTIONS_KEY,
        'value'        => '',
        'channel_code' => null,
        'locale_code'  => null,
    ]);

    $this->loginAsAdmin();

    app()->forgetScopedInstances();

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee(route('admin.settings.data_transfer.imports.index'), false);
});
