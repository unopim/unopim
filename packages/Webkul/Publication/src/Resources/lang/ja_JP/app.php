<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '公開',
            'info'     => '公開済みのロケール別コンテンツのための公開配信層。',
            'settings' => [
                'title'      => '公開設定',
                'enabled'    => '有効',
                'base-url'   => 'ベースURL',
                'cache-ttl'  => 'キャッシュTTL(秒)',
                'rate-limit' => 'レート制限(リクエスト/分)',
                'indexable'  => '検索エンジンのインデックス登録を許可',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => '下書き',
            'published' => '公開済み',
            'withdrawn' => '撤回済み',
            'redacted'  => '編集済み(非開示)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'パスポートが見つかりません。',
        ],
        '429' => [
            'heading' => 'リクエストが多すぎます。しばらくしてから再度お試しください。',
        ],
        'withdrawn' => [
            'heading' => 'このパスポートは現在利用できません。',
        ],
    ],
];
