<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '发布',
            'info'     => '面向已发布、按语言区分内容的公开分发层。',
            'settings' => [
                'title'                            => '发布设置',
                'enabled'                          => '已启用',
                'base-url'                         => '基础 URL',
                'cache-ttl'                        => '缓存 TTL(秒)',
                'rate-limit'                       => '速率限制(请求数/分钟)',
                'indexable'                        => '允许搜索引擎收录',
                'enabled-hint'                     => '公开服务层的总开关。关闭时，每个公开护照 URL 都会返回 404，护照菜单也会被隐藏。',
                'base-url-hint'                    => '提供护照的公开地址，用于生成 QR 码和可分享链接。留空则使用本站自身的域名。',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => '已渲染的公开护照在重新生成之前的缓存时长。数值越高负载越低；数值越低越能更快反映编辑。',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => '单个访客每分钟允许的公开护照请求上限，超出后将被限流。',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => '允许搜索引擎索引公开护照页面。关闭后护照仍可通过链接访问，但会从搜索结果中隐藏。',
                'gs1-passport-channel'             => 'GS1 Digital Link 护照渠道',
                'gs1-passport-channel-hint'        => '当同一产品在多个渠道发布时，扫描的 GS1 条形码（/01/{gtin}）解析到的渠道。留空则使用第一个已启用的渠道。',
                'gs1-passport-channel-placeholder' => '第一个已启用的渠道（自动）',
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
        'product-delete-blocked' => '该产品存在已发布的护照时无法删除,请先撤回。',
        'channel-delete-blocked' => '该渠道存在已发布的护照时无法删除,请先撤回。',
    ],

    'public' => [
        '404' => [
            'heading' => '未找到护照信息。',
            'notice'  => '此产品护照不可用。它可能尚未发布，或者链接不正确。',
        ],
        '429' => [
            'heading' => '请求过多,请稍后再试。',
            'notice'  => '您的请求过于频繁。请稍候片刻再试。',
        ],
        'withdrawn' => [
            'heading' => '此护照信息已不再可用。',
            'notice'  => '此记录出于透明度目的予以保留,但不再进行主动维护。',
        ],
    ],
];
