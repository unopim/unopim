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

    $this->get(route('admin.settings.system.edit', ['key' => 'digital_product_passport.publication']))
        ->assertStatus(403);

    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.publication',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'digital_product_passport.publication']))
        ->assertOk();
});

it('gates the measurement section by its own permission', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.measurement']))
        ->assertStatus(403);

    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.measurement',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.measurement']))
        ->assertOk();
});

it('enforces the measurement section permission on update', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->put(route('admin.settings.system.update', ['key' => 'system.measurement']), [
        'system' => ['measurement' => ['amount' => '5']],
    ])->assertStatus(403);

    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.measurement',
    ]);

    $this->put(route('admin.settings.system.update', ['key' => 'system.measurement']), [
        'system' => ['measurement' => ['amount' => '5']],
    ])->assertRedirect();

    $this->assertDatabaseHas('core_config', [
        'code'  => 'system.measurement.amount',
        'value' => '5',
    ]);
});

it('isolates the measurement section from the debug section in both directions', function () {
    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.debug',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.measurement']))
        ->assertStatus(403);

    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.measurement',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.debug']))
        ->assertStatus(403);
});

it('registers the measurement section as an assignable permission with a resolvable label', function () {
    expect(collect(config('acl'))->pluck('key'))
        ->toContain('configuration.system_settings.measurement');

    expect(trans('measurement::app.config.catalog.measurement.title'))
        ->not->toBe('measurement::app.config.catalog.measurement.title');
});

it('hides the measurement card from the settings hub without the permission', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertDontSee(route('admin.settings.system.edit', 'system.measurement'));
});

it('shows the measurement card on the settings hub with the permission', function () {
    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.measurement',
    ]);

    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee(route('admin.settings.system.edit', 'system.measurement'));
});
