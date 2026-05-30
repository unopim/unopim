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
        'products' => [
            'title'      => 'Produk',
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
        'attribute-groups' => [
            'title' => 'Grup Atribut',
        ],
        'attribute-families' => [
            'title' => 'Keluarga Atribut',
        ],
        'attribute-options' => [
            'title' => 'Opsi Atribut',
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
                'all'    => 'All',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Eksekusi pekerjaan dimulai',
        'completed' => 'Eksekusi pekerjaan selesai',
    ],
];
