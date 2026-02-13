<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Auth\TenantPermissionGuard;
use Webkul\Tenant\Models\Tenant;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Mail::fake();
});

// --- Story 5.2: Tenant Admin full-resource management ---

it('Tenant Admin has all tenant-scoped permissions via permission_type=all', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Tenant Admin Test',
        'code'            => 'ta-test-'.uniqid(),
        'permission_type' => 'all',
        'is_locked'       => true,
    ]);

    expect($role->permission_type)->toBe('all');
    expect($role->tenant_id)->toBe($tenant->id);

    core()->setCurrentTenantId(null);
});

// --- Story 5.3: Tenant User configurable permissions ---

it('Tenant User has custom permissions within tenant scope', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Tenant User Test',
        'code'            => 'tu-test-'.uniqid(),
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'catalog', 'catalog.products'],
    ]);

    expect($role->permission_type)->toBe('custom');
    expect($role->permissions)->toContain('dashboard');
    expect($role->permissions)->toContain('catalog.products');

    core()->setCurrentTenantId(null);
});

// --- Story 5.4: Platform Operator cross-tenant access ---

it('Platform Operator has tenant_id NULL for cross-tenant access', function () {
    core()->setCurrentTenantId(null);

    $role = Role::withoutGlobalScopes()->create([
        'name'            => 'Platform Operator Test',
        'code'            => 'po-test-'.uniqid(),
        'permission_type' => 'all',
        'is_locked'       => true,
        'tenant_id'       => null,
    ]);

    expect($role->tenant_id)->toBeNull();
    expect($role->permission_type)->toBe('all');
});

// --- Story 5.5: Tenant role isolation from platform-reserved permissions ---

it('TenantPermissionGuard blocks platform-reserved permissions for tenant users', function () {
    $guard = new TenantPermissionGuard;

    // Create a mock tenant user with tenant_id set
    $user = new \stdClass;
    $user->tenant_id = 1;

    expect($guard->isAllowed($user, 'platform.tenants'))->toBeFalse();
    expect($guard->isAllowed($user, 'platform.tenants.create'))->toBeFalse();
    expect($guard->isAllowed($user, 'platform.system'))->toBeFalse();
});

it('TenantPermissionGuard allows regular permissions for tenant users', function () {
    $guard = new TenantPermissionGuard;

    $user = new \stdClass;
    $user->tenant_id = 1;

    expect($guard->isAllowed($user, 'dashboard'))->toBeTrue();
    expect($guard->isAllowed($user, 'catalog.products'))->toBeTrue();
    expect($guard->isAllowed($user, 'settings.users.users.create'))->toBeTrue();
});

it('TenantPermissionGuard allows all permissions for platform users', function () {
    $guard = new TenantPermissionGuard;

    $user = new \stdClass;
    $user->tenant_id = null;

    expect($guard->isAllowed($user, 'platform.tenants'))->toBeTrue();
    expect($guard->isAllowed($user, 'platform.system'))->toBeTrue();
    expect($guard->isAllowed($user, 'dashboard'))->toBeTrue();
});

it('TenantPermissionGuard filters permissions list for tenant users', function () {
    $guard = new TenantPermissionGuard;

    $user = new \stdClass;
    $user->tenant_id = 1;

    $permissions = ['dashboard', 'catalog', 'platform.tenants', 'platform.system'];
    $filtered = $guard->filterPermissions($user, $permissions);

    expect($filtered)->toContain('dashboard');
    expect($filtered)->toContain('catalog');
    expect($filtered)->not->toContain('platform.tenants');
    expect($filtered)->not->toContain('platform.system');
});

it('TenantPermissionGuard does not filter for platform users', function () {
    $guard = new TenantPermissionGuard;

    $user = new \stdClass;
    $user->tenant_id = null;

    $permissions = ['dashboard', 'platform.tenants', 'platform.system'];
    $filtered = $guard->filterPermissions($user, $permissions);

    expect($filtered)->toHaveCount(3);
});

it('isPlatformReserved correctly identifies platform prefixes', function () {
    $guard = new TenantPermissionGuard;

    expect($guard->isPlatformReserved('platform.tenants'))->toBeTrue();
    expect($guard->isPlatformReserved('platform.tenants.create'))->toBeTrue();
    expect($guard->isPlatformReserved('dashboard'))->toBeFalse();
    expect($guard->isPlatformReserved('catalog.products'))->toBeFalse();
});

it('isRoleScopeValid validates tenant role must have tenant_id', function () {
    $guard = new TenantPermissionGuard;

    $role = new \stdClass;
    $role->code = 'tenant-admin';
    $role->tenant_id = 1;

    expect($guard->isRoleScopeValid($role))->toBeTrue();

    $role->tenant_id = null;
    expect($guard->isRoleScopeValid($role))->toBeFalse();
});

it('isRoleScopeValid validates platform role must have null tenant_id', function () {
    $guard = new TenantPermissionGuard;

    $role = new \stdClass;
    $role->code = 'platform-operator';
    $role->tenant_id = null;

    expect($guard->isRoleScopeValid($role))->toBeTrue();

    $role->tenant_id = 1;
    expect($guard->isRoleScopeValid($role))->toBeFalse();
});
