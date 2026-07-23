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
                'title'                  => '产品护照设置',
                'enabled'                => '已启用',
                'auto-publish'           => '保存时自动发布',
                'completeness-threshold' => '完整度阈值(%)',
                'operator-name'          => '经济运营商名称',
                'operator-address'       => '经济运营商地址',
                'operator-eu-rep'        => '欧盟授权代表',
                'support-url'            => '支持链接',
            ],
        ],
    ],
];
