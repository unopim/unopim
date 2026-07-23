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
                'title'                  => '產品護照設定',
                'enabled'                => '已啟用',
                'auto-publish'           => '儲存時自動發布',
                'completeness-threshold' => '完整度門檻(%)',
                'operator-name'          => '經濟營運者名稱',
                'operator-address'       => '經濟營運者地址',
                'operator-eu-rep'        => '歐盟授權代表',
                'support-url'            => '支援連結',
            ],
        ],
    ],
];
