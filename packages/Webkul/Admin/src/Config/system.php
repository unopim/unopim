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
        'key'       => 'general.magic_ai',
        'name'      => 'admin::app.configuration.index.general.magic-ai.title',
        'info'      => 'admin::app.configuration.index.general.magic-ai.info',
        'icon'      => 'settings/magic-ai.svg',
        'sort'      => 3,
    ], [
        'key'    => 'general.magic_ai.settings',
        'name'   => 'admin::app.configuration.index.general.magic-ai.settings.title',
        'info'   => 'admin::app.configuration.index.general.magic-ai.settings.title-info',
        'sort'   => 2,
        'fields' => [
            [
                'name'  => 'enabled',
                'title' => 'admin::app.configuration.index.general.magic-ai.settings.enabled',
                'type'  => 'boolean',
            ], [
                'name'  => 'ai_platform',
                'title' => 'admin::app.configuration.index.general.magic-ai.settings.ai-platform',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.text-generation-platform',
            ], [
                'name'  => 'ai_model',
                'title' => 'admin::app.configuration.index.general.magic-ai.settings.ai-model',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.text-generation-model',
            ],
        ],
    ], [
        'key'    => 'general.magic_ai.image_generation',
        'name'   => 'admin::app.configuration.index.general.magic-ai.image-generation.title',
        'info'   => 'admin::app.configuration.index.general.magic-ai.image-generation.title-info',
        'sort'   => 3,
        'fields' => [
            [
                'name'  => 'enabled',
                'title' => 'admin::app.configuration.index.general.magic-ai.image-generation.enabled',
                'type'  => 'boolean',
            ], [
                'name'  => 'ai_platform',
                'title' => 'admin::app.configuration.index.general.magic-ai.image-generation.ai-platform',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.image-generation-platform',
            ], [
                'name'  => 'ai_model',
                'title' => 'admin::app.configuration.index.general.magic-ai.image-generation.ai-model',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.image-generation-model',
            ],
        ],
    ],
    [
        'key'    => 'general.magic_ai.translation',
        'name'   => 'admin::app.configuration.index.general.magic-ai.translation.title',
        'info'   => 'admin::app.configuration.index.general.magic-ai.translation.title-info',
        'sort'   => 4,
        'fields' => [
            [
                'name'  => 'enabled',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.enabled',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.translation-boolean',
            ], [
                'name'  => 'ai_platform',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.ai-platform',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.translation-platform',
            ], [
                'name'  => 'ai_model',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.translation-model',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.translation-model',
            ], [
                'name'  => 'replace',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.replace-existing-value',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.replace-toggle',
            ], [
                'name'  => 'source_channel',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.global-source-channel',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.channel',
            ], [
                'name'  => 'target_channel',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.target-channel',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.target-channel',
            ], [
                'name'  => 'source_locale',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.global-source-locale',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.locale',
            ], [
                'name'  => 'target_locale',
                'title' => 'admin::app.configuration.index.general.magic-ai.translation.target-locales',
                'type'  => 'blade',
                'path'  => 'admin::configuration.magic-ai.field.target-locale',
            ],
        ],
    ],

    /**
     * Agentic PIM — AI Agent Chat and autonomous workflows.
     */
    [
        'key'    => 'general.magic_ai.agentic_pim',
        'name'   => 'admin::app.configuration.index.general.magic-ai.agentic-pim.title',
        'info'   => 'admin::app.configuration.index.general.magic-ai.agentic-pim.title-info',
        'sort'   => 1,
        'fields' => [
            [
                'name'  => 'enabled',
                'title' => 'admin::app.configuration.index.general.magic-ai.agentic-pim.enabled',
                'type'  => 'boolean',
                'info'  => 'admin::app.configuration.index.general.magic-ai.agentic-pim.enabled-info',
            ], [
                'name'          => 'max_steps',
                'title'         => 'admin::app.configuration.index.general.magic-ai.agentic-pim.max-steps',
                'type'          => 'select',
                'info'          => 'admin::app.configuration.index.general.magic-ai.agentic-pim.max-steps-info',
                'default_value' => '5',
                'options'       => [
                    ['title' => '3 (Fast)', 'value' => '3'],
                    ['title' => '5 (Default)', 'value' => '5'],
                    ['title' => '10 (Thorough)', 'value' => '10'],
                    ['title' => '15 (Maximum)', 'value' => '15'],
                ],
            ], [
                'name'          => 'daily_token_budget',
                'title'         => 'admin::app.configuration.index.general.magic-ai.agentic-pim.daily-token-budget',
                'type'          => 'number',
                'info'          => 'admin::app.configuration.index.general.magic-ai.agentic-pim.daily-token-budget-info',
                'default_value' => '0',
                'placeholder'   => '0 = unlimited, e.g. 500000',
            ], [
                'name'  => 'auto_enrichment',
                'title' => 'admin::app.configuration.index.general.magic-ai.agentic-pim.auto-enrichment',
                'type'  => 'boolean',
                'info'  => 'admin::app.configuration.index.general.magic-ai.agentic-pim.auto-enrichment-info',
            ], [
                'name'  => 'quality_monitor',
                'title' => 'admin::app.configuration.index.general.magic-ai.agentic-pim.quality-monitor',
                'type'  => 'boolean',
                'info'  => 'admin::app.configuration.index.general.magic-ai.agentic-pim.quality-monitor-info',
            ], [
                'name'          => 'confidence_threshold',
                'title'         => 'admin::app.configuration.index.general.magic-ai.agentic-pim.confidence-threshold',
                'type'          => 'select',
                'info'          => 'admin::app.configuration.index.general.magic-ai.agentic-pim.confidence-threshold-info',
                'default_value' => '0.7',
                'options'       => [
                    ['title' => '0.5 (Lenient — auto-apply most suggestions)', 'value' => '0.5'],
                    ['title' => '0.7 (Balanced — default)', 'value' => '0.7'],
                    ['title' => '0.8 (Strict — only high-confidence changes)', 'value' => '0.8'],
                    ['title' => '0.9 (Very strict — almost everything needs review)', 'value' => '0.9'],
                ],
            ], [
                'name'          => 'approval_mode',
                'title'         => 'admin::app.configuration.index.general.magic-ai.agentic-pim.approval-mode',
                'type'          => 'select',
                'info'          => 'admin::app.configuration.index.general.magic-ai.agentic-pim.approval-mode-info',
                'default_value' => 'auto',
                'options'       => [
                    ['title' => 'Confirm & apply (propose values, ask to confirm, then execute)', 'value' => 'auto'],
                    ['title' => 'Strict confirm (always confirm + verify after every change)', 'value' => 'review'],
                    ['title' => 'Suggest only (describe changes but never execute)', 'value' => 'suggest'],
                ],
            ],
        ],
    ],
];
