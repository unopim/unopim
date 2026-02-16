<?php

return [
    /**
     * Shopify.
     */
    [
        'key'   => 'shopify',
        'name'  => 'shopify::app.components.layouts.sidebar.shopify',
        'route' => 'shopify.credentials.index',
        'sort'  => 10,
        'icon'  => 'icon-shopify',
    ], [
        'key'   => 'shopify.credentials',
        'name'  => 'shopify::app.components.layouts.sidebar.credentials',
        'route' => 'shopify.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'shopify.export-mappings',
        'name'   => 'shopify::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.shopify.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'shopify.import-mappings',
        'name'   => 'shopify::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.shopify.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'shopify.meta-fields',
        'name'   => 'shopify::app.components.layouts.sidebar.meta-fields',
        'route'  => 'shopify.metafield.index',
        'sort'   => 4,
    ], [
        'key'    => 'shopify.settings',
        'name'   => 'shopify::app.components.layouts.sidebar.settings',
        'route'  => 'admin.shopify.settings',
        'params' => [2],
        'sort'   => 5,
    ],
];
