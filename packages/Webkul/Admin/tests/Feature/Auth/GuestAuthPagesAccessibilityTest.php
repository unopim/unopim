<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Webkul\User\Models\Admin;

function resetPasswordPage(): string
{
    $admin = Admin::factory()->create([
        'email'    => 'a11y-reset@example.com',
        'password' => Hash::make('password'),
    ]);

    return test()->get(route('admin.reset_password.create', [
        'token' => Password::broker('admins')->createToken($admin),
        'email' => $admin->email,
    ]))->assertOk()->getContent();
}

it('links each login label to its field', function () {
    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('for="email"', false)
        ->assertSee('for="password"', false);
});

it('links the forgot password label to its field', function () {
    $this->get(route('admin.forget_password.create'))
        ->assertOk()
        ->assertSee('for="email"', false);
});

it('links each reset password label to its field', function () {
    expect(resetPasswordPage())
        ->toContain('for="email"')
        ->toContain('for="password"')
        ->toContain('for="password_confirmation"');
});

it('renders the login password toggle as a keyboard operable button', function () {
    $html = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect($html)->toMatch('/<button\s[^>]*type="button"[^>]*id="visibilityIcon"[^>]*aria-pressed="false"/s')
        ->not->toMatch('/<span[^>]*id="visibilityIcon"/');
});

it('renders both reset password toggles as keyboard operable buttons', function () {
    $html = resetPasswordPage();

    expect($html)->toMatch('/<button\s[^>]*type="button"[^>]*id="passwordIcon"[^>]*aria-pressed="false"/s')
        ->toMatch('/<button\s[^>]*type="button"[^>]*id="passwordConfirmationIcon"[^>]*aria-pressed="false"/s')
        ->not->toMatch('/<span[^>]*id="password(Confirmation)?Icon"/');
});
