<?php

return [
    /**
     * WooCommerce.
     */
    [
        'key'   => 'woocommerce',
        'name'  => 'woocommerce::app.components.layouts.sidebar.woocommerce',
        'route' => 'woocommerce.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-woocommerce',
    ], [
        'key'   => 'woocommerce.credentials',
        'name'  => 'woocommerce::app.components.layouts.sidebar.credentials',
        'route' => 'woocommerce.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'woocommerce.export-mappings',
        'name'   => 'woocommerce::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.woocommerce.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'woocommerce.import-mappings',
        'name'   => 'woocommerce::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.woocommerce.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'woocommerce.settings',
        'name'   => 'woocommerce::app.components.layouts.sidebar.settings',
        'route'  => 'admin.woocommerce.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
