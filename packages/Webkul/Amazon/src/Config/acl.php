<?php

return [
    [
        'key'   => 'amazon',
        'name'  => 'amazon::app.components.layouts.sidebar.amazon',
        'route' => 'amazon.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'amazon.credentials',
        'name'  => 'amazon::app.components.layouts.sidebar.credentials',
        'route' => 'amazon.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'amazon.credentials.create',
        'name'  => 'amazon::app.amazon.acl.credential.create',
        'route' => 'amazon.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'amazon.credentials.edit',
        'name'  => 'amazon::app.amazon.acl.credential.edit',
        'route' => 'amazon.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'amazon.credentials.delete',
        'name'  => 'amazon::app.amazon.acl.credential.delete',
        'route' => 'amazon.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'amazon.export-mappings',
        'name'  => 'amazon::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.amazon.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'amazon.import-mappings',
        'name'  => 'amazon::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.amazon.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'amazon.settings',
        'name'  => 'amazon::app.components.layouts.sidebar.settings',
        'route' => 'admin.amazon.settings',
        'sort'  => 4,
    ],
];
