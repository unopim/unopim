<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

it('returns a json redirect_url without a full redirect on valid ajax login', function () {
    $admin = Admin::factory()->create([
        'email'    => 'ajax-login@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->postJson(route('admin.session.store'), [
        'email'    => 'ajax-login@example.com',
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['redirect_url']);
    $this->assertAuthenticatedAs($admin, 'admin');
});

it('returns a 401 json error message on invalid ajax credentials instead of redirecting', function () {
    Admin::factory()->create([
        'email'    => 'ajax-login@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->postJson(route('admin.session.store'), [
        'email'    => 'ajax-login@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => trans('admin::app.settings.users.login-error')]);
    $this->assertGuest('admin');
});

it('returns a 403 warning json for an inactive account on ajax login', function () {
    Admin::factory()->create([
        'email'    => 'inactive@example.com',
        'password' => Hash::make('password'),
        'status'   => 0,
    ]);

    $response = $this->postJson(route('admin.session.store'), [
        'email'    => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'type'    => 'warning',
        'message' => trans('admin::app.settings.users.activate-warning'),
    ]);
    $this->assertGuest('admin');
});

it('still redirects a non-ajax valid login (backward compatible)', function () {
    $admin = Admin::factory()->create([
        'email'    => 'classic-login@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->post(route('admin.session.store'), [
        'email'    => 'classic-login@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($admin, 'admin');
});
