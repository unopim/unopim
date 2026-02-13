<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Admin Routes, ACL, and Menu Tests
|--------------------------------------------------------------------------
|
| Verifies route registration, ACL config loading, and menu config loading.
|
*/

// -- Routes exist -----------------------------------------------------------

it('registers admin.settings.tenants.index route', function () {
    expect(Route::has('admin.settings.tenants.index'))->toBeTrue();
});

it('registers admin.settings.tenants.create route', function () {
    expect(Route::has('admin.settings.tenants.create'))->toBeTrue();
});

it('registers admin.settings.tenants.store route', function () {
    expect(Route::has('admin.settings.tenants.store'))->toBeTrue();
});

it('registers admin.settings.tenants.show route', function () {
    expect(Route::has('admin.settings.tenants.show'))->toBeTrue();
});

it('registers admin.settings.tenants.edit route', function () {
    expect(Route::has('admin.settings.tenants.edit'))->toBeTrue();
});

it('registers admin.settings.tenants.update route', function () {
    expect(Route::has('admin.settings.tenants.update'))->toBeTrue();
});

it('registers admin.settings.tenants.destroy route', function () {
    expect(Route::has('admin.settings.tenants.destroy'))->toBeTrue();
});

it('registers admin.settings.tenants.suspend route', function () {
    expect(Route::has('admin.settings.tenants.suspend'))->toBeTrue();
});

it('registers admin.settings.tenants.activate route', function () {
    expect(Route::has('admin.settings.tenants.activate'))->toBeTrue();
});

// -- API routes exist -------------------------------------------------------

it('registers api.v1.tenants.index route', function () {
    expect(Route::has('api.v1.tenants.index'))->toBeTrue();
});

it('registers api.v1.tenants.store route', function () {
    expect(Route::has('api.v1.tenants.store'))->toBeTrue();
});

// -- ACL config loaded ------------------------------------------------------

it('loads tenant ACL config with settings.tenants key', function () {
    $acl = config('acl');

    $keys = collect($acl)->pluck('key')->toArray();
    expect($keys)->toContain('settings.tenants');
});

it('loads tenant ACL children (create, edit, delete, suspend, activate)', function () {
    $acl = config('acl');

    $keys = collect($acl)->pluck('key')->toArray();
    expect($keys)->toContain('settings.tenants.create');
    expect($keys)->toContain('settings.tenants.edit');
    expect($keys)->toContain('settings.tenants.delete');
    expect($keys)->toContain('settings.tenants.suspend');
    expect($keys)->toContain('settings.tenants.activate');
});

// -- Menu config loaded -----------------------------------------------------

it('loads tenant menu config under settings', function () {
    $menu = config('menu.admin');

    $keys = collect($menu)->pluck('key')->toArray();
    expect($keys)->toContain('settings.tenants');
});

// -- Middleware on routes ---------------------------------------------------

it('admin tenant routes use platform.operator middleware', function () {
    $route = Route::getRoutes()->getByName('admin.settings.tenants.index');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain('platform.operator');
});
