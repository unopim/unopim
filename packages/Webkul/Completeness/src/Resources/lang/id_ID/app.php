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
                    'configure'           => 'Konfigurasi kelengkapan',
                    'channel-required'    => 'Diperlukan di saluran',
                    'save-btn'            => 'Simpan',
                    'back-btn'            => 'Kembali',
                    'mass-update-success' => 'Kelengkapan berhasil diperbarui',
                    'datagrid'            => [
                        'code'             => 'Kode',
                        'name'             => 'Nama',
                        'channel-required' => 'Diperlukan di saluran',
                        'actions'          => [
                            'change-requirement' => 'Ubah persyaratan kelengkapan',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'T/T',
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
    'notifications' => [
        'completeness-title'             => 'Perhitungan kelengkapan selesai',
        'completeness-calculated'        => 'Kelengkapan dihitung untuk :count produk.',
        'completeness-calculated-family' => 'Kelengkapan dihitung untuk :count produk dalam keluarga ":family".',
        'email-subject'                  => 'Perhitungan kelengkapan selesai',
        'email-greeting'                 => 'Halo,',
        'email-body'                     => 'Perhitungan kelengkapan telah selesai untuk :count produk.',
        'email-body-family'              => 'Perhitungan kelengkapan telah selesai untuk :count produk dalam keluarga atribut ":family".',
        'email-footer'                   => 'Anda dapat melihat detail kelengkapan di dasbor Anda.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produk yang dihitung',
                'suggestion'          => [
                    'low'     => 'Kelengkapan rendah, tambahkan detail untuk meningkatkan.',
                    'medium'  => 'Terus lanjutkan, terus tambahkan informasi.',
                    'high'    => 'Hampir lengkap, hanya beberapa detail tersisa.',
                    'perfect' => 'Informasi produk sudah sepenuhnya lengkap.',
                ],
            ],
        ],
    ],
];
