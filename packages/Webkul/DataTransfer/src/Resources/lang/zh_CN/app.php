<?php

return [
    'importers' => [

        'products' => [
            'title' => '产品',

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
    ],

    'exporters' => [

        'products' => [
            'title' => '产品',

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
        ],
    ],

    'job' => [
        'started'   => '作业执行开始',
        'completed' => '作业执行完成',
    ],
];
