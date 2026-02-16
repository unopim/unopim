<?php

return [
    /**
     * Amazon.
     */
    [
        'key'   => 'amazon',
        'name'  => 'amazon::app.components.layouts.sidebar.amazon',
        'route' => 'amazon.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-amazon',
    ], [
        'key'   => 'amazon.credentials',
        'name'  => 'amazon::app.components.layouts.sidebar.credentials',
        'route' => 'amazon.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'amazon.export-mappings',
        'name'   => 'amazon::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.amazon.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'amazon.import-mappings',
        'name'   => 'amazon::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.amazon.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'amazon.settings',
        'name'   => 'amazon::app.components.layouts.sidebar.settings',
        'route'  => 'admin.amazon.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
