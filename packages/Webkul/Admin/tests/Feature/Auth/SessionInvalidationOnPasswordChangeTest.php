<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;

/**
 * A password change must end every other session of that admin. Each session
 * remembers the password hash it was opened with, so a session opened before
 * the change ("another browser") carries the old hash.
 */
function sessionOpenedWith(string $passwordHash): array
{
    return ['password_hash_admin' => auth()->guard('admin')->hashPasswordForCookie($passwordHash)];
}

function accountPasswordPayload(Admin $admin, string $password): array
{
    return [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => $admin->ui_locale_id ?: Locale::where('status', 1)->value('id'),
        'current_password'      => 'password',
        'password'              => $password,
        'password_confirmation' => $password,
    ];
}

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

it('keeps an untouched session signed in', function () {
    $admin = $this->loginAsAdmin();

    $this->withSession(sessionOpenedWith($admin->password))
        ->get(route('admin.dashboard.index'))
        ->assertOk();
});

it('signs out other sessions when the admin changes their own password', function () {
    $admin = $this->loginAsAdmin();

    $oldHash = $admin->password;

    $this->put(route('admin.account.update'), accountPasswordPayload($admin, 'NewPassw0rd!'))
        ->assertSessionHasNoErrors();

    $this->get(route('admin.dashboard.index'))->assertOk();

    $this->flushSession();

    $this->actingAs($admin->fresh(), 'admin')
        ->withSession(sessionOpenedWith($oldHash))
        ->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));

    $this->assertGuest('admin');
});

it('signs out the sessions of a user whose password another admin changes', function () {
    $this->loginAsAdmin();

    $target = Admin::factory()->create(['name' => 'Password Target', 'password' => Hash::make('password')]);

    $oldHash = $target->password;

    $this->withHeaders([
        'Accept'           => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Ajax-Form'      => 'true',
    ])->put(route('admin.settings.users.update'), [
        'id'                    => $target->id,
        'name'                  => $target->name,
        'email'                 => $target->email,
        'role_id'               => $target->role_id,
        'ui_locale_id'          => $target->ui_locale_id ?: Locale::where('status', 1)->value('id'),
        'timezone'              => 'Asia/Kolkata',
        'password'              => 'NewPassw0rd!',
        'password_confirmation' => 'NewPassw0rd!',
        'status'                => '1',
        'current_password'      => 'password',
    ])->assertOk();

    $this->flushHeaders()->flushSession();

    $this->actingAs($target->fresh(), 'admin')
        ->withSession(sessionOpenedWith($oldHash))
        ->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));

    $this->assertGuest('admin');
});

it('signs out the other sessions of an admin who resets a forgotten password', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => Hash::make('password'), 'status' => 1]);

    $oldHash = $admin->password;

    $this->post(route('admin.reset_password.store'), [
        'token'                 => Password::broker('admins')->createToken($admin),
        'email'                 => $admin->email,
        'password'              => 'NewPassw0rd!',
        'password_confirmation' => 'NewPassw0rd!',
    ])->assertRedirect(route('admin.dashboard.index'));

    $this->get(route('admin.dashboard.index'))->assertOk();

    $this->flushSession();

    $this->actingAs($admin->fresh(), 'admin')
        ->withSession(sessionOpenedWith($oldHash))
        ->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));

    $this->assertGuest('admin');
});

it('answers an ajax request from a stale session with 401', function () {
    $admin = $this->loginAsAdmin();

    $oldHash = $admin->password;

    $admin->forceFill(['password' => Hash::make('NewPassw0rd!')])->save();

    $this->actingAs($admin->fresh(), 'admin')
        ->withSession(sessionOpenedWith($oldHash))
        ->getJson(route('admin.dashboard.index'))
        ->assertUnauthorized();
});
