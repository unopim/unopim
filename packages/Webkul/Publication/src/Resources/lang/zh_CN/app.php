<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '发布',
            'info'     => '面向已发布、按语言区分内容的公开分发层。',
            'settings' => [
                'title'      => '发布设置',
                'enabled'    => '已启用',
                'base-url'   => '基础 URL',
                'cache-ttl'  => '缓存 TTL(秒)',
                'rate-limit' => '速率限制(请求数/分钟)',
                'indexable'  => '允许搜索引擎收录',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => '草稿',
            'published' => '已发布',
            'withdrawn' => '已撤回',
            'redacted'  => '已隐去(涂黑)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => '未找到护照信息。',
        ],
        '429' => [
            'heading' => '请求过多,请稍后再试。',
        ],
        'withdrawn' => [
            'heading' => '此护照信息已不再可用。',
        ],
    ],
];
