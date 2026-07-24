<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Webkul\User\Models\Admin;

/*
 * Password reset must not auto-establish a session for a deactivated account,
 * mirroring the status gate the login flow enforces.
 */
it('does not auto-login a deactivated admin after password reset', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create([
        'email'    => 'inactive-reset@example.com',
        'password' => Hash::make('old-password'),
        'status'   => 0,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    $this->postJson(route('admin.reset_password.store'), [
        'token'                 => $token,
        'email'                 => 'inactive-reset@example.com',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $this->assertGuest('admin');
});
