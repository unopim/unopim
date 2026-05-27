<?php

return [

    'attribute' => [
        'measurement' => 'Хэмжилт',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Хэмжих гэр бүл үүсгэх',
            'code'                  => 'Код',
            'standard'              => 'Стандарт нэгжийн код',
            'symbol'                => 'Бэлгэ тэмдэг',
            'save'                  => 'Хадгалах',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Хэмжих гэр бүл засах',
            'back'                  => 'Буцах',
            'save'                  => 'Хадгалах',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Ерөнхий',
            'code'                  => 'Код',
            'label'                 => 'Тэмдэглэгээ',
            'units'                 => 'Нэгжүүд',
            'create_units'          => 'Нэгж үүсгэх',
        ],

        'unit' => [
            'edit_unit'             => 'Нэгж засах',
            'create_unit'           => 'Нэгж үүсгэх',
            'symbol'                => 'Бэлгэ тэмдэг',
            'save'                  => 'Хадгалах',
            'conversion_operation'  => 'Хөрвүүлэх үйлдэл',
            'add_new_operation'     => 'Шинэ үйлдэл нэмэх',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Хэмжих гэр бүлүүд',
        'measurement_family'   => 'Хэмжих гэр бүл',
        'measurement_unit'     => 'Хэмжих нэгж',
    ],

    'datagrid' => [
        'labels'        => 'Тэмдэглэгээ',
        'code'          => 'Код',
        'standard_unit' => 'Стандарт нэгж',
        'unit_count'    => 'Нэгжүүдийн тоо',
        'is_standard'   => 'Стандарт нэгжээр тэмдэглэх',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Хэмжилтийн бүлэг амжилттай үүсгэгдлээ.',
            'updated'      => 'Хэмжих гэр бүл амжилттай шинэчлэгдлээ.',
            'deleted'      => 'Хэмжих гэр бүл амжилттай устгагдлаа.',
            'mass_deleted' => 'Сонгосон хэмжих гэр бүлүүд амжилттай устгагдлаа.',
        ],

        'unit' => [
            'not_found'         => 'Хэмжих гэр бүл олдсонгүй.',
            'already_exists'    => 'Нэгжийн код аль хэдийн оршиж байна.',
            'units_not_found'        => 'Нэгж олдсонгүй.',
            'deleted'           => 'Нэгж амжилттай устгагдлаа.',
            'no_items_selected' => 'Сонгосон зүйл байхгүй.',
            'mass_deleted'      => 'Сонгосон хэмжих нэгжүүд амжилттай устгагдлаа.',
        ],
    ],

];
