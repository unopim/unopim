<?php

return [
    'type' => [
        'label' => '数字产品护照',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => '产品护照',
            'info'     => '数字产品护照的发布设置。',
            'settings' => [
                'title'                              => '产品护照设置',
                'enabled'                            => '已启用',
                'auto-publish'                       => '保存时自动发布',
                'completeness-threshold'             => '完整度阈值(%)',
                'operator-name'                      => '经济运营商名称',
                'operator-address'                   => '经济运营商地址',
                'operator-eu-rep'                    => '欧盟授权代表',
                'support-url'                        => '支持链接',
                'enabled-hint'                       => '为此目录启用数字产品护照功能。关闭时，护照面板和列表将被隐藏。',
                'auto-publish-hint'                  => '每当产品保存并达到完整度阈值时自动发布护照版本。保持关闭则手动发布。',
                'completeness-threshold-hint'        => '为某个语言环境发布护照前所需的最低产品完整度（百分比）。',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => '制造商或责任经济经营者的法定名称，按照 ESPR 法规要求显示在每个公开护照上。',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => '经济经营者的注册通讯地址，显示在公开护照上以便追溯。',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => '欧盟授权代表的名称和联系方式，当制造商设立在欧盟之外时为必填项。',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => '客户可查找帮助或保修信息的公开页面。以链接形式显示在每个护照上。',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => '数字产品护照',
    ],
    'attributes' => [
        'dpp_material_composition'      => '材料成分',
        'dpp_substances_of_concern'     => '关注物质',
        'dpp_recycled_content_pct'      => '再生材料含量 (%)',
        'dpp_carbon_footprint'          => '碳足迹',
        'dpp_energy_consumption'        => '能耗',
        'dpp_durability_statement'      => '耐用性声明',
        'dpp_repairability_score'       => '可维修性评分',
        'dpp_spare_parts_availability'  => '备件供应情况',
        'dpp_care_instructions'         => '保养说明',
        'dpp_disassembly_guide'         => '拆解指南',
        'dpp_manufacturer_name'         => '制造商名称',
        'dpp_manufacturing_site'        => '生产地点',
        'dpp_country_of_origin'         => '原产国',
        'dpp_supply_chain_notes'        => '供应链说明',
        'dpp_end_of_life_instructions'  => '报废处理说明',
        'dpp_take_back_scheme'          => '回收计划',
        'dpp_declaration_of_conformity' => '符合性声明',
        'dpp_test_reports'              => '测试报告',
        'dpp_certificates'              => '证书',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => '型号标识符',
        'dpp_batch_identifier'          => '批次标识符',
        'dpp_warranty_terms'            => '保修条款',
    ],
    'console' => [
        'install-attributes' => [
            'success' => '数字产品护照属性已成功安装。',
        ],
    ],

    'public' => [
        'badge'         => 'EU 数字产品护照',
        'search-locale' => '搜索语言',
        'sections'      => [
            'passport' => '产品护照',
        ],
        'title'      => '数字产品护照',
        'identifier' => [
            'title'        => '标识信息',
            'gtin'         => 'GTIN',
            'model'        => '型号',
            'batch'        => '批次',
            'not-provided' => '未提供',
        ],
        'operator' => [
            'title' => '经济运营商',
        ],
        'documents' => [
            'title' => '文件',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => '护照发布当前已禁用。现有护照显示在下方以供管理（查看和撤回）。',
            'title'           => '数字产品护照',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => '渠道',
            'status'          => '状态',
            'live-locales'    => '活跃语言',
            'last-published'  => '最近发布时间',
            'withdraw'        => '撤回',
        ],
        'publish-queued' => '护照发布已排队。',
        'withdrawn'      => '护照已成功撤回。',
        'mass-publish'   => [
            'action' => '发布数字产品护照',
            'queued' => '已将 :count 个产品的护照发布加入队列。',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => '护照',
            'view'     => '查看',
            'publish'  => '发布',
            'withdraw' => '撤回',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => '护照',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => '发布中…',
                    'queued'              => '排队中',
                    'title'               => '数字产品护照',
                    'publishing-disabled' => '该渠道已禁用护照发布。',
                    'locale'              => '语言',
                    'version'             => '版本',
                    'published-at'        => '发布时间',
                    'missing-fields'      => '缺失字段',
                    'not-published'       => '未发布',
                    'unscored'            => '未评分',
                    'publish'             => '发布',
                    'republish'           => '重新发布',
                    'publish-all'         => '发布所有语言',
                    'auto-publish-on'     => '自动发布已开启 — 当产品保存并达到完整度阈值时，护照将自动发布。使用按钮立即发布。',
                    'auto-publish-off'    => '手动发布 — 使用按钮为每种语言发布此产品的护照。',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute 必须是有效的 GTIN（8、12、13 或 14 位数字，且校验位正确）。',
    ],
    'mapping' => [
        'title' => '护照字段映射',
        'info' => '从您已维护的属性中获取每个护照字段。将字段保持未映射以回退到其专用护照属性。',
        'menu' => '字段映射',
        'field' => '护照字段',
        'source' => '来源属性',
        'select-source' => '使用护照属性',
        'save-btn' => '保存映射',
        'type-mismatch' => '所选来源与此护照字段的类型不兼容。',
        'saved' => '字段映射保存成功。',
    ],

];
