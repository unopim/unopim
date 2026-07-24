<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publikasi',
            'info'     => 'Lapisan penyajian publik untuk konten yang dipublikasikan per bahasa.',
            'settings' => [
                'title'                  => 'Pengaturan Publikasi',
                'enabled'                => 'Diaktifkan',
                'enabled-hint'           => 'Sakelar utama untuk tingkat penyajian publik. Saat nonaktif, setiap URL paspor publik mengembalikan 404 dan menu paspor disembunyikan.',
                'base-url'               => 'URL Dasar',
                'base-url-hint'          => 'Alamat publik tempat paspor disajikan, digunakan untuk membuat kode QR dan tautan yang dapat dibagikan. Biarkan kosong untuk menggunakan domain situs ini sendiri.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl'              => 'TTL Cache (detik)',
                'cache-ttl-hint'         => 'Berapa lama paspor publik yang telah dirender disimpan dalam cache sebelum dibangun ulang. Nilai lebih tinggi mengurangi beban; nilai lebih rendah mencerminkan perubahan lebih cepat.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit'             => 'Batas Laju (permintaan/menit)',
                'rate-limit-hint'        => 'Jumlah maksimum permintaan paspor publik yang diizinkan setiap menit dari satu pengunjung sebelum dibatasi.',
                'rate-limit-placeholder' => '60',
                'indexable'              => 'Izinkan pengindeksan mesin pencari',
                'indexable-hint'         => 'Izinkan mesin pencari mengindeks halaman paspor publik. Nonaktifkan agar paspor tetap dapat diakses melalui tautan tetapi tersembunyi dari hasil pencarian.',
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
        'product-delete-blocked' => 'Produk ini tidak dapat dihapus selama masih memiliki paspor yang diterbitkan. Tarik kembali terlebih dahulu.',
        'channel-delete-blocked' => 'Saluran ini tidak dapat dihapus selama masih memiliki paspor yang diterbitkan. Tarik kembali terlebih dahulu.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Paspor tidak ditemukan.',
            'notice'  => 'Paspor produk ini tidak tersedia. Mungkin belum dipublikasikan, atau tautannya salah.',
        ],
        '429' => [
            'heading' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.',
            'notice'  => 'Anda telah melakukan terlalu banyak permintaan. Harap tunggu sebentar dan coba lagi.',
        ],
        'withdrawn' => [
            'heading' => 'Paspor ini sudah tidak tersedia lagi.',
            'notice'  => 'Catatan ini disimpan untuk transparansi, tetapi tidak lagi dikelola secara aktif.',
        ],
    ],
];
