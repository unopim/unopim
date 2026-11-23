<?php

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Exception\TransportException;
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

    Exceptions::fake();
});

it('gives the generic answer and reports the failure when the reset mail cannot be sent', function () {
    Admin::factory()->create([
        'email'    => 'smtp-broken@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $this->postJson(route('admin.forget_password.store'), [
        'email' => 'smtp-broken@example.com',
    ])
        ->assertOk()
        ->assertExactJson(['message' => trans('admin::app.users.forget-password.create.reset-link-sent')]);

    Exceptions::assertReported(TransportException::class);
});

it('flashes the generic success on a non-ajax submit when the reset mail cannot be sent', function () {
    Admin::factory()->create([
        'email'    => 'smtp-broken-web@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $this->post(route('admin.forget_password.store'), [
        'email' => 'smtp-broken-web@example.com',
    ])
        ->assertRedirect(route('admin.forget_password.create'))
        ->assertSessionHas('success', trans('admin::app.users.forget-password.create.reset-link-sent'))
        ->assertSessionMissing('warning');
});
