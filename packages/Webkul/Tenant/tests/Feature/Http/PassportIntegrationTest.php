<?php

use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Webkul\AdminApi\Models\Client;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\Tenant\Models\TenantOAuthClient;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| Passport / OAuth2 Tenant Integration Tests
|--------------------------------------------------------------------------
|
| Verifies that:
| - Passport's client model (AdminApi\Client) has BelongsToTenant
| - TenantOAuthClient is a standalone tenant-aware Passport client
| - Admin::findForPassport() works correctly with tenant_id
| - Token → admin → tenant resolution chain
|
*/

beforeEach(function () {
    core()->setCurrentTenantId(null);
});

it('confirms AdminApi Client model uses BelongsToTenant trait', function () {
    $traits = class_uses_recursive(Client::class);

    expect($traits)->toHaveKey(BelongsToTenant::class);
});

it('confirms TenantOAuthClient uses BelongsToTenant trait', function () {
    $traits = class_uses_recursive(TenantOAuthClient::class);

    expect($traits)->toHaveKey(BelongsToTenant::class);
});

it('confirms Passport uses AdminApi Client as its client model', function () {
    $clientModel = Passport::clientModel();

    expect($clientModel)->toBe(Client::class);
});

it('confirms Admin::findForPassport works in tenant context', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    $roleA = DB::table('roles')->insertGetId([
        'name' => 'Role-A-'.uniqid(), 'description' => 'Test',
        'permission_type' => 'all', 'permissions' => json_encode([]),
        'tenant_id' => $tA, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $roleB = DB::table('roles')->insertGetId([
        'name' => 'Role-B-'.uniqid(), 'description' => 'Test',
        'permission_type' => 'all', 'permissions' => json_encode([]),
        'tenant_id' => $tB, 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('admins')->insert([
        'tenant_id' => $tA, 'name' => 'Admin A', 'email' => 'admin-a@test.com',
        'password' => bcrypt('password'), 'role_id' => $roleA, 'status' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('admins')->insert([
        'tenant_id' => $tB, 'name' => 'Admin B', 'email' => 'admin-b@test.com',
        'password' => bcrypt('password'), 'role_id' => $roleB, 'status' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // In tenant A context, findForPassport should only find Admin A
    core()->setCurrentTenantId($tA);
    $admin = (new Admin)->findForPassport('admin-a@test.com');
    expect($admin)->not->toBeNull();
    expect($admin->name)->toBe('Admin A');

    // Admin B should not be visible in Tenant A context
    $notFound = (new Admin)->findForPassport('admin-b@test.com');
    expect($notFound)->toBeNull();
});

it('resolves tenant from admin via the token chain', function () {
    $tA = $this->tenantA->id;

    $roleId = DB::table('roles')->insertGetId([
        'name' => 'Role-'.uniqid(), 'description' => 'Test',
        'permission_type' => 'all', 'permissions' => json_encode([]),
        'tenant_id' => $tA, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $adminId = DB::table('admins')->insertGetId([
        'tenant_id' => $tA, 'name' => 'Token Admin', 'email' => 'token@test.com',
        'password' => bcrypt('password'), 'role_id' => $roleId, 'status' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Verify the admin has the correct tenant_id
    $admin = Admin::withoutGlobalScopes()->find($adminId);
    expect($admin->tenant_id)->toBe($tA);

    // The chain: token → admin.tenant_id → Tenant model
    $tenant = \Webkul\Tenant\Models\Tenant::find($admin->tenant_id);
    expect($tenant)->not->toBeNull();
    expect($tenant->id)->toBe($tA);
});

it('treats platform admin (null tenant_id) as valid without tenant context', function () {
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'Platform Role-'.uniqid(), 'description' => 'Platform',
        'permission_type' => 'all', 'permissions' => json_encode([]),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $adminId = DB::table('admins')->insertGetId([
        'tenant_id' => null, 'name' => 'Platform Op', 'email' => 'platform@test.com',
        'password' => bcrypt('password'), 'role_id' => $roleId, 'status' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Platform admin has null tenant_id — visible in platform (null) context
    core()->setCurrentTenantId(null);
    $admin = Admin::find($adminId);
    expect($admin)->not->toBeNull();
    expect($admin->tenant_id)->toBeNull();
});
