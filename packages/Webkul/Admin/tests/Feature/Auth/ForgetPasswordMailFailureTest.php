<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

beforeEach(function () {
    // Nothing listens on 127.0.0.1:1, so the transport throws on send.
    config([
        'mail.default'      => 'smtp',
        'mail.mailers.smtp' => [
            'transport' => 'smtp',
            'host'      => '127.0.0.1',
            'port'      => 1,
            'timeout'   => 1,
        ],
    ]);
});

it('returns a 200 warning (never a 500) when the reset mail cannot be sent', function () {
    Admin::factory()->create([
        'email'    => 'smtp-broken@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->postJson(route('admin.forget_password.store'), [
        'email' => 'smtp-broken@example.com',
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => trans('admin::app.users.forget-password.create.email-settings-error'),
        'type'    => 'warning',
    ]);
});

it('flashes a warning on a non-ajax submit when the reset mail cannot be sent', function () {
    Admin::factory()->create([
        'email'    => 'smtp-broken-web@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->post(route('admin.forget_password.store'), [
        'email' => 'smtp-broken-web@example.com',
    ]);

    $response->assertRedirect(route('admin.forget_password.create'));
    $response->assertSessionHas('warning', trans('admin::app.users.forget-password.create.email-settings-error'));
});
