<?php

use Webkul\User\Models\Role;

use function Pest\Laravel\putJson;

beforeEach(function (): void {
    $this->loginWithPermissions('custom', ['dashboard', 'settings', 'settings.roles', 'settings.roles.edit']);
});

it('forbids a role manager from rewriting a role whose current access exceeds its own', function (array $current): void {
    $role = Role::factory()->create($current);

    putJson(route('admin.settings.roles.update', ['id' => $role->id]), [
        'name'            => 'Rewritten',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ])->assertForbidden();

    expect($role->fresh()->permission_type)->toBe($current['permission_type'])
        ->and($role->fresh()->name)->not->toBe('Rewritten');
})->with([
    'full access'          => [['permission_type' => 'all', 'permissions' => null]],
    'wider custom access'  => [['permission_type' => 'custom', 'permissions' => ['dashboard', 'settings.users']]],
]);

it('lets a role manager rewrite a role within its own access', function (): void {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard', 'settings']]);

    putJson(route('admin.settings.roles.update', ['id' => $role->id]), [
        'name'            => 'Rewritten',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ])->assertOk();

    expect($role->fresh()->name)->toBe('Rewritten');
});
