<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 8.5: Tenant Middleware on Completeness Routes
|--------------------------------------------------------------------------
|
| Verifies that completeness routes have tenant middleware applied.
|
*/

it('completeness settings routes have tenant middleware', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => str_contains($r->uri(), 'completeness-settings'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->middleware())->toContain('tenant');
    }
});

it('completeness dashboard route has tenant middleware', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => str_contains($r->uri(), 'completeness/dashboard'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->middleware())->toContain('tenant');
    }
});

it('completeness routes are registered with correct names', function () {
    expect(Route::has('admin.catalog.families.completeness.edit'))->toBeTrue();
    expect(Route::has('admin.catalog.families.completeness.update'))->toBeTrue();
    expect(Route::has('admin.catalog.families.completeness.mass_update'))->toBeTrue();
    expect(Route::has('admin.dashboard.completeness.data'))->toBeTrue();
});
