<?php

return [
    /**
     * Salla.
     */
    [
        'key'   => 'salla',
        'name'  => 'salla::app.components.layouts.sidebar.salla',
        'route' => 'salla.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-salla',
    ], [
        'key'   => 'salla.credentials',
        'name'  => 'salla::app.components.layouts.sidebar.credentials',
        'route' => 'salla.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'salla.export-mappings',
        'name'   => 'salla::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.salla.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'salla.import-mappings',
        'name'   => 'salla::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.salla.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'salla.settings',
        'name'   => 'salla::app.components.layouts.sidebar.settings',
        'route'  => 'admin.salla.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
