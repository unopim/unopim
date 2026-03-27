<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => '웹훅',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => '설정에서 Webhook을 활성화해 주세요',
        'success'       => '제품 데이터가 Webhook으로 성공적으로 전송되었습니다',
    ],
    'acl' => [
        'webhook' => [
            'index' => '웹훅',
        ],
        'settings' => [
            'index'  => '설정',
            'update' => '설정 업데이트',
        ],
        'logs' => [
            'index'       => '로그',
            'delete'      => '삭제',
            'mass-delete' => '일괄 삭제',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => '설정',
                    'title'   => 'Webhook 설정',
                    'save'    => '저장',
                    'general' => '일반',
                    'active'  => [
                        'label' => '활성 Webhook',
                    ],
                    'webhook_url' => [
                        'label' => '웹훅 URL',
                    ],
                    'success'    => 'Webhook 설정이 성공적으로 저장되었습니다',
                    'logs-title' => '로그',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => '날짜/시간',
                        'user'       => '사용자',
                        'status'     => '상태',
                        'success'    => '성공',
                        'failed'     => '실패',
                        'delete'     => '삭제',
                    ],
                    'title'          => 'Webhook 로그',
                    'delete-success' => 'Webhook 로그가 성공적으로 삭제되었습니다',
                    'delete-failed'  => 'Webhook 로그 삭제가 예기치 않게 실패했습니다',
                ],
            ],
        ],
    ],
];
