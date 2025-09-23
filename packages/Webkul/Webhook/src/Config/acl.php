<?php

return [
    [
        'key'   => 'configuration.webhook',
        'name'  => 'webhook::app.acl.webhook.index',
        'route' => 'webhook.settings.index',
        'sort'  => 12,
    ],
    [
        'key'   => 'configuration.webhook.settings',
        'name'  => 'webhook::app.acl.settings.index',
        'route' => 'webhook.settings.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'configuration.webhook.settings.update',
        'name'  => 'webhook::app.acl.settings.update',
        'route' => 'webhook.settings.store',
        'sort'  => 1,
    ],
    [
        'key'   => 'configuration.webhook.logs',
        'name'  => 'webhook::app.acl.logs.index',
        'route' => 'webhook.logs.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'configuration.webhook.logs.delete',
        'name'  => 'webhook::app.acl.logs.delete',
        'route' => 'webhook.logs.delete',
        'sort'  => 1,
    ],
    [
        'key'   => 'configuration.webhook.logs.mass_delete',
        'name'  => 'webhook::app.acl.logs.mass-delete',
        'route' => 'webhook.logs.mass_delete',
        'sort'  => 2,
    ],
];
