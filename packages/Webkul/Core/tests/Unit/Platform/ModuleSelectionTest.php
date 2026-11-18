<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Core\Platform\Module;
use Webkul\Core\Platform\ModuleGate;
use Webkul\Core\Rules\PlatformModuleSelection;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config([
        'platform_modules.records' => ['name' => 'records', 'requires' => []],
        'platform_modules.dam'     => ['name' => 'dam', 'requires' => []],
        'platform_modules.shopify' => ['name' => 'shopify', 'requires' => ['pim'], 'route_prefixes' => ['admin.shopify.']],
    ]);
});

it('runs every independent module alone or in any combination', function (string $stored, array $expected) {
    expect($this->setPlatformModules($stored)->modules())->toBe($expected);
})->with([
    'pim alone'      => ['pim', ['pim']],
    'records alone'  => ['records', ['records']],
    'dam alone'      => ['dam', ['dam']],
    'records, dam'   => ['records,dam', ['records', 'dam']],
    'all together'   => ['pim,records,dam', ['pim', 'records', 'dam']],
    'shopify on pim' => ['pim,shopify', ['pim', 'shopify']],
]);

it('switches a dependent module off when its requirement is off', function () {
    $gate = $this->setPlatformModules('records,shopify');

    expect($gate->modules())->toBe(['records'])
        ->and($gate->enabled('shopify'))->toBeFalse()
        ->and($gate->allowsRoute('admin.shopify.credentials.index'))->toBeFalse();
});

it('falls back to pim when nothing usable is stored', function (string $stored) {
    expect($this->setPlatformModules($stored)->modules())->toBe([Module::Pim->value]);
})->with(['', 'unknown', 'shopify']);

it('lists registered modules as setting options with pim first', function () {
    expect(array_column(app(ModuleGate::class)->moduleOptions(), 'value'))
        ->toBe(['pim', 'records', 'dam', 'shopify']);
});

it('rejects a dependent module selected without its requirement', function () {
    $validator = Validator::make(['modules' => ['records', 'shopify']], ['modules' => ['required', app(PlatformModuleSelection::class)]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('modules'))->toBe(trans('core::validation.platform-modules.requires', [
            'module'   => 'shopify',
            'requires' => trans('core::app.platform-modules.pim'),
        ]));
});

it('rejects an unregistered module', function () {
    $validator = Validator::make(['modules' => 'pim,erp'], ['modules' => [app(PlatformModuleSelection::class)]]);

    expect($validator->errors()->first('modules'))
        ->toBe(trans('core::validation.platform-modules.unknown', ['module' => 'erp']));
});

it('accepts independent modules alone, together, and a dependent one with its requirement', function (array $modules) {
    expect(Validator::make(['modules' => $modules], ['modules' => [app(PlatformModuleSelection::class)]])->passes())->toBeTrue();
})->with([
    [['records']],
    [['dam']],
    [['records', 'dam']],
    [['pim', 'records', 'dam', 'shopify']],
]);
