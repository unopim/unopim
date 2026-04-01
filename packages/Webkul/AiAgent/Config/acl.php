<?php

// ACL — flat array, no nested children

return [
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.configuration.edit',
        'sort'  => 10,
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
    ],
    [
        'key'    => 'ai-agent.general',
        'name'   => 'ai-agent::app.acl.general',
        'route'  => 'admin.configuration.edit',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.prompt',
        'name'   => 'ai-agent::app.acl.prompt',
        'route'  => 'admin.magic_ai.prompt.index',
        'sort'   => 2,
    ],
    [
        'key'    => 'ai-agent.system-prompt',
        'name'   => 'ai-agent::app.acl.system-prompt',
        'route'  => 'admin.magic_ai.system_prompt.index',
        'sort'   => 3,
    ],

    [
        'key'   => 'ai-agent.generate',
        'name'  => 'ai-agent::app.acl.generate',
        'route' => 'ai-agent.generate.index',
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
];
