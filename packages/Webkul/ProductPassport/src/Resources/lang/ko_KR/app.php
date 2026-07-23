<?php

return [
    'type' => [
        'label' => '디지털 제품 여권',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => '제품 여권',
            'info'     => '디지털 제품 여권 게시 설정입니다.',
            'settings' => [
                'title'                  => '제품 여권 설정',
                'enabled'                => '활성화됨',
                'auto-publish'           => '저장 시 자동으로 게시',
                'completeness-threshold' => '완성도 임계값(%)',
                'operator-name'          => '경제 운영자 이름',
                'operator-address'       => '경제 운영자 주소',
                'operator-eu-rep'        => 'EU 공인 대리인',
                'support-url'            => '지원 URL',
            ],
        ],
    ],
];
