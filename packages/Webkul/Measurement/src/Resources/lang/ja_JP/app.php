<?php

return [

    'acl' => [
        'unauthorized' => 'この操作を実行する権限がありません。',
    ],
    'attribute' => [
        'measurement' => '測定',
    ],

    'measurement' => [
        'index' => [
            'create'                => '測定ファミリーを作成',
            'code'                  => 'コード',
            'standard'              => '標準単位コード',
            'symbol'                => '記号',
            'save'                  => '保存',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => '測定ファミリーを編集',
            'back'                  => '戻る',
            'save'                  => '保存',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => '一般',
            'code'                  => 'コード',
            'label'                 => 'ラベル',
            'units'                 => '単位',
            'create_units'          => '単位を作成',
        ],

        'unit' => [
            'edit_unit'             => '単位を編集',
            'create_unit'           => '単位を作成',
            'symbol'                => '記号',
            'save'                  => '保存',
            'conversion_operation'  => '変換操作',
            'add_new_operation'     => '新しい操作を追加',
            'conversion_value'      => '値',
            'conversion_operator'   => '演算子',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => '測定ファミリー',
        'measurement_family'   => '測定ファミリー',
        'measurement_unit'     => '測定単位',
    ],

    'datagrid' => [
        'labels'        => '名前',
        'code'          => 'コード',
        'standard_unit' => '標準単位',
        'unit_count'    => '単位数',
        'is_standard'   => '標準単位としてマーク',
    ],

    'importers' => [
        'products' => [
            'validation' => [
                'invalid-unit' => '単位「:unit」は測定属性「:attribute」の有効な単位ではありません。',
            ],
        ],
    ],

    'messages' => [
        'family' => [
            'created'      => '測定ファミリーが正常に作成されました。',
            'updated'      => '測定ファミリーが正常に更新されました。',
            'deleted'      => '測定ファミリーが正常に削除されました。',
            'mass_deleted' => '選択された測定ファミリーが正常に削除されました。',
        ],

        'unit' => [
            'not_found'              => '測定ファミリーが見つかりません。',
            'already_exists'         => '単位コードは既に存在します。',
            'units_not_found'        => '単位が見つかりません。',
            'deleted'                => '単位が正常に削除されました。',
            'no_items_selected'      => 'アイテムが選択されていません。',
            'mass_deleted'           => '選択された測定単位が正常に削除されました。',
        ],
    ],

];
