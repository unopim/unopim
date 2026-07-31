<?php

use Illuminate\Support\Facades\Hash;

it('updates the profile without a current password', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'         => 'Renamed Admin',
        'email'        => $admin->email,
        'timezone'     => 'Asia/Kolkata',
        'ui_locale_id' => $admin->ui_locale_id ?: 1,
    ]);

    $response->assertSessionHasNoErrors();

    expect($admin->fresh()->name)->toBe('Renamed Admin');
});

it('ignores a wrong current password when no new password is set', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'             => 'Autofilled Admin',
        'email'            => $admin->email,
        'timezone'         => 'Asia/Kolkata',
        'ui_locale_id'     => $admin->ui_locale_id ?: 1,
        'current_password' => 'not-the-password',
    ]);

    $response->assertSessionHasNoErrors();

    expect($admin->fresh()->name)->toBe('Autofilled Admin');
});

it('requires the current password when a new password is set', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'password'              => 'NewPassw0rd',
        'password_confirmation' => 'NewPassw0rd',
    ]);

    $response->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue();
});

it('rejects a password change when the current password is wrong', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'not-the-password',
        'password'              => 'NewPassw0rd',
        'password_confirmation' => 'NewPassw0rd',
    ]);

    $response->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue();
});

it('changes the password when the current password is right', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'password',
        'password'              => 'NewPassw0rd',
        'password_confirmation' => 'NewPassw0rd',
    ]);

    $response->assertSessionHasNoErrors();

    expect(Hash::check('NewPassw0rd', $admin->fresh()->password))->toBeTrue();
});
