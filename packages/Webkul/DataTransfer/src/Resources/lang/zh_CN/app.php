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
        'category-fields' => [
            'title'      => '分类字段',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '分类字段代码 :code 已被使用。',
                    'code_not_found_to_delete' => '未找到用于删除的分类字段代码。',
                ],
            ],
        ],
        'attributes' => [
            'title'      => '属性',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => '属性代码 :code 已被使用。',
                    'code_not_found_to_delete'             => '未找到要删除的属性代码。',
                    'code_is_system_and_cannot_be_deleted' => '无法删除系统属性。',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => '属性组',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => '属性组代码 :code 已被使用。',
                    'code_not_found_to_delete'             => '未找到要删除的属性组代码。',
                    'code_is_system_and_cannot_be_deleted' => '无法删除系统属性组。',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => '属性族',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '属性族代码 :code 已被使用。',
                    'code_not_found_to_delete' => '未找到要删除的属性族代码。',
                    'invalid-attribute-group'  => '属性组“:code”不存在。',
                    'invalid-attribute'        => '属性“:code”不存在。',
                    'invalid-channel'          => '渠道“:code”不存在。',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => '属性选项',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '属性选项代码 :code 已被使用。',
                    'code_not_found_to_delete' => '未找到要删除的属性选项代码。',
                    'locale-not-exist'         => '区域设置“:code”不存在。',
                    'invalid-attribute'        => '属性“:code”不存在。',
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
        'products' => [
            'title'      => '产品',
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
        'category-fields' => [
            'title' => '分类字段',
        ],
        'attributes' => [
            'title' => '属性',
        ],
        'attribute-groups' => [
            'title' => '属性组',
        ],
        'attribute-families' => [
            'title' => '属性族',
        ],
        'attribute-options' => [
            'title' => '属性选项',
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
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => '作业执行开始',
        'completed' => '作业执行完成',
    ],
];
