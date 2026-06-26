<?php

return [
    'importers' => [
        'products' => [
            'title'      => '产品',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL 密钥：已为 SKU 为“%s”的商品生成了“%s”。',
                    'invalid-attribute-family'                 => '属性系列列的值无效（属性系列不存在？）',
                    'invalid-type'                             => '产品类型无效或不受支持',
                    'sku-not-found'                            => '未找到指定 SKU 的产品',
                    'super-attribute-not-found'                => '代码为 :code 的可配置属性未找到或不属于属性系列 :familyCode',
                    'configurable-attributes-not-found'        => '创建产品模型需要可配置的属性',
                    'configurable-attributes-wrong-type'       => '仅允许选择不基于区域设置或通道的类型属性作为可配置产品的可配置属性',
                    'variant-configurable-attribute-not-found' => '变体可配置属性 创建时需要 :code',
                    'not-unique-variant-product'               => '具有相同可配置属性的产品已经存在。',
                    'channel-not-exist'                        => '该频道不存在。',
                    'locale-not-in-channel'                    => '未在频道中选择此区域设置。',
                    'locale-not-exist'                         => '该区域设置不存在',
                    'not-unique-value'                         => ':code 值必须是唯一的。',
                    'incorrect-family-for-variant'             => '家庭必须与父母家庭相同',
                    'parent-not-exist'                         => '父级不存在。',
                ],
            ],
        ],
        'categories' => [
            'title'      => '类别',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => '您无法删除与频道关联的根类别',
                ],
            ],
        ],
        'locales' => [
            'title'      => '语言',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => '语言代码 \'%s\' 已在此批次中导入。',
                    'code-not-found-to-delete'    => '系统中未找到代码为 \'%s\' 的语言。',
                    'invalid-status'              => '状态必须为 0 或 1（或留空表示默认启用）。',
                    'channel-related-locale-root' => '无法删除代码为 :code 的语言，因为它与某个渠道关联。',
                ],
            ],
        ],
        'channels' => [
            'title'      => '渠道',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => '未找到代码为 :code 的渠道，无法删除。',
                    'locale-not-found'         => '一个或多个语言不存在。',
                    'root-category-not-found'  => '根分类不存在。',
                    'currency-not-found'       => '一个或多个货币不存在。',
                    'invalid-locale'           => '语言不存在。',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => '此导出过大，无法运行：预计 :rows 行 × :columns 列（~:estimated）超出了可用空间（~:available）。请通过选择更少的渠道/区域（和属性）来缩小导出范围，然后重试。',
        'fields'           => [
            'file-format'         => '文件格式',
            'with-media'          => '包含媒体',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => '状态',
            'enable'         => '启用',
            'all'            => '全部',
        ],
        'products' => [
            'title'              => '产品',
            'invalid-locales'    => '并非所有所选语言环境都适用于所选渠道。',
            'invalid-currencies' => '并非所有所选货币都适用于所选渠道。',
            'filters'            => [
                'channels'             => '渠道',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => '货币',
                'currencies-info'      => '价格属性按每个所选货币导出。留空则导出所有渠道货币。',
                'locales'              => '语言环境',
                'locales-info'         => '可本地化属性按每个所选语言环境导出一次。留空则导出所有渠道语言环境。',
                'attributes'           => '属性',
                'attributes-info'      => '仅导出所选属性。留空则导出该属性族中的所有属性。',
                'attribute-families'   => '属性族',
                'categories'           => '类别',
                'completeness'         => '完整度',
                'completeness-options' => [
                    'none'         => '无完整度条件',
                    'at-least-one' => '在至少一个所选语言环境中完整',
                    'all'          => '在所有所选语言环境中完整',
                ],
                'time-condition' => '时间条件',
                'time-options'   => [
                    'none'              => '无日期条件',
                    'last-n-days'       => '过去 N 天内更新的产品',
                    'between-dates'     => '在两个日期之间更新的产品',
                    'since-last-export' => '自上次导出以来更新的产品',
                ],
                'time-value'     => '天数',
                'time-date'      => '开始日期',
                'time-date-end'  => '结束日期',
                'status'         => '状态',
                'status-options' => [
                    'enable'  => '启用',
                    'disable' => '禁用',
                    'all'     => '全部',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => '标识符',
                'identifiers-info' => '每行粘贴一个 SKU / 标识符，仅导出这些产品。留空则导出所有产品。',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL 密钥：已为 SKU 为“%s”的商品生成了“%s”。',
                    'invalid-attribute-family'  => '属性系列列的值无效（属性系列不存在？）',
                    'invalid-type'              => '产品类型无效或不受支持',
                    'sku-not-found'             => '未找到指定 SKU 的产品',
                    'super-attribute-not-found' => '代码为“%s”的超级属性未找到或不属于属性系列：“%s”',
                ],
            ],
        ],
        'categories' => [
            'title' => '类别',
        ],

        'locales' => [
            'title' => '语言',
        ],
        'channels' => [
            'title' => '渠道',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => '状态',
                'active' => 'Active',
                'all'    => '全部',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => '列号“%s”的标题为空。',
            'column-name-invalid'  => '无效的列名：“%s”。',
            'column-not-found'     => '未找到所需的列：%s。',
            'column-numbers'       => '列数与标题中的行数不对应。',
            'invalid-attribute'    => '标头包含无效属性：“%s”。',
            'system'               => '发生意外的系统错误。',
            'wrong-quotes'         => '使用弯引号代替直引号。',
            'file-empty'           => '文件为空或不包含标题行。请上传包含数据的有效文件。',
        ],
    ],
    'job' => [
        'started'   => '作业执行开始',
        'completed' => '作业执行完成',
    ],
];
