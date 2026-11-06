<?php

use Webkul\Core\Platform\ModuleGate;
use Webkul\Core\Repositories\CoreConfigRepository;

it('resolves the default value from the default_repository callback', function () {
    $default = app(CoreConfigRepository::class)->getDefaultValue([
        'default_value'      => 'ignored',
        'default_repository' => ModuleGate::class.'@defaultHiddenSections',
    ]);

    expect($default)->toBe(app(ModuleGate::class)->defaultHiddenSections())
        ->and($default)->toContain('dashboard');
});

it('falls back to default_value without a default_repository', function () {
    expect(app(CoreConfigRepository::class)->getDefaultValue(['default_value' => 'pim']))->toBe('pim');
});

it('returns null when the field declares no default', function () {
    expect(app(CoreConfigRepository::class)->getDefaultValue(['name' => 'modules']))->toBeNull();
});
