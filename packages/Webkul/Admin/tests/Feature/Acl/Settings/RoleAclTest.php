<?php

use Webkul\User\Models\Role;

it('should not display the roles list if does not have permission', function () {
    $this->loginWithPermissions();

    $response = $this->get(route('admin.settings.roles.index'));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the roles list if has permission', function () {
    $this->loginWithPermissions(permissions: ['settings', 'settings.roles']);

    $this->get(route('admin.settings.roles.index'))
        ->assertSeeText(trans('admin::app.settings.roles.index.title'))
        ->assertStatus(200);
});

it('should not display the create role form if does not have permission', function () {
    $this->loginWithPermissions();

    $response = $this->get(route('admin.settings.roles.create'));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the create role form if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.roles.create']);

    $this->get(route('admin.settings.roles.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.roles.create.title'));
});

it('should not display the role edit if does not have permission', function () {
    $this->loginWithPermissions();
    $role = Role::first();

    $response = $this->get(route('admin.settings.roles.edit', ['id' => $role->id]));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the role edit if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.roles.edit']);
    $role = Role::first();

    $this->get(route('admin.settings.roles.edit', ['id' => $role->id]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.roles.edit.title'));
});

it('should not be able to delete roles if does not have permission', function () {
    $this->loginWithPermissions();
    $role = Role::first();

    $response = $this->delete(route('admin.settings.roles.delete', ['id' => $role->id]));
    $this->assertStringContainsString('Unauthorized', $response->getContent());

    $this->assertDatabaseHas($this->getFullTableName(Role::class),
        ['id' => $role->id]
    );
});

it('should be able to delete roles if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.roles.delete']);

    $role = Role::factory()->create();

    $this->delete(route('admin.settings.roles.delete', ['id' => $role->id]))
        ->assertJsonFragment(['message' => trans('admin::app.settings.roles.delete-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(Role::class), [
        'id' => $role->id,
    ]);
});
