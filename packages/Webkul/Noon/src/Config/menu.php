<?php

return [
    /**
     * Noon.
     */
    [
        'key'   => 'noon',
        'name'  => 'noon::app.components.layouts.sidebar.noon',
        'route' => 'noon.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-noon',
    ], [
        'key'   => 'noon.credentials',
        'name'  => 'noon::app.components.layouts.sidebar.credentials',
        'route' => 'noon.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'noon.export-mappings',
        'name'   => 'noon::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.noon.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'noon.import-mappings',
        'name'   => 'noon::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.noon.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'noon.settings',
        'name'   => 'noon::app.components.layouts.sidebar.settings',
        'route'  => 'admin.noon.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
