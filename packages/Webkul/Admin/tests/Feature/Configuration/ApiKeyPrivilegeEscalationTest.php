<?php

use Webkul\AdminApi\Models\Apikey;

/**
 * Regression coverage for the API-key privilege-escalation fix.
 *
 * The Integrations create/update endpoints let an admin mint an API key whose
 * authority exceeded their own role: permission_type=all and arbitrary api.*
 * permissions were accepted with no cap. The fix restricts the requested
 * authority to a subset of the creating admin's effective ACL.
 */

/** A low-privilege admin (Integrations permission only) must not be able to mint an all-access key. */
it('forbids a low-privilege admin from creating an all-access API key', function () {
    $admin = $this->loginWithPermissions('custom', [
        'configuration',
        'configuration.integrations',
        'configuration.integrations.create',
    ]);

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Escalation Key',
        'admin_id'        => $admin->id,
        'permission_type' => 'all',
    ])->assertInvalid(['permission_type']);

    $this->assertDatabaseMissing($this->getFullTableName(Apikey::class), [
        'name'            => 'Escalation Key',
        'permission_type' => 'all',
    ]);
});

/** A low-privilege admin must not be able to grant a scope their own role lacks. */
it('forbids granting a custom permission the creating admin does not hold', function () {
    $admin = $this->loginWithPermissions('custom', [
        'configuration',
        'configuration.integrations',
        'configuration.integrations.create',
    ]);

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Over-scoped Key',
        'admin_id'        => $admin->id,
        'permission_type' => 'custom',
        'permissions'     => ['api.catalog.products.create'],
    ])->assertInvalid(['permissions']);

    $this->assertDatabaseMissing($this->getFullTableName(Apikey::class), [
        'name' => 'Over-scoped Key',
    ]);
});

/** A low-privilege admin may grant a scope they DO hold (mapped from the web ACL key). */
it('allows granting a custom permission the creating admin holds', function () {
    $admin = $this->loginWithPermissions('custom', [
        'configuration',
        'configuration.integrations',
        'configuration.integrations.create',
        'catalog',
        'catalog.products',
        'catalog.products.create',
    ]);

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'In-scope Key',
        'admin_id'        => $admin->id,
        'permission_type' => 'custom',
        'permissions'     => ['api.catalog.products.create'],
    ])->assertSessionHas('success', trans('admin::app.configuration.integrations.create-success'));

    $this->assertDatabaseHas($this->getFullTableName(Apikey::class), [
        'name'            => 'In-scope Key',
        'admin_id'        => $admin->id,
        'permission_type' => 'custom',
    ]);
});

/** A full-access (super) admin may still mint an all-access key. */
it('allows a full-access admin to create an all-access API key', function () {
    $admin = $this->loginWithPermissions('all', []);

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Admin All Key',
        'admin_id'        => $admin->id,
        'permission_type' => 'all',
    ])->assertSessionHas('success', trans('admin::app.configuration.integrations.create-success'));

    $this->assertDatabaseHas($this->getFullTableName(Apikey::class), [
        'name'            => 'Admin All Key',
        'admin_id'        => $admin->id,
        'permission_type' => 'all',
    ]);
});
