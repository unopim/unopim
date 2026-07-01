<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('blocks a low-privilege admin from creating a role (store route must be ACL-gated)', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->post(route('admin.settings.roles.store'), [
        'name'            => 'escalated',
        'permission_type' => 'all',
    ])->assertSeeText('Unauthorized');

    $this->assertDatabaseMissing($this->getFullTableName(Role::class), [
        'name'            => 'escalated',
        'permission_type' => 'all',
    ]);
});

it('blocks a low-privilege admin from updating a role (update route must be ACL-gated)', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $this->put(route('admin.settings.roles.update', $role->id), [
        'name'            => $role->name,
        'permission_type' => 'all',
    ])->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'id'              => $role->id,
        'permission_type' => 'custom',
    ]);
});

it('blocks a low-privilege admin from updating a user (update route must be ACL-gated)', function () {
    $attacker = $this->loginWithPermissions('custom', ['dashboard']);

    $allRole = Role::factory()->create(['permission_type' => 'all']);

    $this->put(route('admin.settings.users.update'), [
        'id'      => $attacker->id,
        'name'    => $attacker->name,
        'email'   => $attacker->email,
        'role_id' => $allRole->id,
        'status'  => 1,
    ])->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'id'      => $attacker->id,
        'role_id' => $attacker->role_id,
    ]);
});

it('allows an admin with the roles.create permission to reach the store handler', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.roles', 'settings.roles.create']);

    $this->post(route('admin.settings.roles.store'), [
        'name'            => 'legit-role',
        'description'     => 'legit role',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ])->assertDontSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'name' => 'legit-role',
    ]);
});
