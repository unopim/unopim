<?php

return [
    /**
     * Ebay.
     */
    [
        'key'   => 'ebay',
        'name'  => 'ebay::app.components.layouts.sidebar.ebay',
        'route' => 'ebay.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-ebay',
    ], [
        'key'   => 'ebay.credentials',
        'name'  => 'ebay::app.components.layouts.sidebar.credentials',
        'route' => 'ebay.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'ebay.export-mappings',
        'name'   => 'ebay::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.ebay.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'ebay.import-mappings',
        'name'   => 'ebay::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.ebay.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'ebay.settings',
        'name'   => 'ebay::app.components.layouts.sidebar.settings',
        'route'  => 'admin.ebay.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
