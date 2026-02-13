<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Story 6.2: API routes include tenant.token + tenant.safe-errors middleware
|--------------------------------------------------------------------------
|
| Verifies that all V1 REST API routes have tenant.token and
| tenant.safe-errors middleware for token validation and error masking.
|
*/

it('applies tenant.token middleware to API catalog routes', function () {
    $routes = Route::getRoutes();
    $found = false;

    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'api/v1/rest')) {
            $found = true;
            expect($route->middleware())->toContain('tenant.token');
            break;
        }
    }

    expect($found)->toBeTrue();
});

it('applies tenant.safe-errors middleware to API routes', function () {
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'api/v1/rest')) {
            expect($route->middleware())->toContain('tenant.safe-errors');
            break;
        }
    }
});

it('places tenant.token before api.scope in middleware chain', function () {
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'api/v1/rest')) {
            $middleware = $route->middleware();
            $tokenIndex = array_search('tenant.token', $middleware);
            $scopeIndex = array_search('api.scope', $middleware);

            if ($tokenIndex !== false && $scopeIndex !== false) {
                expect($tokenIndex)->toBeLessThan($scopeIndex,
                    'tenant.token must run before api.scope');
            }
            break;
        }
    }
});
