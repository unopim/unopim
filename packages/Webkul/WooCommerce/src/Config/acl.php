<?php

return [
    [
        'key'   => 'woocommerce',
        'name'  => 'woocommerce::app.components.layouts.sidebar.woocommerce',
        'route' => 'woocommerce.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'woocommerce.credentials',
        'name'  => 'woocommerce::app.components.layouts.sidebar.credentials',
        'route' => 'woocommerce.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'woocommerce.credentials.create',
        'name'  => 'woocommerce::app.woocommerce.acl.credential.create',
        'route' => 'woocommerce.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'woocommerce.credentials.edit',
        'name'  => 'woocommerce::app.woocommerce.acl.credential.edit',
        'route' => 'woocommerce.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'woocommerce.credentials.delete',
        'name'  => 'woocommerce::app.woocommerce.acl.credential.delete',
        'route' => 'woocommerce.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'woocommerce.export-mappings',
        'name'  => 'woocommerce::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.woocommerce.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'woocommerce.import-mappings',
        'name'  => 'woocommerce::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.woocommerce.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'woocommerce.settings',
        'name'  => 'woocommerce::app.components.layouts.sidebar.settings',
        'route' => 'admin.woocommerce.settings',
        'sort'  => 4,
    ],
];
