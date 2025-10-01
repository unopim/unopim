<?php

return [
    [
        'key'   => 'configuration.webhook',
        'name'  => 'webhook::app.components.layouts.sidebar.menu.webhook.name',
        'route' => 'webhook.settings.index',
        'sort'  => 3,
        'icon'  => 'icon-webhook',
    ], [
        'key'    => 'configuration.webhook.settings',
        'name'   => 'webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.name',
        'route'  => 'webhook.settings.index',
        'sort'   => 1,
    ], [
        'key'    => 'configuration.webhook.logs',
        'name'   => 'webhook::app.components.layouts.sidebar.menu.webhook.submenu.logs.name',
        'route'  => 'webhook.logs.index',
        'sort'   => 2,
    ], [
        'key'    => 'configuration.webhook.history',
        'name'   => 'webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.history.name',
        'route'  => 'webhook.settings.history.get',
        'sort'   => 3,
    ],
];
