<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => '완전성',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => '완전성이 성공적으로 업데이트되었습니다',
                    'title'               => '완전성',
                    'configure'           => '완전성 구성',
                    'channel-required'    => '채널에서 필수',
                    'save-btn'            => '저장',
                    'back-btn'            => '뒤로',
                    'mass-update-success' => '완전성이 성공적으로 업데이트되었습니다',
                    'datagrid'            => [
                        'code'             => '코드',
                        'name'             => '이름',
                        'channel-required' => '채널에서 필수',
                        'actions'          => [
                            'change-requirement' => '완전성 요구 사항 변경',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => '해당 없음',
                    'completeness'                 => '완료',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => '완전성',
                    'subtitle' => '평균 완전성',
                ],
                'required-attributes' => '누락된 필수 속성',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => '완전성 계산 완료',
        'completeness-calculated'        => ':count개 제품의 완전성이 계산되었습니다.',
        'completeness-calculated-family' => '패밀리 ":family"의 :count개 제품의 완전성이 계산되었습니다.',
        'email-subject'                  => '완전성 계산 완료',
        'email-greeting'                 => '안녕하세요,',
        'email-body'                     => ':count개 제품에 대한 완전성 계산이 완료되었습니다.',
        'email-body-family'              => '속성 패밀리 ":family"의 :count개 제품에 대한 완전성 계산이 완료되었습니다.',
        'email-footer'                   => '대시보드에서 완전성 세부 정보를 확인할 수 있습니다.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => '계산된 제품',
                'suggestion'          => [
                    'low'     => '완전성이 낮습니다. 세부 정보를 추가하여 개선하세요.',
                    'medium'  => '계속하세요, 정보를 계속 추가하세요.',
                    'high'    => '거의 완료되었습니다. 몇 가지 세부 정보만 남았습니다.',
                    'perfect' => '제품 정보가 완전히 완성되었습니다.',
                ],
            ],
        ],
    ],

    'configuration' => [
        'score-thresholds' => [
            'title'           => '완성도 점수 색상',
            'info'            => '완성도 표시가 초록색 또는 빨간색으로 바뀌는 점수를 설정합니다.',
            'green-from'      => '초록색 시작 (%)',
            'green-from-info' => '이 값 이상인 점수는 초록색으로 표시됩니다.',
            'red-below'       => '빨간색 기준 미만 (%)',
            'red-below-info'  => '이 값 미만인 점수는 빨간색으로 표시됩니다. 그 사이의 점수는 주황색으로 표시됩니다.',
            'order-error'     => '초록색 기준값은 빨간색 기준값보다 커야 합니다.',
        ],
    ],
];
