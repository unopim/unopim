<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completeness',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => '완성도가 성공적으로 업데이트되었습니다',
                    'title'               => '완성도',
                    'configure'           => '완성도 구성',
                    'channel-required'    => '채널에서 필요함',
                    'save-btn'            => '저장',
                    'back-btn'            => '뒤로',
                    'mass-update-success' => '완성도가 성공적으로 업데이트되었습니다',

                    'datagrid' => [
                        'code'             => '코드',
                        'name'             => '이름',
                        'channel-required' => '채널에서 필요함',

                        'actions' => [
                            'change-requirement' => '완성도 요구사항 변경',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => '설정 없음',
                    'completeness'                 => '완성도',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => '완성도',
                    'subtitle' => '평균 완성도',
                ],

                'required-attributes' => '누락된 필수 속성',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => '계산된 제품',

                'suggestion' => [
                    'low'     => '완성도가 낮습니다. 개선을 위해 세부 정보를 추가하세요.',
                    'medium'  => '계속 진행하세요. 정보를 계속 추가하세요.',
                    'high'    => '거의 완료되었습니다. 몇 가지 세부 정보만 남았습니다.',
                    'perfect' => '제품 정보가 완전히 완성되었습니다.',
                ],
            ],
        ],
    ],
];
