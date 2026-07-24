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
                'title'                              => '제품 여권 설정',
                'enabled'                            => '활성화됨',
                'auto-publish'                       => '저장 시 자동으로 게시',
                'completeness-threshold'             => '완성도 임계값(%)',
                'operator-name'                      => '경제 운영자 이름',
                'operator-address'                   => '경제 운영자 주소',
                'operator-eu-rep'                    => 'EU 공인 대리인',
                'support-url'                        => '지원 URL',
                'enabled-hint'                       => '이 카탈로그에 대해 디지털 제품 여권 기능을 켭니다. 꺼져 있으면 여권 패널과 그리드가 숨겨집니다.',
                'auto-publish-hint'                  => '제품이 저장되고 완성도 임계값을 충족할 때마다 여권 버전을 자동으로 게시합니다. 수동으로 게시하려면 꺼 두세요.',
                'completeness-threshold-hint'        => '로케일에 대해 여권을 게시하기 전에 필요한 최소 제품 완성도(백분율)입니다.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'ESPR 규정에 따라 모든 공개 여권에 표시되는 제조업체 또는 책임 경제 운영자의 법적 이름입니다.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => '추적성을 위해 공개 여권에 표시되는 경제 운영자의 등록 우편 주소입니다.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => '제조업체가 EU 외부에 설립된 경우 필요한 EU 공인 대리인의 이름과 연락처입니다.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => '고객이 도움말이나 보증 정보를 찾을 수 있는 공개 페이지입니다. 모든 여권에 링크로 표시됩니다.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => '디지털 제품 여권',
    ],
    'attributes' => [
        'dpp_material_composition'      => '소재 구성',
        'dpp_substances_of_concern'     => '우려 물질',
        'dpp_recycled_content_pct'      => '재활용 함유량(%)',
        'dpp_carbon_footprint'          => '탄소 발자국',
        'dpp_energy_consumption'        => '에너지 소비량',
        'dpp_durability_statement'      => '내구성 설명',
        'dpp_repairability_score'       => '수리 용이성 점수',
        'dpp_spare_parts_availability'  => '예비 부품 가용성',
        'dpp_care_instructions'         => '관리 지침',
        'dpp_disassembly_guide'         => '분해 가이드',
        'dpp_manufacturer_name'         => '제조업체 이름',
        'dpp_manufacturing_site'        => '제조 장소',
        'dpp_country_of_origin'         => '원산지',
        'dpp_supply_chain_notes'        => '공급망 참고 사항',
        'dpp_end_of_life_instructions'  => '수명 종료 지침',
        'dpp_take_back_scheme'          => '반환 제도',
        'dpp_declaration_of_conformity' => '적합성 선언',
        'dpp_test_reports'              => '시험 보고서',
        'dpp_certificates'              => '인증서',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => '모델 식별자',
        'dpp_batch_identifier'          => '배치 식별자',
        'dpp_warranty_terms'            => '보증 조건',
    ],
    'console' => [
        'install-attributes' => [
            'success' => '디지털 제품 여권 속성이 성공적으로 설치되었습니다.',
        ],
    ],

    'public' => [
        'badge'         => 'EU 디지털 제품 여권',
        'search-locale' => '검색 언어',
        'sections'      => [
            'passport' => '제품 여권',
        ],
        'title'      => '디지털 제품 여권',
        'identifier' => [
            'title'        => '식별 정보',
            'gtin'         => 'GTIN',
            'model'        => '모델',
            'batch'        => '배치',
            'not-provided' => '제공되지 않음',
        ],
        'operator' => [
            'title' => '경제 운영자',
        ],
        'documents' => [
            'title' => '문서',
        ],
    ],

    'publications' => [
        'not-found'      => 'ID :id에 대한 여권을 찾을 수 없습니다.',
        'index'          => [
            'disabled-notice' => '여권 게시가 현재 비활성화되어 있습니다. 기존 여권은 관리(보기 및 철회)를 위해 아래에 표시됩니다.',
            'title'           => '디지털 제품 여권 목록',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => '채널',
            'status'          => '상태',
            'live-locales'    => '활성 언어',
            'last-published'  => '마지막 게시일',
            'withdraw'        => '철회',
        ],
        'publish-queued' => '여권 게시가 대기열에 추가되었습니다.',
        'withdrawn'      => '여권이 철회되었습니다.',
        'mass-publish'   => [
            'action' => '디지털 제품 여권 게시',
            'queued' => '제품 :count개의 여권 게시가 대기열에 추가되었습니다.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => '여권',
            'view'     => '보기',
            'publish'  => '게시',
            'withdraw' => '철회',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => '여권',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => '게시 중…',
                    'queued'               => '대기 중',
                    'copy-operator-link'   => '운영자 링크 복사',
                    'copy-authority-link'  => '당국 링크 복사',
                    'link-copied'          => '링크가 복사되었습니다',
                    'download-qr'          => 'QR 코드 다운로드',
                    'title'                => '디지털 제품 여권',
                    'publishing-disabled'  => '이 채널에서는 여권 게시가 비활성화되어 있습니다.',
                    'locale'               => '언어',
                    'version'              => '버전',
                    'published-at'         => '게시일',
                    'missing-fields'       => '누락된 필드',
                    'not-published'        => '게시되지 않음',
                    'unscored'             => '평가되지 않음',
                    'publish'              => '게시',
                    'republish'            => '다시 게시',
                    'publish-all'          => '모든 로케일 게시',
                    'auto-publish-on'      => '자동 게시가 켜져 있습니다 — 제품을 저장하고 완전성 임계값을 충족하면 여권이 자동으로 게시됩니다. 지금 게시하려면 버튼을 사용하세요.',
                    'auto-publish-off'     => '수동 게시 — 각 로케일에 대해 이 제품의 여권을 게시하려면 버튼을 사용하세요.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute은(는) 올바른 검사 숫자를 포함한 8, 12, 13 또는 14자리의 유효한 GTIN이어야 합니다.',
    ],
    'mapping' => [
        'title'         => '패스포트 필드 매핑',
        'info'          => '이미 관리 중인 속성에서 각 패스포트 필드를 가져옵니다. 필드를 매핑하지 않으면 전용 패스포트 속성으로 대체됩니다.',
        'menu'          => '필드 매핑',
        'field'         => '패스포트 필드',
        'source'        => '소스 속성',
        'select-source' => '패스포트 속성 사용',
        'save-btn'      => '매핑 저장',
        'type-mismatch' => '선택한 소스가 이 여권 필드 유형과 호환되지 않습니다.',
        'saved'         => '필드 매핑이 성공적으로 저장되었습니다.',
    ],

];
