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

    'configuration' => [
        'score-thresholds' => [
            'title'           => '完成度スコアの色',
            'info'            => '完成度インジケーターが緑または赤に変わるスコアを設定します。',
            'green-from'      => '緑の下限 (%)',
            'green-from-info' => 'この値以上のスコアは緑で表示されます。',
            'red-below'       => '赤の上限 (%)',
            'red-below-info'  => 'この値未満のスコアは赤で表示されます。その間のスコアは黄色で表示されます。',
            'order-error'     => '緑のしきい値は赤のしきい値より大きくする必要があります。',
        ],
    ],
];
