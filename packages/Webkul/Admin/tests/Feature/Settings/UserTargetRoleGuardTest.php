<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\deleteJson;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->fullRole = Role::factory()->create(['permission_type' => 'all', 'permissions' => null]);
    $this->heldRole = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);
    $this->widerRole = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard', 'settings', 'settings.roles']]);

    Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);

    $this->userManager = $this->loginWithPermissions('custom', [
        'dashboard',
        'settings',
        'settings.users',
        'settings.users.users',
        'settings.users.users.edit',
        'settings.users.users.delete',
    ]);
});

function takeOverUser(Admin $target, int $roleId): TestResponse
{
    return test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'X-Ajax-Form' => 'true'])
        ->put(route('admin.settings.users.update'), [
            'id'                    => $target->id,
            'name'                  => 'Taken Over',
            'email'                 => 'taken-over-'.$target->id.'@example.com',
            'role_id'               => $roleId,
            'ui_locale_id'          => $target->ui_locale_id ?: Locale::where('status', 1)->value('id'),
            'timezone'              => 'Asia/Kolkata',
            'password'              => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
            'status'                => '1',
            'current_password'      => 'password',
        ]);
}

it('blocks a user manager from editing an admin whose current role it could not assign', function (string $targetRole): void {
    $target = Admin::factory()->create([
        'role_id'  => $this->{$targetRole}->id,
        'status'   => 1,
        'password' => Hash::make('original'),
    ]);

    takeOverUser($target, $this->heldRole->id)->assertForbidden();

    $target->refresh();

    expect($target->email)->not->toBe('taken-over-'.$target->id.'@example.com')
        ->and($target->role_id)->toBe($this->{$targetRole}->id)
        ->and(Hash::check('original', $target->password))->toBeTrue();
})->with(['full access' => 'fullRole', 'wider custom access' => 'widerRole']);

it('lets a user manager edit an admin whose current role it could assign', function (): void {
    $target = Admin::factory()->create(['role_id' => $this->heldRole->id, 'status' => 1]);

    takeOverUser($target, $this->heldRole->id)->assertOk();

    expect($target->fresh()->email)->toBe('taken-over-'.$target->id.'@example.com');
});

it('blocks a user manager from deleting an admin whose current role it could not assign', function (string $targetRole): void {
    $target = Admin::factory()->create(['role_id' => $this->{$targetRole}->id, 'status' => 1]);

    deleteJson(route('admin.settings.users.delete', $target->id))
        ->assertForbidden()
        ->assertJson(['message' => trans('admin::app.common.unauthorized')]);

    expect(Admin::find($target->id))->not->toBeNull();
})->with(['full access' => 'fullRole', 'wider custom access' => 'widerRole']);

it('lets a user manager delete an admin whose current role it could assign', function (): void {
    $target = Admin::factory()->create(['role_id' => $this->heldRole->id, 'status' => 1]);

    deleteJson(route('admin.settings.users.delete', $target->id))->assertOk();

    expect(Admin::find($target->id))->toBeNull();
});
