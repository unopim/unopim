<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Webkul\User\Models\Admin;

it('returns a json message on valid ajax forget-password without a reload', function () {
    Admin::factory()->create([
        'email'    => 'forgot@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->postJson(route('admin.forget_password.store'), [
        'email' => 'forgot@example.com',
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => trans('admin::app.users.forget-password.create.reset-link-sent'),
    ]);
});

it('returns a 422 json field error on invalid ajax forget-password email', function () {
    $response = $this->postJson(route('admin.forget_password.store'), [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

it('still redirects a non-ajax forget-password submit (backward compatible)', function () {
    Admin::factory()->create([
        'email'    => 'forgot2@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->post(route('admin.forget_password.store'), [
        'email' => 'forgot2@example.com',
    ]);

    $response->assertRedirect(route('admin.forget_password.create'));
});

it('returns a json redirect_url and logs in on valid ajax reset-password', function () {
    $admin = Admin::factory()->create([
        'email'    => 'reset@example.com',
        'password' => Hash::make('old-password'),
        'status'   => 1,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    $response = $this->postJson(route('admin.reset_password.store'), [
        'token'                 => $token,
        'email'                 => 'reset@example.com',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk();
    $response->assertJson(['redirect_url' => route('admin.dashboard.index')]);
    $this->assertAuthenticatedAs($admin->fresh(), 'admin');
});

it('returns a 422 json field error on an invalid ajax reset-password token', function () {
    Admin::factory()->create([
        'email'    => 'reset2@example.com',
        'password' => Hash::make('old-password'),
        'status'   => 1,
    ]);

    $response = $this->postJson(route('admin.reset_password.store'), [
        'token'                 => 'totally-invalid-token',
        'email'                 => 'reset2@example.com',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $this->assertGuest('admin');
});
