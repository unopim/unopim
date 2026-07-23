<?php

use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('repoints legacy human-bound keys to robots and is idempotent', function () {
    $role = Role::factory()->create();
    $human = Admin::factory()->create(['role_id' => $role->id, 'type' => 'user']);
    $key = Apikey::factory()->create(['admin_id' => $human->id, 'revoked' => 0]);

    $migration = include base_path('packages/Webkul/AdminApi/src/Database/Migrations/2026_07_20_100200_migrate_existing_api_keys_to_robots.php');
    $migration->up();
    $migration->up();

    $key->refresh();
    $newOwner = Admin::findOrFail($key->admin_id);

    expect($newOwner->isApiUser())->toBeTrue()
        ->and($newOwner->id)->not->toBe($human->id);

    $robotIdAfterFirstRun = $newOwner->id;

    $migration->up();
    $key->refresh();

    expect($key->admin_id)->toBe($robotIdAfterFirstRun);
});
