<?php

return [
    [
        'key'   => 'channel_connector',
        'name'  => 'channel_connector::app.components.layouts.sidebar.channel-connectors',
        'route' => 'admin.channel_connector.connectors.index',
        'sort'  => 9,
        'icon'  => 'icon-settings',
    ], [
        'key'   => 'channel_connector.connectors',
        'name'  => 'channel_connector::app.components.layouts.sidebar.connectors',
        'route' => 'admin.channel_connector.connectors.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'channel_connector.sync',
        'name'  => 'channel_connector::app.components.layouts.sidebar.sync-monitor',
        'route' => 'admin.channel_connector.dashboard.index',
        'sort'  => 2,
        'icon'  => '',
    ], [
        'key'   => 'channel_connector.conflicts',
        'name'  => 'channel_connector::app.components.layouts.sidebar.conflicts',
        'route' => 'admin.channel_connector.conflicts.index',
        'sort'  => 3,
        'icon'  => '',
    ],
];
