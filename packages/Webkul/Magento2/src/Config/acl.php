<?php

return [
    [
        'key'   => 'magento2',
        'name'  => 'magento2::app.components.layouts.sidebar.magento2',
        'route' => 'magento2.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'magento2.credentials',
        'name'  => 'magento2::app.components.layouts.sidebar.credentials',
        'route' => 'magento2.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'magento2.credentials.create',
        'name'  => 'magento2::app.magento2.acl.credential.create',
        'route' => 'magento2.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'magento2.credentials.edit',
        'name'  => 'magento2::app.magento2.acl.credential.edit',
        'route' => 'magento2.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'magento2.credentials.delete',
        'name'  => 'magento2::app.magento2.acl.credential.delete',
        'route' => 'magento2.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'magento2.export-mappings',
        'name'  => 'magento2::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.magento2.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'magento2.import-mappings',
        'name'  => 'magento2::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.magento2.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'magento2.settings',
        'name'  => 'magento2::app.components.layouts.sidebar.settings',
        'route' => 'admin.magento2.settings',
        'sort'  => 4,
    ],
];
