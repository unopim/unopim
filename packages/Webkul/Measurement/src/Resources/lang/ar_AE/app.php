<?php

return [

    'attribute' => [
        'measurement' => 'القياس',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'إنشاء عائلة القياس',
            'code'     => 'الرمز',
            'standard' => 'رمز الوحدة القياسية',
            'symbol'   => 'الرمز',
            'save'     => 'حفظ',
        ],

        'edit' => [
            'measurement_edit' => 'تعديل عائلة القياس',
            'back'             => 'رجوع',
            'save'             => 'حفظ',
            'general'          => 'عام',
            'code'             => 'الرمز',
            'label'            => 'التسمية',
            'units'            => 'الوحدات',
            'create_units'     => 'إنشاء وحدات',
        ],

        'unit' => [
            'edit_unit'   => 'تعديل الوحدة',
            'create_unit' => 'إنشاء وحدة',
            'symbol'      => 'الرمز',
            'save'        => 'حفظ',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'عائلات القياس',
        'measurement_family'   => 'عائلة القياس',
        'measurement_unit'     => 'وحدة القياس',
    ],

    'datagrid' => [
        'labels'        => 'التسميات',
        'code'          => 'الرمز',
        'standard_unit' => 'الوحدة القياسية',
        'unit_count'    => 'عدد الوحدات',
        'is_standard'   => 'تحديد كوحدة قياسية',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'تم تحديث عائلة القياس بنجاح.',
            'deleted'      => 'تم حذف عائلة القياس بنجاح.',
            'mass_deleted' => 'تم حذف عائلات القياس المحددة بنجاح.',
        ],

        'unit' => [
            'not_found'         => 'عائلة القياس غير موجودة.',
            'already_exists'    => 'رمز الوحدة موجود بالفعل.',
            'not_foundd'        => 'الوحدة غير موجودة.',
            'deleted'           => 'تم حذف الوحدة بنجاح.',
            'no_items_selected' => 'لم يتم تحديد أي عناصر.',
            'mass_deleted'      => 'تم حذف وحدات القياس المحددة بنجاح.',
        ],
    ],

];
