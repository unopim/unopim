<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\User\Models\Admin;

it('answers an existing and an unknown email identically', function () {
    Notification::fake();

    Admin::factory()->create([
        'email'    => 'known-account@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $known = $this->postJson(route('admin.forget_password.store'), ['email' => 'known-account@example.com']);
    $unknown = $this->postJson(route('admin.forget_password.store'), ['email' => 'nobody-123@example.com']);

    $known->assertOk();
    $unknown->assertOk();

    expect($known->json())->toBe($unknown->json());
});

it('still sends the reset link to an existing account', function () {
    Notification::fake();

    $admin = Admin::factory()->create([
        'email'    => 'link-sent@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $this->postJson(route('admin.forget_password.store'), ['email' => 'link-sent@example.com'])->assertOk();

    Notification::assertSentTo($admin, ResetPasswordNotification::class);
});

it('sends the reset link only after the response has been built', function () {
    Notification::fake();

    $admin = Admin::factory()->create([
        'email'    => 'after-response@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $this->app->terminating(fn () => Notification::assertNothingSentTo($admin));

    $this->postJson(route('admin.forget_password.store'), ['email' => 'after-response@example.com'])->assertOk();
});
