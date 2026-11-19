<?php

return [
    'acl' => [
        'variant-structures' => 'バリエーション構造',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'コピー -:value',
        'copy-of'                       => 'コピー :value',
        'variant-already-exist-message' => '同じ属性オプションを持つバリアントがすでに存在します。',
    ],

    'response' => [
        'product-can-not-be-copied' => 'タイプ :type の製品はコピーできません',
    ],

    'type'     => [
        'configurable'  => '設定可能',
        'simple'        => 'シンプル',
        'variant-group' => 'バリアントグループ',
        'variant_group' => 'バリアントグループ',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'テンプレートの循環参照が検出されました: :path',
            'currency-not-allowed'    => '通貨を指定できるのは価格属性のみですが、":attribute" は価格属性ではありません。',
            'currency-not-enabled'    => 'テンプレートで使用されている通貨 ":currency" はどのチャネルでも有効になっていません。',
            'currency-not-in-channel' => '通貨 ":currency" はチャネル ":channel" で有効になっていません。',
            'filter-failed'           => 'フィルター ":filter" を ":attribute" の値に適用できませんでした。',
            'filter-not-supported'    => 'フィルター ":filter" は属性 ":attribute" に適用できません。',
            'format-date-position'    => 'format_date フィルターは ":expression" の最初のフィルターである必要があります。',
            'inside-html-tag'         => 'テンプレート式は、リンクや画像などの HTML タグや属性の内部には配置できません。',
            'invalid-filter-argument' => '":expression" 内のフィルター ":filter" の引数が不足しているか、無効です。',
            'invalid-reference'       => '":attribute" に保存されているテンプレートは無効なため、レンダリングできませんでした。',
            'invalid-syntax'          => 'テンプレート式 ":expression" は無効です。属性コードの後に任意のフィルターを続けて指定してください (例: name | upper)。',
            'max-depth'               => 'テンプレート参照が ":attribute" で :max 階層を超えてネストされています。',
            'missing-value'           => '属性 ":attribute" には、このチャネルとロケールの値がありません。',
            'not-in-family'           => 'テンプレートで使用されている属性 ":attribute" は、製品の属性ファミリに属していません。',
            'output-too-large'        => 'レンダリングされたテンプレートが :max バイトを超えたため、切り詰められました。',
            'self-reference'          => 'テンプレートは自身の属性 ":attribute" を参照できません。',
            'single-brace'            => '単一の波括弧で囲まれた ":text" が見つかりました。テンプレート式には二重の波括弧が必要です。',
            'source-too-long'         => 'テンプレートの長さは最大 :max バイトです。',
            'too-complex'             => 'テンプレートが複雑すぎるため確認できません。短くするか簡略化してください。',
            'too-many-expressions'    => 'テンプレートに含めることができる式は最大 :max 個です。',
            'too-many-filters'        => 'テンプレート式 ":expression" で使用できるフィルターは最大 :max 個です。',
            'unclosed-expression'     => 'テンプレート式が開始されましたが、閉じられていません。',
            'unknown-attribute'       => 'テンプレートで使用されている属性 ":attribute" は存在しません。',
            'unknown-currency'        => 'テンプレートで使用されている通貨 ":currency" は存在しません。',
            'unknown-filter'          => 'フィルター ":filter" はサポートされていません。使用可能なフィルター: :filters',
            'unsupported-attribute'   => '属性 ":attribute" はタイプがサポートされていないため、テンプレートで使用できません。',
        ],
    ],
];
