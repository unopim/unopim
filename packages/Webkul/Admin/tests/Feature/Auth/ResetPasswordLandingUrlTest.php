<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Webkul\User\Models\Admin;

/**
 * Reset the admin's password through the reset form.
 */
function resetPasswordFor(Admin $admin, bool $json = false): TestResponse
{
    $payload = [
        'token'                 => Password::broker('admins')->createToken($admin),
        'email'                 => $admin->email,
        'password'              => 'NewPassw0rd!',
        'password_confirmation' => 'NewPassw0rd!',
    ];

    return $json
        ? test()->postJson(route('admin.reset_password.store'), $payload)
        : test()->post(route('admin.reset_password.store'), $payload);
}

beforeEach(function () {
    config(['app.url' => 'https://pim.example.com']);

    $this->withoutMiddleware(ThrottleRequests::class);
});

it('returns to the remembered admin page after a password reset', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['dashboard', 'catalog', 'catalog.products']);

    $this->withSession(['url.intended' => 'https://pim.example.com/admin/catalog/products']);

    resetPasswordFor($admin)->assertRedirect('https://pim.example.com/admin/catalog/products');
});

it('ignores a remembered page on another origin after a password reset', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['dashboard']);

    $this->withSession(['url.intended' => 'https://pim.example.com:6666/admin/phish']);

    resetPasswordFor($admin, json: true)
        ->assertOk()
        ->assertJson(['redirect_url' => route('admin.dashboard.index')]);
});

it('lands a role without dashboard access on its first permitted page after a password reset', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['data_transfer', 'data_transfer.export']);

    resetPasswordFor($admin)->assertRedirect(route('admin.settings.data_transfer.exports.index'));
});

it('sends a deactivated admin to the sign-in page after a password reset', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['dashboard']);

    $admin->forceFill(['status' => 0])->save();

    resetPasswordFor($admin)->assertRedirect(route('admin.session.create'));

    $this->assertGuest('admin');
});
