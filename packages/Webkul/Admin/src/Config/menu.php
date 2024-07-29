<?php

return [
    /**
     * Dashboard.
     */
    [
        'key'        => 'dashboard',
        'name'       => 'admin::app.components.layouts.sidebar.dashboard',
        'route'      => 'admin.dashboard.index',
        'sort'       => 1,
        'icon'       => 'icon-dashboard',
    ],

    /**
     * Catalog.
     */
    [
        'key'        => 'catalog',
        'name'       => 'admin::app.components.layouts.sidebar.catalog',
        'route'      => 'admin.catalog.products.index',
        'sort'       => 3,
        'icon'       => 'icon-catalog',
    ], [
        'key'        => 'catalog.products',
        'name'       => 'admin::app.components.layouts.sidebar.products',
        'route'      => 'admin.catalog.products.index',
        'sort'       => 1,
        'icon'       => '',
    ], [
        'key'        => 'catalog.categories',
        'name'       => 'admin::app.components.layouts.sidebar.categories',
        'route'      => 'admin.catalog.categories.index',
        'sort'       => 2,
        'icon'       => '',
    ], [
        'key'        => 'catalog.category_fields',
        'name'       => 'admin::app.components.layouts.sidebar.category_fields',
        'route'      => 'admin.catalog.category_fields.index',
        'sort'       => 3,
        'icon'       => '',
    ], [
        'key'        => 'catalog.attributes',
        'name'       => 'admin::app.components.layouts.sidebar.attributes',
        'route'      => 'admin.catalog.attributes.index',
        'sort'       => 4,
        'icon'       => '',
    ], [
        'key'        => 'catalog.attribute_groups',
        'name'       => 'admin::app.components.layouts.sidebar.attribute-groups',
        'route'      => 'admin.catalog.attribute.groups.index',
        'sort'       => 5,
        'icon'       => '',
    ], [
        'key'        => 'catalog.families',
        'name'       => 'admin::app.components.layouts.sidebar.attribute-families',
        'route'      => 'admin.catalog.families.index',
        'sort'       => 6,
        'icon'       => '',
    ],

    /**
     * Data Transfer.
     */
    [
        'key'        => 'data_transfer',
        'name'       => 'admin::app.components.layouts.sidebar.data-transfer',
        'route'      => 'admin.settings.data_transfer.tracker.index',
        'sort'       => 8,
        'icon'       => 'icon-data-transfer',
    ],  [
        'key'        => 'data_transfer.tracker',
        'name'       => 'admin::app.components.layouts.sidebar.tracker',
        'route'      => 'admin.settings.data_transfer.tracker.index',
        'sort'       => 1,
        'icon'       => '',
    ], [
        'key'        => 'data_transfer.imports',
        'name'       => 'admin::app.components.layouts.sidebar.imports',
        'route'      => 'admin.settings.data_transfer.imports.index',
        'sort'       => 2,
        'icon'       => '',
    ], [
        'key'        => 'data_transfer.export',
        'name'       => 'admin::app.components.layouts.sidebar.exports',
        'route'      => 'admin.settings.data_transfer.exports.index',
        'sort'       => 3,
        'icon'       => '',
    ],

    /**
     * Settings.
     */
    [
        'key'        => 'settings',
        'name'       => 'admin::app.components.layouts.sidebar.settings',
        'route'      => 'admin.settings.locales.index',
        'sort'       => 8,
        'icon'       => 'icon-setting',
    ], [
        'key'        => 'settings.locales',
        'name'       => 'admin::app.components.layouts.sidebar.locales',
        'route'      => 'admin.settings.locales.index',
        'sort'       => 1,
        'icon'       => '',
    ], [
        'key'        => 'settings.currencies',
        'name'       => 'admin::app.components.layouts.sidebar.currencies',
        'route'      => 'admin.settings.currencies.index',
        'sort'       => 2,
        'icon'       => '',
    ], [
        'key'        => 'settings.channels',
        'name'       => 'admin::app.components.layouts.sidebar.channels',
        'route'      => 'admin.settings.channels.index',
        'sort'       => 5,
        'icon'       => '',
    ], [
        'key'        => 'settings.users',
        'name'       => 'admin::app.components.layouts.sidebar.users',
        'route'      => 'admin.settings.users.index',
        'sort'       => 6,
        'icon'       => '',
    ], [
        'key'        => 'settings.roles',
        'name'       => 'admin::app.components.layouts.sidebar.roles',
        'route'      => 'admin.settings.roles.index',
        'sort'       => 7,
        'icon'       => '',
    ],
    /**
     * Configuration.
     */
    [
        'key'    => 'configuration',
        'name'   => 'admin::app.components.layouts.sidebar.configure',
        'route'  => 'admin.configuration.edit',
        'params' => ['general', 'magic_ai'],
        'sort'   => 9,
        'icon'   => 'icon-configuration',
    ], [
        'key'    => 'configuration.magic-ai',
        'name'   => 'admin::app.components.layouts.sidebar.magic-ai',
        'route'  => 'admin.configuration.edit',
        'params' => ['general', 'magic_ai'],
        'sort'   => 3,
        'icon'   => '',
    ],
];
