<?php

return [

    'attribute' => [
        'measurement' => '計量',
    ],

    'measurement' => [
        'index' => [
            'create'   => '建立計量單位群組',
            'code'     => '代碼',
            'standard' => '標準單位代碼',
            'symbol'   => '符號',
            'save'     => '儲存',
        ],

        'edit' => [
            'measurement_edit' => '編輯計量單位群組',
            'back'             => '返回',
            'save'             => '儲存',
            'general'          => '一般',
            'code'             => '代碼',
            'label'            => '標籤',
            'units'            => '單位',
            'create_units'     => '建立單位',
        ],

        'unit' => [
            'edit_unit'   => '編輯單位',
            'create_unit' => '建立單位',
            'symbol'      => '符號',
            'save'        => '儲存',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => '計量單位群組',
        'measurement_family'   => '計量單位群組',
        'measurement_unit'     => '計量單位',
    ],

    'datagrid' => [
        'labels'        => '標籤',
        'code'          => '代碼',
        'standard_unit' => '標準單位',
        'unit_count'    => '單位數量',
        'is_standard'   => '標記為標準單位',
    ],

    'messages' => [
        'family' => [
            'updated'      => '計量單位群組已成功更新。',
            'deleted'      => '計量單位群組已成功刪除。',
            'mass_deleted' => '所選計量單位群組已成功刪除。',
        ],

        'unit' => [
            'not_found'         => '找不到計量單位群組。',
            'already_exists'    => '單位代碼已存在。',
            'not_foundd'        => '找不到單位。',
            'deleted'           => '單位已成功刪除。',
            'no_items_selected' => '未選取任何項目。',
            'mass_deleted'      => '所選計量單位已成功刪除。',
        ],
    ],

];
