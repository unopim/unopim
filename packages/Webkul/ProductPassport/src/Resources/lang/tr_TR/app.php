<?php

return [
    'type' => [
        'label' => 'Dijital Ürün Pasaportu',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Ürün Pasaportu',
            'info'     => 'Dijital ürün pasaportu yayın ayarları.',
            'settings' => [
                'title'                  => 'Ürün Pasaportu Ayarları',
                'enabled'                => 'Etkin',
                'auto-publish'           => 'Kaydederken otomatik olarak yayınla',
                'completeness-threshold' => 'Tamlık Eşiği (%)',
                'operator-name'          => 'Ekonomik İşletmecinin Adı',
                'operator-address'       => 'Ekonomik İşletmecinin Adresi',
                'operator-eu-rep'        => 'AB Yetkili Temsilcisi',
                'support-url'            => 'Destek URL\'si',
            ],
        ],
    ],
];
