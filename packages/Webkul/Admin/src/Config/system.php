<?php

return [
    /**
     * General.
     */
    [
        'key'  => 'general',
        'name' => 'admin::app.configuration.index.general.title',
        'info' => 'admin::app.configuration.index.general.info',
        'sort' => 1,
    ], [
        'key'  => 'general.magic_ai',
        'name' => 'admin::app.configuration.index.general.magic-ai.title',
        'info' => 'admin::app.configuration.index.general.magic-ai.info',
        'icon' => 'settings/magic-ai.svg',
        'sort' => 3,
    ], [
        'key'    => 'general.magic_ai.settings',
        'name'   => 'admin::app.configuration.index.general.magic-ai.settings.title',
        'info'   => 'admin::app.configuration.index.general.magic-ai.settings.title-info',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'admin::app.configuration.index.general.magic-ai.settings.enabled',
                'type'          => 'boolean',
            ], [
                'name'          => 'api_key',
                'title'         => 'admin::app.configuration.index.general.magic-ai.settings.api-key',
                'type'          => 'password',
            ], [
                'name'          => 'organization',
                'title'         => 'admin::app.configuration.index.general.magic-ai.settings.organization',
                'type'          => 'text',
            ], [
                'name'          => 'api_domain',
                'title'         => 'admin::app.configuration.index.general.magic-ai.settings.llm-api-domain',
                'type'          => 'text',
            ],
        ],
    ],
];
