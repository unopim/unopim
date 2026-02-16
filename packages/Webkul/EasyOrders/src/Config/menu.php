<?php

return [
    /**
     * EasyOrders.
     */
    [
        'key'   => 'easyorders',
        'name'  => 'easyorders::app.components.layouts.sidebar.easyorders',
        'route' => 'easyorders.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-easyorders',
    ], [
        'key'   => 'easyorders.credentials',
        'name'  => 'easyorders::app.components.layouts.sidebar.credentials',
        'route' => 'easyorders.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'easyorders.export-mappings',
        'name'   => 'easyorders::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.easyorders.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'easyorders.import-mappings',
        'name'   => 'easyorders::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.easyorders.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'easyorders.settings',
        'name'   => 'easyorders::app.components.layouts.sidebar.settings',
        'route'  => 'admin.easyorders.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
