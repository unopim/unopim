<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => '完整性',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => '完整性更新成功',
                    'title'               => '完整性',
                    'configure'           => '配置完整性',
                    'channel-required'    => '在渠道中必填',
                    'save-btn'            => '保存',
                    'back-btn'            => '返回',
                    'mass-update-success' => '完整性更新成功',
                    'datagrid'            => [
                        'code'             => '代码',
                        'name'             => '名称',
                        'channel-required' => '在渠道中必填',
                        'actions'          => [
                            'change-requirement' => '更改完整性要求',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => '不适用',
                    'completeness'                 => '完成',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => '完整性',
                    'subtitle' => '平均完整性',
                ],
                'required-attributes' => '缺少必填属性',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => '完整性计算已完成',
        'completeness-calculated'        => '已为 :count 个产品计算完整性。',
        'completeness-calculated-family' => '已为系列 ":family" 中的 :count 个产品计算完整性。',
        'email-subject'                  => '完整性计算已完成',
        'email-greeting'                 => '您好，',
        'email-body'                     => '已完成 :count 个产品的完整性计算。',
        'email-body-family'              => '已完成属性系列 ":family" 中 :count 个产品的完整性计算。',
        'email-footer'                   => '您可以在仪表板上查看完整性详情。',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => '已计算的产品',
                'suggestion'          => [
                    'low'     => '完整性较低，请添加详细信息以改善。',
                    'medium'  => '继续加油，持续添加信息。',
                    'high'    => '即将完成，仅剩少量细节。',
                    'perfect' => '产品信息已完全完整。',
                ],
            ],
        ],
    ],
];
