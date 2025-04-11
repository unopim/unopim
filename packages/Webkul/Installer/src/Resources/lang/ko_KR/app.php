<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => '기본',
            'attribute-groups'   => [
                'description'      => '설명',
                'general'          => '일반',
                'inventories'      => '재고',
                'meta-description' => '메타 설명',
                'price'            => '가격',
                'technical'        => '기술',
                'shipping'         => '배송',
            ],
            'attributes' => [
                'brand'                => '브랜드',
                'color'                => '색상',
                'cost'                 => '비용',
                'description'          => '설명',
                'featured'             => '추천됨',
                'guest-checkout'       => '게스트 체크아웃',
                'height'               => '높이',
                'length'               => '길이',
                'manage-stock'         => '재고 관리',
                'meta-description'     => '메타 설명',
                'meta-keywords'        => '메타 키워드',
                'meta-title'           => '메타 제목',
                'name'                 => '이름',
                'new'                  => '새로운',
                'price'                => '가격',
                'product-number'       => '제품 번호',
                'short-description'    => '짧은 설명',
                'size'                 => '크기',
                'sku'                  => 'SKU',
                'special-price-from'   => '특가 시작일',
                'special-price-to'     => '특가 종료일',
                'special-price'        => '특가',
                'status'               => '상태',
                'tax-category'         => '세금 카테고리',
                'url-key'              => 'URL 키',
                'visible-individually' => '개별적으로 표시됨',
                'weight'               => '무게',
                'width'                => '너비',
            ],
            'attribute-options' => [
                'black'  => '검정',
                'green'  => '초록',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => '빨강',
                's'      => 'S',
                'white'  => '하얀',
                'xl'     => 'XL',
                'yellow' => '노랑',
            ],
        ],
        'category' => [
            'categories' => [
                'description' => '루트 카테고리 설명',
                'name'        => '루트',
            ],
            'category_fields' => [
                'name'        => '이름',
                'description' => '설명',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => '회사 소개 페이지 콘텐츠',
                    'title'   => '회사 소개',
                ],
                'contact-us' => [
                    'content' => '문의하기 페이지 콘텐츠',
                    'title'   => '문의하기',
                ],
                'customer-service' => [
                    'content' => '고객 서비스 페이지 콘텐츠',
                    'title'   => '고객 서비스',
                ],
                'payment-policy' => [
                    'content' => '결제 정책 페이지 콘텐츠',
                    'title'   => '결제 정책',
                ],
                'privacy-policy' => [
                    'content' => '개인 정보 보호 정책 페이지 콘텐츠',
                    'title'   => '개인 정보 보호 정책',
                ],
                'refund-policy' => [
                    'content' => '환불 정책 페이지 콘텐츠',
                    'title'   => '환불 정책',
                ],
                'return-policy' => [
                    'content' => '반품 정책 페이지 콘텐츠',
                    'title'   => '반품 정책',
                ],
                'shipping-policy' => [
                    'content' => '배송 정책 페이지 콘텐츠',
                    'title'   => '배송 정책',
                ],
                'terms-conditions' => [
                    'content' => '이용 약관 페이지 콘텐츠',
                    'title'   => '이용 약관',
                ],
                'terms-of-use' => [
                    'content' => '이용 약관 페이지 콘텐츠',
                    'title'   => '이용 약관',
                ],
                'whats-new' => [
                    'content' => '새로운 콘텐츠 페이지',
                    'title'   => '새로운 콘텐츠',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'meta-title'       => '데모 상점',
                'meta-keywords'    => '데모 상점 메타 키워드',
                'meta-description' => '데모 상점 메타 설명',
                'name'             => '기본',
            ],
            'currencies' => [
                'AED' => '디르함',
                'AFN' => '이스라엘 셰켈',
                'CNY' => '중국 위안화',
                'EUR' => '유로',
                'GBP' => '영국 파운드',
                'INR' => '인도 루피',
                'IRR' => '이란 리알',
                'JPY' => '일본 엔화',
                'RUB' => '러시아 루블',
                'SAR' => '사우디 리얄',
                'TRY' => '터키 리라',
                'UAH' => '우크라이나 그리브나',
                'USD' => '미국 달러',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general'   => '일반',
                'guest'     => '게스트',
                'wholesale' => '도매',
            ],
        ],
        'inventory' => [
            'inventory-sources' => [
                'name' => '기본',
            ],
        ],
        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name'    => '모든 제품',
                    'options' => [
                        'title' => '모든 제품',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title'   => '모두 보기',
                        'description' => '새로운 Bold 컬렉션을 소개합니다! 스타일을 한 단계 끌어올리고, 도전적인 디자인과 화려한 색상을 통해 의상에 활력을 불어넣어 보세요. 강렬한 패턴과 bold 색상을 통해 당신의 의상을 새롭게 정의하십시오. 특별한 순간을 준비하세요!',
                        'title'       => '새 Bold 컬렉션을 만나보세요!',
                    ],
                    'name' => 'Bold 컬렉션',
                ],
                'categories-collections' => [
                    'name' => '카테고리 컬렉션',
                ],
                'featured-collections' => [
                    'name'    => '주요 컬렉션',
                    'options' => [
                        'title' => '추천 제품',
                    ],
                ],
                'footer-links' => [
                    'name'    => '푸터 링크',
                    'options' => [
                        'about-us'         => '회사 소개',
                        'contact-us'       => '문의하기',
                        'customer-service' => '고객 서비스',
                        'payment-policy'   => '결제 정책',
                        'privacy-policy'   => '개인 정보 보호 정책',
                        'refund-policy'    => '환불 정책',
                        'return-policy'    => '반품 정책',
                        'shipping-policy'  => '배송 정책',
                        'terms-conditions' => '이용 약관',
                        'terms-of-use'     => '이용 약관',
                        'whats-new'        => '새로운 콘텐츠',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => '우리의 컬렉션',
                        'sub-title-2' => '우리의 컬렉션',
                        'title'       => '우리의 새로운 추가 사항으로 게임을 준비하세요!',
                    ],
                    'name' => '게임 컨테이너',
                ],
                'image-carousel' => [
                    'name'    => '이미지 슬라이더',
                    'sliders' => [
                        'title' => '새로운 컬렉션을 준비하세요',
                    ],
                ],
                'new-products' => [
                    'name'    => '새로운 제품',
                    'options' => [
                        'title' => '새로운 제품',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => '최초 주문 시 최대 40% 할인 SHOP NOW',
                    ],
                    'name' => '제안 정보',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info'   => '모든 주요 신용 카드에서 EMI 이용 가능',
                        'free-shipping-info'   => '모든 주문에 무료 배송 제공',
                        'product-replace-info' => '쉽게 교체할 수 있는 제품 제공!',
                        'time-support-info'    => '채팅 및 이메일을 통한 24시간 지원',
                    ],
                    'name'  => '서비스 콘텐츠',
                    'title' => [
                        'emi-available'   => 'EMI 사용 가능',
                        'free-shipping'   => '무료 배송',
                        'product-replace' => '제품 교체',
                        'time-support'    => '24시간 지원',
                    ],
                ],
                'top-collections' => [
                    'content' => [
                        'sub-title-1' => '우리의 컬렉션',
                        'sub-title-2' => '우리의 컬렉션',
                        'sub-title-3' => '우리의 컬렉션',
                        'sub-title-4' => '우리의 컬렉션',
                        'sub-title-5' => '우리의 컬렉션',
                        'sub-title-6' => '우리의 컬렉션',
                        'title'       => '우리의 새로운 추가 사항으로 게임을 준비하세요!',
                    ],
                    'name' => '탑 컬렉션',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => '이 역할을 가진 사용자는 모든 접근 권한을 가집니다',
                'name'        => '관리자',
            ],
            'users' => [
                'name' => '예제',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => '관리자',
                'unopim'           => 'UnoPim',
                'confirm-password' => '비밀번호 확인',
                'email-address'    => 'admin@example.com',
                'email'            => '이메일',
                'password'         => '비밀번호',
                'title'            => '관리자 생성',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => '허용된 통화',
                'allowed-locales'     => '허용된 로케일',
                'application-name'    => '애플리케이션 이름',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => '중국 위안화 (CNY)',
                'database-connection' => '데이터베이스 연결',
                'database-hostname'   => '데이터베이스 호스트명',
                'database-name'       => '데이터베이스 이름',
                'database-password'   => '데이터베이스 비밀번호',
                'database-port'       => '데이터베이스 포트',
                'database-prefix'     => '데이터베이스 접두사',
                'database-username'   => '데이터베이스 사용자 이름',
                'default-currency'    => '기본 통화',
                'default-locale'      => '기본 로케일',
                'default-timezone'    => '기본 시간대',
                'default-url-link'    => 'https://localhost',
                'default-url'         => '기본 URL',
                'dirham'              => '디르함 (AED)',
                'euro'                => '유로 (EUR)',
                'iranian'             => '이란 리알 (IRR)',
                'israeli'             => '이스라엘 셰켈 (ILS)',
                'japanese-yen'        => '일본 엔화 (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => '영국 파운드 (GBP)',
                'rupee'               => '인도 루피 (INR)',
                'russian-ruble'       => '러시아 루블 (RUB)',
                'saudi'               => '사우디 리얄 (SAR)',
                'select-timezone'     => '시간대를 선택하세요',
                'sqlsrv'              => 'SQLSRV',
                'title'               => '데이터베이스 구성',
                'turkish-lira'        => '터키 리라 (TRY)',
                'ukrainian-hryvnia'   => '우크라이나 흐리브냐 (UAH)',
                'usd'                 => '미국 달러 (USD)',
                'warning-message'     => '주의! 기본 로케일 및 통화 설정은 나중에 변경할 수 없습니다.',
            ],

            'installation-processing' => [
                'unopim'      => 'UnoPim 설치 중',
                'unopim-info' => '데이터베이스 테이블을 생성 중입니다. 시간이 걸릴 수 있습니다.',
                'title'       => '설치 중',
            ],

            'installation-completed' => [
                'admin-panel'               => '관리자 패널',
                'unopim-forums'             => 'UnoPim 포럼',
                'explore-unopim-extensions' => 'UnoPim 확장 프로그램 탐색',
                'title-info'                => 'UnoPim이 성공적으로 설치되었습니다.',
                'title'                     => '설치 완료',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => '데이터베이스 테이블 생성',
                'install-info-button'     => '아래 버튼을 클릭하여 설치를 시작하세요.',
                'install-info'            => 'UnoPim 설치',
                'install'                 => '설치',
                'populate-database-table' => '데이터베이스 테이블 채우기',
                'start-installation'      => '설치 시작',
                'title'                   => '설치 준비 완료',
            ],

            'start' => [
                'locale'        => '로케일',
                'main'          => '시작',
                'select-locale' => '로케일을 선택하세요',
                'title'         => 'UnoPim 설치',
                'welcome-title' => 'UnoPim :version에 오신 것을 환영합니다.',
            ],

            'server-requirements' => [
                'calendar'    => '캘린더',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => '파일 정보',
                'filter'      => '필터',
                'gd'          => 'GD',
                'hash'        => '해시',
                'intl'        => '국제화',
                'json'        => 'JSON',
                'mbstring'    => '멀티바이트 문자열',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 이상',
                'php'         => 'PHP',
                'session'     => '세션',
                'title'       => '시스템 요구 사항',
                'tokenizer'   => '토크나이저',
                'xml'         => 'XML',
            ],

            'back'                     => '뒤로',
            'unopim-info'              => '커뮤니티 프로젝트',
            'unopim-logo'              => 'UnoPim 로고',
            'unopim'                   => 'UnoPim',
            'continue'                 => '계속',
            'installation-description' => 'UnoPim 설치는 여러 단계를 거칩니다. 간략한 개요는 다음과 같습니다:',
            'wizard-language'          => '설치 마법사 언어',
            'installation-info'        => '여기 와주셔서 감사합니다!',
            'installation-title'       => '설치 환영',
            'save-configuration'       => '구성 저장',
            'skip'                     => '건너뛰기',
            'title'                    => 'UnoPim 설치 마법사',
            'webkul'                   => 'Webkul',
        ],
    ],
];
