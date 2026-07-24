<?php

use Webkul\User\Models\Role;

use function Pest\Laravel\putJson;

/*
 * Guards against privilege escalation through the role editor: a non-superadmin
 * must not be able to promote a role to full access, nor grant permissions it
 * does not itself hold.
 */
it('forbids a non-all admin from promoting a role to full access', function () {
    $admin = $this->loginWithPermissions('custom', ['dashboard', 'settings.roles', 'settings.roles.edit']);

    putJson(route('admin.settings.roles.update', ['id' => $admin->role_id]), [
        'name'            => 'Escalated',
        'permission_type' => 'all',
    ])->assertStatus(403);

    expect(Role::find($admin->role_id)->permission_type)->toBe('custom');
});

it('forbids a non-all admin from promoting a role to full access regardless of casing', function () {
    $admin = $this->loginWithPermissions('custom', ['dashboard', 'settings.roles', 'settings.roles.edit']);

    putJson(route('admin.settings.roles.update', ['id' => $admin->role_id]), [
        'name'            => 'Escalated',
        'permission_type' => 'All',
    ])->assertStatus(403);

    expect(Role::find($admin->role_id)->permission_type)->toBe('custom');
});

it('allows a superadmin to keep a role at full access', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    putJson(route('admin.settings.roles.update', ['id' => $role->id]), [
        'name'            => 'Promoted',
        'permission_type' => 'all',
    ])->assertStatus(200);

    expect(Role::find($role->id)->permission_type)->toBe('all');
});
