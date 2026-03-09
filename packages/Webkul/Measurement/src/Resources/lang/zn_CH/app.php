<?php

return [

    'attribute' => [
        'measurement' => '计量',
    ],

    'measurement' => [
        'index' => [
            'create'   => '创建计量单位组',
            'code'     => '代码',
            'standard' => '标准单位代码',
            'symbol'   => '符号',
            'save'     => '保存',
        ],

        'edit' => [
            'measurement_edit' => '编辑计量单位组',
            'back'             => '返回',
            'save'             => '保存',
            'general'          => '通用',
            'code'             => '代码',
            'label'            => '标签',
            'units'            => '单位',
            'create_units'     => '创建单位',
        ],

        'unit' => [
            'edit_unit'   => '编辑单位',
            'create_unit' => '创建单位',
            'symbol'      => '符号',
            'save'        => '保存',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => '计量单位组',
        'measurement_family'   => '计量单位组',
        'measurement_unit'     => '计量单位',
    ],

    'datagrid' => [
        'labels'        => '标签',
        'code'          => '代码',
        'standard_unit' => '标准单位',
        'unit_count'    => '单位数量',
        'is_standard'   => '标记为标准单位',
    ],

    'messages' => [
        'family' => [
            'updated'      => '计量单位组已成功更新。',
            'deleted'      => '计量单位组已成功删除。',
            'mass_deleted' => '所选计量单位组已成功删除。',
        ],

        'unit' => [
            'not_found'         => '未找到计量单位组。',
            'already_exists'    => '单位代码已存在。',
            'not_foundd'        => '未找到单位。',
            'deleted'           => '单位已成功删除。',
            'no_items_selected' => '未选择任何项目。',
            'mass_deleted'      => '所选计量单位已成功删除。',
        ],
    ],

];
