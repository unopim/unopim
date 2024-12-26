<?php

return [
    'importers' => [
        'products' => [
            'title'      => '제품',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL 키: \'%s\'는 SKU: \'%s\'를 가진 항목에 이미 생성되었습니다.',
                    'invalid-attribute-family'                 => '속성 가족 열에 유효하지 않은 값(속성 가족이 존재하지 않음?)',
                    'invalid-type'                             => '제품 유형이 잘못되었거나 지원되지 않음',
                    'sku-not-found'                            => '지정된 SKU를 가진 제품을 찾을 수 없습니다',
                    'super-attribute-not-found'                => '코드: \'%s\'의 구성 가능한 속성이 찾을 수 없거나 속성 가족: \'%s\'에 속하지 않음',
                    'configurable-attributes-not-found'        => '구성 가능한 속성이 제품 모델을 생성하기 위해 필요함',
                    'configurable-attributes-wrong-type'       => '구성 가능한 제품을 위한 구성 가능한 속성으로 지역이나 채널 기반이 아닌 속성만 선택할 수 있음',
                    'variant-configurable-attribute-not-found' => '생성하려는 변형 구성 가능한 속성: :code이 필요함',
                    'not-unique-variant-product'               => '같은 구성 가능한 속성이 있는 제품이 이미 존재함.',
                    'channel-not-exist'                        => '이 채널이 존재하지 않음.',
                    'locale-not-in-channel'                    => '이 로컬은 채널에서 선택되지 않음.',
                    'locale-not-exist'                         => '이 로컬이 존재하지 않음',
                    'not-unique-value'                         => ':code 값은 고유해야 함.',
                    'incorrect-family-for-variant'             => '가족은 부모 가족과 동일해야 함',
                    'parent-not-exist'                         => '부모가 존재하지 않음.',
                ],
            ],
        ],
        'categories' => [
            'title'      => '카테고리',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => '채널과 관련된 루트 카테고리를 삭제할 수 없음',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => '제품',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL 키: \'%s\'는 SKU: \'%s\'를 가진 항목에 이미 생성되었습니다.',
                    'invalid-attribute-family'  => '속성 가족 열에 유효하지 않은 값(속성 가족이 존재하지 않음?)',
                    'invalid-type'              => '제품 유형이 잘못되었거나 지원되지 않음',
                    'sku-not-found'             => '지정된 SKU를 가진 제품을 찾을 수 없습니다',
                    'super-attribute-not-found' => '코드: \'%s\'의 구성 가능한 속성이 찾을 수 없거나 속성 가족: \'%s\'에 속하지 않음',
                ],
            ],
        ],
        'categories' => [
            'title' => '카테고리',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => '열 번호 "%s"에 빈 머리글이 있습니다.',
            'column-name-invalid'  => '유효하지 않은 열 이름: "%s".',
            'column-not-found'     => '필수 열을 찾을 수 없습니다: %s.',
            'column-numbers'       => '열 수가 헤더의 행 수와 일치하지 않습니다.',
            'invalid-attribute'    => '헤더에 유효하지 않은 속성이 있습니다: "%s".',
            'system'               => '예기치 않은 시스템 오류가 발생했습니다.',
            'wrong-quotes'         => '쌍따옴표 대신 곧은 따옴표가 사용되었습니다.',
        ],
    ],
    'job' => [
        'started'   => '작업 실행 시작됨',
        'completed' => '작업 실행 완료됨',
    ],
];
