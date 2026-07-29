<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * The profile page and the user edit page render one Blade. These pin the flags
 * that decide what each caller exposes, so a future edit cannot leak the role or
 * status controls onto the self-service page.
 */
it('renders the profile page from the shared user edit view', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.account.edit'));

    $response->assertStatus(200);
    $response->assertViewIs('admin::settings.users.edit');
    $response->assertViewHas('canManage', false);
    $response->assertViewHas('requiresCurrentPassword', true);
});

it('hides the role and status controls on the profile page', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.account.edit'));

    $response->assertDontSee('name="role_id"', false);
    $response->assertDontSee('name="status"', false);
    $response->assertSee('name="current_password"', false);
});

it('renders the user edit page from the same view with the role controls', function () {
    $this->loginAsAdmin();

    $target = Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]);

    $response = $this->get(route('admin.settings.users.edit', $target->id));

    $response->assertStatus(200);
    $response->assertViewIs('admin::settings.users.edit');
    $response->assertViewHas('canManage', true);
    $response->assertViewHas('requiresCurrentPassword', true);
    $response->assertSee('name="role_id"', false);
    $response->assertSee('name="current_password"', false);
});

it('refuses a user update when the acting admin password is wrong', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->loginAsAdmin();

    $target = Admin::factory()->create([
        'name'    => 'Before Sudo',
        'role_id' => Role::factory()->create(['permission_type' => 'all'])->id,
    ]);

    $this->put(route('admin.settings.users.update'), [
        'id'               => $target->id,
        'name'             => 'After Sudo',
        'email'            => $target->email,
        'role_id'          => $target->role_id,
        'ui_locale_id'     => $target->ui_locale_id,
        'timezone'         => 'Asia/Kolkata',
        'password'         => '',
        'current_password' => 'not-the-password',
    ])->assertSessionHasErrors('current_password');

    expect($target->fresh()->name)->toBe('Before Sudo');
});

it('accepts a user update when the acting admin password is right', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->loginAsAdmin();

    $target = Admin::factory()->create([
        'name'    => 'Before Sudo',
        'role_id' => Role::factory()->create(['permission_type' => 'all'])->id,
    ]);

    $this->put(route('admin.settings.users.update'), [
        'id'               => $target->id,
        'name'             => 'After Sudo',
        'email'            => $target->email,
        'role_id'          => $target->role_id,
        'ui_locale_id'     => $target->ui_locale_id,
        'timezone'         => 'Asia/Kolkata',
        'password'         => '',
        'current_password' => 'password',
    ]);

    expect($target->fresh()->name)->toBe('After Sudo');
});

it('keeps the status switch off the user edit page when editing yourself', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.users.edit', $admin->id));

    $response->assertStatus(200);
    $response->assertViewHas('isSelf', true);
    $response->assertSee('name="role_id"', false);
});
