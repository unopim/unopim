<?php

/*
|--------------------------------------------------------------------------
| Publication Core Config Tree
|--------------------------------------------------------------------------
|
| Merged into the `core` namespace. Every ancestor of the package's leaf key
| is declared here, `general` included, even though `Webkul\Admin` already
| registers its own `general` entry: `Core\Tree`/`Core::getConfigField()`
| reconstruct the settings tree by scanning every merged entry regardless of
| source, and a leaf registered without its ancestors produces an
| `Undefined array key "key"` warning and a tree keyed by `''`.
|
*/

return [
    [
        'key'  => 'general',
        'name' => 'admin::app.settings.system-settings.system.title',
        'sort' => 1,
    ], [
        'key'  => 'general.publication',
        'name' => 'publication::app.configuration.publication.title',
        'info' => 'publication::app.configuration.publication.info',
        'sort' => 8,
    ], [
        'key'    => 'general.publication.settings',
        'name'   => 'publication::app.configuration.publication.settings.title',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'publication::app.configuration.publication.settings.enabled',
                'type'          => 'boolean',
                'channel_based' => true,
            ], [
                'name'       => 'base_url',
                'title'      => 'publication::app.configuration.publication.settings.base-url',
                'type'       => 'text',
                'validation' => 'nullable|url',
            ], [
                'name'       => 'cache_ttl',
                'title'      => 'publication::app.configuration.publication.settings.cache-ttl',
                'type'       => 'text',
                'validation' => 'required|integer|min:0',
            ], [
                'name'       => 'rate_limit',
                'title'      => 'publication::app.configuration.publication.settings.rate-limit',
                'type'       => 'text',
                'validation' => 'required|integer|min:1',
            ], [
                'name'          => 'indexable',
                'title'         => 'publication::app.configuration.publication.settings.indexable',
                'type'          => 'boolean',
                'channel_based' => true,
            ],
        ],
    ],
];
