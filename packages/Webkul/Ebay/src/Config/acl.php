<?php

return [
    [
        'key'   => 'ebay',
        'name'  => 'ebay::app.components.layouts.sidebar.ebay',
        'route' => 'ebay.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'ebay.credentials',
        'name'  => 'ebay::app.components.layouts.sidebar.credentials',
        'route' => 'ebay.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'ebay.credentials.create',
        'name'  => 'ebay::app.ebay.acl.credential.create',
        'route' => 'ebay.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'ebay.credentials.edit',
        'name'  => 'ebay::app.ebay.acl.credential.edit',
        'route' => 'ebay.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'ebay.credentials.delete',
        'name'  => 'ebay::app.ebay.acl.credential.delete',
        'route' => 'ebay.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'ebay.export-mappings',
        'name'  => 'ebay::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.ebay.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'ebay.import-mappings',
        'name'  => 'ebay::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.ebay.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'ebay.settings',
        'name'  => 'ebay::app.components.layouts.sidebar.settings',
        'route' => 'admin.ebay.settings',
        'sort'  => 4,
    ],
];
