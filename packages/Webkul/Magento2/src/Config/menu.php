<?php

return [
    /**
     * Magento2.
     */
    [
        'key'   => 'magento2',
        'name'  => 'magento2::app.components.layouts.sidebar.magento2',
        'route' => 'magento2.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-magento2',
    ], [
        'key'   => 'magento2.credentials',
        'name'  => 'magento2::app.components.layouts.sidebar.credentials',
        'route' => 'magento2.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => 'magento2.export-mappings',
        'name'   => 'magento2::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.magento2.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => 'magento2.import-mappings',
        'name'   => 'magento2::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.magento2.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => 'magento2.settings',
        'name'   => 'magento2::app.components.layouts.sidebar.settings',
        'route'  => 'admin.magento2.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
