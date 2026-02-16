<?php

return [
    [
        'key'   => 'shopify',
        'name'  => 'shopify::app.components.layouts.sidebar.shopify',
        'route' => 'shopify.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'shopify.credentials',
        'name'  => 'shopify::app.components.layouts.sidebar.credentials',
        'route' => 'shopify.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'shopify.credentials.create',
        'name'  => 'shopify::app.shopify.acl.credential.create',
        'route' => 'shopify.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'shopify.credentials.edit',
        'name'  => 'shopify::app.shopify.acl.credential.edit',
        'route' => 'shopify.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'shopify.credentials.delete',
        'name'  => 'shopify::app.shopify.acl.credential.delete',
        'route' => 'shopify.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => 'shopify.export-mappings',
        'name'  => 'shopify::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.shopify.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => 'shopify.import-mappings',
        'name'  => 'shopify::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.shopify.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => 'shopify.meta-fields',
        'name'  => 'shopify::app.components.layouts.sidebar.metafield-definitions',
        'route' => 'shopify.metafield.index',
        'sort'  => 4,
    ], [
        'key'   => 'shopify.meta-fields.create',
        'name'  => 'shopify::app.shopify.acl.metafield.create',
        'route' => 'shopify.metafield.store',
        'sort'  => 1,
    ], [
        'key'   => 'shopify.meta-fields.edit',
        'name'  => 'shopify::app.shopify.acl.metafield.edit',
        'route' => 'shopify.metafield.edit',
        'sort'  => 2,
    ], [
        'key'   => 'shopify.meta-fields.delete',
        'name'  => 'shopify::app.shopify.acl.metafield.delete',
        'route' => 'shopify.metafield.delete',
        'sort'  => 3,
    ], [
        'key'   => 'shopify.settings',
        'name'  => 'shopify::app.components.layouts.sidebar.settings',
        'route' => 'admin.shopify.settings',
        'sort'  => 5,
    ],
];
