<?php

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
    $response->assertViewHas('requiresCurrentPassword', false);
    $response->assertSee('name="role_id"', false);
    $response->assertDontSee('name="current_password"', false);
});

it('keeps the status switch off the user edit page when editing yourself', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.users.edit', $admin->id));

    $response->assertStatus(200);
    $response->assertViewHas('isSelf', true);
    $response->assertSee('name="role_id"', false);
});
