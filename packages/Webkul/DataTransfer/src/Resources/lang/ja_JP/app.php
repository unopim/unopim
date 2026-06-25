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
        'locales' => [
            'title'      => 'ロケール',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'ロケールコード \'%s\' はこのバッチですでにインポートされています。',
                    'code-not-found-to-delete'    => 'コード \'%s\' のロケールがシステムに見つかりません。',
                    'invalid-status'              => 'ステータスは0または1である必要があります（またはデフォルト有効の場合は空）。',
                    'channel-related-locale-root' => 'コード :code のロケールはチャネルに関連付けられているため削除できません。',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'チャネル',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'コード :code のチャネルは削除対象として見つかりません。',
                    'locale-not-found'         => '1つ以上のロケールが存在しません。',
                    'root-category-not-found'  => 'ルートカテゴリが存在しません。',
                    'currency-not-found'       => '1つ以上の通貨が存在しません。',
                    'invalid-locale'           => 'ロケールが存在しません。',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
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
        'locales' => [
            'title' => 'ロケール',
        ],
        'channels' => [
            'title' => 'チャネル',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
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
            'file-empty'           => 'ファイルが空であるか、ヘッダー行が含まれていません。データを含む有効なファイルをアップロードしてください。',
        ],
    ],
    'job' => [
        'started'   => 'ジョブの実行が開始されました',
        'completed' => 'ジョブの実行が完了しました',
    ],
];
