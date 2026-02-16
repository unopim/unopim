<?php

return [
    [
        'key'   => 'noon',
        'name'  => 'noon::app.components.layouts.sidebar.noon',
        'route' => 'noon.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'noon.credentials',
        'name'  => 'noon::app.components.layouts.sidebar.credentials',
        'route' => 'noon.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'noon.credentials.create',
        'name'  => 'noon::app.noon.acl.credential.create',
        'route' => 'noon.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'noon.credentials.edit',
        'name'  => 'noon::app.noon.acl.credential.edit',
        'route' => 'noon.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'noon.credentials.delete',
        'name'  => 'noon::app.noon.acl.credential.delete',
        'route' => 'noon.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'noon.export-mappings',
        'name'  => 'noon::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.noon.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'noon.import-mappings',
        'name'  => 'noon::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.noon.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'noon.settings',
        'name'  => 'noon::app.components.layouts.sidebar.settings',
        'route' => 'admin.noon.settings',
        'sort'  => 4,
    ],
];
