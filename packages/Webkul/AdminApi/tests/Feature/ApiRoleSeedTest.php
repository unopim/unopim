<?php

use Webkul\AdminApi\Support\ApiRole;
use Webkul\User\Models\Role;

it('seeds a permission-less API role exactly once', function () {
    $role = Role::where('name', 'API')->first();

    expect($role)->not->toBeNull()
        ->and($role->permission_type)->toBe('custom')
        ->and($role->permissions)->toBe([]);

    // Idempotent: re-running the seed helper does not duplicate.
    ApiRole::ensure();

    expect(Role::where('name', 'API')->count())->toBe(1);
});
