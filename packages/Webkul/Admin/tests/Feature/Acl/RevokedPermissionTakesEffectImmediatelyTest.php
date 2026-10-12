<?php

use Webkul\User\Models\Role;

it('rejects a write as soon as its permission is revoked mid-session', function () {
    $admin = $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.create',
    ]);

    $payload = fn (string $code) => [
        'code'   => $code,
        'en_US'  => ['name' => 'Family '.$code],
        'groups' => [],
    ];

    $before = $this->post(route('admin.catalog.families.store'), $payload('family_before_revoke'));

    expect($before->getStatusCode())->not->toBe(403);

    Role::query()->whereKey($admin->role_id)->update([
        'permissions' => json_encode(['catalog', 'catalog.families']),
    ]);

    $admin->unsetRelation('role');

    $this->post(route('admin.catalog.families.store'), $payload('family_after_revoke'))
        ->assertStatus(403);
});

it('logs the session out once every permission is revoked', function () {
    $admin = $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);

    $this->get(route('admin.catalog.families.index'))->assertOk();

    Role::query()->whereKey($admin->role_id)->update(['permissions' => json_encode([])]);

    $admin->unsetRelation('role');

    $this->get(route('admin.catalog.families.index'))
        ->assertRedirect(route('admin.session.create'));

    $this->assertGuest('admin');
});

it('reports a new acl fingerprint once a permission is revoked', function () {
    $admin = $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.create']);

    $before = $this->getJson(route('admin.acl.version'))->assertOk()->json('version');

    expect($before)->not->toBe('');

    Role::query()->whereKey($admin->role_id)->update([
        'permissions' => json_encode(['catalog', 'catalog.families']),
    ]);

    $admin->unsetRelation('role');

    $after = $this->getJson(route('admin.acl.version'))->assertOk()->json('version');

    expect($after)->not->toBe($before);
});

it('keeps the acl fingerprint stable when nothing changes', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);

    $first = $this->getJson(route('admin.acl.version'))->assertOk()->json('version');
    $second = $this->getJson(route('admin.acl.version'))->assertOk()->json('version');

    expect($second)->toBe($first);
});
