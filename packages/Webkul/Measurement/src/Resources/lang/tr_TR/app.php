<?php

return [

    'acl' => [
        'unauthorized' => 'Bu işlemi gerçekleştirme izniniz yok.',
    ],
    'attribute' => [
        'measurement' => 'Ölçüm',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Ölçüm Ailesi Oluştur',
            'code'                  => 'Kod',
            'standard'              => 'Standart Birim Kodu',
            'symbol'                => 'Sembol',
            'save'                  => 'Kaydet',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Ölçüm Ailesini Düzenle',
            'back'                  => 'Geri',
            'save'                  => 'Kaydet',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Genel',
            'code'                  => 'Kod',
            'label'                 => 'Etiket',
            'units'                 => 'Birimler',
            'create_units'          => 'Birim Oluştur',
        ],

        'unit' => [
            'edit_unit'             => 'Birim Düzenle',
            'create_unit'           => 'Birim Oluştur',
            'symbol'                => 'Sembol',
            'save'                  => 'Kaydet',
            'conversion_operation'  => 'Dönüştürme işlemi',
            'add_new_operation'     => 'Yeni işlem ekle',
            'conversion_value'      => 'Değer',
            'conversion_operator'   => 'Operatör',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Ölçüm Aileleri',
        'measurement_family'   => 'Ölçüm Ailesi',
        'measurement_unit'     => 'Ölçüm Birimi',
    ],

    'datagrid' => [
        'labels'        => 'Ad',
        'code'          => 'Kod',
        'standard_unit' => 'Standart Birim',
        'unit_count'    => 'Birim Sayısı',
        'is_standard'   => 'Standart Birim Olarak İşaretle',
    ],

    'importers' => [
        'products' => [
            'validation' => [
                'invalid-unit' => '":unit" birimi, ":attribute" ölçüm özelliği için geçerli bir birim değil.',
            ],
        ],
    ],

    'messages' => [
        'family' => [
            'created'      => 'Ölçüm ailesi başarıyla oluşturuldu.',
            'updated'      => 'Ölçüm ailesi başarıyla güncellendi.',
            'deleted'      => 'Ölçüm ailesi başarıyla silindi.',
            'mass_deleted' => 'Seçilen ölçüm aileleri başarıyla silindi.',
        ],

        'unit' => [
            'not_found'              => 'Ölçüm ailesi bulunamadı.',
            'already_exists'         => 'Birim kodu zaten mevcut.',
            'units_not_found'        => 'Birim bulunamadı.',
            'deleted'                => 'Birim başarıyla silindi.',
            'no_items_selected'      => 'Hiçbir öğe seçilmedi.',
            'mass_deleted'           => 'Seçilen ölçüm birimleri başarıyla silindi.',
        ],
    ],

];
