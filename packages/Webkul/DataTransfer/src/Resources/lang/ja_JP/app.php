<?php

return [
    'importers' => [
        'products' => [
            'title'      => '製品',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URLキー「%s」は、SKU「%s」のアイテムに対して既に生成されています。',
                    'invalid-attribute-family'                 => '属性ファミリー列の値が無効です（属性ファミリーが存在しない可能性があります）',
                    'invalid-type'                             => '製品タイプが無効またはサポートされていません',
                    'sku-not-found'                            => '指定されたSKUの製品が見つかりません',
                    'super-attribute-not-found'                => 'コード :code のコンフィギュラブル属性が見つからないか、属性ファミリー :familyCode に属していません',
                    'configurable-attributes-not-found'        => '製品モデルを作成するにはコンフィギュラブル属性が必要です',
                    'configurable-attributes-wrong-type'       => 'ロケールまたはチャネルベースでない選択タイプの属性のみが、コンフィギュラブル製品のコンフィギュラブル属性として許可されます',
                    'variant-configurable-attribute-not-found' => 'バリアントコンフィギュラブル属性 :code は作成に必要です',
                    'not-unique-variant-product'               => '同じコンフィギュラブル属性を持つ製品が既に存在します。',
                    'channel-not-exist'                        => 'このチャネルは存在しません。',
                    'locale-not-in-channel'                    => 'このロケールはチャネルで選択されていません。',
                    'locale-not-exist'                         => 'このロケールは存在しません',
                    'not-unique-value'                         => ':code の値は一意である必要があります。',
                    'incorrect-family-for-variant'             => 'ファミリーは親ファミリーと同じでなければなりません',
                    'parent-not-exist'                         => '親が存在しません。',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'カテゴリー',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'チャネルに関連付けられているルートカテゴリーは削除できません',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'カテゴリフィールド',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'カテゴリフィールドコード :code は既に使用されています。',
                    'code_not_found_to_delete' => '削除するカテゴリフィールドコードが見つかりません。',
                ],
            ],
        ],
        'attributes' => [
            'title'      => '属性',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => '属性コード :code はすでに使用されています。',
                    'code_not_found_to_delete'             => '削除する属性コードが見つかりません。',
                    'code_is_system_and_cannot_be_deleted' => 'システム属性は削除できません。',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => '属性グループ',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => '属性グループコード :code はすでに使用されています。',
                    'code_not_found_to_delete'             => '削除する属性グループコードが見つかりません。',
                    'code_is_system_and_cannot_be_deleted' => 'システム属性グループは削除できません。',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => '属性ファミリー',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '属性ファミリーコード :code はすでに使用されています。',
                    'code_not_found_to_delete' => '削除する属性ファミリーコードが見つかりません。',
                    'invalid-attribute-group'  => '属性グループ ":code" は存在しません。',
                    'invalid-attribute'        => '属性 ":code" は存在しません。',
                    'invalid-channel'          => 'チャネル ":code" は存在しません。',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => '属性オプション',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => '属性オプションコード :code はすでに使用されています。',
                    'code_not_found_to_delete' => '削除する属性オプションコードが見つかりません。',
                    'locale-not-exist'         => 'ロケール ":code" は存在しません。',
                    'invalid-attribute'        => '属性 ":code" は存在しません。',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => '製品',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URLキー「%s」は、SKU「%s」のアイテムに対して既に生成されています。',
                    'invalid-attribute-family'  => '属性ファミリー列の値が無効です（属性ファミリーが存在しない可能性があります）',
                    'invalid-type'              => '製品タイプが無効またはサポートされていません',
                    'sku-not-found'             => '指定されたSKUの製品が見つかりません',
                    'super-attribute-not-found' => 'コード「%s」のスーパー属性が見つからないか、属性ファミリー「%s」に属していません',
                ],
            ],
        ],
        'categories' => [
            'title' => 'カテゴリー',
        ],
        'category-fields' => [
            'title' => 'カテゴリフィールド',
        ],
        'attributes' => [
            'title' => '属性',
        ],
        'attribute-groups' => [
            'title' => '属性グループ',
        ],
        'attribute-families' => [
            'title' => '属性ファミリー',
        ],
        'attribute-options' => [
            'title' => '属性オプション',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => '列番号「%s」のヘッダーが空です。',
            'column-name-invalid'  => '無効な列名: 「%s」。',
            'column-not-found'     => '必須列が見つかりません: %s。',
            'column-numbers'       => '列の数がヘッダーの行数と一致しません。',
            'invalid-attribute'    => 'ヘッダーに無効な属性が含まれています: 「%s」。',
            'system'               => '予期しないシステムエラーが発生しました。',
            'wrong-quotes'         => 'ストレート引用符の代わりに波型引用符が使用されています。',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'ジョブの実行が開始されました',
        'completed' => 'ジョブの実行が完了しました',
    ],
];
