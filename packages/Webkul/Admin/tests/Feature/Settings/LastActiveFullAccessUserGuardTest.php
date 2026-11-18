<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->fullRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => null]);
    $this->customRole = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    Admin::query()->update(['role_id' => $this->customRole->id]);
    Role::query()->where('permission_type', 'all')->whereKeyNot($this->fullRole->id)->update(['permission_type' => 'custom']);

    $this->lastActiveAdmin = Admin::factory()->create([
        'role_id'  => $this->fullRole->id,
        'status'   => 1,
        'password' => Hash::make('password'),
    ]);
    $this->inactiveFullAdmin = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 0]);
});

function moveUserToRole(Admin $target, int $roleId)
{
    return test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'X-Ajax-Form' => 'true'])
        ->put(route('admin.settings.users.update'), [
            'id'                    => $target->id,
            'name'                  => 'Full Access Probe',
            'email'                 => $target->email,
            'role_id'               => $roleId,
            'ui_locale_id'          => $target->ui_locale_id ?: Locale::where('status', 1)->value('id'),
            'timezone'              => 'Asia/Kolkata',
            'password'              => '',
            'password_confirmation' => '',
            'status'                => '1',
            'current_password'      => 'password',
        ]);
}

it('blocks moving the last active full-access admin to a custom role while only an inactive one remains', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings', 'settings.users', 'settings.users.users', 'settings.users.users.edit']);

    moveUserToRole($this->lastActiveAdmin, $this->customRole->id)
        ->assertForbidden();

    expect($this->lastActiveAdmin->fresh()->role_id)->toBe($this->fullRole->id);
});

it('allows moving a full-access admin while another active one keeps full access', function () {
    $this->loginAsAdmin(Admin::factory()->create([
        'role_id'  => $this->fullRole->id,
        'status'   => 1,
        'password' => Hash::make('password'),
    ]));

    moveUserToRole($this->lastActiveAdmin, $this->customRole->id);

    expect($this->lastActiveAdmin->fresh()->role_id)->toBe($this->customRole->id);
});

it('blocks the last active full-access admin from deleting their own account', function () {
    $this->loginAsAdmin($this->lastActiveAdmin);

    putJson(route('admin.settings.users.destroy'), ['password' => 'password'])
        ->assertStatus(400)
        ->assertJson(['message' => trans('admin::app.settings.users.last-all-access-delete-error')]);

    expect(Admin::find($this->lastActiveAdmin->id))->not->toBeNull();
});

it('allows deleting an inactive full-access admin while an active one remains', function () {
    $this->loginAsAdmin($this->lastActiveAdmin);

    deleteJson(route('admin.settings.users.delete', $this->inactiveFullAdmin->id))->assertOk();

    expect(Admin::find($this->inactiveFullAdmin->id))->toBeNull();
});

it('locks the full-access roles before writing a user change that can remove full access', function (string $change) {
    $secondActiveAdmin = Admin::factory()->create([
        'role_id'  => $this->fullRole->id,
        'status'   => 1,
        'password' => Hash::make('password'),
    ]);

    $this->loginAsAdmin($change === 'self delete' ? $secondActiveAdmin : $this->lastActiveAdmin);

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = strtolower($query->sql);
    });

    match ($change) {
        'role change' => moveUserToRole($secondActiveAdmin, $this->customRole->id),
        'delete'      => deleteJson(route('admin.settings.users.delete', $secondActiveAdmin->id)),
        'self delete' => putJson(route('admin.settings.users.destroy'), ['password' => 'password']),
    };

    $lockAt = collect($queries)->search(fn (string $sql): bool => str_contains($sql, 'roles') && str_contains($sql, 'for update'));
    $writeAt = collect($queries)->search(fn (string $sql): bool => preg_match('/^(update|delete from) \S*admins/', $sql) === 1);

    expect($lockAt)->toBeInt()
        ->and($writeAt)->toBeInt()
        ->and($lockAt)->toBeLessThan($writeAt);
})->with(['role change', 'delete', 'self delete']);

it('does not lock the full-access roles when editing a user who holds no full access', function () {
    $this->loginAsAdmin($this->lastActiveAdmin);

    $customUser = Admin::factory()->create(['role_id' => $this->customRole->id, 'status' => 1]);

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = strtolower($query->sql);
    });

    moveUserToRole($customUser, $this->customRole->id)->assertOk();

    expect(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'roles') && str_contains($sql, 'for update')))->toBeFalse();
});

it('returns the reason itself when a user change is refused', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings', 'settings.users', 'settings.users.users', 'settings.users.users.edit']);

    moveUserToRole($this->lastActiveAdmin, $this->customRole->id)
        ->assertForbidden()
        ->assertExactJson(['message' => trans('admin::app.settings.users.cannot-change', ['name' => 'role'])]);
});
