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
                    'update-success'      => '完整性已成功更新',
                    'title'               => '完整性',
                    'configure'           => '配置完整性',
                    'channel-required'    => '频道中必需',
                    'save-btn'            => '保存',
                    'back-btn'            => '返回',
                    'mass-update-success' => '完整性已成功更新',

                    'datagrid' => [
                        'code'             => '代码',
                        'name'             => '名称',
                        'channel-required' => '频道中必需',

                        'actions' => [
                            'change-requirement' => '更改完整性要求',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => '无设置',
                    'completeness'                 => '完整',
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

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => '已计算产品',

                'suggestion' => [
                    'low'     => '完整性较低，请添加详细信息以改进。',
                    'medium'  => '继续，继续添加信息。',
                    'high'    => '几乎完成，只剩一些细节。',
                    'perfect' => '产品信息已完全完整。',
                ],
            ],
        ],
    ],
];
