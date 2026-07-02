<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produk',
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
        'channels' => [
            'title'      => 'Saluran',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Saluran dengan kode :code tidak ditemukan untuk dihapus.',
                    'locale-not-found'         => 'Satu atau lebih bahasa tidak ada.',
                    'root-category-not-found'  => 'Kategori utama tidak ada.',
                    'currency-not-found'       => 'Satu atau lebih mata uang tidak ada.',
                    'invalid-locale'           => 'Bahasa tidak ada.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => 'Ekspor ini terlalu besar untuk dijalankan: perkiraan :rows baris × :columns kolom (~:estimated) melebihi ruang yang tersedia (~:available). Persempit ekspor dengan memilih lebih sedikit saluran/lokal (dan atribut) lalu coba lagi.',
        'fields'           => [
            'file-format'         => 'Format file',
            'with-media'          => 'Dengan media',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Aktif',
            'all'            => 'Semua',
        ],
        'products' => [
            'title'              => 'Produk',
            'invalid-locales'    => 'Tidak semua lokal yang dipilih tersedia untuk saluran yang dipilih.',
            'invalid-currencies' => 'Tidak semua mata uang yang dipilih tersedia untuk saluran yang dipilih.',
            'filters'            => [
                'channels'             => 'Saluran',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Mata uang',
                'currencies-info'      => 'Atribut harga diekspor per mata uang yang dipilih. Biarkan kosong untuk mengekspor semua mata uang saluran.',
                'locales'              => 'Lokal',
                'locales-info'         => 'Atribut yang dapat dilokalkan diekspor sekali per lokal yang dipilih. Biarkan kosong untuk mengekspor semua lokal saluran.',
                'attributes'           => 'Atribut',
                'attributes-info'      => 'Hanya atribut yang dipilih yang diekspor. Biarkan kosong untuk mengekspor semua atribut dalam keluarga.',
                'attribute-families'   => 'Keluarga atribut',
                'categories'           => 'Kategori',
                'completeness'         => 'Kelengkapan',
                'completeness-options' => [
                    'none'         => 'Tidak ada kondisi kelengkapan',
                    'at-least-one' => 'Lengkap pada setidaknya satu lokal yang dipilih',
                    'all'          => 'Lengkap pada semua lokal yang dipilih',
                ],
                'time-condition' => 'Kondisi waktu',
                'time-options'   => [
                    'none'              => 'Tidak ada kondisi tanggal',
                    'last-n-days'       => 'Produk yang diperbarui dalam N hari terakhir',
                    'between-dates'     => 'Produk yang diperbarui di antara dua tanggal',
                    'since-last-export' => 'Produk yang diperbarui sejak ekspor terakhir',
                ],
                'time-value'     => 'Jumlah hari',
                'time-date'      => 'Tanggal mulai',
                'time-date-end'  => 'Tanggal selesai',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Aktif',
                    'disable' => 'Nonaktif',
                    'all'     => 'Semua',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Pengidentifikasi',
                'identifiers-info' => 'Tempel satu SKU / pengidentifikasi per baris untuk mengekspor hanya produk tersebut. Biarkan kosong untuk mengekspor semua produk.',
            ],
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
        'channels' => [
            'title' => 'Saluran',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'Semua',
            ],
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
            'file-empty'           => 'File kosong atau tidak memiliki baris header. Silakan unggah file yang valid dengan data.',
        ],
    ],
    'job' => [
        'started'   => 'Eksekusi pekerjaan dimulai',
        'completed' => 'Eksekusi pekerjaan selesai',
    ],
];
