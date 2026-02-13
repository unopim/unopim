<?php

/*
|--------------------------------------------------------------------------
| Translation Keys Test
|--------------------------------------------------------------------------
|
| Verifies all required translation keys exist in the tenant language file.
|
*/

it('has all required tenant translation keys', function () {
    $requiredKeys = [
        'tenant::app.tenants.title',
        'tenant::app.tenants.index.title',
        'tenant::app.tenants.index.create-btn',
        'tenant::app.tenants.create.title',
        'tenant::app.tenants.create.name',
        'tenant::app.tenants.create.domain',
        'tenant::app.tenants.create.admin-email',
        'tenant::app.tenants.create.save-btn',
        'tenant::app.tenants.create.back-btn',
        'tenant::app.tenants.edit.title',
        'tenant::app.tenants.edit.name',
        'tenant::app.tenants.edit.domain',
        'tenant::app.tenants.edit.status',
        'tenant::app.tenants.edit.save-btn',
        'tenant::app.tenants.edit.back-btn',
        'tenant::app.tenants.show.title',
        'tenant::app.tenants.show.domain',
        'tenant::app.tenants.show.status',
        'tenant::app.tenants.show.created-at',
        'tenant::app.tenants.show.back-btn',
        'tenant::app.tenants.show.edit-btn',
        'tenant::app.tenants.datagrid.id',
        'tenant::app.tenants.datagrid.name',
        'tenant::app.tenants.datagrid.domain',
        'tenant::app.tenants.datagrid.status',
        'tenant::app.tenants.datagrid.created-at',
        'tenant::app.tenants.datagrid.edit',
        'tenant::app.tenants.datagrid.delete',
        'tenant::app.tenants.status.provisioning',
        'tenant::app.tenants.status.active',
        'tenant::app.tenants.status.suspended',
        'tenant::app.tenants.status.deleting',
        'tenant::app.tenants.status.deleted',
        'tenant::app.tenants.create-success',
        'tenant::app.tenants.update-success',
        'tenant::app.tenants.delete-success',
        'tenant::app.tenants.delete-failed',
        'tenant::app.tenants.suspend-success',
        'tenant::app.tenants.activate-success',
        'tenant::app.tenants.cannot-delete-provisioning',
        'tenant::app.acl.tenants',
        'tenant::app.acl.create',
        'tenant::app.acl.edit',
        'tenant::app.acl.delete',
        'tenant::app.acl.suspend',
        'tenant::app.acl.activate',
    ];

    foreach ($requiredKeys as $key) {
        $translated = trans($key);
        // If the key is not found, trans() returns the key itself
        expect($translated)->not->toBe($key, "Translation key '{$key}' is missing");
    }
});
