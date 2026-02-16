<?php

return [
    [
        'key'   => 'channel_connector',
        'name'  => 'channel_connector::app.acl.channel-connectors',
        'route' => 'admin.api.channel_connector.connectors.index',
        'sort'  => 11,
    ], [
        'key'   => 'channel_connector.connectors',
        'name'  => 'channel_connector::app.acl.connectors',
        'route' => 'admin.api.channel_connector.connectors.index',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.connectors.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.connectors.index',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.connectors.create',
        'name'  => 'channel_connector::app.acl.create',
        'route' => 'admin.api.channel_connector.connectors.store',
        'sort'  => 2,
    ], [
        'key'   => 'channel_connector.connectors.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.connectors.show',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.connectors.edit',
        'name'  => 'channel_connector::app.acl.edit',
        'route' => 'admin.api.channel_connector.connectors.update',
        'sort'  => 3,
    ], [
        'key'   => 'channel_connector.connectors.edit',
        'name'  => 'channel_connector::app.acl.edit',
        'route' => 'admin.api.channel_connector.connectors.test',
        'sort'  => 3,
    ], [
        'key'   => 'channel_connector.connectors.delete',
        'name'  => 'channel_connector::app.acl.delete',
        'route' => 'admin.api.channel_connector.connectors.destroy',
        'sort'  => 4,
    ], [
        'key'   => 'channel_connector.mappings',
        'name'  => 'channel_connector::app.acl.mappings',
        'route' => 'admin.api.channel_connector.mappings.index',
        'sort'  => 2,
    ], [
        'key'   => 'channel_connector.mappings.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.mappings.index',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.mappings.edit',
        'name'  => 'channel_connector::app.acl.edit',
        'route' => 'admin.api.channel_connector.mappings.store',
        'sort'  => 2,
    ], [
        'key'   => 'channel_connector.sync',
        'name'  => 'channel_connector::app.acl.sync',
        'route' => 'admin.api.channel_connector.sync.index',
        'sort'  => 3,
    ], [
        'key'   => 'channel_connector.sync.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.sync.index',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.sync.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.sync.show',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.sync.create',
        'name'  => 'channel_connector::app.acl.create',
        'route' => 'admin.api.channel_connector.sync.trigger',
        'sort'  => 2,
    ], [
        'key'   => 'channel_connector.sync.create',
        'name'  => 'channel_connector::app.acl.create',
        'route' => 'admin.api.channel_connector.sync.retry',
        'sort'  => 2,
    ], [
        'key'   => 'channel_connector.sync.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.dashboard.index',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.sync.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.dashboard.show',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.sync.create',
        'name'  => 'channel_connector::app.acl.create',
        'route' => 'admin.api.channel_connector.dashboard.retry',
        'sort'  => 2,
    ], [
        'key'   => 'channel_connector.conflicts',
        'name'  => 'channel_connector::app.acl.conflicts',
        'route' => 'admin.api.channel_connector.conflicts.index',
        'sort'  => 4,
    ], [
        'key'   => 'channel_connector.conflicts.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.conflicts.index',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.conflicts.view',
        'name'  => 'channel_connector::app.acl.view',
        'route' => 'admin.api.channel_connector.conflicts.show',
        'sort'  => 1,
    ], [
        'key'   => 'channel_connector.conflicts.edit',
        'name'  => 'channel_connector::app.acl.edit',
        'route' => 'admin.api.channel_connector.conflicts.resolve',
        'sort'  => 2,
    ],
];
