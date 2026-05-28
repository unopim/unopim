<?php

use Webkul\User\Models\Role;

it('should deny POST roles.store when user lacks roles.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $response = $this->post(route('admin.settings.roles.store'), [
        'name'            => 'Escalated',
        'description'     => 'bypass attempt',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing($this->getFullTableName(Role::class), [
        'name' => 'Escalated',
    ]);
});

it('should deny PUT roles.update when user lacks roles.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);
    $role = Role::factory()->create(['name' => 'Original']);

    $response = $this->put(route('admin.settings.roles.update', ['id' => $role->id]), [
        'name'            => 'Tampered',
        'description'     => 'bypass attempt',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'id'   => $role->id,
        'name' => 'Original',
    ]);
});

it('should allow POST roles.store when user has roles.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings', 'settings.roles', 'settings.roles.create']);

    $response = $this->post(route('admin.settings.roles.store'), [
        'name'            => 'Legit Role',
        'description'     => 'authorised create',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $response->assertRedirect(route('admin.settings.roles.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'name' => 'Legit Role',
    ]);
});

it('should allow PUT roles.update when user has roles.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings', 'settings.roles', 'settings.roles.edit']);
    $role = Role::factory()->create(['name' => 'Before']);

    $response = $this->put(route('admin.settings.roles.update', ['id' => $role->id]), [
        'name'            => 'After',
        'description'     => 'authorised update',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'id'   => $role->id,
        'name' => 'After',
    ]);
});
