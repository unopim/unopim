<?php

use Webkul\User\Models\Role;

use function Pest\Laravel\putJson;

/*
 * A delegated role manager (custom role holding settings.roles.edit) must not
 * grant, on any role, a permission it does not itself hold, nor promote a role
 * to full access. RoleForm::authorize mirrors UserController's assignment guard.
 */
it('forbids a role manager from granting itself unheld permissions', function () {
    $admin = $this->loginWithPermissions('custom', [
        'dashboard',
        'settings.roles',
        'settings.roles.edit',
    ]);

    putJson(route('admin.settings.roles.update', $admin->role_id), [
        'name'            => $admin->role->name,
        'permission_type' => 'custom',
        'permissions'     => ['settings.roles.edit', 'settings.users.users.delete'],
    ])->assertForbidden();

    expect($admin->role->refresh()->permissions)->not->toContain('settings.users.users.delete');
});

it('forbids the same escalation against another role', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings.roles', 'settings.roles.edit']);

    $other = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    putJson(route('admin.settings.roles.update', $other->id), [
        'name'            => $other->name,
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'catalog.products.delete'],
    ])->assertForbidden();

    expect($other->refresh()->permissions)->not->toContain('catalog.products.delete');
});

it('forbids promoting a role to full access from a custom role', function () {
    $admin = $this->loginWithPermissions('custom', ['dashboard', 'settings.roles', 'settings.roles.edit']);

    putJson(route('admin.settings.roles.update', $admin->role_id), [
        'name'            => $admin->role->name,
        'permission_type' => 'all',
    ])->assertForbidden();
});

it('allows a role manager to grant permissions it already holds', function () {
    $admin = $this->loginWithPermissions('custom', [
        'dashboard',
        'settings.roles',
        'settings.roles.edit',
    ]);

    putJson(route('admin.settings.roles.update', $admin->role_id), [
        'name'            => $admin->role->name,
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'settings.roles'],
    ])->assertOk();

    expect($admin->role->refresh()->permissions)->toBe(['dashboard', 'settings.roles']);
});
