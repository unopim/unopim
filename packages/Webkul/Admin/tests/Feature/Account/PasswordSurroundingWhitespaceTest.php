<?php

use Illuminate\Support\Facades\Hash;

/**
 * Surrounding whitespace in a password is almost always accidental — a stray
 * space from a copy-paste or an autofill — and it locks the account owner out
 * the moment they type the password by hand.
 */
it('rejects a new account password with leading whitespace', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'password',
        'password'              => '   NewPassw0rd',
        'password_confirmation' => '   NewPassw0rd',
    ]);

    $response->assertSessionHasErrors('password');

    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue();
});

it('rejects a new account password with trailing whitespace', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'password',
        'password'              => "NewPassw0rd\t",
        'password_confirmation' => "NewPassw0rd\t",
    ]);

    $response->assertSessionHasErrors('password');

    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue();
});

it('still rejects a password made only of whitespace', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'password',
        'password'              => '          ',
        'password_confirmation' => '          ',
    ]);

    $response->assertSessionHasErrors('password');

    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue();
});

it('accepts a password that contains inner spaces', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'password',
        'password'              => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ]);

    $response->assertSessionHasNoErrors();

    expect(Hash::check('correct horse battery staple', $admin->fresh()->password))->toBeTrue();
});
