<?php

return [

    'attribute' => [
        'measurement' => 'Хэмжилт',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Хэмжих гэр бүл үүсгэх',
            'code'     => 'Код',
            'standard' => 'Стандарт нэгжийн код',
            'symbol'   => 'Бэлгэ тэмдэг',
            'save'     => 'Хадгалах',
        ],

        'edit' => [
            'measurement_edit' => 'Хэмжих гэр бүл засах',
            'back'             => 'Буцах',
            'save'             => 'Хадгалах',
            'general'          => 'Ерөнхий',
            'code'             => 'Код',
            'label'            => 'Тэмдэглэгээ',
            'units'            => 'Нэгжүүд',
            'create_units'     => 'Нэгж үүсгэх',
        ],

        'unit' => [
            'edit_unit'   => 'Нэгж засах',
            'create_unit' => 'Нэгж үүсгэх',
            'symbol'      => 'Бэлгэ тэмдэг',
            'save'        => 'Хадгалах',
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
            'updated'      => 'Хэмжих гэр бүл амжилттай шинэчлэгдлээ.',
            'deleted'      => 'Хэмжих гэр бүл амжилттай устгагдлаа.',
            'mass_deleted' => 'Сонгосон хэмжих гэр бүлүүд амжилттай устгагдлаа.',
        ],

        'unit' => [
            'not_found'         => 'Хэмжих гэр бүл олдсонгүй.',
            'already_exists'    => 'Нэгжийн код аль хэдийн оршиж байна.',
            'not_foundd'        => 'Нэгж олдсонгүй.',
            'deleted'           => 'Нэгж амжилттай устгагдлаа.',
            'no_items_selected' => 'Сонгосон зүйл байхгүй.',
            'mass_deleted'      => 'Сонгосон хэмжих нэгжүүд амжилттай устгагдлаа.',
        ],
    ],

];
