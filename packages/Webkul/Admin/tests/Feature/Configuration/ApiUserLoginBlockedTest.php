<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

// admin.session.store is a session-guarded web route behind Laravel 13 CSRF
// middleware, which this test harness does not satisfy — disable it here,
// mirroring CreateIntegrationProvisionsRobotTest.
it('rejects panel login for API robot accounts', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $role = Role::factory()->create();

    $robot = Admin::factory()->create([
        'role_id'  => $role->id,
        'status'   => 1,
        'type'     => 'api',
        'password' => bcrypt('secret-password'),
    ]);

    $response = $this->post(route('admin.session.store'), [
        'email'    => $robot->email,
        'password' => 'secret-password',
    ]);

    $this->assertGuest('admin');
    $response->assertRedirect(route('admin.session.create'));
});

it('still allows a normal active admin to log in', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $role = Role::factory()->create(['permission_type' => 'all']);

    $user = Admin::factory()->create([
        'role_id'  => $role->id,
        'status'   => 1,
        'type'     => 'user',
        'password' => bcrypt('secret-password'),
    ]);

    $response = $this->post(route('admin.session.store'), [
        'email'    => $user->email,
        'password' => 'secret-password',
    ]);

    $this->assertAuthenticated('admin');
    $response->assertRedirect();
});
