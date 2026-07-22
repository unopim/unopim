<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\Admin\DataGrids\Settings\UserDataGrid;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('excludes API robots from the users datagrid query', function () {
    $role = Role::factory()->create();
    $human = Admin::factory()->create(['role_id' => $role->id, 'type' => 'user']);
    $robot = Admin::factory()->create(['role_id' => $role->id, 'type' => 'api']);

    $rows = app(UserDataGrid::class)
        ->prepareQueryBuilder()
        ->pluck('user_id');

    expect($rows)->toContain($human->id)
        ->and($rows)->not->toContain($robot->id);
});

it('returns 404 when editing an API robot account', function () {
    $this->loginAsAdmin();

    $role = Role::factory()->create();
    $robot = Admin::factory()->create(['role_id' => $role->id, 'type' => 'api']);

    $this->get(route('admin.settings.users.edit', $robot->id))
        ->assertNotFound();
});

it('returns 404 when updating an API robot account', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->loginAsAdmin();

    $role = Role::factory()->create();
    $robot = Admin::factory()->create(['role_id' => $role->id, 'type' => 'api']);

    $this->put(route('admin.settings.users.update'), [
        'id'           => $robot->id,
        'name'         => 'Renamed Robot',
        'email'        => $robot->email,
        'role_id'      => $role->id,
        'ui_locale_id' => $robot->ui_locale_id,
        'timezone'     => 'UTC',
    ])->assertNotFound();
});

it('returns 404 when deleting an API robot account', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->loginAsAdmin();

    $role = Role::factory()->create();
    Admin::factory()->create(['role_id' => $role->id, 'type' => 'user']);
    $robot = Admin::factory()->create(['role_id' => $role->id, 'type' => 'api']);

    $this->delete(route('admin.settings.users.delete', $robot->id))
        ->assertNotFound();
});
