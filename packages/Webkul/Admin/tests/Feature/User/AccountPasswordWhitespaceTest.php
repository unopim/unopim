<?php

beforeEach(function () {
    $this->loginAsAdmin();
});

it('rejects an account password that consists only of spaces', function () {
    $admin = auth('admin')->user();
    $spaces = str_repeat(' ', 8);

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => $admin->timezone ?: 'UTC',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'any-value',
        'password'              => $spaces,
        'password_confirmation' => $spaces,
    ]);

    $response->assertSessionHasErrors('password');
});

it('does not flag a password that has actual content', function () {
    $admin = auth('admin')->user();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => $admin->timezone ?: 'UTC',
        'ui_locale_id'          => $admin->ui_locale_id ?: 1,
        'current_password'      => 'wrong-current-password',
        'password'              => 'ValidPass123',
        'password_confirmation' => 'ValidPass123',
    ]);

    $response->assertSessionDoesntHaveErrors('password');
});
