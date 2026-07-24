<?php

return [
    'type' => [
        'label' => 'デジタルプロダクトパスポート',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'プロダクトパスポート',
            'info'     => 'デジタルプロダクトパスポートの公開設定。',
            'settings' => [
                'title'                              => 'プロダクトパスポート設定',
                'enabled'                            => '有効',
                'enabled-hint'                       => 'このカタログでデジタルプロダクトパスポート機能を有効にします。オフにすると、パスポートパネルとグリッドは非表示になります。',
                'auto-publish'                       => '保存時に自動的に公開する',
                'auto-publish-hint'                  => '製品が保存され、完成度のしきい値を満たすたびに、パスポートのバージョンを自動的に公開します。手動で公開する場合はオフのままにします。',
                'completeness-threshold'             => '完成度しきい値(%)',
                'completeness-threshold-hint'        => 'あるロケールでパスポートを公開できるようにするために必要な、最低限の製品完成度(パーセント)です。',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => '経済事業者名',
                'operator-name-hint'                 => '製造者または責任を負う経済事業者の法的名称で、ESPR規則で義務付けられているとおり、すべての公開パスポートに表示されます。',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => '経済事業者住所',
                'operator-address-hint'              => '経済事業者の登録された郵便住所で、トレーサビリティのために公開パスポートに表示されます。',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'EU認定代理人',
                'operator-eu-rep-hint'               => 'EU域内の認定代理人の名前と連絡先で、製造者がEU域外に拠点を置く場合に必要です。',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'サポートURL',
                'support-url-hint'                   => '顧客がヘルプまたは保証情報を見つけられる公開ページです。すべてのパスポートにリンクとして表示されます。',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'デジタルプロダクトパスポート',
    ],
    'attributes' => [
        'dpp_material_composition'      => '材料構成',
        'dpp_substances_of_concern'     => '懸念物質',
        'dpp_recycled_content_pct'      => 'リサイクル含有率(%)',
        'dpp_carbon_footprint'          => 'カーボンフットプリント',
        'dpp_energy_consumption'        => 'エネルギー消費量',
        'dpp_durability_statement'      => '耐久性に関する声明',
        'dpp_repairability_score'       => '修理可能性スコア',
        'dpp_spare_parts_availability'  => '交換部品の入手可能性',
        'dpp_care_instructions'         => 'お手入れ方法',
        'dpp_disassembly_guide'         => '分解ガイド',
        'dpp_manufacturer_name'         => '製造者名',
        'dpp_manufacturing_site'        => '製造拠点',
        'dpp_country_of_origin'         => '原産国',
        'dpp_supply_chain_notes'        => 'サプライチェーンに関する注記',
        'dpp_end_of_life_instructions'  => '廃棄時の取扱説明',
        'dpp_take_back_scheme'          => '回収スキーム',
        'dpp_declaration_of_conformity' => '適合宣言書',
        'dpp_test_reports'              => '試験報告書',
        'dpp_certificates'              => '証明書',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => '型式識別子',
        'dpp_batch_identifier'          => 'バッチ識別子',
        'dpp_warranty_terms'            => '保証条件',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'デジタルプロダクトパスポートの属性を正常にインストールしました。',
        ],
    ],

    'public' => [
        'badge'         => 'EU デジタル製品パスポート',
        'search-locale' => '検索言語',
        'sections'      => [
            'passport' => 'プロダクトパスポート',
        ],
        'title'      => 'デジタルプロダクトパスポート',
        'identifier' => [
            'title'        => '識別情報',
            'gtin'         => 'GTIN',
            'model'        => 'モデル',
            'batch'        => 'バッチ',
            'not-provided' => '未提供',
        ],
        'operator' => [
            'title' => '経済事業者',
        ],
        'documents' => [
            'title' => '書類',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'パスポートの公開は現在無効になっています。既存のパスポートは管理（表示と取り下げ）のために以下に表示されます。',
            'title'           => 'デジタルプロダクトパスポート一覧',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'チャネル',
            'status'          => 'ステータス',
            'live-locales'    => '公開中の言語',
            'last-published'  => '最終公開日',
            'withdraw'        => '撤回',
        ],
        'publish-queued' => 'パスポートの公開がキューに登録されました。',
        'withdrawn'      => 'パスポートを撤回しました。',
        'mass-publish'   => [
            'action' => 'デジタル製品パスポートを公開',
            'queued' => ':count 件の製品のパスポート公開をキューに登録しました。',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'パスポート',
            'view'     => '表示',
            'publish'  => '公開',
            'withdraw' => '撤回',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'パスポート',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => '公開中…',
                    'queued'              => '待機中',
                    'title'               => 'デジタルプロダクトパスポート',
                    'publishing-disabled' => 'このチャネルではパスポートの公開が無効になっています。',
                    'locale'              => '言語',
                    'version'             => 'バージョン',
                    'published-at'        => '公開日',
                    'missing-fields'      => '不足項目',
                    'not-published'       => '未公開',
                    'unscored'            => '未評価',
                    'publish'             => '公開',
                    'republish'           => '再公開',
                    'publish-all'         => 'すべてのロケールを公開',
                    'auto-publish-on'     => '自動公開が有効です — 製品が保存され、完全性のしきい値を満たすとパスポートが自動的に公開されます。今すぐ公開するにはボタンを使用してください。',
                    'auto-publish-off'    => '手動公開 — 各ロケールでこの製品のパスポートを公開するにはボタンを使用してください。',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute は有効な GTIN（正しいチェックディジットを持つ 8、12、13、または 14 桁）である必要があります。',
    ],
];
