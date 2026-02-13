<?php

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\User\Bouncer;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| Bouncer::allow() Security Tests
|--------------------------------------------------------------------------
|
| Verifies that the static Bouncer::allow() method now routes through
| TenantPermissionGuard for users with permission_type=all, blocking
| platform-reserved permissions for tenant users.
|
*/

afterEach(function () {
    auth('admin')->logout();
});

// -- Tenant admin blocked from platform-reserved --------------------------

it('Bouncer::allow blocks platform-reserved permission for tenant admin', function () {
    $this->actingAsTenant($this->tenantA);

    $admin = Admin::find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    Bouncer::allow('platform.tenants');
})->throws(HttpException::class);

it('Bouncer::allow blocks platform.system for tenant admin', function () {
    $this->actingAsTenant($this->tenantA);

    $admin = Admin::find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    Bouncer::allow('platform.system');
})->throws(HttpException::class);

// -- Tenant admin allowed for regular permissions -------------------------

it('Bouncer::allow permits regular permissions for tenant admin', function () {
    $this->actingAsTenant($this->tenantA);

    $admin = Admin::find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    Bouncer::allow('catalog.products');

    expect(true)->toBeTrue();
});

it('Bouncer::allow permits dashboard for tenant admin', function () {
    $this->actingAsTenant($this->tenantA);

    $admin = Admin::find($this->fixture($this->tenantA, 'admin_id'));
    auth('admin')->login($admin);

    Bouncer::allow('dashboard');

    expect(true)->toBeTrue();
});

// -- Platform operator allowed for platform-reserved ----------------------

it('Bouncer::allow permits platform-reserved permissions for platform operator', function () {
    $this->clearTenantContext();

    $roleId = DB::table('roles')->insertGetId([
        'name'            => 'Platform Op Role',
        'description'     => 'Platform operator test role',
        'permission_type' => 'all',
        'permissions'     => json_encode([]),
        'tenant_id'       => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $adminId = DB::table('admins')->insertGetId([
        'name'       => 'Platform Op',
        'email'      => 'platformop-bouncer@test.local',
        'password'   => bcrypt('password'),
        'role_id'    => $roleId,
        'status'     => 1,
        'tenant_id'  => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = Admin::withoutGlobalScopes()->find($adminId);
    auth('admin')->login($admin);

    Bouncer::allow('platform.tenants');

    expect(true)->toBeTrue();
});

// -- Unauthenticated user -------------------------------------------------

it('Bouncer::allow aborts 401 for unauthenticated user', function () {
    auth('admin')->logout();

    Bouncer::allow('dashboard');
})->throws(HttpException::class);
