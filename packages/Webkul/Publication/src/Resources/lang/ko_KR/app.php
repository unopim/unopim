<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => '게시',
            'info'     => '게시된 로케일별 콘텐츠를 위한 공개 제공 계층입니다.',
            'settings' => [
                'title'                  => '게시 설정',
                'enabled'                => '활성화됨',
                'base-url'               => '기본 URL',
                'cache-ttl'              => '캐시 TTL(초)',
                'rate-limit'             => '속도 제한(요청/분)',
                'indexable'              => '검색 엔진 색인 허용',
                'enabled-hint'           => '공개 제공 계층의 마스터 스위치입니다. 꺼져 있으면 모든 공개 여권 URL이 404를 반환하고 여권 메뉴가 숨겨집니다.',
                'base-url-hint'          => '여권이 제공되는 공개 주소로, QR 코드와 공유 링크를 생성하는 데 사용됩니다. 비워 두면 이 사이트의 도메인을 사용합니다.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl-hint'         => '렌더링된 공개 여권이 다시 생성되기 전까지 캐시되는 기간입니다. 값이 클수록 부하가 줄고, 값이 작을수록 수정 사항이 더 빨리 반영됩니다.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit-hint'        => '단일 방문자가 제한되기 전까지 1분당 허용되는 최대 공개 여권 요청 수입니다.',
                'rate-limit-placeholder' => '60',
                'indexable-hint'         => '검색 엔진이 공개 여권 페이지를 색인하도록 허용합니다. 끄면 링크로는 접근할 수 있지만 검색 결과에는 표시되지 않습니다.',
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
        'product-delete-blocked' => '게시된 여권이 있는 동안에는 이 제품을 삭제할 수 없습니다. 먼저 철회하세요.',
        'channel-delete-blocked' => '게시된 여권이 있는 동안에는 이 채널을 삭제할 수 없습니다. 먼저 철회하세요.',
    ],

    'public' => [
        '404' => [
            'heading' => '여권 정보를 찾을 수 없습니다.',
            'notice'  => '이 제품 패스포트를 사용할 수 없습니다. 아직 게시되지 않았거나 링크가 잘못되었을 수 있습니다.',
        ],
        '429' => [
            'heading' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.',
            'notice'  => '요청을 너무 많이 하셨습니다. 잠시 후 다시 시도해 주세요.',
        ],
        'withdrawn' => [
            'heading' => '이 여권 정보는 더 이상 제공되지 않습니다.',
            'notice'  => '이 기록은 투명성을 위해 보존되지만 더 이상 적극적으로 관리되지 않습니다.',
        ],
    ],
];
