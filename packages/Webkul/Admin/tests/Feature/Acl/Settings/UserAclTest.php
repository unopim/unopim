<?php

use Webkul\User\Models\Admin;

it('should not display the users list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.users.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the users list if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.users.users']);

    $this->get(route('admin.settings.users.index'))
        ->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.users.index.title'));
});

it('should not create the user if does not have permission', function () {
    $this->loginWithPermissions();

    $this->post(route('admin.settings.users.store'))
        ->assertSeeText('Unauthorized');
});

it('should create the user if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.users.users.create']);

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'test',
        'email'                 => 'test@example.com',
        'password'              => 'admin1234',
        'status'                => 1,
        'role_id'               => 1,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 1,
        'password_confirmation' => 'admin1234',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('admins', [
        'email' => 'test@example.com',
    ]);
});

it('should not display the user edit form if does not have permission', function () {
    $this->loginWithPermissions();
    $user = Admin::first();

    $this->get(route('admin.settings.users.edit', ['id' => $user->id]))
        ->assertSeeText('Unauthorized');
});

it('should display the user edit form if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.users.users.edit']);
    $user = Admin::first();

    $response = $this->get(route('admin.settings.users.edit', ['id' => $user->id]));
    $response->assertStatus(200);
    $response->assertJson([
        'user' => $user->toArray(),
    ]);
});

it('should not be able to delete user if does not have permission', function () {
    $this->loginWithPermissions();
    $user = Admin::first();

    $this->delete(route('admin.settings.users.delete', ['id' => $user->id]))
        ->assertSeeText('Unauthorized', false);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class),
        ['id' => $user->id]
    );
});

it('should be able to delete user if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.users.users.delete']);
    $user = Admin::factory()->create();

    $response = $this->delete(route('admin.settings.users.delete', ['id' => $user->id]));
    $response->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'id' => $user->id,
    ]);
});
