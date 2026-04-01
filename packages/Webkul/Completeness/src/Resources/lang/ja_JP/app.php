<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => '完全性',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => '完全性が正常に更新されました',
                    'title'               => '完全性',
                    'configure'           => '完全性を設定',
                    'channel-required'    => 'チャネルで必須',
                    'save-btn'            => '保存',
                    'back-btn'            => '戻る',
                    'mass-update-success' => '完全性が正常に更新されました',
                    'datagrid'            => [
                        'code'             => 'コード',
                        'name'             => '名前',
                        'channel-required' => 'チャネルで必須',
                        'actions'          => [
                            'change-requirement' => '完全性要件を変更',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => '該当なし',
                    'completeness'                 => '完了',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => '完全性',
                    'subtitle' => '平均完全性',
                ],
                'required-attributes' => '必須属性が不足しています',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => '完全性計算が完了しました',
        'completeness-calculated'        => ':count 件の製品の完全性が計算されました。',
        'completeness-calculated-family' => 'ファミリー ":family" の :count 件の製品の完全性が計算されました。',
        'email-subject'                  => '完全性計算が完了しました',
        'email-greeting'                 => 'こんにちは、',
        'email-body'                     => ':count 件の製品の完全性計算が完了しました。',
        'email-body-family'              => '属性ファミリー ":family" の :count 件の製品の完全性計算が完了しました。',
        'email-footer'                   => 'ダッシュボードで完全性の詳細を確認できます。',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => '計算済み製品',
                'suggestion'          => [
                    'low'     => '完全性が低い状態です。詳細を追加して改善してください。',
                    'medium'  => '続けてください。情報を追加し続けましょう。',
                    'high'    => 'ほぼ完了です。あと少し詳細が残っています。',
                    'perfect' => '製品情報は完全に揃っています。',
                ],
            ],
        ],
    ],
];
