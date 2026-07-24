<?php

/**
 * Section-wise System Settings ACL. Every hub row shares one generic editor
 * route (`admin.settings.system.edit/update`), so the Bouncer middleware can
 * only gate at the umbrella `configuration.system_settings` level. Per-section
 * access is enforced in SystemSettingsController against each row's own `acl`;
 * these cover that enforcement and its cross-section isolation.
 */
it('denies the system settings editor entirely without the umbrella permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.debug']))
        ->assertStatus(403);
});

it('denies a section the role lacks even with the umbrella permission', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.debug']))
        ->assertStatus(403);
});

it('allows a section the role is explicitly granted', function () {
    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.debug',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.debug']))
        ->assertOk();
});

it('isolates sections: granting one does not unlock another', function () {
    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.debug',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.email']))
        ->assertStatus(403);
});

it('enforces the section permission on update, not just edit', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->put(route('admin.settings.system.update', ['key' => 'system.debug']), [])
        ->assertStatus(403);
});

it('gates the package-provided publication section by its own permission', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.publication']))
        ->assertStatus(403);

    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.publication',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.publication']))
        ->assertOk();
});
