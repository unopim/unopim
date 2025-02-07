<?php

use Webkul\MagicAI\MagicAI;

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
                'name'          => 'ai_platform',
                'title'         => 'AI Platforms',
                'type'          => 'select',
                'options'       => [
                    [
                        'title' => 'Openai',
                        'value' => MagicAI::MAGIC_OPEN_AI,
                    ], [
                        'title' => 'Groq',
                        'value' => MagicAI::MAGIC_GROQ_AI,
                    ], [
                        'title' => 'Ollama',
                        'value' => MagicAI::MAGIC_OLLAMA_AI,
                    ],
                ],
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
            ], [
                'name'          => 'api_model',
                'title'         => 'admin::app.configuration.index.general.magic-ai.settings.api-model',
                'type'          => 'blade',
                'path'          => 'admin::configuration.magic-ai.field.model',
            ],
        ],
    ], [
        'key'    => 'general.magic_ai.image_generation',
        'name'   => 'admin::app.configuration.index.general.magic-ai.image-generation.title',
        'info'   => 'admin::app.configuration.index.general.magic-ai.image-generation.title-info',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'admin::app.configuration.index.general.magic-ai.image-generation.enabled',
                'type'          => 'boolean',
            ],
        ],
    ],
];
