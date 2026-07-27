<?php

return [
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.configuration.edit',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.image',
        'sort'  => 7,
    ],
    [
        'key'    => 'ai-agent.platform',
        'name'   => 'ai-agent::app.acl.platform',
        'route'  => 'admin.magic_ai.platform.index',
        'sort'   => 1,
    ], [
        'key'    => 'ai-agent.platform.create',
        'name'   => 'ai-agent::app.acl.create',
        'route'  => 'admin.magic_ai.platform.store',
        'sort'   => 1,
    ], [
        'key'    => 'ai-agent.platform.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.platform.edit',
        'sort'   => 2,
    ], [
        'key'    => 'ai-agent.platform.delete',
        'name'   => 'ai-agent::app.acl.delete',
        'route'  => 'admin.magic_ai.platform.delete',
        'sort'   => 3,
    ], [
        'key'    => 'ai-agent.platform.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.platform.update',
        'sort'   => 2,
    ], [
        'key'    => 'ai-agent.platform.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.platform.set_default',
        'sort'   => 2,
    ],
    [
        'key'    => 'ai-agent.general',
        'name'   => 'ai-agent::app.acl.general',
        'route'  => 'admin.configuration.edit',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.general',
        'name'   => 'ai-agent::app.acl.general',
        'route'  => 'admin.magic_ai.settings.index',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.prompt',
        'name'   => 'ai-agent::app.acl.prompt',
        'route'  => 'admin.magic_ai.prompt.index',
        'sort'   => 2,
    ],
    [
        'key'    => 'ai-agent.prompt.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.prompt.edit',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.prompt.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.prompt.store',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.prompt.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.prompt.update',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.prompt.delete',
        'name'   => 'ai-agent::app.acl.delete',
        'route'  => 'admin.magic_ai.prompt.delete',
        'sort'   => 2,
    ],
    [
        'key'    => 'ai-agent.system-prompt',
        'name'   => 'ai-agent::app.acl.system-prompt',
        'route'  => 'admin.magic_ai.system_prompt.index',
        'sort'   => 3,
    ],
    [
        'key'    => 'ai-agent.system-prompt.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.system_prompt.edit',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.system-prompt.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.system_prompt.store',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.system-prompt.edit',
        'name'   => 'ai-agent::app.acl.edit',
        'route'  => 'admin.magic_ai.system_prompt.update',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.system-prompt.delete',
        'name'   => 'ai-agent::app.acl.delete',
        'route'  => 'admin.magic_ai.system_prompt.delete',
        'sort'   => 2,
    ],

    [
        'key'   => 'ai-agent.generate',
        'name'  => 'ai-agent::app.acl.generate',
        'route' => 'ai-agent.generate.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'ai-agent.generate',
        'name'  => 'ai-agent::app.acl.generate',
        'route' => 'ai-agent.generate.process',
        'sort'  => 5,
    ],
    [
        'key'   => 'ai-agent.execute',
        'name'  => 'ai-agent::app.acl.execute',
        'route' => 'ai-agent.execute',
        'sort'  => 6,
    ],
    [
        'key'   => 'ai-agent.credentials',
        'name'  => 'ai-agent::app.acl.credentials',
        'route' => 'ai-agent.credentials.index',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.index',
        'sort'  => 8,
    ],
    [
        'key'   => 'ai-agent.dashboard',
        'name'  => 'ai-agent::app.acl.dashboard',
        'route' => 'ai-agent.dashboard.analytics',
        'sort'  => 9,
    ],
    [
        'key'   => 'ai-agent.approvals',
        'name'  => 'ai-agent::app.acl.approvals',
        'route' => 'ai-agent.approvals.index',
        'sort'  => 10,
    ],

    /**
     * MagicAI content/translation/model helper endpoints. Their controllers
     * already enforce the top-level `ai-agent` permission, so they map to that
     * same key here — a sub-key would over-restrict callers holding `ai-agent`.
     */
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.content',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.translate',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.translate.all.attribute',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.store.translated',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.store.translated.all_attribute',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.suggestion_values',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.check.is_translatable',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.check.is_all_attribute_translatable',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.available_model',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.model',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.default_prompt',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.platforms',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.platform.fetch_models',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.platform.test',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.magic_ai.validate_credential',
        'sort'  => 7,
    ],

    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.get',
        'sort'  => 8,
    ],
    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.create',
        'sort'  => 8,
    ],
    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.store',
        'sort'  => 8,
    ],
    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.edit',
        'sort'  => 8,
    ],
    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.update',
        'sort'  => 8,
    ],
    [
        'key'   => 'ai-agent.agents',
        'name'  => 'ai-agent::app.acl.agents',
        'route' => 'ai-agent.agents.destroy',
        'sort'  => 8,
    ],
    /**
     * Conversation + chat endpoints map to the keys their controllers enforce
     * (ConversationController → `ai-agent.general`, ChatController → the
     * top-level `ai-agent`), so the middleware gate matches the in-controller
     * check rather than adding a second, stricter requirement.
     */
    [
        'key'   => 'ai-agent.general',
        'name'  => 'ai-agent::app.acl.general',
        'route' => 'ai-agent.conversations.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'ai-agent.general',
        'name'  => 'ai-agent::app.acl.general',
        'route' => 'ai-agent.conversations.show',
        'sort'  => 1,
    ],
    [
        'key'   => 'ai-agent.general',
        'name'  => 'ai-agent::app.acl.general',
        'route' => 'ai-agent.conversations.store',
        'sort'  => 1,
    ],
    [
        'key'   => 'ai-agent.general',
        'name'  => 'ai-agent::app.acl.general',
        'route' => 'ai-agent.conversations.destroy',
        'sort'  => 1,
    ],

    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'ai-agent.chat.send',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'ai-agent.chat.stream',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'ai-agent.chat.rate',
        'sort'  => 7,
    ],
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'ai-agent.chat.magic-ai-config',
        'sort'  => 7,
    ],

    [
        'key'   => 'ai-agent.dashboard',
        'name'  => 'ai-agent::app.acl.dashboard',
        'route' => 'ai-agent.dashboard.audit-trail',
        'sort'  => 9,
    ],
    [
        'key'   => 'ai-agent.dashboard',
        'name'  => 'ai-agent::app.acl.dashboard',
        'route' => 'ai-agent.dashboard.notifications',
        'sort'  => 9,
    ],
    [
        'key'   => 'ai-agent.dashboard',
        'name'  => 'ai-agent::app.acl.dashboard',
        'route' => 'ai-agent.dashboard.notifications.dismiss',
        'sort'  => 9,
    ],
    [
        'key'   => 'ai-agent.dashboard',
        'name'  => 'ai-agent::app.acl.dashboard',
        'route' => 'ai-agent.dashboard.rollback',
        'sort'  => 9,
    ],

    [
        'key'   => 'ai-agent.general',
        'name'  => 'ai-agent::app.acl.general',
        'route' => 'ai-agent.settings',
        'sort'  => 1,
    ],
];
