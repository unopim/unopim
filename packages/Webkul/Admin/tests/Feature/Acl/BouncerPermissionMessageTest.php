<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('should show a translated 403 error message when user with no permissions is logged out by bouncer', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => [],
    ]);

    $admin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $role->id,
    ]);

    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.dashboard.index'));

    $response->assertRedirect(route('admin.session.create'));

    $response->assertSessionHas('error');

    $errorMessage = session('error');

    $this->assertNotEquals('admin::app.error.403.message', $errorMessage, 'The 403 error message should be translated, not show a raw translation key');

    $this->assertNotEmpty($errorMessage);
});
