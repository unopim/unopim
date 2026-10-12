<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('defaults new admins to the user type and scopes humans', function () {
    $role = Role::factory()->create();

    $human = Admin::factory()->create(['role_id' => $role->id]);
    $robot = Admin::factory()->create(['role_id' => $role->id, 'type' => 'api']);

    expect($human->type)->toBe('user')
        ->and($human->isApiUser())->toBeFalse()
        ->and($robot->isApiUser())->toBeTrue();

    $humanIds = Admin::humans()->pluck('id');

    expect($humanIds)->toContain($human->id)
        ->and($humanIds)->not->toContain($robot->id);
});
