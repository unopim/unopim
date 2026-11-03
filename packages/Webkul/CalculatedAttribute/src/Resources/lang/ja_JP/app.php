<?php

return [

    'attribute' => [
        'calculated' => '計算',
    ],

    'formula' => [
        'title'            => '計算式',
        'label'            => '計算式',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => '値は、商品の表示時、エクスポート時、API 応答時のたびにこの式から導出されます。保存されることはなく、インポートもできません。',
        'reference-hint'   => '他の属性はコードで参照します。',
        'available'        => '利用できる関数',
        'examples'         => '例',
        'example-concat'   => '2 つの属性を連結し、空のものは除外します',
        'example-category' => 'カテゴリ名のパスを # で連結します',
        'example-fallback' => '最初の属性が空のときに 2 番目の属性を使います',
        'read-only'        => '計算',
        'read-only-hint'   => 'この値は他の属性から計算されるため、編集できません。',

        'variables' => [
            'title'      => '利用できる変数',
            'sku'        => '商品の SKU',
            'type'       => '商品タイプ',
            'status'     => '商品ステータス',
            'family'     => '属性ファミリーのコード',
            'parent'     => 'バリアントの場合、親商品の SKU',
            'categories' => '割り当てられたカテゴリコード',
            'channel'    => '描画対象のチャネル',
            'locale'     => '描画対象の言語',
        ],

        'errors' => [
            'syntax'              => '計算式を解析できませんでした: :detail',
            'unknown-variable'    => '計算式に不明な属性または変数「:name」があります。',
            'disallowed-function' => '関数「:name」は計算式では使用できません。',
            'disallowed-operator' => '演算子「:name」は計算式では使用できません。',
            'reserved-variable'   => '名前「:name」は予約されているため、計算式では使用できません。',
            'too-long'            => '計算式は :max 文字以内にしてください。',
            'empty'               => '計算属性には計算式が必要です。',
            'circular'            => '計算式が循環参照を作成しています: :cycle',
            'referenced-by'       => 'この属性は次の属性の計算式で使用されているため削除できません: :attributes',
        ],
    ],

];
