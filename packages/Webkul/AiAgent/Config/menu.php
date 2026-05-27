<?php

return [
    [
        'key'    => 'ai-agent',
        'name'   => 'ai-agent::app.menu.ai-agent',
        'route'  => 'admin.magic_ai.platform.index',
        'sort'   => 7,
        'icon'   => 'icon-magic-ai',
    ],
    [
        'key'    => 'ai-agent.platform',
        'name'   => 'ai-agent::app.menu.platform',
        'route'  => 'admin.magic_ai.platform.index',
        'sort'   => 1,
        'icon'   => '',
    ],
    [
        'key'    => 'ai-agent.general',
        'name'   => 'ai-agent::app.menu.settings',
        'route'  => 'admin.configuration.edit',
        'params' => ['general', 'magic_ai'],
        'sort'   => 2,
        'icon'   => '',
    ],
    [
        'key'    => 'ai-agent.prompt',
        'name'   => 'ai-agent::app.menu.prompt',
        'route'  => 'admin.magic_ai.prompt.index',
        'sort'   => 3,
        'icon'   => '',
    ],
    [
        'key'    => 'ai-agent.system-prompt',
        'name'   => 'ai-agent::app.menu.system-prompt',
        'route'  => 'admin.magic_ai.system_prompt.index',
        'sort'   => 4,
        'icon'   => '',
    ],
];
