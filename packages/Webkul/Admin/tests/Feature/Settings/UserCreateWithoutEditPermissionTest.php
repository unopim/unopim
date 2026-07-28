<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\postJson;

$createOnly = [
    'dashboard',
    'settings',
    'settings.users',
    'settings.users.users',
    'settings.users.users.create',
];

$payload = fn (int $roleId): array => [
    'name'                  => 'Created Without Edit',
    'email'                 => 'create-only-'.uniqid().'@example.com',
    'password'              => 'admin1234',
    'password_confirmation' => 'admin1234',
    'status'                => 1,
    'role_id'               => $roleId,
    'timezone'              => 'Asia/Kolkata',
    'ui_locale_id'          => 2,
];

it('does not send a create-only admin to a forbidden page after a successful create', function () use ($createOnly, $payload) {
    $this->loginWithPermissions('custom', $createOnly);

    $subsetRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $response = postJson(route('admin.settings.users.store'), $payload($subsetRole->id))
        ->assertOk();

    expect($response->json('redirect_url'))->toBeNull();

    $this->assertDatabaseHas('admins', ['name' => 'Created Without Edit']);
});

it('still redirects to the edit screen when the actor holds the edit permission', function () use ($createOnly, $payload) {
    $this->loginWithPermissions('custom', [...$createOnly, 'settings.users.users.edit']);

    $subsetRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ]);

    $response = postJson(route('admin.settings.users.store'), $payload($subsetRole->id))
        ->assertOk();

    expect($response->json('redirect_url'))->not->toBeNull();

    $this->get($response->json('redirect_url'))->assertOk();
});

it('omits roles the acting admin could never assign from the users listing', function () use ($createOnly) {
    Role::factory()->create(['permission_type' => 'all', 'permissions' => []]);

    $this->loginWithPermissions('custom', $createOnly);

    $response = $this->get(route('admin.settings.users.index'))->assertOk();

    expect($response->getContent())->not->toContain('"permission_type":"all"');
});

it('still offers every role to a full-access admin', function () {
    $allAccessRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => []]);

    $this->loginAsAdmin();

    $this->get(route('admin.settings.users.index'))
        ->assertOk()
        ->assertSee((string) $allAccessRole->id, false);
});

it('keeps the currently assigned role visible on the edit screen even when unassignable', function () use ($createOnly) {
    $powerRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'settings.roles', 'settings.roles.edit'],
    ]);

    $target = Admin::factory()->create(['role_id' => $powerRole->id]);

    $this->loginWithPermissions('custom', [...$createOnly, 'settings.users.users.edit']);

    $this->getJson(route('admin.settings.users.edit', $target->id))
        ->assertOk()
        ->assertJsonFragment(['id' => $powerRole->id]);
});

it('reports the accurate reason when the target role carries unheld permissions', function () use ($createOnly, $payload) {
    $this->loginWithPermissions('custom', $createOnly);

    $powerRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'settings.roles', 'settings.roles.edit'],
    ]);

    postJson(route('admin.settings.users.store'), $payload($powerRole->id))
        ->assertStatus(403)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.users.cannot-assign-unheld-permissions'),
        ]);
});

it('keeps the all-access wording for an all-access target role', function () use ($createOnly, $payload) {
    $this->loginWithPermissions('custom', $createOnly);

    $allAccessRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => []]);

    postJson(route('admin.settings.users.store'), $payload($allAccessRole->id))
        ->assertStatus(403)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.users.cannot-escalate-role'),
        ]);
});
