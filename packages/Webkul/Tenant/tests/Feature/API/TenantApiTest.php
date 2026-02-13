<?php

use Webkul\Tenant\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Tenant API Tests
|--------------------------------------------------------------------------
|
| Verifies REST API endpoints for tenant management.
| Since these tests use TenantTestCase (no OAuth setup), we test
| the controller logic via direct method invocation and route structure.
|
*/

// -- Route registration -----------------------------------------------------

it('registers API routes for tenant CRUD', function () {
    $routes = [
        'api.v1.tenants.index',
        'api.v1.tenants.show',
        'api.v1.tenants.store',
        'api.v1.tenants.update',
        'api.v1.tenants.destroy',
        'api.v1.tenants.suspend',
        'api.v1.tenants.activate',
    ];

    foreach ($routes as $routeName) {
        expect(\Illuminate\Support\Facades\Route::has($routeName))
            ->toBeTrue("Route {$routeName} should be registered");
    }
});

// -- API route middleware ---------------------------------------------------

it('API tenant routes use auth:api middleware', function () {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('api.v1.tenants.index');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain('auth:api');
});

it('API tenant routes use tenant.token middleware', function () {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('api.v1.tenants.index');

    expect($route->gatherMiddleware())->toContain('tenant.token');
});

it('API tenant routes use tenant.safe-errors middleware', function () {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('api.v1.tenants.index');

    expect($route->gatherMiddleware())->toContain('tenant.safe-errors');
});

// -- Controller unit tests via app() resolution -----------------------------

it('TenantApiController index returns paginated tenants', function () {
    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $response = $controller->index();

    expect($response->getStatusCode())->toBe(200);
    $data = $response->getData(true);
    expect($data)->toHaveKey('data');
    expect($data)->toHaveKey('total');
});

it('TenantApiController show returns a single tenant', function () {
    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $response = $controller->show($this->tenantA->id);

    expect($response->getStatusCode())->toBe(200);
    $data = $response->getData(true);
    expect($data['data']['id'])->toBe($this->tenantA->id);
});

it('TenantApiController update modifies tenant name', function () {
    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $request = new \Illuminate\Http\Request;
    $request->merge(['name' => 'API Updated']);
    $request->setMethod('PUT');

    $response = $controller->update($request, $this->tenantA->id);

    expect($response->getStatusCode())->toBe(200);
    $this->tenantA->refresh();
    expect($this->tenantA->name)->toBe('API Updated');
});

it('TenantApiController suspend transitions tenant to suspended', function () {
    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $response = $controller->suspend($this->tenantA->id);

    expect($response->getStatusCode())->toBe(200);
    $this->tenantA->refresh();
    expect($this->tenantA->status)->toBe(Tenant::STATUS_SUSPENDED);
});

it('TenantApiController activate transitions suspended tenant to active', function () {
    $this->tenantA->update(['status' => Tenant::STATUS_SUSPENDED]);

    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $response = $controller->activate($this->tenantA->id);

    expect($response->getStatusCode())->toBe(200);
    $this->tenantA->refresh();
    expect($this->tenantA->status)->toBe(Tenant::STATUS_ACTIVE);
});

it('TenantApiController destroy rejects provisioning tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $response = $controller->destroy($tenant->id);

    expect($response->getStatusCode())->toBe(400);
});

it('TenantApiController suspend returns 400 for invalid transition', function () {
    // A provisioning tenant cannot transition directly to suspended
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $controller = app(\Webkul\Tenant\Http\Controllers\API\TenantApiController::class);

    $response = $controller->suspend($tenant->id);

    expect($response->getStatusCode())->toBe(400);
});
