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
                    'super-attribute-not-found'                => '코드: \'%s\'의 구성 가능한 속성이 찾을 수 없거나 속성 가족: \'%s\'에 속하지 않음 :code :familyCode',
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
        'channels' => [
            'title'      => '채널',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => '코드 :code 인 채널을 삭제할 수 없습니다.',
                    'locale-not-found'         => '하나 이상의 로케일이 존재하지 않습니다.',
                    'root-category-not-found'  => '루트 카테고리가 존재하지 않습니다.',
                    'currency-not-found'       => '하나 이상의 통화가 존재하지 않습니다.',
                    'invalid-locale'           => '로케일이 존재하지 않습니다.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => '이 내보내기는 너무 커서 실행할 수 없습니다: 예상 :rows 행 × :columns 열(~:estimated)이 사용 가능한 공간(~:available)을 초과합니다. 채널/로케일(및 속성)을 줄여 범위를 좁힌 후 다시 시도하세요.',
        'fields'           => [
            'file-format'         => '파일 형식',
            'with-media'          => '미디어 포함',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => '상태',
            'enable'         => '활성화',
            'all'            => '전체',
        ],
        'products' => [
            'title'              => '제품',
            'invalid-locales'    => '선택한 로케일이 모두 선택한 채널에서 사용 가능한 것은 아닙니다.',
            'invalid-currencies' => '선택한 통화가 모두 선택한 채널에서 사용 가능한 것은 아닙니다.',
            'filters'            => [
                'channels'             => '채널',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => '통화',
                'currencies-info'      => '가격 속성은 선택한 통화별로 내보내집니다. 모든 채널 통화를 내보내려면 비워 두세요.',
                'locales'              => '로케일',
                'locales-info'         => '현지화 가능한 속성은 선택한 로케일마다 한 번씩 내보내집니다. 모든 채널 로케일을 내보내려면 비워 두세요.',
                'attributes'           => '속성',
                'attributes-info'      => '선택한 속성만 내보내집니다. 패밀리의 모든 속성을 내보내려면 비워 두세요.',
                'attribute-families'   => '속성 패밀리',
                'categories'           => '카테고리',
                'completeness'         => '완전성',
                'completeness-options' => [
                    'none'         => '완전성 조건 없음',
                    'at-least-one' => '선택한 하나 이상의 로케일에서 완전',
                    'all'          => '선택한 모든 로케일에서 완전',
                ],
                'time-condition' => '시간 조건',
                'time-options'   => [
                    'none'              => '날짜 조건 없음',
                    'last-n-days'       => '지난 N일 동안 업데이트된 제품',
                    'between-dates'     => '두 날짜 사이에 업데이트된 제품',
                    'since-last-export' => '마지막 내보내기 이후 업데이트된 제품',
                ],
                'time-value'     => '일수',
                'time-date'      => '시작일',
                'time-date-end'  => '종료일',
                'status'         => '상태',
                'status-options' => [
                    'enable'  => '활성화',
                    'disable' => '비활성화',
                    'all'     => '전체',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => '식별자',
                'identifiers-info' => '해당 제품만 내보내려면 한 줄에 하나의 SKU / 식별자를 붙여넣으세요. 모든 제품을 내보내려면 비워 두세요.',
            ],
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
        'channels' => [
            'title' => '채널',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => '상태',
                'active' => 'Active',
                'all'    => '전체',
            ],
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
            'file-empty'           => '파일이 비어 있거나 헤더 행이 없습니다. 데이터가 포함된 유효한 파일을 업로드하세요.',
        ],
    ],
    'job' => [
        'started'   => '작업 실행 시작됨',
        'completed' => '작업 실행 완료됨',
    ],
];
