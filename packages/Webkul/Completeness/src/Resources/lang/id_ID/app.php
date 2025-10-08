<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Kelengkapan',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Kelengkapan berhasil diperbarui',
                    'title'               => 'Kelengkapan',
                    'configure'           => 'Konfigurasi Kelengkapan',
                    'channel-required'    => 'Diperlukan di Saluran',
                    'save-btn'            => 'Simpan',
                    'back-btn'            => 'Kembali',
                    'mass-update-success' => 'Kelengkapan berhasil diperbarui',

                    'datagrid' => [
                        'code'             => 'Kode',
                        'name'             => 'Nama',
                        'channel-required' => 'Diperlukan di Saluran',

                        'actions' => [
                            'change-requirement' => 'Ubah Persyaratan Kelengkapan',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Tidak ada pengaturan',
                    'completeness'                 => 'Lengkap',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Kelengkapan',
                    'subtitle' => 'Rata-rata kelengkapan',
                ],

                'required-attributes' => 'atribut wajib yang hilang',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produk yang dihitung',

                'suggestion' => [
                    'low'     => 'Kelengkapan rendah — tambahkan detail untuk meningkatkan.',
                    'medium'  => 'Lanjutkan, terus tambahkan informasi.',
                    'high'    => 'Hampir lengkap, hanya beberapa detail tersisa.',
                    'perfect' => 'Informasi produk sepenuhnya lengkap.',
                ],
            ],
        ],
    ],
];
