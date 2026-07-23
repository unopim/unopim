<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '發布',
            'info'     => '針對已發布、依語言區分內容的公開發佈層。',
            'settings' => [
                'title'      => '發布設定',
                'enabled'    => '已啟用',
                'base-url'   => '基礎 URL',
                'cache-ttl'  => '快取 TTL(秒)',
                'rate-limit' => '速率限制(請求數/分鐘)',
                'indexable'  => '允許搜尋引擎索引',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => '草稿',
            'published' => '已發布',
            'withdrawn' => '已撤回',
            'redacted'  => '已遮蔽(編輯隱藏)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => '找不到護照資訊。',
        ],
        '429' => [
            'heading' => '請求過多,請稍後再試。',
        ],
        'withdrawn' => [
            'heading' => '此護照資訊已不再提供。',
        ],
    ],
];
