<?php

use Webkul\User\Models\Role;

it('forbids a role editor from granting a permission it does not hold', function () {
    $admin = $this->loginWithPermissions('custom', [
        'settings',
        'settings.roles',
        'settings.roles.edit',
    ]);

    $this->put(route('admin.settings.roles.update', $admin->role->id), [
        'name'            => $admin->role->name,
        'permission_type' => 'custom',
        'permissions'     => ['settings.roles.edit', 'settings.users.users.create'],
    ])->assertForbidden();

    expect($admin->role->refresh()->permissions)->not->toContain('settings.users.users.create');
});

it('forbids the same escalation against another role', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.roles', 'settings.roles.edit']);

    $other = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $this->put(route('admin.settings.roles.update', $other->id), [
        'name'            => $other->name,
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'settings.users.users.create'],
    ])->assertForbidden();

    expect($other->refresh()->permissions)->not->toContain('settings.users.users.create');
});

it('forbids promoting a role to full access from a custom role', function () {
    $admin = $this->loginWithPermissions('custom', ['settings', 'settings.roles', 'settings.roles.edit']);

    $this->put(route('admin.settings.roles.update', $admin->role->id), [
        'name'            => $admin->role->name,
        'permission_type' => 'all',
    ])->assertForbidden();
});

it('still allows granting permissions the editor already holds', function () {
    $admin = $this->loginWithPermissions('custom', [
        'settings',
        'settings.roles',
        'settings.roles.edit',
        'dashboard',
    ]);

    $this->put(route('admin.settings.roles.update', $admin->role->id), [
        'name'            => $admin->role->name,
        'permission_type' => 'custom',
        'permissions'     => ['settings', 'settings.roles', 'settings.roles.edit'],
    ])->assertRedirect();

    expect($admin->role->refresh()->permissions)->toContain('settings.roles.edit');
});
