<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Tenant\Http\Middleware\PlatformOperatorMiddleware;
use Webkul\Tenant\Models\Tenant;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| PlatformOperatorMiddleware Tests
|--------------------------------------------------------------------------
|
| Verifies that only platform operators (tenant_id = null) can pass through.
|
*/

function callPlatformMiddleware(Request $request): SymfonyResponse
{
    $middleware = new PlatformOperatorMiddleware;

    return $middleware->handle($request, function ($req) {
        return new Response('OK', 200);
    });
}

it('allows platform operator (tenant_id = null) through', function () {
    $admin = Admin::factory()->create([
        'tenant_id' => null,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);

    auth('admin')->login($admin);

    $request = Request::create('/admin/settings/tenants');
    $response = callPlatformMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('blocks tenant user (tenant_id != null) with 403', function () {
    $admin = Admin::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);

    auth('admin')->login($admin);

    $request = Request::create('/admin/settings/tenants');

    callPlatformMiddleware($request);
})->throws(HttpException::class);

it('blocks tenant user with JSON 403 when request expects JSON', function () {
    $admin = Admin::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);

    auth('admin')->login($admin);

    $request = Request::create('/admin/settings/tenants');
    $request->headers->set('Accept', 'application/json');

    $response = callPlatformMiddleware($request);

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true))->toHaveKey('message');
});

it('blocks unauthenticated requests', function () {
    auth('admin')->logout();

    $request = Request::create('/admin/settings/tenants');

    callPlatformMiddleware($request);
})->throws(HttpException::class);

it('distinguishes between platform operator and tenant admin', function () {
    $platformAdmin = Admin::factory()->create([
        'tenant_id' => null,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);
    $tenantAdmin = Admin::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);

    // Platform operator passes
    auth('admin')->login($platformAdmin);
    $request = Request::create('/admin/settings/tenants');
    $response = callPlatformMiddleware($request);
    expect($response->getStatusCode())->toBe(200);

    // Tenant admin blocked (JSON response to avoid abort)
    auth('admin')->login($tenantAdmin);
    $request = Request::create('/admin/settings/tenants');
    $request->headers->set('Accept', 'application/json');
    $response = callPlatformMiddleware($request);
    expect($response->getStatusCode())->toBe(403);
});
