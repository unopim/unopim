<?php

use Illuminate\Console\Attributes\Signature;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;
use Webkul\Installer\Console\Commands\Installer;
use Webkul\Installer\Helpers\PlatformModules;

beforeEach(function () {
    config([
        'platform_modules.records' => ['name' => 'records', 'requires' => []],
        'platform_modules.shopify' => ['name' => 'shopify', 'requires' => ['pim']],
    ]);

    app()->forgetScopedInstances();
});

function storedPlatformModules(): ?string
{
    return CoreConfig::query()->where('code', ModuleGate::CONFIG_KEY)->value('value');
}

it('offers the registered platform modules with pim first', function () {
    expect(app(PlatformModules::class)->options())->toBe(['pim', 'records', 'shopify']);
});

it('parses a comma separated selection into unique lower-cased keys', function () {
    expect(app(PlatformModules::class)->parse(' PIM, records ,,pim '))->toBe(['pim', 'records']);
});

it('reports why a selection cannot be stored', function (array $modules, string $error) {
    expect(app(PlatformModules::class)->errors($modules))->toBe([$error]);
})->with([
    'empty'       => fn () => [[], trans('core::validation.platform-modules.empty')],
    'unknown'     => fn () => [['erp'], trans('core::validation.platform-modules.unknown', ['module' => 'erp'])],
    'requirement' => fn () => [['shopify'], trans('core::validation.platform-modules.requires', [
        'module'   => 'shopify',
        'requires' => trans('core::app.platform-modules.pim'),
    ])],
]);

it('stores a valid selection for the module gate', function () {
    app(PlatformModules::class)->store(['records', 'pim']);

    app()->forgetScopedInstances();

    expect(storedPlatformModules())->toBe('records,pim')
        ->and(app(ModuleGate::class)->modules())->toBe(['records', 'pim']);
});

it('refuses to store an invalid selection', function () {
    expect(fn () => app(PlatformModules::class)->store(['erp']))
        ->toThrow(InvalidArgumentException::class, trans('core::validation.platform-modules.unknown', ['module' => 'erp']));

    expect(storedPlatformModules())->not->toBe('erp');
});

describe('unopim:install --modules', function () {
    beforeEach(function () {
        $this->app->extend(Installer::class, fn () => new #[Signature('unopim:install {--skip-env-check} {--skip-admin-creation} {--with-demo-data} {--modules=} {--with-packages=} {--force}')] class extends Installer
        {
            protected function checkForEnvFile(): ?array
            {
                throw new RuntimeException('install-steps-reached');
            }
        });
    });

    it('fails before installing anything when the modules are invalid', function () {
        $this->artisan('unopim:install', ['--modules' => 'erp'])
            ->expectsOutputToContain(trans('installer::app.installer.console.invalid-modules', [
                'modules' => 'erp',
                'allowed' => 'pim, records, shopify',
            ]))
            ->assertFailed();
    });

    it('goes on to install with a valid module selection', function () {
        expect(fn () => $this->artisan('unopim:install', ['--modules' => 'pim,records'])->run())
            ->toThrow(RuntimeException::class, 'install-steps-reached');
    });
});
