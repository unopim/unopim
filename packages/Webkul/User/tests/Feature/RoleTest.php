<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the roles index page', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.roles.index'));
    $response->assertSeeText(trans('admin::app.settings.roles.index.title'));

    $response->assertStatus(200);
});

it('should returns the roles edit page', function () {
    $this->loginAsAdmin();

    $role = Role::factory()->create();

    $this->get(route('admin.settings.roles.edit', ['id' => $role->id]))
        ->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.roles.edit.title'));
});

it('should return the roles datagrid', function () {
    $this->loginAsAdmin();
    Role::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->json('GET', route('admin.settings.roles.index'));

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'id'    => $data['records'][0]['id'],
    ]);
});

it('should create a Role type ALL', function () {
    $this->loginAsAdmin();

    $role = [
        'name'            => 'newTests',
        'permission_type' => 'All',
        'description'     => 'description for the role',
    ];

    $response = postJson(route('admin.settings.roles.store'), $role);

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'name'            => 'newTests',
        'permission_type' => 'All',
    ]);

});

it('should create a Role type CUSTOM', function () {
    $this->loginAsAdmin();

    $role = [
        'name'            => 'newTests',
        'permission_type' => 'custom',
        'description'     => 'description for the role',
        'permissions'     => ['admin.users.index', 'admin.users.create'],
    ];

    // In permission type ,any value is stored but only All and custom should be stored
    postJson(route('admin.settings.roles.store'), $role);

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'name' => 'newTests',
    ]);
});

it('should update a Role', function () {
    $this->loginAsAdmin();

    $role = Role::factory()->create();

    $updated = [
        'id'              => $role->id,
        'name'            => 'demo role',
        'permission_type' => 'custom',
        'description'     => 'description for the upated role',
        'permissions'     => ['admin.users.index', 'admin.users.create'],
    ];

    putJson(route('admin.settings.roles.update', ['id' => $role->id]), $updated);

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'name'        => 'demo role',
    ]);
});

it('should give validation message when creating a Role without description', function () {
    $this->loginAsAdmin();

    $role = [
        'name'            => 'demo role',
        'permission_type' => 'custom',
    ];

    $response = postJson(route('admin.settings.roles.store'), $role);

    $response->assertJsonValidationErrorFor('description');
});

it('should give validation message when creating a Role without permission type', function () {
    $this->loginAsAdmin();

    $role = [
        'name'        => 'demo role',
        'description' => 'custom',
    ];

    $response = postJson(route('admin.settings.roles.store'), $role);

    $response->assertJsonValidationErrorFor('permission_type');
});

it('should give validation message when updating a Role without permission type', function () {
    $this->loginAsAdmin();

    $role = Role::factory()->create();

    $updated = [
        'name'        => 'demo role',
        'description' => 'custom',
    ];

    $response = putJson(route('admin.settings.roles.update', ['id' => $role->id]), $updated);

    $response->assertJsonValidationErrorFor('permission_type');
});

it('should delete a Role', function () {
    $this->loginAsAdmin();

    $role = Role::factory()->create();

    $this->delete(route('admin.settings.roles.delete', ['id' => $role->id]))
        ->assertJsonFragment(['message' => trans('admin::app.settings.roles.delete-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(Role::class), [
        'id' => $role->id,
    ]);
});

it('should not delete a Role if it is assigned to user', function () {
    $this->loginAsAdmin();

    $role = Role::factory()->create();
    $user = Admin::factory()->create(['role_id' => $role->id]);

    $response = $this->delete(route('admin.settings.roles.delete', ['id' => $user->role->id]));

    $this->assertDatabaseHas($this->getFullTableName(Role::class), [
        'id' => $user->role->id,
    ]);

    $response->assertJsonFragment([
        'message' => trans('admin::app.settings.roles.being-used-by', ['name' => $user->name]),
    ]);
});
