<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publikasi',
            'info'     => 'Lapisan penyajian publik untuk konten yang dipublikasikan per bahasa.',
            'settings' => [
                'title'      => 'Pengaturan Publikasi',
                'enabled'    => 'Diaktifkan',
                'base-url'   => 'URL Dasar',
                'cache-ttl'  => 'TTL Cache (detik)',
                'rate-limit' => 'Batas Laju (permintaan/menit)',
                'indexable'  => 'Izinkan pengindeksan mesin pencari',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Draf',
            'published' => 'Diterbitkan',
            'withdrawn' => 'Ditarik',
            'redacted'  => 'Disunting',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Paspor tidak ditemukan.',
        ],
        '429' => [
            'heading' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.',
        ],
        'withdrawn' => [
            'heading' => 'Paspor ini sudah tidak tersedia lagi.',
        ],
    ],
];
