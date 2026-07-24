<?php

return [
    'type' => [
        'label' => '數位產品護照',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => '產品護照',
            'info'     => '數位產品護照的發布設定。',
            'settings' => [
                'title'                              => '產品護照設定',
                'enabled'                            => '已啟用',
                'auto-publish'                       => '儲存時自動發布',
                'completeness-threshold'             => '完整度門檻(%)',
                'operator-name'                      => '經濟營運者名稱',
                'operator-address'                   => '經濟營運者地址',
                'operator-eu-rep'                    => '歐盟授權代表',
                'support-url'                        => '支援連結',
                'enabled-hint'                       => '為此目錄啟用數位產品護照功能。關閉時，護照面板和清單將被隱藏。',
                'auto-publish-hint'                  => '每當產品儲存並達到完整度門檻時自動發佈護照版本。保持關閉則手動發佈。',
                'completeness-threshold-hint'        => '為某個語系發佈護照前所需的最低產品完整度（百分比）。',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => '製造商或負責的經濟營運者的法定名稱，依照 ESPR 法規要求顯示在每個公開護照上。',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => '經濟營運者的註冊郵政地址，顯示在公開護照上以便追溯。',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => '歐盟授權代表的名稱和聯絡方式，當製造商設立於歐盟之外時為必填項。',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => '客戶可尋找協助或保固資訊的公開頁面。以連結形式顯示在每個護照上。',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => '數位產品護照',
    ],
    'attributes' => [
        'dpp_material_composition'      => '材料成分',
        'dpp_substances_of_concern'     => '關注物質',
        'dpp_recycled_content_pct'      => '回收材料含量 (%)',
        'dpp_carbon_footprint'          => '碳足跡',
        'dpp_energy_consumption'        => '能源消耗',
        'dpp_durability_statement'      => '耐用性聲明',
        'dpp_repairability_score'       => '可維修性評分',
        'dpp_spare_parts_availability'  => '備用零件供應情況',
        'dpp_care_instructions'         => '保養說明',
        'dpp_disassembly_guide'         => '拆解指南',
        'dpp_manufacturer_name'         => '製造商名稱',
        'dpp_manufacturing_site'        => '生產地點',
        'dpp_country_of_origin'         => '原產國',
        'dpp_supply_chain_notes'        => '供應鏈備註',
        'dpp_end_of_life_instructions'  => '報廢處理說明',
        'dpp_take_back_scheme'          => '回收計畫',
        'dpp_declaration_of_conformity' => '符合性聲明',
        'dpp_test_reports'              => '測試報告',
        'dpp_certificates'              => '證書',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => '型號識別碼',
        'dpp_batch_identifier'          => '批次識別碼',
        'dpp_warranty_terms'            => '保固條款',
    ],
    'console' => [
        'install-attributes' => [
            'success' => '數位產品護照屬性已成功安裝。',
        ],
    ],

    'public' => [
        'badge'         => 'EU 數位產品護照',
        'search-locale' => '搜尋語言',
        'sections'      => [
            'passport' => '產品護照',
        ],
        'title'      => '數位產品護照',
        'identifier' => [
            'title'        => '識別資訊',
            'gtin'         => 'GTIN',
            'model'        => '型號',
            'batch'        => '批次',
            'not-provided' => '未提供',
        ],
        'operator' => [
            'title' => '經濟營運者',
        ],
        'documents' => [
            'title' => '文件',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => '護照發布目前已停用。現有護照顯示於下方以供管理（檢視與撤回）。',
            'title'           => '數位產品護照',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => '通路',
            'status'          => '狀態',
            'live-locales'    => '啟用語言',
            'last-published'  => '最近發布時間',
            'withdraw'        => '撤回',
        ],
        'publish-queued' => '護照發布已排入佇列。',
        'withdrawn'      => '護照已成功撤回。',
        'mass-publish'   => [
            'action' => '發布數位產品護照',
            'queued' => '已將 :count 個產品的護照發布排入佇列。',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => '護照',
            'view'     => '檢視',
            'publish'  => '發布',
            'withdraw' => '撤回',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => '護照',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => '發布中…',
                    'queued'              => '排隊中',
                    'title'               => '數位產品護照',
                    'publishing-disabled' => '此通路已停用護照發布。',
                    'locale'              => '語言',
                    'version'             => '版本',
                    'published-at'        => '發布時間',
                    'missing-fields'      => '缺少的欄位',
                    'not-published'       => '尚未發布',
                    'unscored'            => '尚未評分',
                    'publish'             => '發布',
                    'republish'           => '重新發布',
                    'publish-all'         => '發布所有語言',
                    'auto-publish-on'     => '自動發布已開啟 — 當產品儲存並達到完整度門檻時，護照將自動發布。使用按鈕立即發布。',
                    'auto-publish-off'    => '手動發布 — 使用按鈕為每種語言發布此產品的護照。',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute 必須是有效的 GTIN（8、12、13 或 14 位數字，且檢查碼正確）。',
    ],
    'mapping' => [
        'title' => '護照欄位對應',
        'info' => '從您已維護的屬性中取得每個護照欄位。將欄位保持未對應以回退至其專用護照屬性。',
        'menu' => '欄位對應',
        'field' => '護照欄位',
        'source' => '來源屬性',
        'select-source' => '使用護照屬性',
        'save-btn' => '儲存對應',
        'type-mismatch' => '所選來源與此護照欄位的類型不相容。',
        'saved' => '欄位對應已成功儲存。',
    ],

];
