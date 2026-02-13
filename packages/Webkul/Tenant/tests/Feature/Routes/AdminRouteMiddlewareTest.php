<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Story 6.1: Admin routes include tenant middleware
|--------------------------------------------------------------------------
|
| Verifies that all authenticated admin route groups have the 'tenant'
| middleware applied so that TenantScope is activated for web requests.
|
*/

it('applies tenant middleware to catalog routes', function () {
    $route = Route::getRoutes()->getByName('admin.catalog.products.index');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('tenant');
});

it('applies tenant middleware to settings routes', function () {
    $route = Route::getRoutes()->getByName('admin.settings.channels.index');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('tenant');
});

it('applies tenant middleware to configuration routes', function () {
    $route = Route::getRoutes()->getByName('admin.configuration.edit');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('tenant');
});

it('applies tenant middleware to notification routes', function () {
    $route = Route::getRoutes()->getByName('admin.notification.index');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('tenant');
});

it('applies tenant middleware to dashboard routes', function () {
    $route = Route::getRoutes()->getByName('admin.dashboard.index');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('tenant');
});

it('applies tenant middleware to history routes', function () {
    $route = Route::getRoutes()->getByName('admin.history.index');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('tenant');
});

it('does NOT apply tenant middleware to auth routes', function () {
    $route = Route::getRoutes()->getByName('admin.session.create');

    expect($route)->not->toBeNull();
    expect($route->middleware())->not->toContain('tenant');
});
