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
        'product-associations' => [
            'title'      => '商品関連付け',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => '\'%s\' フィールドは必須です。',
                    'self-link-not-allowed'       => '商品 \'%s\' は自分自身と関連付けることはできません。',
                    'sku-not-found'               => 'SKU \'%s\' の商品が見つかりません。',
                    'related-sku-not-found'       => 'SKU \'%s\' の関連商品が見つかりません。',
                    'association-type-not-found'  => '関連付けタイプ \'%s\' は存在しないか無効です。',
                    'invalid-field-value'         => '関連付けフィールドに無効な値が指定されました。',
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
            'title'   => '通貨',
            'filters' => [
                'status' => 'ステータス',
                'enable' => '有効',
                'all'    => 'すべて',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'ステータスは0または1である必要があります（またはデフォルト有効の場合は空）。',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'ロール',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'ユーザー',
            'filters' => [
                'status' => 'ステータス',
                'active' => 'アクティブ',
                'all'    => 'すべて',
            ],
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
        'export-too-large' => 'このエクスポートは大きすぎて実行できません：推定 :rows 行 × :columns 列（~:estimated）が利用可能な容量（~:available）を超えています。チャネル／ロケール（および属性）を絞り込んでから再試行してください。',
        'fields'           => [
            'file-format'         => 'ファイル形式',
            'with-media'          => 'メディアを含む',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'ファイル パス',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'ステータス',
            'enable'         => '有効',
            'all'            => 'すべて',
        ],
        'products' => [
            'title'              => '製品',
            'invalid-locales'    => '選択したロケールのすべてが、選択したチャネルで利用できるわけではありません。',
            'invalid-currencies' => '選択した通貨のすべてが、選択したチャネルで利用できるわけではありません。',
            'filters'            => [
                'channels'             => 'チャネル',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => '通貨',
                'currencies-info'      => '価格属性は選択した通貨ごとにエクスポートされます。空欄の場合はすべてのチャネル通貨をエクスポートします。',
                'locales'              => 'ロケール',
                'locales-info'         => 'ローカライズ可能な属性は、選択したロケールごとに1回エクスポートされます。空欄の場合はすべてのチャネルロケールをエクスポートします。',
                'attributes'           => '属性',
                'attributes-info'      => '選択した属性のみがエクスポートされます。空欄の場合はファミリー内のすべての属性をエクスポートします。',
                'attribute-families'   => '属性ファミリー',
                'categories'           => 'カテゴリー',
                'completeness'         => '完全性',
                'completeness-options' => [
                    'none'         => '完全性の条件なし',
                    'at-least-one' => '選択した少なくとも1つのロケールで完全',
                    'all'          => '選択したすべてのロケールで完全',
                ],
                'time-condition' => '時間条件',
                'time-options'   => [
                    'none'              => '日付条件なし',
                    'last-n-days'       => '過去 N 日間に更新された商品',
                    'between-dates'     => '2つの日付の間に更新された商品',
                    'since-last-export' => '前回のエクスポート以降に更新された商品',
                ],
                'time-value'     => '日数',
                'time-date'      => '開始日',
                'time-date-end'  => '終了日',
                'status'         => 'ステータス',
                'status-options' => [
                    'enable'  => '有効',
                    'disable' => '無効',
                    'all'     => 'すべて',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => '識別子',
                'identifiers-info' => '1行に1つのSKU / 識別子を貼り付けると、それらの商品のみがエクスポートされます。空欄の場合はすべての商品がエクスポートされます。',
            ],
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
        'locales' => [
            'title' => 'ロケール',
        ],
        'channels' => [
            'title' => 'チャネル',
        ],
        'currencies' => [
            'title' => '通貨',
        ],
        'roles' => [
            'title' => 'ロール',
        ],
        'users' => [
            'title'   => 'ユーザー',
            'filters' => [
                'status' => 'ステータス',
                'active' => '有効',
                'all'    => 'すべて',
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
