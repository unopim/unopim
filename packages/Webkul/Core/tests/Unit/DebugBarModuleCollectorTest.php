<?php

use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Support\Facades\DB;
use Webkul\DebugBar\DataCollector\ModuleCollector;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;

it('registers the module collector as a debugbar custom collector', function () {
    expect(config('debugbar.custom_collectors'))->toHaveKey(ModuleCollector::class, true);
});

it('keeps custom collectors configured by the application', function () {
    config(['debugbar.custom_collectors' => ['App\\Collectors\\ShopCollector' => true]]);

    (new DebugBarServiceProvider(app()))->register();

    expect(config('debugbar.custom_collectors'))
        ->toHaveKey('App\\Collectors\\ShopCollector', true)
        ->toHaveKey(ModuleCollector::class, true);
});

it('lets the application switch the module collector off', function () {
    config(['debugbar.custom_collectors' => [ModuleCollector::class => false]]);

    (new DebugBarServiceProvider(app()))->register();

    expect(config('debugbar.custom_collectors'))->toHaveKey(ModuleCollector::class, false);
});

it('does not build the module collector while the debugbar is disabled, as in console and queue workers', function () {
    DB::select('select 1');

    expect(app(LaravelDebugbar::class)->isEnabled())->toBeFalse()
        ->and(app()->resolved(ModuleCollector::class))->toBeFalse();
});

it('adds the module collector once the debugbar is enabled at runtime', function () {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    expect($debugbar->hasCollector('modules'))->toBeTrue()
        ->and($debugbar->getCollector('modules'))->toBeInstanceOf(ModuleCollector::class);

    $debugbar->disable();
});

it('records queries once the debugbar is enabled', function () {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    DB::select('select 1');

    $query = collect($debugbar->getCollector('modules')->queries)->last();

    expect($query['sql'])->toBe('select 1')
        ->and($query['duration_str'])->toBeString()->not->toBeEmpty();

    $debugbar->disable();
});
