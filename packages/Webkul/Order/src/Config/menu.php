<?php

return [
    /**
     * Order Management
     */
    [
        'key'   => 'order',
        'name'  => 'order::app.menu.order',
        'route' => 'admin.order.orders.index',
        'sort'  => 9,
        'icon'  => 'icon-orders',
    ],

    /**
     * Orders
     */
    [
        'key'   => 'order.orders',
        'name'  => 'order::app.menu.orders',
        'route' => 'admin.order.orders.index',
        'sort'  => 1,
        'icon'  => '',
    ],

    /**
     * Sync Logs
     */
    [
        'key'   => 'order.sync',
        'name'  => 'order::app.menu.sync-logs',
        'route' => 'admin.order.sync.index',
        'sort'  => 2,
        'icon'  => '',
    ],

    /**
     * Profitability
     */
    [
        'key'   => 'order.profitability',
        'name'  => 'order::app.menu.profitability',
        'route' => 'admin.order.profitability.index',
        'sort'  => 3,
        'icon'  => '',
    ],

    /**
     * Webhooks
     */
    [
        'key'   => 'order.webhooks',
        'name'  => 'order::app.menu.webhooks',
        'route' => 'admin.order.webhooks.index',
        'sort'  => 4,
        'icon'  => '',
    ],
];
