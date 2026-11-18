<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\deleteJson;

uses(DatabaseTransactions::class);

it('refuses to delete a role still assigned to an admin whose name is falsy', function () {
    $this->loginWithPermissions('all', []);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);
    Admin::factory()->create(['name' => '0', 'role_id' => $role->id, 'status' => 1]);

    deleteJson(route('admin.settings.roles.delete', $role->id))
        ->assertBadRequest()
        ->assertJson(['message' => trans('admin::app.settings.roles.being-used-by', ['name' => '0'])]);

    expect(Role::find($role->id))->not->toBeNull();
});

it('names the earliest assigned admin when refusing to delete a role in use', function () {
    $this->loginWithPermissions('all', []);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);
    $first = Admin::factory()->create(['name' => 'First Holder', 'role_id' => $role->id, 'status' => 1]);
    Admin::factory()->create(['name' => 'Second Holder', 'role_id' => $role->id, 'status' => 1]);

    deleteJson(route('admin.settings.roles.delete', $role->id))
        ->assertBadRequest()
        ->assertJson(['message' => trans('admin::app.settings.roles.being-used-by', ['name' => $first->name])]);
});
