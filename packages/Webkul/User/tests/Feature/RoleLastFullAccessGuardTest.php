<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Repositories\AdminRepository;

use function Pest\Laravel\put;
use function Pest\Laravel\putJson;

uses(DatabaseTransactions::class);

function makeOnlyFullAccessRole(): Role
{
    $role = Role::factory()->create(['permission_type' => 'all', 'permissions' => null]);

    $customRole = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    Admin::query()->where('role_id', '!=', $role->id)->update(['role_id' => $customRole->id]);

    Role::query()->where('permission_type', 'all')->whereKeyNot($role->id)->update(['permission_type' => 'custom']);

    return $role;
}

function rolePayload(Role $role, string $permissionType): array
{
    return [
        'name'            => $role->name,
        'permission_type' => $permissionType,
        'permissions'     => $permissionType === 'custom' ? ['dashboard'] : [],
    ];
}

it('blocks changing the only full-access role to custom when it has several users', function () {
    $role = makeOnlyFullAccessRole();

    $admin = Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);
    Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);

    $this->loginAsAdmin($admin);

    putJson(route('admin.settings.roles.update', $role->id), rolePayload($role, 'custom'))
        ->assertUnprocessable()
        ->assertJson(['message' => trans('admin::app.settings.roles.last-full-access')]);

    expect($role->refresh()->permission_type)->toBe('all');
});

it('blocks changing the only full-access role to custom when it has one user', function () {
    $role = makeOnlyFullAccessRole();

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id, 'status' => 1]));

    putJson(route('admin.settings.roles.update', $role->id), rolePayload($role, 'custom'))->assertUnprocessable();

    expect($role->refresh()->permission_type)->toBe('all');
});

it('blocks the change when the only other full-access user is inactive', function () {
    $role = makeOnlyFullAccessRole();

    $otherFullAccessRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => null]);

    Admin::factory()->create(['role_id' => $otherFullAccessRole->id, 'status' => 0]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id, 'status' => 1]));

    putJson(route('admin.settings.roles.update', $role->id), rolePayload($role, 'custom'))->assertUnprocessable();

    expect($role->refresh()->permission_type)->toBe('all');
});

it('redirects back to the roles list with an error when the request is not ajax', function () {
    $role = makeOnlyFullAccessRole();

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id, 'status' => 1]));
    Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);

    put(route('admin.settings.roles.update', $role->id), rolePayload($role, 'custom'))
        ->assertRedirect(route('admin.settings.roles.index'))
        ->assertSessionHas('error', trans('admin::app.settings.roles.last-full-access'));

    expect($role->refresh()->permission_type)->toBe('all');
});

it('allows changing a full-access role to custom while an active user on another role keeps full access', function () {
    $role = makeOnlyFullAccessRole();

    $otherFullAccessRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => null]);

    $admin = Admin::factory()->create(['role_id' => $otherFullAccessRole->id, 'status' => 1]);
    Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);
    Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);

    $this->loginAsAdmin($admin);

    putJson(route('admin.settings.roles.update', $role->id), rolePayload($role, 'custom'))->assertOk();

    expect($role->refresh()->permission_type)->toBe('custom')
        ->and(app(AdminRepository::class)->hasOtherActiveFullAccessAdmin())->toBeTrue();
});

it('allows renaming the only full-access role when it stays on all', function () {
    $role = makeOnlyFullAccessRole();

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id, 'status' => 1]));

    putJson(route('admin.settings.roles.update', $role->id), [
        ...rolePayload($role, 'all'),
        'name' => 'Super Administrator',
    ])->assertOk();

    expect($role->refresh())
        ->name->toBe('Super Administrator')
        ->permission_type->toBe('all');
});

it('allows updating a custom role while only one full-access user exists', function () {
    $role = makeOnlyFullAccessRole();

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id, 'status' => 1]));

    $customRole = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    putJson(route('admin.settings.roles.update', $customRole->id), [
        'name'            => $customRole->name,
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'settings'],
    ])->assertOk();

    expect($customRole->refresh()->permissions)->toBe(['dashboard', 'settings']);
});

it('reports whether an active full-access admin remains outside a role', function () {
    $role = makeOnlyFullAccessRole();

    Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);

    $otherFullAccessRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => null]);

    $inactiveAdmin = Admin::factory()->create(['role_id' => $otherFullAccessRole->id, 'status' => 0]);

    $repository = app(AdminRepository::class);

    expect($repository->hasOtherActiveFullAccessAdmin())->toBeTrue()
        ->and($repository->hasOtherActiveFullAccessAdmin(exceptRoleId: $role->id))->toBeFalse();

    $inactiveAdmin->update(['status' => 1]);

    expect($repository->hasOtherActiveFullAccessAdmin(exceptRoleId: $role->id))->toBeTrue();
});
