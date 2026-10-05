<?php

it('rejects a malformed permissions payload without a server error', function () {
    $admin = $this->loginWithPermissions('custom', ['settings', 'settings.roles', 'settings.roles.edit']);

    $this->put(route('admin.settings.roles.update', $admin->role->id), [
        'name'            => $admin->role->name,
        'permission_type' => 'custom',
        'permissions'     => ['settings.roles.edit', ['nested' => 'payload']],
    ])->assertForbidden();
});
