<?php

/*
|--------------------------------------------------------------------------
| Publication Core Config Tree
|--------------------------------------------------------------------------
|
| Merged into the `core` namespace. The settings group has no browsable page of
| its own — it is edited from the `digital_product_passport.publication` system
| settings row — so only the field group is declared, plus the `general` ancestor
| it hangs from. `Core::getConfigField()` and `SystemSettings::formGroup()` look
| the group up by a flat scan of the merged entries, not through the tree, so the
| fields stay reachable without a section entry.
|
*/

return [
    [
        'key'  => 'general',
        'name' => 'admin::app.settings.system-settings.system.title',
        'sort' => 1,
    ], [
        'key'    => 'general.publication.settings',
        'name'   => 'publication::app.configuration.publication.settings.title',
        'sort'   => 1,
        'fields' => [
            [
                'name'  => 'enabled',
                'title' => 'publication::app.configuration.publication.settings.enabled',
                'type'  => 'boolean',
                'info'  => 'publication::app.configuration.publication.settings.enabled-hint',
            ], [
                'name'        => 'base_url',
                'title'       => 'publication::app.configuration.publication.settings.base-url',
                'type'        => 'text',
                'placeholder' => 'publication::app.configuration.publication.settings.base-url-placeholder',
                'info'        => 'publication::app.configuration.publication.settings.base-url-hint',
            ], [
                'name'        => 'cache_ttl',
                'title'       => 'publication::app.configuration.publication.settings.cache-ttl',
                'type'        => 'text',
                'validation'  => 'required|integer|min:0',
                'placeholder' => 'publication::app.configuration.publication.settings.cache-ttl-placeholder',
                'info'        => 'publication::app.configuration.publication.settings.cache-ttl-hint',
            ], [
                'name'        => 'rate_limit',
                'title'       => 'publication::app.configuration.publication.settings.rate-limit',
                'type'        => 'text',
                'validation'  => 'required|integer|min:1',
                'placeholder' => 'publication::app.configuration.publication.settings.rate-limit-placeholder',
                'info'        => 'publication::app.configuration.publication.settings.rate-limit-hint',
            ], [
                'name'  => 'indexable',
                'title' => 'publication::app.configuration.publication.settings.indexable',
                'type'  => 'boolean',
                'info'  => 'publication::app.configuration.publication.settings.indexable-hint',
            ], [
                'name'        => 'gs1_passport_channel',
                'title'       => 'publication::app.configuration.publication.settings.gs1-passport-channel',
                'type'        => 'select',
                'repository'  => '\Webkul\Publication\Support\PublicationChannelOptions@toSettingsOptions',
                'placeholder' => 'publication::app.configuration.publication.settings.gs1-passport-channel-placeholder',
                'info'        => 'publication::app.configuration.publication.settings.gs1-passport-channel-hint',
            ],
        ],
    ],
];
