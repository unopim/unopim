<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

it('sets the recaller cookie and persists remember_token on an ajax remember login', function () {
    $admin = Admin::factory()->create([
        'email'          => 'rem-ajax@example.com',
        'password'       => Hash::make('password-123'),
        'status'         => 1,
        'remember_token' => null,
    ]);

    $response = $this->withHeader('X-Ajax-Form', 'true')->post(route('admin.session.store'), [
        'email'    => 'rem-ajax@example.com',
        'password' => 'password-123',
        'remember' => '1',
    ]);

    $response->assertOk();
    $this->assertAuthenticatedAs($admin, 'admin');

    // recaller cookie issued (so the browser can re-auth after the session expires)
    $response->assertCookie(auth()->guard('admin')->getRecallerName());

    // token persisted to the DB so the recaller can be validated on a later visit
    expect($admin->fresh()->remember_token)->not->toBeNull();
});

it('does not issue a recaller cookie when remember is not checked', function () {
    $admin = Admin::factory()->create([
        'email'    => 'no-rem@example.com',
        'password' => Hash::make('password-123'),
        'status'   => 1,
    ]);

    $response = $this->withHeader('X-Ajax-Form', 'true')->post(route('admin.session.store'), [
        'email'    => 'no-rem@example.com',
        'password' => 'password-123',
    ]);

    $response->assertOk();
    $response->assertCookieMissing(auth()->guard('admin')->getRecallerName());
});
