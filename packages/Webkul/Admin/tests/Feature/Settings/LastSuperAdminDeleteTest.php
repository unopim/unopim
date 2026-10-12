<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\deleteJson;

/*
 * The last active all-access admin must not be deletable, or the system is left
 * with no superadmin (org-wide lockout of all-access management).
 */
$deletePermissions = ['dashboard', 'settings', 'settings.users', 'settings.users.users', 'settings.users.users.delete'];

it('forbids deleting the last active all-access admin', function () use ($deletePermissions) {
    $superadmin = Admin::whereHas('role', fn ($query) => $query->where('permission_type', 'all'))->firstOrFail();

    $this->loginWithPermissions('custom', $deletePermissions);

    deleteJson(route('admin.settings.users.delete', ['id' => $superadmin->id]))
        ->assertStatus(400);

    $this->assertDatabaseHas('admins', ['id' => $superadmin->id]);
});

it('allows deleting an all-access admin when another remains', function () use ($deletePermissions) {
    $allRoleId = Role::where('permission_type', 'all')->value('id');

    $extra = Admin::factory()->create(['role_id' => $allRoleId, 'status' => 1]);

    $this->loginWithPermissions('custom', $deletePermissions);

    deleteJson(route('admin.settings.users.delete', ['id' => $extra->id]))
        ->assertOk();

    $this->assertDatabaseMissing('admins', ['id' => $extra->id]);
});
