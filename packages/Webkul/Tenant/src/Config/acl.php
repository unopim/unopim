<?php

return [
    [
        'key'   => 'settings.tenants',
        'name'  => 'tenant::app.acl.tenants',
        'route' => 'admin.settings.tenants.index',
        'sort'  => 8,
    ],
    [
        'key'   => 'settings.tenants.create',
        'name'  => 'tenant::app.acl.create',
        'route' => 'admin.settings.tenants.create',
        'sort'  => 1,
    ],
    [
        'key'   => 'settings.tenants.edit',
        'name'  => 'tenant::app.acl.edit',
        'route' => 'admin.settings.tenants.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'settings.tenants.delete',
        'name'  => 'tenant::app.acl.delete',
        'route' => 'admin.settings.tenants.destroy',
        'sort'  => 3,
    ],
    [
        'key'   => 'settings.tenants.suspend',
        'name'  => 'tenant::app.acl.suspend',
        'route' => 'admin.settings.tenants.suspend',
        'sort'  => 4,
    ],
    [
        'key'   => 'settings.tenants.activate',
        'name'  => 'tenant::app.acl.activate',
        'route' => 'admin.settings.tenants.activate',
        'sort'  => 5,
    ],
];
