<?php

return [
    [
        'key'   => 'configuration.webhook',
        'name'  => 'webhook::app.components.layouts.sidebar.menu.webhook.name',
        'route' => 'webhook.index',
        'sort'  => 3,
    ], [
        'key'   => 'configuration.webhook.create',
        'name'  => 'webhook::app.acl.webhook.create',
        'route' => 'webhook.store',
        'sort'  => 1,
    ], [
        'key'   => 'configuration.webhook.edit',
        'name'  => 'webhook::app.acl.webhook.edit',
        'route' => 'webhook.update',
        'sort'  => 2,
    ], [
        'key'   => 'configuration.webhook.delete',
        'name'  => 'webhook::app.acl.webhook.delete',
        'route' => 'webhook.delete',
        'sort'  => 3,
    ], [
        'key'   => 'configuration.webhook.logs',
        'name'  => 'webhook::app.acl.logs.index',
        'route' => 'webhook.logs.index',
        'sort'  => 4,
    ], [
        'key'   => 'configuration.webhook.logs.view',
        'name'  => 'webhook::app.acl.logs.view',
        'route' => 'webhook.logs.show',
        'sort'  => 1,
    ], [
        'key'   => 'configuration.webhook.logs.delete',
        'name'  => 'webhook::app.acl.logs.delete',
        'route' => 'webhook.logs.delete',
        'sort'  => 2,
    ], [
        'key'   => 'configuration.webhook.logs.mass_delete',
        'name'  => 'webhook::app.acl.logs.mass-delete',
        'route' => 'webhook.logs.mass_delete',
        'sort'  => 3,
    ], [
        'key'   => 'configuration.webhook.edit',
        'name'  => 'webhook::app.acl.webhook.edit',
        'route' => 'webhook.edit',
        'sort'  => 2,
    ], [
        'key'   => 'configuration.webhook.edit',
        'name'  => 'webhook::app.acl.webhook.edit',
        'route' => 'webhook.test',
        'sort'  => 2,
    ], [
        'key'   => 'configuration.webhook.delete',
        'name'  => 'webhook::app.acl.webhook.delete',
        'route' => 'webhook.mass_delete',
        'sort'  => 3,
    ], [
        'key'   => 'configuration.webhook.logs',
        'name'  => 'webhook::app.acl.logs.index',
        'route' => 'webhook.logs.for-webhook',
        'sort'  => 4,
    ],
];
