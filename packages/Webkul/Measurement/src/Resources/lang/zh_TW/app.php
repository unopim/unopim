<?php

return [

    'acl' => [
        'unauthorized' => '您沒有執行此操作的權限。',
    ],
    'attribute' => [
        'measurement' => '計量',
    ],

    'measurement' => [
        'index' => [
            'create'                => '建立計量單位群組',
            'code'                  => '代碼',
            'standard'              => '標準單位代碼',
            'symbol'                => '符號',
            'save'                  => '儲存',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => '編輯計量單位群組',
            'back'                  => '返回',
            'save'                  => '儲存',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => '一般',
            'code'                  => '代碼',
            'label'                 => '標籤',
            'units'                 => '單位',
            'create_units'          => '建立單位',
        ],

        'unit' => [
            'edit_unit'             => '編輯單位',
            'create_unit'           => '建立單位',
            'symbol'                => '符號',
            'save'                  => '儲存',
            'conversion_operation'  => '轉換操作',
            'add_new_operation'     => '新增操作',
            'conversion_value'      => '值',
            'conversion_operator'   => '運算符',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => '計量單位群組',
        'measurement_family'   => '計量單位群組',
        'measurement_unit'     => '計量單位',
    ],

    'datagrid' => [
        'labels'        => '名稱',
        'code'          => '代碼',
        'standard_unit' => '標準單位',
        'unit_count'    => '單位數量',
        'is_standard'   => '標記為標準單位',
    ],

    'importers' => [
        'products' => [
            'validation' => [
                'invalid-unit' => '單位「:unit」不是測量屬性「:attribute」的有效單位。',
            ],
        ],
    ],

    'messages' => [
        'family' => [
            'created'      => '測量系列已成功建立。',
            'updated'      => '計量單位群組已成功更新。',
            'deleted'      => '計量單位群組已成功刪除。',
            'mass_deleted' => '所選計量單位群組已成功刪除。',
        ],

        'unit' => [
            'not_found'              => '找不到計量單位群組。',
            'already_exists'         => '單位代碼已存在。',
            'units_not_found'        => '找不到單位。',
            'deleted'                => '單位已成功刪除。',
            'no_items_selected'      => '未選取任何項目。',
            'mass_deleted'           => '所選計量單位已成功刪除。',
        ],
    ],

];
