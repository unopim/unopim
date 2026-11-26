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

it('gives the visitor a fresh session id and csrf token on logout', function () {
    $admin = Admin::factory()->create([
        'email'    => 'logout-rotate@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $sessionId = session()->getId();
    $token = session()->token();

    $this->delete(route('admin.session.destroy'))->assertRedirect(route('admin.session.create'));

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($token);

    $this->assertGuest('admin');
});
