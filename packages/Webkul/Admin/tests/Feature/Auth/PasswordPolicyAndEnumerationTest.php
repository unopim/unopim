<?php

it('rejects a weak (too short) password on admin user create', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Weak Pass User',
        'email'                 => 'weakpass@example.test',
        'password'              => 'ab',
        'password_confirmation' => 'ab',
        'role_id'               => 1,
        'timezone'              => 'UTC',
    ])->assertSessionHasErrors(['password']);
});

it('does not reveal whether an email exists on forgot-password (no enumeration)', function () {
    $this->post(route('admin.forget_password.store'), [
        'email' => 'does-not-exist@example.test',
    ])->assertSessionHasNoErrors();
});
