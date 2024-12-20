<?php

return [
    'users' => [
        'sessions' => [
            'email'                => '이메일 주소',
            'forget-password-link' => '비밀번호를 잊으셨나요?',
            'password'             => '비밀번호',
            'submit-btn'           => '로그인',
            'title'                => '로그인',
        ],

        'forget-password' => [
            'create' => [
                'email'                => '등록된 이메일',
                'email-not-exist'      => '이메일이 존재하지 않습니다',
                'page-title'           => '비밀번호 찾기',
                'reset-link-sent'      => '비밀번호 재설정 링크가 전송되었습니다',
                'email-settings-error' => '이메일을 보낼 수 없습니다. 이메일 설정을 확인해주세요',
                'sign-in-link'         => '로그인 화면으로 돌아가기?',
                'submit-btn'           => '재설정',
                'title'                => '비밀번호 복구',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => '로그인 화면으로 돌아가기?',
            'confirm-password' => '비밀번호 확인',
            'email'            => '등록된 이메일',
            'password'         => '비밀번호',
            'submit-btn'       => '비밀번호 재설정',
            'title'            => '비밀번호 재설정',
        ],
    ],

    'notifications' => [
        'description-text' => '모든 알림 목록',
        'marked-success'   => '알림이 성공적으로 표시되었습니다',
        'no-record'        => '기록이 없습니다',
        'read-all'         => '모두 읽음으로 표시',
        'title'            => '알림',
        'view-all'         => '모두 보기',
        'status'           => [
            'all'        => '모두',
            'canceled'   => '취소됨',
            'closed'     => '닫힘',
            'completed'  => '완료됨',
            'pending'    => '대기 중',
            'processing' => '처리 중',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => '뒤로',
            'change-password'   => '비밀번호 변경',
            'confirm-password'  => '비밀번호 확인',
            'current-password'  => '현재 비밀번호',
            'email'             => '이메일',
            'general'           => '일반',
            'invalid-password'  => '입력하신 현재 비밀번호가 잘못되었습니다.',
            'name'              => '이름',
            'password'          => '비밀번호',
            'profile-image'     => '프로필 이미지',
            'save-btn'          => '계정 저장',
            'title'             => '내 계정',
            'ui-locale'         => 'UI 로캘',
            'update-success'    => '계정이 성공적으로 업데이트되었습니다',
            'upload-image-info' => '프로필 이미지를 업로드하세요 (110px X 110px)',
            'user-timezone'     => '시간대',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => '대시보드',
            'user-info'        => 'PIM의 중요 항목 모니터링',
            'user-name'        => '안녕하세요! :user_name',
            'catalog-details'  => '카탈로그',
            'total-families'   => '전체 가족 수',
            'total-attributes' => '전체 속성',
            'total-groups'     => '전체 그룹',
            'total-categories' => '전체 카테고리',
            'total-products'   => '전체 제품 수',
            'settings-details' => '카탈로그 구조',
            'total-locales'    => '전체 로캘',
            'total-currencies' => '전체 통화',
            'total-channels'   => '전체 채널',
        ],
    ],

    'acl' => [
        'addresses'                => '주소',
        'attribute-families'       => '속성 가족',
        'attribute-groups'         => '속성 그룹',
        'attributes'               => '속성',
        'cancel'                   => '취소',
        'catalog'                  => '카탈로그',
        'categories'               => '카테고리',
        'channels'                 => '채널',
        'configure'                => '구성',
        'configuration'            => '구성',
        'copy'                     => '복사',
        'create'                   => '생성',
        'currencies'               => '통화',
        'dashboard'                => '대시보드',
        'data-transfer'            => '데이터 전송',
        'delete'                   => '삭제',
        'edit'                     => '편집',
        'email-templates'          => '이메일 템플릿',
        'events'                   => '이벤트',
        'groups'                   => '그룹',
        'import'                   => '임포트',
        'imports'                  => '임포트',
        'invoices'                 => '송장',
        'locales'                  => '로캘',
        'magic-ai'                 => '마법 AI',
        'marketing'                => '마케팅',
        'newsletter-subscriptions' => '뉴스레터 구독',
        'note'                     => '메모',
        'orders'                   => '주문',
        'products'                 => '제품',
        'promotions'               => '프로모션',
        'refunds'                  => '환불',
        'reporting'                => '보고',
        'reviews'                  => '리뷰',
        'roles'                    => '역할',
        'sales'                    => '판매',
        'search-seo'               => '검색 및 SEO',
        'search-synonyms'          => '검색 동의어',
        'search-terms'             => '검색어',
        'settings'                 => '설정',
        'shipments'                => '배송',
        'sitemaps'                 => '사이트 맵',
        'subscribers'              => '뉴스레터 구독자',
        'tax-categories'           => '세금 카테고리',
        'tax-rates'                => '세금률',
        'taxes'                    => '세금',
        'themes'                   => '테마',
        'integration'              => '통합',
        'url-rewrites'             => 'URL 리라이트',
        'users'                    => '사용자',
        'category_fields'          => '카테고리 필드',
        'view'                     => '보기',
        'execute'                  => '실행',
        'history'                  => '기록',
        'restore'                  => '복원',
        'integrations'             => '통합',
        'api'                      => 'API',
        'tracker'                  => '추적기',
        'imports'                  => '임포트',
        'exports'                  => '임포트',
    ],

    'errors' => [
        'dashboard' => '대시보드',
        'go-back'   => '뒤로 가기',
        'support'   => '문제가 해결되지 않으면, <a href=":link" class=":class">:email</a>으로 문의하십시오.',

        '404' => [
            'description' => '앗! 찾고 있는 페이지를 찾을 수 없습니다. 찾고 있는 것을 찾지 못했습니다.',
            'title'       => '404 페이지를 찾을 수 없음',
        ],

        '401' => [
            'description' => '앗! 이 페이지에 접근할 권한이 없습니다. 필요한 자격 증명이 부족합니다.',
            'title'       => '401 접근 권한 없음',
            'message'     => '인증에 실패했습니다. 자격 증명이 잘못되었거나 토큰이 만료되었습니다.',
        ],

        '403' => [
            'description' => '앗! 이 페이지는 접근할 수 없습니다. 보려면 권한이 필요합니다.',
            'title'       => '403 접근 금지',
        ],

        '413' => [
            'description' => '앗! 업로드하려는 파일이 너무 큽니다. 파일을 업로드하려면 PHP 구성 내용을 업데이트하십시오.',
            'title'       => '413 콘텐츠 너무 큼',
        ],

        '419' => [
            'description' => '앗! 세션이 만료되었습니다. 페이지를 새로고침하고 다시 로그인하여 계속하십시오.',
            'title'       => '419 세션 만료',
        ],

        '500' => [
            'description' => '앗! 무언가 잘못되었습니다. 찾고 있는 페이지를 로드하는 데 문제가 발생했습니다.',
            'title'       => '500 내부 서버 오류',
        ],

        '503' => [
            'description' => '앗! 현재 서비스가 임시로 사용 불가능합니다. 나중에 다시 시도하십시오.',
            'title'       => '503 서비스 사용 불가',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => '다운로드',
        'export'     => '빠른 내보내기',
        'no-records' => '내보낼 기록 없음',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => '이 슬러그는 카테고리 또는 제품에서 사용 중입니다.',
        'slug-reserved'   => '이 슬러그는 예약되었습니다.',
        'invalid-locale'  => '잘못된 로컬 :locales',
    ],

    'footer' => [
        'copy-right' => '<a href="https://unopim.com/" target="_blank">UnoPim</a>에 의해 구동됨, <a href="https://webkul.com/" target="_blank">Webkul</a>의 커뮤니티 프로젝트',
    ],

    'emails' => [
        'dear'   => 'Dear :admin_name',
        'thanks' => 'If you need any kind of help please contact us at <a href=":link" style=":style">:email</a>.<br/>Thanks!',

        'admin' => [
            'forgot-password' => [
                'description'    => 'You are receiving this email because we received a password reset request for your account.',
                'greeting'       => 'Forgot Password!',
                'reset-password' => 'Reset Password',
                'subject'        => 'Reset Password Email',
            ],
        ],
    ],

    'common' => [
        'yes'     => '예',
        'no'      => '아니요',
        'true'    => '참',
        'false'   => '거짓',
        'enable'  => '사용',
        'disable' => '사용 안함',
    ],

    'configuration' => [
        'index' => [
            'delete'                       => '삭제',
            'no-result-found'              => '결과를 찾을 수 없음',
            'save-btn'                     => '구성 저장',
            'save-message'                 => '구성 성공적으로 저장됨',
            'search'                       => '검색',
            'title'                        => '구성',

            'general' => [
                'info'  => '',
                'title' => '일반',

                'general' => [
                    'info'  => '',
                    'title' => '일반',
                ],

                'magic-ai' => [
                    'info'  => 'Magic AI 옵션을 설정합니다.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API 키',
                        'enabled'        => '사용 가능',
                        'llm-api-domain' => 'LLM API 도메인',
                        'organization'   => '조직 ID',
                        'title'          => '일반 설정',
                        'title-info'     => 'Magic AI 경험을 향상시키려면 자신의 API 키를 입력하고 관련 조직을 지정하여 통합을 원활하게 하세요. OpenAI 자격 증명 제어 및 필요에 맞게 설정을 사용자 정의할 수 있습니다.',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => '생성',
                'title'      => '통합',

                'datagrid' => [
                    'delete'          => '삭제',
                    'edit'            => '수정',
                    'id'              => 'ID',
                    'name'            => '이름',
                    'user'            => '사용자',
                    'client-id'       => '클라이언트 ID',
                    'permission-type' => '권한 유형',
                ],
            ],

            'create' => [
                'access-control' => '접근 제어',
                'all'            => '모두',
                'back-btn'       => '뒤로',
                'custom'         => '사용자 정의',
                'assign-user'    => '사용자 할당',
                'general'        => '일반',
                'name'           => '이름',
                'permissions'    => '권한',
                'save-btn'       => '저장',
                'title'          => '새 통합',
            ],

            'edit' => [
                'access-control' => '접근 제어',
                'all'            => '모두',
                'back-btn'       => '뒤로',
                'custom'         => '사용자 정의',
                'assign-user'    => '사용자 할당',
                'general'        => '일반',
                'name'           => '이름',
                'credentials'    => '자격 증명',
                'client-id'      => '클라이언트 ID',
                'secret-key'     => '비밀 키',
                'generate-btn'   => '생성',
                're-secret-btn'  => '비밀 키 다시 생성',
                'permissions'    => '권한',
                'save-btn'       => '저장',
                'title'          => '통합 수정',
            ],

            'being-used'                     => 'API 통합은 이미 Admin 사용자에서 사용 중입니다',
            'create-success'                 => 'API 통합이 성공적으로 생성되었습니다',
            'delete-failed'                  => 'API 통합 삭제 실패',
            'delete-success'                 => 'API 통합이 성공적으로 삭제되었습니다',
            'last-delete-error'              => '마지막 API 통합을 삭제할 수 없습니다',
            'update-success'                 => 'API 통합이 성공적으로 업데이트되었습니다',
            'generate-key-success'           => 'API 키가 성공적으로 생성되었습니다',
            're-generate-secret-key-success' => 'API 비밀 키가 성공적으로 재생성되었습니다',
            'client-not-found'               => '클라이언트를 찾을 수 없습니다',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => '계정',
                'app-version'   => '버전 : :version',
                'logout'        => '로그아웃',
                'my-account'    => '내 계정',
                'notifications' => '알림',
                'visit-shop'    => '매장 방문',
            ],

            'sidebar' => [
                'attribute-families'       => '속성 그룹',
                'attribute-groups'         => '속성 그룹',
                'attributes'               => '속성',
                'history'                  => '이력',
                'edit-section'             => '데이터',
                'general'                  => '일반',
                'catalog'                  => '카탈로그',
                'categories'               => '카테고리',
                'category_fields'          => '카테고리 필드',
                'channels'                 => '채널',
                'collapse'                 => '축소',
                'configure'                => '구성',
                'currencies'               => '통화',
                'dashboard'                => '대시보드',
                'data-transfer'            => '데이터 전송',
                'groups'                   => '그룹',
                'tracker'                  => '작업 트래커',
                'imports'                  => '수입',
                'exports'                  => '수출',
                'locales'                  => '로컬',
                'magic-ai'                 => '마법 AI',
                'mode'                     => '다크 모드',
                'products'                 => '제품',
                'roles'                    => '역할',
                'settings'                 => '설정',
                'themes'                   => '테마',
                'users'                    => '사용자',
                'integrations'             => '통합',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => '선택된 기록이 없습니다.',
                'must-select-a-mass-action-option' => '대량 작업 옵션을 선택해야 합니다.',
                'must-select-a-mass-action'        => '대량 작업을 선택해야 합니다.',
            ],

            'toolbar' => [
                'length-of' => ':length',
                'of'        => '중',
                'per-page'  => '페이지 당',
                'results'   => ':total 결과',
                'selected'  => ':total 선택됨',

                'mass-actions' => [
                    'submit'        => '제출',
                    'select-option' => '옵션 선택',
                    'select-action' => '작업 선택',
                ],

                'filter' => [
                    'title' => '필터',
                ],

                'search_by' => [
                    'code'       => '코드로 검색',
                    'code_or_id' => '코드 또는 ID로 검색',
                ],

                'search' => [
                    'title' => '검색',
                ],
            ],

            'filters' => [
                'select'   => '선택',
                'title'    => '필터 적용',
                'save'     => '저장',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => '최소 2자 이상 입력...',
                        'no-results'        => '결과 없음...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => '모두 지우기',
                    'title'     => '사용자 정의 필터',
                ],

                'boolean-options' => [
                    'false' => '거짓',
                    'true'  => '참',
                ],

                'date-options' => [
                    'last-month'        => '지난 달',
                    'last-six-months'   => '지난 6개월',
                    'last-three-months' => '지난 3개월',
                    'this-month'        => '이번 달',
                    'this-week'         => '이번 주',
                    'this-year'         => '올해',
                    'today'             => '오늘',
                    'yesterday'         => '어제',
                ],
            ],

            'table' => [
                'actions'              => '작업',
                'no-records-available' => '사용 가능한 기록이 없습니다.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => '동의',
                'disagree-btn' => '취소',
                'message'      => '이 작업을 수행하시겠습니까?',
                'title'        => '정말로?',
            ],

            'delete' => [
                'agree-btn'    => '삭제',
                'disagree-btn' => '취소',
                'message'      => '정말 삭제하시겠습니까?',
                'title'        => '삭제 확인',
            ],

            'history' => [
                'title'           => '이력 미리보기',
                'subtitle'        => '빠르게 업데이트와 변경 사항을 검토하세요.',
                'close-btn'       => '닫기',
                'version-label'   => '버전',
                'date-time-label' => '날짜/시간',
                'user-label'      => '사용자',
                'name-label'      => '키',
                'old-value-label' => '구 값',
                'new-value-label' => '새 값',
                'no-history'      => '이력 없음',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => '선택한 제품 추가',
                'empty-info'    => '검색어에 대한 제품이 없습니다.',
                'empty-title'   => '제품 없음',
                'product-image' => '제품 이미지',
                'qty'           => ':qty 사용 가능',
                'sku'           => 'SKU - :sku',
                'title'         => '제품 선택',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => '이미지 추가',
                'ai-add-image-btn'  => '마법 AI',
                'ai-btn-info'       => '이미지 생성',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => '이미지 파일만 허용됩니다 (.jpeg, .jpg, .png, ..).',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => '적용',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => '생성',
                    'generating'       => '생성 중...',
                    'hd'               => 'HD',
                    'model'            => '모델',
                    'number-of-images' => '이미지 수',
                    'prompt'           => '프롬프트',
                    'quality'          => '품질',
                    'regenerate'       => '재생성',
                    'regenerating'     => '재생성 중...',
                    'size'             => '크기',
                    'standard'         => '표준',
                    'title'            => 'AI 이미지 생성',
                ],

                'placeholders' => [
                    'front'     => '전면',
                    'next'      => '다음',
                    'size'      => '크기',
                    'use-cases' => '용도',
                    'zoom'      => '줌',
                ],
            ],

            'videos' => [
                'add-video-btn'     => '비디오 추가',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => '비디오 파일만 허용됩니다 (.mp4, .mov, .ogg ..).',
            ],

            'files' => [
                'add-file-btn'      => '파일 추가',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => 'PDF 파일만 허용됩니다',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => '마법 AI',

            'ai-generation' => [
                'apply'                  => '적용',
                'generate'               => '생성',
                'generated-content'      => '생성된 콘텐츠',
                'generated-content-info' => 'AI 콘텐츠는 오도일 수 있습니다. 생성된 콘텐츠를 검토한 후에 적용하세요.',
                'generating'             => '생성 중...',
                'prompt'                 => '프롬프트',
                'title'                  => 'AI 지원',
                'model'                  => '모델',
                'gpt-3-5-turbo'          => 'OpenAI gpt-3.5-turbo',
                'llama2'                 => 'Llama 2',
                'mistral'                => 'Mistral',
                'dolphin-phi'            => 'Dolphin Phi',
                'phi'                    => 'Phi-2',
                'starling-lm'            => 'Starling',
                'llama2-uncensored'      => 'Llama 2 uncensored',
                'llama2:13b'             => 'Llama 2 13B',
                'llama2:70b'             => 'Llama 2 70B',
                'orca-mini'              => 'Orca Mini',
                'vicuna'                 => 'Vicuna',
                'llava'                  => 'LLaVA',
            ],
        ],
    ],
];
