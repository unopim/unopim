<?php

use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the Roles index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.roles.index'));

    $response->assertStatus(200);
});

it('should create a Role type ALL', function () {
    $this->loginAsAdmin();

    $role = [
        'name'            => 'newTests',
        'permission_type' => 'All',
        'description'     => 'description for the role',
    ];

    $response = postJson(route('admin.settings.roles.store'), $role);

    $this->assertDatabaseHas('roles', [
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
    $response = postJson(route('admin.settings.roles.store'), $role);

    $this->assertDatabaseHas('roles', [
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
    ];

    $response = putJson(route('admin.settings.roles.update', ['id' => 2]), $updated);

    $this->assertDatabaseHas('roles', [
        'name' => 'demo role',
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
