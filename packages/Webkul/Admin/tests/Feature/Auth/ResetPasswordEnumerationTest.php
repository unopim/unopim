<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

it('returns the same generic error for a non-existent email and for a wrong token (no enumeration)', function () {
    Admin::factory()->create([
        'email'    => 'exists@example.com',
        'password' => Hash::make('old-password'),
        'status'   => 1,
    ]);

    $payload = [
        'token'                 => 'garbage-token',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ];

    $unknownEmail = $this->postJson(route('admin.reset_password.store'), [
        ...$payload,
        'email' => 'does-not-exist@example.com',
    ]);

    $existingEmail = $this->postJson(route('admin.reset_password.store'), [
        ...$payload,
        'email' => 'exists@example.com',
    ]);

    $generic = trans('admin::app.users.reset-password.invalid-link');

    $unknownEmail->assertStatus(422);
    $existingEmail->assertStatus(422);

    $unknownMessage = $unknownEmail->json('errors.email.0');
    $existingMessage = $existingEmail->json('errors.email.0');

    expect($unknownMessage)->toBe($generic)
        ->and($existingMessage)->toBe($generic)
        ->and($unknownMessage)->toBe($existingMessage);
});
