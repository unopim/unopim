<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '게시',
            'info'     => '게시된 로케일별 콘텐츠를 위한 공개 제공 계층입니다.',
            'settings' => [
                'title'      => '게시 설정',
                'enabled'    => '활성화됨',
                'base-url'   => '기본 URL',
                'cache-ttl'  => '캐시 TTL(초)',
                'rate-limit' => '속도 제한(요청/분)',
                'indexable'  => '검색 엔진 색인 허용',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => '초안',
            'published' => '게시됨',
            'withdrawn' => '철회됨',
            'redacted'  => '편집됨(비공개 처리)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => '여권 정보를 찾을 수 없습니다.',
        ],
        '429' => [
            'heading' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.',
        ],
        'withdrawn' => [
            'heading' => '이 여권 정보는 더 이상 제공되지 않습니다.',
        ],
    ],
];
