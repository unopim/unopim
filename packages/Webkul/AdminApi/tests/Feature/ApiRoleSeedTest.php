<?php

use Webkul\AdminApi\Support\ApiRole;
use Webkul\User\Models\Role;

it('seeds a permission-less API role exactly once', function () {
    // Seeded by migration on a fresh install; ensured here so the assertion does
    // not depend on whatever the ambient database happens to hold.
    ApiRole::ensure();

    $role = Role::where('name', 'API')->first();

    expect($role)->not->toBeNull()
        ->and($role->permission_type)->toBe('custom')
        ->and($role->permissions)->toBe([]);

    ApiRole::ensure();

    expect(Role::where('name', 'API')->count())->toBe(1);
});
