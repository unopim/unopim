<?php

use Webkul\User\Models\Role;

use function Pest\Laravel\postJson;

/*
 * Guards against privilege escalation through user creation: a non-superadmin
 * must not assign a role (all-access, or a custom role) that carries permissions
 * beyond the acting admin's own set.
 */
function createUserPayload(int $roleId): array
{
    return [
        'name'                  => 'Escalation Target',
        'email'                 => 'escalation-'.uniqid().'@example.com',
        'password'              => 'admin1234',
        'password_confirmation' => 'admin1234',
        'status'                => 1,
        'role_id'               => $roleId,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 2,
    ];
}

it('forbids assigning a custom role that exceeds the acting admin permissions', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings.users.users.create']);

    $powerRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'settings.roles', 'settings.roles.edit'],
    ]);

    postJson(route('admin.settings.users.store'), createUserPayload($powerRole->id))
        ->assertStatus(403);
});

it('allows assigning a custom role within the acting admin permissions', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings.users.users.create']);

    $withinRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    postJson(route('admin.settings.users.store'), createUserPayload($withinRole->id))
        ->assertStatus(200);
});
