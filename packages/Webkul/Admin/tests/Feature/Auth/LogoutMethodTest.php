<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

it('rejects a GET to the logout route with 405 (why the anchor needs data-no-ajax-nav)', function () {
    $admin = Admin::factory()->create([
        'email'    => 'logout-get@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.session.destroy'));

    $response->assertStatus(405);
    $this->assertAuthenticatedAs($admin, 'admin');
});

it('logs the admin out on a DELETE to the logout route', function () {
    $admin = Admin::factory()->create([
        'email'    => 'logout-delete@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->actingAs($admin, 'admin')->delete(route('admin.session.destroy'));

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('admin');
});
