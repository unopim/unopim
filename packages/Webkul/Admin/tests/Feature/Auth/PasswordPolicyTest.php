<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Webkul\User\Models\Admin;

it('exposes a single admin password minimum via config', function () {
    expect(config('admin.auth.password_min'))->toBe(8);
});

it('rejects a new reset password shorter than the configured minimum', function () {
    $admin = Admin::factory()->create([
        'email'    => 'short-reset@example.com',
        'password' => Hash::make('old-password'),
        'status'   => 1,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    $response = $this->postJson(route('admin.reset_password.store'), [
        'token'                 => $token,
        'email'                 => 'short-reset@example.com',
        'password'              => 'short12',
        'password_confirmation' => 'short12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $this->assertGuest('admin');
});

it('accepts a reset password at exactly the configured minimum', function () {
    $admin = Admin::factory()->create([
        'email'    => 'ok-reset@example.com',
        'password' => Hash::make('old-password'),
        'status'   => 1,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    $response = $this->postJson(route('admin.reset_password.store'), [
        'token'                 => $token,
        'email'                 => 'ok-reset@example.com',
        'password'              => str_repeat('a', config('admin.auth.password_min')),
        'password_confirmation' => str_repeat('a', config('admin.auth.password_min')),
    ]);

    $response->assertOk();
    $response->assertJson(['redirect_url' => route('admin.dashboard.index')]);
});

it('still lets an admin with an existing sub-minimum password log in (policy never blocks login)', function () {
    $admin = Admin::factory()->create([
        'email'    => 'legacy@example.com',
        'password' => Hash::make('sixchr'),
        'status'   => 1,
    ]);

    expect(strlen('sixchr'))->toBeLessThan(config('admin.auth.password_min'));

    $response = $this->post(route('admin.session.store'), [
        'email'    => 'legacy@example.com',
        'password' => 'sixchr',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($admin, 'admin');
});
