<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Webkul\Tenant\Http\Middleware\TenantMiddleware;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| Header Security Tests
|--------------------------------------------------------------------------
|
| Verifies that X-Tenant-ID header resolution requires authentication
| and validates the user belongs to the requested tenant.
| Covers the security fix for unauthenticated header injection.
|
*/

function callSecurityMiddleware(Request $request): Response
{
    $middleware = new TenantMiddleware;

    return $middleware->handle($request, function ($req) {
        return new Response('OK', 200);
    });
}

beforeEach(function () {
    core()->setCurrentTenantId(null);
});

afterEach(function () {
    auth('admin')->logout();
});

// -- Unauthenticated header rejection ------------------------------------

it('rejects X-Tenant-ID header when no user is authenticated', function () {
    config(['app.url' => 'http://localhost']);

    $request = Request::create('http://localhost/api/v1/products');
    $request->headers->set('X-Tenant-ID', (string) $this->tenantA->id);

    callSecurityMiddleware($request);

    expect(core()->getCurrentTenantId())->toBeNull();
});

// -- Authenticated tenant user (same tenant) ------------------------------

it('accepts X-Tenant-ID header for authenticated tenant user matching their tenant', function () {
    config(['app.url' => 'http://localhost']);

    $admin = Admin::withoutGlobalScopes()->find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    $request = Request::create('http://localhost/admin');
    $request->headers->set('X-Tenant-ID', (string) $this->tenantA->id);

    callSecurityMiddleware($request);

    expect(core()->getCurrentTenantId())->toBe($this->tenantA->id);
});

// -- Cross-tenant rejection -----------------------------------------------

it('rejects X-Tenant-ID header for tenant user accessing different tenant', function () {
    config(['app.url' => 'http://localhost']);

    $admin = Admin::withoutGlobalScopes()->find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    $request = Request::create('http://localhost/admin');
    $request->headers->set('X-Tenant-ID', (string) $this->tenantB->id);

    callSecurityMiddleware($request);

    expect(core()->getCurrentTenantId())->toBeNull();
});

// -- Platform operator can switch -----------------------------------------

it('allows platform operator to switch to any tenant via header', function () {
    config(['app.url' => 'http://localhost']);

    $platformAdminId = DB::table('admins')->insertGetId([
        'name'       => 'Platform Operator',
        'email'      => 'platform-header@test.local',
        'password'   => bcrypt('password'),
        'role_id'    => $this->fixture($this->tenantA, 'role_id'),
        'status'     => 1,
        'tenant_id'  => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $platformAdmin = Admin::withoutGlobalScopes()->find($platformAdminId);
    auth('admin')->login($platformAdmin);

    $request = Request::create('http://localhost/admin');
    $request->headers->set('X-Tenant-ID', (string) $this->tenantB->id);

    callSecurityMiddleware($request);

    expect(core()->getCurrentTenantId())->toBe($this->tenantB->id);
});

// -- Non-existent tenant --------------------------------------------------

it('rejects X-Tenant-ID header for non-existent tenant even with auth', function () {
    config(['app.url' => 'http://localhost']);

    $admin = Admin::withoutGlobalScopes()->find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    $request = Request::create('http://localhost/admin');
    $request->headers->set('X-Tenant-ID', '99999');

    callSecurityMiddleware($request);

    expect(core()->getCurrentTenantId())->toBeNull();
});
