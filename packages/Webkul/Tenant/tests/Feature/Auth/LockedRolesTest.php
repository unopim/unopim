<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;
use Webkul\User\Models\Role;

beforeEach(function () {
    Mail::fake();
});

// --- Story 5.1: Four locked roles & permission matrices ---

it('tenant-roles config defines four locked roles', function () {
    $roles = config('tenant-roles.locked_roles');

    expect($roles)->toHaveKeys([
        'tenant-admin',
        'tenant-user',
        'platform-operator',
        'support-agent',
    ]);
});

it('tenant-admin role has permission_type "all" and scope "tenant"', function () {
    $role = config('tenant-roles.locked_roles.tenant-admin');

    expect($role['permission_type'])->toBe('all');
    expect($role['scope'])->toBe('tenant');
});

it('tenant-user role has permission_type "custom" and scope "tenant"', function () {
    $role = config('tenant-roles.locked_roles.tenant-user');

    expect($role['permission_type'])->toBe('custom');
    expect($role['scope'])->toBe('tenant');
});

it('platform-operator role has permission_type "all" and scope "platform"', function () {
    $role = config('tenant-roles.locked_roles.platform-operator');

    expect($role['permission_type'])->toBe('all');
    expect($role['scope'])->toBe('platform');
});

it('support-agent role has permission_type "custom" and scope "platform"', function () {
    $role = config('tenant-roles.locked_roles.support-agent');

    expect($role['permission_type'])->toBe('custom');
    expect($role['scope'])->toBe('platform');
});

it('support-agent has default read-only permissions defined', function () {
    $role = config('tenant-roles.locked_roles.support-agent');

    expect($role['default_permissions'])->toContain('dashboard');
    expect($role['default_permissions'])->toContain('catalog');
    expect($role['default_permissions'])->toContain('history.view');
});

it('Role model has is_locked in fillable', function () {
    $role = new Role;

    expect($role->getFillable())->toContain('is_locked');
});

it('Role model has code in fillable', function () {
    $role = new Role;

    expect($role->getFillable())->toContain('code');
});

it('Role model casts is_locked to boolean', function () {
    $role = new Role;
    $casts = $role->getCasts();

    expect($casts)->toHaveKey('is_locked');
    expect($casts['is_locked'])->toBe('boolean');
});

it('prevents deleting locked roles (FR-5.1)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Test Locked Role',
        'code'            => 'test-locked-'.uniqid(),
        'permission_type' => 'all',
        'is_locked'       => true,
    ]);

    expect(fn () => $role->delete())->toThrow(\RuntimeException::class, 'Cannot delete a locked role.');

    core()->setCurrentTenantId(null);
});

it('prevents changing permission_type on locked roles (FR-5.1)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Test Locked Role 2',
        'code'            => 'test-locked-2-'.uniqid(),
        'permission_type' => 'all',
        'is_locked'       => true,
    ]);

    expect(fn () => $role->update(['permission_type' => 'custom']))
        ->toThrow(\RuntimeException::class, 'Cannot change permission_type on a locked role.');

    core()->setCurrentTenantId(null);
});

it('prevents unlocking a locked role', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Test Locked Role 3',
        'code'            => 'test-locked-3-'.uniqid(),
        'permission_type' => 'all',
        'is_locked'       => true,
    ]);

    expect(fn () => $role->update(['is_locked' => false]))
        ->toThrow(\RuntimeException::class, 'Cannot unlock a locked role.');

    core()->setCurrentTenantId(null);
});

it('allows updating name/description on locked roles', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Test Locked Role 4',
        'code'            => 'test-locked-4-'.uniqid(),
        'permission_type' => 'all',
        'is_locked'       => true,
    ]);

    $role->update(['name' => 'Updated Name', 'description' => 'New description']);
    $role->refresh();

    expect($role->name)->toBe('Updated Name');
    expect($role->description)->toBe('New description');

    core()->setCurrentTenantId(null);
});

it('allows deleting non-locked roles', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $role = Role::create([
        'name'            => 'Deletable Role',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
        'is_locked'       => false,
    ]);

    $roleId = $role->id;
    $role->delete();

    expect(Role::withoutGlobalScopes()->find($roleId))->toBeNull();

    core()->setCurrentTenantId(null);
});
