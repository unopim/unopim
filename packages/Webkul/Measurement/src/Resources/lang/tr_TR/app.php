<?php

return [

    'attribute' => [
        'measurement' => 'Ölçüm',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Ölçüm Ailesi Oluştur',
            'code'     => 'Kod',
            'standard' => 'Standart Birim Kodu',
            'symbol'   => 'Sembol',
            'save'     => 'Kaydet',
        ],

        'edit' => [
            'measurement_edit' => 'Ölçüm Ailesini Düzenle',
            'back'             => 'Geri',
            'save'             => 'Kaydet',
            'general'          => 'Genel',
            'code'             => 'Kod',
            'label'            => 'Etiket',
            'units'            => 'Birimler',
            'create_units'     => 'Birim Oluştur',
        ],

        'unit' => [
            'edit_unit'   => 'Birim Düzenle',
            'create_unit' => 'Birim Oluştur',
            'symbol'      => 'Sembol',
            'save'        => 'Kaydet',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Ölçüm Aileleri',
        'measurement_family'   => 'Ölçüm Ailesi',
        'measurement_unit'     => 'Ölçüm Birimi',
    ],

    'datagrid' => [
        'labels'        => 'Etiketler',
        'code'          => 'Kod',
        'standard_unit' => 'Standart Birim',
        'unit_count'    => 'Birim Sayısı',
        'is_standard'   => 'Standart Birim Olarak İşaretle',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Ölçüm ailesi başarıyla güncellendi.',
            'deleted'      => 'Ölçüm ailesi başarıyla silindi.',
            'mass_deleted' => 'Seçilen ölçüm aileleri başarıyla silindi.',
        ],

        'unit' => [
            'not_found'         => 'Ölçüm ailesi bulunamadı.',
            'already_exists'    => 'Birim kodu zaten mevcut.',
            'not_foundd'        => 'Birim bulunamadı.',
            'deleted'           => 'Birim başarıyla silindi.',
            'no_items_selected' => 'Hiçbir öğe seçilmedi.',
            'mass_deleted'      => 'Seçilen ölçüm birimleri başarıyla silindi.',
        ],
    ],

];
