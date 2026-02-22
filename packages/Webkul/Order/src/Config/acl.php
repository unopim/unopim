<?php

return [
    /**
     * Order Management
     */
    [
        'key'   => 'order',
        'name'  => 'order::app.acl.order',
        'route' => 'admin.order.orders.index',
        'sort'  => 13,
    ],

    /**
     * Orders
     */
    [
        'key'   => 'order.orders',
        'name'  => 'order::app.acl.orders',
        'route' => 'admin.order.orders.index',
        'sort'  => 1,
    ], [
        'key'   => 'order.orders.view',
        'name'  => 'order::app.acl.view',
        'route' => 'admin.order.orders.view',
        'sort'  => 1,
    ], [
        'key'   => 'order.orders.create',
        'name'  => 'order::app.acl.create',
        'route' => 'admin.order.orders.create',
        'sort'  => 2,
    ], [
        'key'   => 'order.orders.edit',
        'name'  => 'order::app.acl.edit',
        'route' => 'admin.order.orders.edit',
        'sort'  => 3,
    ], [
        'key'   => 'order.orders.delete',
        'name'  => 'order::app.acl.delete',
        'route' => 'admin.order.orders.delete',
        'sort'  => 4,
    ],

    /**
     * Order Sync
     */
    [
        'key'   => 'order.sync',
        'name'  => 'order::app.acl.sync',
        'route' => 'admin.order.sync.index',
        'sort'  => 2,
    ], [
        'key'   => 'order.sync.view',
        'name'  => 'order::app.acl.view',
        'route' => 'admin.order.sync.view',
        'sort'  => 1,
    ], [
        'key'   => 'order.sync.trigger',
        'name'  => 'order::app.acl.trigger',
        'route' => 'admin.order.sync.trigger',
        'sort'  => 2,
    ], [
        'key'   => 'order.sync.retry',
        'name'  => 'order::app.acl.retry',
        'route' => 'admin.order.sync.retry',
        'sort'  => 3,
    ],

    /**
     * Profitability
     */
    [
        'key'   => 'order.profitability',
        'name'  => 'order::app.acl.profitability',
        'route' => 'admin.order.profitability.index',
        'sort'  => 3,
    ], [
        'key'   => 'order.profitability.view',
        'name'  => 'order::app.acl.view',
        'route' => 'admin.order.profitability.view',
        'sort'  => 1,
    ], [
        'key'   => 'order.profitability.analyze',
        'name'  => 'order::app.acl.analyze',
        'route' => 'admin.order.profitability.analyze',
        'sort'  => 2,
    ],

    /**
     * Webhooks
     */
    [
        'key'   => 'order.webhooks',
        'name'  => 'order::app.acl.webhooks',
        'route' => 'admin.order.webhooks.index',
        'sort'  => 4,
    ], [
        'key'   => 'order.webhooks.view',
        'name'  => 'order::app.acl.view',
        'route' => 'admin.order.webhooks.view',
        'sort'  => 1,
    ], [
        'key'   => 'order.webhooks.create',
        'name'  => 'order::app.acl.create',
        'route' => 'admin.order.webhooks.create',
        'sort'  => 2,
    ], [
        'key'   => 'order.webhooks.edit',
        'name'  => 'order::app.acl.edit',
        'route' => 'admin.order.webhooks.edit',
        'sort'  => 3,
    ], [
        'key'   => 'order.webhooks.delete',
        'name'  => 'order::app.acl.delete',
        'route' => 'admin.order.webhooks.delete',
        'sort'  => 4,
    ],
];
