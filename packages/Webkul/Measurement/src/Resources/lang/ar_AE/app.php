<?php

return [

    'attribute' => [
        'measurement' => 'القياس',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'إنشاء عائلة القياس',
            'code'                  => 'الرمز',
            'standard'              => 'رمز الوحدة القياسية',
            'symbol'                => 'الرمز',
            'save'                  => 'حفظ',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'تعديل عائلة القياس',
            'back'                  => 'رجوع',
            'save'                  => 'حفظ',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'عام',
            'code'                  => 'الرمز',
            'label'                 => 'التسمية',
            'units'                 => 'الوحدات',
            'create_units'          => 'إنشاء وحدات',
        ],

        'unit' => [
            'edit_unit'             => 'تعديل الوحدة',
            'create_unit'           => 'إنشاء وحدة',
            'symbol'                => 'الرمز',
            'save'                  => 'حفظ',
            'conversion_operation'  => 'عملية التحويل',
            'add_new_operation'     => 'إضافة عملية جديدة',
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
            'created'      => 'تم إنشاء عائلة القياس بنجاح.',
            'updated'      => 'تم تحديث عائلة القياس بنجاح.',
            'deleted'      => 'تم حذف عائلة القياس بنجاح.',
            'mass_deleted' => 'تم حذف عائلات القياس المحددة بنجاح.',
        ],

        'unit' => [
            'not_found'              => 'عائلة القياس غير موجودة.',
            'already_exists'         => 'رمز الوحدة موجود بالفعل.',
            'units_not_found'        => 'الوحدة غير موجودة.',
            'deleted'                => 'تم حذف الوحدة بنجاح.',
            'no_items_selected'      => 'لم يتم تحديد أي عناصر.',
            'mass_deleted'           => 'تم حذف وحدات القياس المحددة بنجاح.',
        ],
    ],

];
