<?php

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Webkul\Resource\Tests\Fixtures\FakeResource;

/*
|--------------------------------------------------------------------------
| Minimal routing bootstrap
|--------------------------------------------------------------------------
|
| These Unit tests run against a bare PHPUnit TestCase (no Laravel app is
| booted for this suite), yet AbstractResource::toViewModel() calls the
| route() helper. We stand up just enough of the Illuminate routing layer
| (container + router + url generator) so the throwaway "admin.fakes.*"
| route names used by FakeResource resolve, without booting the framework.
|
*/
beforeEach(function () {
    $container = new Container;

    Container::setInstance($container);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);

    $container->instance('app', $container);

    $events = new Dispatcher($container);
    $container->instance('events', $events);

    $router = new Router($events, $container);
    $container->instance('router', $router);

    $request = Request::create('/', 'GET');
    $container->instance('request', $request);

    Route::get('fakes', fn () => null)->name('admin.fakes.index');
    Route::get('fakes/create', fn () => null)->name('admin.fakes.create');
    Route::post('fakes', fn () => null)->name('admin.fakes.store');
    Route::put('fakes/{id}', fn () => null)->name('admin.fakes.update');

    $router->getRoutes()->refreshNameLookups();

    $container->instance('url', new UrlGenerator($router->getRoutes(), $request));
});

it('produces a view model the frontend can consume', function () {
    $vm = (new FakeResource)->toViewModel();

    expect($vm['routePrefix'])->toBe('admin.fakes');
    expect($vm['aclPrefix'])->toBe('fakes');
    expect($vm['schema'])->toHaveCount(1);
    expect($vm['schema'][0]['name'])->toBe('name');
    expect($vm['urls']['index'])->toBeString();
    expect($vm['urls']['create'])->toBeString();
    expect($vm['urls']['store'])->toBeString();
    expect($vm['urls']['update'])->toBeString();
});
