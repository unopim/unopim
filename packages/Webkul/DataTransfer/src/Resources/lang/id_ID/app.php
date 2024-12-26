<?php

return [
    'importers' => [

        'products' => [
            'title' => 'Produk',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Kunci URL: \'%s\' telah dibuat untuk item dengan SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Nilai tidak valid untuk kolom keluarga atribut (keluarga atribut tidak ada?)',
                    'invalid-type'                             => 'Jenis produk tidak valid atau tidak didukung',
                    'sku-not-found'                            => 'Produk dengan SKU tertentu tidak ditemukan',
                    'super-attribute-not-found'                => 'Atribut yang dapat dikonfigurasi dengan kode :code tidak ditemukan atau tidak termasuk dalam keluarga atribut :familyCode',
                    'configurable-attributes-not-found'        => 'Atribut yang dapat dikonfigurasi diperlukan untuk membuat model produk',
                    'configurable-attributes-wrong-type'       => 'Hanya atribut jenis tertentu yang bukan berbasis lokal atau saluran yang diizinkan menjadi atribut yang dapat dikonfigurasi untuk produk yang dapat dikonfigurasi',
                    'variant-configurable-attribute-not-found' => 'Atribut varian yang dapat dikonfigurasi :code diperlukan untuk pembuatan',
                    'not-unique-variant-product'               => 'Produk dengan atribut yang dapat dikonfigurasi sama sudah ada.',
                    'channel-not-exist'                        => 'Saluran ini tidak ada.',
                    'locale-not-in-channel'                    => 'Lokal ini tidak dipilih di saluran.',
                    'locale-not-exist'                         => 'Lokal ini tidak ada',
                    'not-unique-value'                         => 'Nilai :code harus unik.',
                    'incorrect-family-for-variant'             => 'Keluarga tersebut harus sama dengan keluarga orang tua',
                    'parent-not-exist'                         => 'Orang tua tidak ada.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategori',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Anda tidak dapat menghapus kategori akar yang dikaitkan dengan suatu saluran',
                ],
            ],
        ],
    ],

    'exporters' => [

        'products' => [
            'title' => 'Produk',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Kunci URL: \'%s\' telah dibuat untuk item dengan SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Nilai tidak valid untuk kolom keluarga atribut (keluarga atribut tidak ada?)',
                    'invalid-type'              => 'Jenis produk tidak valid atau tidak didukung',
                    'sku-not-found'             => 'Produk dengan SKU tertentu tidak ditemukan',
                    'super-attribute-not-found' => 'Atribut super dengan kode: \'%s\' tidak ditemukan atau tidak termasuk dalam kelompok atribut: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategori',
        ],
    ],

    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolom nomor "%s" memiliki header kosong.',
            'column-name-invalid'  => 'Nama kolom tidak valid: "%s".',
            'column-not-found'     => 'Kolom yang wajib diisi tidak ditemukan: %s.',
            'column-numbers'       => 'Jumlah kolom tidak sesuai dengan jumlah baris pada header.',
            'invalid-attribute'    => 'Header berisi atribut yang tidak valid: "%s".',
            'system'               => 'Terjadi kesalahan sistem yang tidak terduga.',
            'wrong-quotes'         => 'Kutipan keriting digunakan sebagai pengganti tanda kutip lurus.',
        ],
    ],

    'job' => [
        'started'   => 'Eksekusi pekerjaan dimulai',
        'completed' => 'Eksekusi pekerjaan selesai',
    ],
];
