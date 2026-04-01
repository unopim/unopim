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
                    'configure'           => '設定完整性',
                    'channel-required'    => '在頻道中必填',
                    'save-btn'            => '儲存',
                    'back-btn'            => '返回',
                    'mass-update-success' => '完整性更新成功',
                    'datagrid'            => [
                        'code'             => '代碼',
                        'name'             => '名稱',
                        'channel-required' => '在頻道中必填',
                        'actions'          => [
                            'change-requirement' => '變更完整性要求',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => '不適用',
                    'completeness'                 => '完成',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => '完整性',
                    'subtitle' => '平均完整性',
                ],
                'required-attributes' => '缺少必填屬性',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => '完整性計算已完成',
        'completeness-calculated'        => '已為 :count 個產品計算完整性。',
        'completeness-calculated-family' => '已為系列 ":family" 中的 :count 個產品計算完整性。',
        'email-subject'                  => '完整性計算已完成',
        'email-greeting'                 => '您好，',
        'email-body'                     => '已完成 :count 個產品的完整性計算。',
        'email-body-family'              => '已完成屬性系列 ":family" 中 :count 個產品的完整性計算。',
        'email-footer'                   => '您可以在儀表板上查看完整性詳情。',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => '已計算的產品',
                'suggestion'          => [
                    'low'     => '完整性較低，請新增詳細資訊以改善。',
                    'medium'  => '繼續加油，持續新增資訊。',
                    'high'    => '即將完成，僅剩少量細節。',
                    'perfect' => '產品資訊已完全完整。',
                ],
            ],
        ],
    ],
];
