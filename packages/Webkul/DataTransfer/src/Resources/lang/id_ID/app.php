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
        'category-fields' => [
            'title'      => 'Field Kategori',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kode field kategori :code sudah digunakan.',
                    'code_not_found_to_delete' => 'Kode field kategori tidak ditemukan untuk dihapus.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Atribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Kode atribut :code sudah digunakan.',
                    'code_not_found_to_delete'             => 'Kode atribut tidak ditemukan untuk dihapus.',
                    'code_is_system_and_cannot_be_deleted' => 'Atribut sistem tidak dapat dihapus.',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => 'Asosiasi Produk',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Kolom \'%s\' wajib diisi.',
                    'self-link-not-allowed'       => 'Produk \'%s\' tidak dapat diasosiasikan dengan dirinya sendiri.',
                    'sku-not-found'               => 'Produk dengan SKU \'%s\' tidak ditemukan.',
                    'related-sku-not-found'       => 'Produk terkait dengan SKU \'%s\' tidak ditemukan.',
                    'association-type-not-found'  => 'Jenis asosiasi \'%s\' tidak ada atau tidak aktif.',
                    'invalid-field-value'         => 'Nilai tidak valid diberikan untuk kolom asosiasi.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Grup Atribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Kode grup atribut :code sudah digunakan.',
                    'code_not_found_to_delete'             => 'Kode grup atribut tidak ditemukan untuk dihapus.',
                    'code_is_system_and_cannot_be_deleted' => 'Grup atribut sistem tidak dapat dihapus.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Keluarga Atribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kode keluarga atribut :code sudah digunakan.',
                    'code_not_found_to_delete' => 'Kode keluarga atribut tidak ditemukan untuk dihapus.',
                    'invalid-attribute-group'  => 'Grup atribut ":code" tidak ada.',
                    'invalid-attribute'        => 'Atribut ":code" tidak ada.',
                    'invalid-channel'          => 'Saluran ":code" tidak ada.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opsi Atribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kode opsi atribut :code sudah digunakan.',
                    'code_not_found_to_delete' => 'Kode opsi atribut tidak ditemukan untuk dihapus.',
                    'locale-not-exist'         => 'Lokal ":code" tidak ada.',
                    'invalid-attribute'        => 'Atribut ":code" tidak ada.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Bahasa',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Kode bahasa \'%s\' sudah diimpor dalam batch ini.',
                    'code-not-found-to-delete'    => 'Bahasa dengan kode \'%s\' tidak ditemukan di sistem.',
                    'invalid-status'              => 'Status harus 0 atau 1 (atau kosong untuk default aktif).',
                    'channel-related-locale-root' => 'Anda tidak dapat menghapus bahasa dengan kode :code karena terkait dengan channel.',
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
            'title'   => 'Mata uang',
            'filters' => [
                'status' => 'Negara',
                'enable' => 'Aktifkan',
                'all'    => 'Semua',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status harus 0 atau 1 (atau kosong untuk default aktif).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Peran',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Pengguna',
            'filters' => [
                'status' => 'Negara',
                'active' => 'Aktif',
                'all'    => 'Semua',
            ],
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
            'file-format'            => 'Format file',
            'with-media'             => 'Dengan media',
            'with-associations'      => 'Dengan asosiasi',
            'with-associations-info' => 'Sertakan kolom daftar SKU lama (up_sells, cross_sells, dan related_products) dalam ekspor',
            'header-row'             => 'Header Row',
            'header-row-info'        => 'Write attribute codes as the first line',
            'use-labels'             => 'Use Labels',
            'use-labels-info'        => 'Export readable labels instead of codes',
            'date-format'            => 'Date Format',
            'date-format-options'    => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Jalur file',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Negara',
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
                'status'         => 'Negara',
                'status-options' => [
                    'enable'  => 'Aktif',
                    'disable' => 'Nonaktif',
                    'all'     => 'Semua',
                ],
                'sku'              => 'Sku',
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
        'category-fields' => [
            'title' => 'Field Kategori',
        ],
        'attributes' => [
            'title' => 'Atribut',
        ],
        'product-associations' => [
            'title' => 'Asosiasi Produk',
        ],
        'attribute-groups' => [
            'title' => 'Grup Atribut',
        ],
        'attribute-families' => [
            'title' => 'Keluarga Atribut',
        ],
        'attribute-options' => [
            'title' => 'Opsi Atribut',
        ],
        'locales' => [
            'title' => 'Bahasa',
        ],
        'channels' => [
            'title' => 'Saluran',
        ],
        'currencies' => [
            'title' => 'Mata uang',
        ],
        'roles' => [
            'title' => 'Peran',
        ],
        'users' => [
            'title'   => 'Pengguna',
            'filters' => [
                'status' => 'Negara',
                'active' => 'Aktif',
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
