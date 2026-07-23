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
            'index'  => '웹훅',
            'create' => '생성',
            'edit'   => '편집',
            'delete' => '삭제',
        ],
        'logs' => [
            'index'       => '로그',
            'view'        => '보기',
            'delete'      => '삭제',
            'mass-delete' => '일괄 삭제',
        ],
    ],

    'events' => [
        'product' => [
            'created' => '제품 생성됨',
            'updated' => '제품 업데이트됨',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => '웹훅',
            'create-btn'   => '웹훅 생성',
            'logs-btn'     => '로그',
            'back-btn'     => '웹훅으로 돌아가기',
            'default-name' => '기본',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => '이름',
                'url'        => 'URL',
                'events'     => '이벤트',
                'status'     => '상태',
                'active'     => '활성',
                'inactive'   => '비활성',
                'created_at' => '생성일',
                'edit'       => '편집',
                'delete'     => '삭제',
            ],
        ],
        'create' => [
            'title'    => '웹훅 생성',
            'save-btn' => '저장',
        ],
        'edit' => [
            'title'    => '웹훅 편집',
            'save-btn' => '저장',
        ],
        'form' => [
            'general'       => '일반',
            'name'          => '이름',
            'url'           => 'URL',
            'events'        => '이벤트',
            'select-events' => '이벤트 선택',
            'secret'        => '서명 비밀키',
            'secret-set'    => '비밀키가 이미 설정되어 있습니다',
            'secret-hint'   => 'HMAC SHA-256 서명으로 각 페이로드에 서명하는 데 사용됩니다. 현재 비밀키를 유지하려면 비워 두세요.',
            'settings'      => '설정',
            'active'        => '활성',
            'test'          => '연결 테스트',
            'test-hint'     => '위 URL로 테스트 요청을 보냅니다.',
            'test-btn'      => '테스트 전송',
            'test-no-url'   => '먼저 URL을 입력해 주세요.',
            'test-failed'   => '테스트 요청이 실패했습니다.',
            'headers'       => '사용자 지정 헤더',
            'add-header'    => '헤더 추가',
            'no-headers'    => '추가된 사용자 지정 헤더가 없습니다.',
            'header-key'    => '헤더',
            'header-value'  => '값',
        ],
        'create-success' => '웹훅이 성공적으로 생성되었습니다',
        'update-success' => '웹훅이 성공적으로 업데이트되었습니다',
        'delete-success' => '웹훅이 성공적으로 삭제되었습니다',
        'delete-failed'  => '웹훅 삭제에 실패했습니다',
        'validation'     => [
            'unsafe-url' => 'URL이 비공개, 루프백 또는 내부 주소를 가리키므로 허용되지 않습니다.',
            'scheme'     => 'URL은 http:// 또는 https://로 시작해야 합니다.',
        ],
        'test' => [
            'payload-message'   => 'Unopim 웹훅 테스트 요청',
            'connection-failed' => 'URL에 연결할 수 없습니다. URL을 확인해 주세요.',
            'unreachable'       => 'URL에 연결할 수 없습니다 (HTTP :code).',
            'reachable'         => 'URL에 연결할 수 있습니다.',
        ],
        'prune' => [
            'disabled' => '웹훅 로그 보관이 비활성화되어 있어 삭제된 항목이 없습니다.',
            'done'     => ':days일보다 오래된 웹훅 로그 :count개를 삭제했습니다.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => '웹훅',
                        'sku'              => 'SKU',
                        'event'            => '이벤트',
                        'created_at'       => '날짜/시간',
                        'user'             => '사용자',
                        'status'           => '상태',
                        'success'          => '성공',
                        'failed'           => '실패',
                        'server_error'     => '서버 오류',
                        'timeout_or_error' => '시간 초과/오류',
                        'delete'           => '삭제',
                        'view'             => '보기',
                    ],
                    'title'          => 'Webhook 로그',
                    'show-title'     => 'Webhook 로그 세부 정보',
                    'sent-payload'   => '전송된 페이로드',
                    'response'       => '응답',
                    'back'           => 'Back to Logs',
                    'no-payload'     => '이 로그에 기록된 페이로드가 없습니다.',
                    'load-failed'    => '로그 세부 정보를 불러오지 못했습니다.',
                    'delete-success' => 'Webhook 로그가 성공적으로 삭제되었습니다',
                    'delete-failed'  => 'Webhook 로그 삭제가 예기치 않게 실패했습니다',
                    'unauthorized'   => '이 작업은 권한이 없습니다',
                ],
            ],
        ],
    ],
];
