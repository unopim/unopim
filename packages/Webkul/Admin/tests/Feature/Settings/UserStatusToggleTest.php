<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * The status switch ships a hidden `0` fallback, so `status` is always present in
 * the payload. Deriving the new value from the key's presence therefore made the
 * user permanently enabled; these pin the value itself.
 */
function userStatusPayload(Admin $target, string $status): array
{
    return [
        'id'                    => $target->id,
        'name'                  => 'Status Probe',
        'email'                 => $target->email,
        'role_id'               => $target->role_id,
        'ui_locale_id'          => $target->ui_locale_id ?: Locale::where('status', 1)->value('id'),
        'timezone'              => 'Asia/Kolkata',
        'password'              => '',
        'password_confirmation' => '',
        'status'                => $status,
    ];
}

function updateUserStatus($test, Admin $target, string $status)
{
    return $test->withHeaders([
        'Accept'           => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Ajax-Form'      => 'true',
    ])->put(route('admin.settings.users.update'), userStatusPayload($target, $status));
}

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->loginAsAdmin();
});

it('disables a user when the status switch is turned off', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    $target = Admin::factory()->create(['status' => 1, 'role_id' => $role->id]);

    updateUserStatus($this, $target, '0')->assertOk();

    expect((int) $target->fresh()->status)->toBe(0);
});

it('enables a user when the status switch is turned on', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    $target = Admin::factory()->create(['status' => 0, 'role_id' => $role->id]);

    updateUserStatus($this, $target, '1')->assertOk();

    expect((int) $target->fresh()->status)->toBe(1);
});

it('keeps a user enabled when the payload carries no status at all', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    $target = Admin::factory()->create(['status' => 1, 'role_id' => $role->id]);

    $payload = userStatusPayload($target, '1');

    unset($payload['status']);

    $this->withHeaders([
        'Accept'           => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Ajax-Form'      => 'true',
    ])->put(route('admin.settings.users.update'), $payload)->assertOk();

    expect((int) $target->fresh()->status)->toBe(0);
});
