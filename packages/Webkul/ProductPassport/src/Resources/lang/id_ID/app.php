<?php

return [
    'type' => [
        'label' => 'Paspor Produk Digital',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Paspor Produk',
            'info'     => 'Pengaturan penerbitan Paspor Produk Digital.',
            'settings' => [
                'title'                  => 'Pengaturan Paspor Produk',
                'enabled'                => 'Diaktifkan',
                'auto-publish'           => 'Terbitkan otomatis saat menyimpan',
                'completeness-threshold' => 'Ambang Kelengkapan (%)',
                'operator-name'          => 'Nama Operator Ekonomi',
                'operator-address'       => 'Alamat Operator Ekonomi',
                'operator-eu-rep'        => 'Perwakilan Resmi UE',
                'support-url'            => 'URL Dukungan',
            ],
        ],
    ],
];
