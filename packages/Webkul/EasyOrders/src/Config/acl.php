<?php

return [
    [
        'key'   => 'easyorders',
        'name'  => 'easyorders::app.components.layouts.sidebar.easyorders',
        'route' => 'easyorders.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'easyorders.credentials',
        'name'  => 'easyorders::app.components.layouts.sidebar.credentials',
        'route' => 'easyorders.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'easyorders.credentials.create',
        'name'  => 'easyorders::app.easyorders.acl.credential.create',
        'route' => 'easyorders.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'easyorders.credentials.edit',
        'name'  => 'easyorders::app.easyorders.acl.credential.edit',
        'route' => 'easyorders.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'easyorders.credentials.delete',
        'name'  => 'easyorders::app.easyorders.acl.credential.delete',
        'route' => 'easyorders.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'easyorders.export-mappings',
        'name'  => 'easyorders::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.easyorders.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'easyorders.import-mappings',
        'name'  => 'easyorders::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.easyorders.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'easyorders.settings',
        'name'  => 'easyorders::app.components.layouts.sidebar.settings',
        'route' => 'admin.easyorders.settings',
        'sort'  => 4,
    ],
];
