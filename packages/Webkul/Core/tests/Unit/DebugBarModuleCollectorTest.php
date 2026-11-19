<?php

use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Core\Models\Channel;
use Webkul\DebugBar\DataCollector\ModuleCollector;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;

it('registers the module collector as a debugbar custom collector', function (): void {
    expect(config('debugbar.custom_collectors'))->toHaveKey(ModuleCollector::class, true);
});

it('keeps custom collectors configured by the application', function (): void {
    config(['debugbar.custom_collectors' => ['App\\Collectors\\ShopCollector' => true]]);

    (new DebugBarServiceProvider(app()))->register();

    expect(config('debugbar.custom_collectors'))
        ->toHaveKey('App\\Collectors\\ShopCollector', true)
        ->toHaveKey(ModuleCollector::class, true);
});

it('lets the application switch the module collector off', function (): void {
    config(['debugbar.custom_collectors' => [ModuleCollector::class => false]]);

    (new DebugBarServiceProvider(app()))->register();

    expect(config('debugbar.custom_collectors'))->toHaveKey(ModuleCollector::class, false);
});

it('does not build the module collector while the debugbar is disabled, as in console and queue workers', function (): void {
    DB::select('select 1');

    expect(app(LaravelDebugbar::class)->isEnabled())->toBeFalse()
        ->and(app()->resolved(ModuleCollector::class))->toBeFalse();
});

it('adds the module collector once the debugbar is enabled at runtime', function (): void {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    expect($debugbar->hasCollector('modules'))->toBeTrue()
        ->and($debugbar->getCollector('modules'))->toBeInstanceOf(ModuleCollector::class);

    $debugbar->disable();
});

it('records queries once the debugbar is enabled', function (): void {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    DB::select('select 1');

    $query = collect($debugbar->getCollector('modules')->queries)->last();

    expect($query['sql'])->toBe('select 1')
        ->and($query['duration_str'])->toBeString()->not->toBeEmpty();

    $debugbar->disable();
});

it('records retrieved models and composed views once the debugbar is enabled', function (): void {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    Channel::query()->firstOrFail();

    $viewPath = sys_get_temp_dir().'/module-collector-'.Str::random(8).'.blade.php';

    file_put_contents($viewPath, 'ok');

    try {
        view()->file($viewPath)->render();
    } finally {
        unlink($viewPath);
    }

    $collector = $debugbar->getCollector('modules');

    expect($collector->models)->toHaveKey(Channel::class)
        ->and($collector->count)->toBeGreaterThan(0)
        ->and($collector->views)->not->toBeEmpty();

    $debugbar->disable();
});

it('collects nothing while the debugbar is disabled', function (): void {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    $collector = $debugbar->getCollector('modules');

    $debugbar->disable();

    DB::select('select 1');

    expect($collector->queries)->toBeEmpty()
        ->and($collector->models)->toBeEmpty()
        ->and($collector->views)->toBeEmpty();
});

it('does not carry collected data across a request reset', function (): void {
    $debugbar = app(LaravelDebugbar::class);

    $debugbar->enable();

    $collector = $debugbar->getCollector('modules');

    DB::select('select 1');

    expect($collector->queries)->not->toBeEmpty();

    $debugbar->reset();

    expect($collector->queries)->toBeEmpty()
        ->and($collector->models)->toBeEmpty()
        ->and($collector->views)->toBeEmpty()
        ->and($collector->count)->toBe(0);

    $debugbar->disable();
});

it('does not add listeners each time the module collector is built', function (): void {
    $listenerCount = count(Event::getListeners(QueryExecuted::class));

    app()->make(ModuleCollector::class);
    app()->make(ModuleCollector::class);

    expect(Event::getListeners(QueryExecuted::class))->toHaveCount($listenerCount);
});
