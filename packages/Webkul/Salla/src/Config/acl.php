<?php

return [
    [
        'key'   => 'salla',
        'name'  => 'salla::app.components.layouts.sidebar.salla',
        'route' => 'salla.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'salla.credentials',
        'name'  => 'salla::app.components.layouts.sidebar.credentials',
        'route' => 'salla.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'salla.credentials.create',
        'name'  => 'salla::app.salla.acl.credential.create',
        'route' => 'salla.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'salla.credentials.edit',
        'name'  => 'salla::app.salla.acl.credential.edit',
        'route' => 'salla.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'salla.credentials.delete',
        'name'  => 'salla::app.salla.acl.credential.delete',
        'route' => 'salla.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'salla.export-mappings',
        'name'  => 'salla::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.salla.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'salla.import-mappings',
        'name'  => 'salla::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.salla.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'salla.settings',
        'name'  => 'salla::app.components.layouts.sidebar.settings',
        'route' => 'admin.salla.settings',
        'sort'  => 4,
    ],
];
