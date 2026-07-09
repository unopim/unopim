<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

it('renders the login page with remember-me, subtitle and autofill attributes', function () {
    $response = $this->get(route('admin.session.create'));

    $response->assertOk();
    $response->assertSee('name="remember"', false);
    $response->assertSee(trans('admin::app.users.sessions.remember-me'));
    $response->assertSee(trans('admin::app.users.sessions.subtitle'));
    $response->assertSee('autocomplete="username"', false);
    $response->assertSee('autocomplete="current-password"', false);
});

it('renders the login in light mode when the dark_mode cookie is light (does not force dark)', function () {
    $this->withUnencryptedCookie('dark_mode', 'light')
        ->get(route('admin.session.create'))
        ->assertOk()
        ->assertDontSee('class="dark"', false);
});

it('renders the login in dark mode when the dark_mode cookie is dark', function () {
    $this->withUnencryptedCookie('dark_mode', 'dark')
        ->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('class="dark"', false);
});

it('includes both the light and dark default logos so they swap with the theme', function () {
    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('dark_logo', false)
        ->assertSee('dark:hidden', false);
});

it('hides the Microsoft SSO option when SSO is not configured', function () {
    config()->set('services.microsoft_sso.enabled', false);

    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertDontSee(route('admin.session.microsoft.redirect'));
});

it('shows the Microsoft SSO option when SSO is configured', function () {
    config()->set('services.microsoft_sso.enabled', true);
    config()->set('services.microsoft_sso.tenant', 'tenant-id');
    config()->set('services.microsoft_sso.client_id', 'client-id');
    config()->set('services.microsoft_sso.client_secret', 'client-secret');

    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee(route('admin.session.microsoft.redirect'), false);
});

it('keeps the admin logged in across the remember cookie when remember is checked', function () {
    $admin = Admin::factory()->create([
        'email'    => 'remember@example.com',
        'password' => Hash::make('password-123'),
        'status'   => 1,
    ]);

    $response = $this->post(route('admin.session.store'), [
        'email'    => 'remember@example.com',
        'password' => 'password-123',
        'remember' => '1',
    ]);

    $this->assertAuthenticatedAs($admin, 'admin');
    $response->assertCookie(auth()->guard('admin')->getRecallerName());
});

it('returns the friendly throttle message as json on too many login attempts', function () {
    $response = null;

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $response = $this->postJson(route('admin.session.store'), [
            'email'    => 'flood@example.com',
            'password' => 'wrong-'.$attempt,
        ]);
    }

    // The throttle 429 is rendered by the Core exception handler as an
    // {error, description} payload (see LoginThrottleErrorPageTest).
    $response->assertStatus(429);
    $response->assertJson([
        'error'       => trans('admin::app.errors.429.title'),
        'description' => trans('admin::app.errors.429.description'),
    ]);
});
