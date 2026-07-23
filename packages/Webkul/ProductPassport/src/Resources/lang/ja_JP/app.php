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
                'title'                  => 'プロダクトパスポート設定',
                'enabled'                => '有効',
                'auto-publish'           => '保存時に自動的に公開する',
                'completeness-threshold' => '完成度しきい値(%)',
                'operator-name'          => '経済事業者名',
                'operator-address'       => '経済事業者住所',
                'operator-eu-rep'        => 'EU認定代理人',
                'support-url'            => 'サポートURL',
            ],
        ],
    ],
];
