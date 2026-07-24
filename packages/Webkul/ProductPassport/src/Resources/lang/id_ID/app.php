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
                'title'                              => 'Pengaturan Paspor Produk',
                'enabled'                            => 'Diaktifkan',
                'enabled-hint'                       => 'Aktifkan fitur Paspor Produk Digital untuk katalog ini. Saat nonaktif, panel dan kisi paspor disembunyikan.',
                'auto-publish'                       => 'Terbitkan otomatis saat menyimpan',
                'auto-publish-hint'                  => 'Terbitkan versi paspor secara otomatis setiap kali produk disimpan dan memenuhi ambang kelengkapan. Biarkan nonaktif untuk menerbitkan secara manual.',
                'completeness-threshold'             => 'Ambang Kelengkapan (%)',
                'completeness-threshold-hint'        => 'Kelengkapan produk minimum, dalam persen, yang diperlukan sebelum paspor dapat diterbitkan untuk suatu bahasa.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Nama Operator Ekonomi',
                'operator-name-hint'                 => 'Nama resmi produsen atau operator ekonomi yang bertanggung jawab, ditampilkan pada setiap paspor publik sebagaimana diwajibkan oleh regulasi ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Alamat Operator Ekonomi',
                'operator-address-hint'              => 'Alamat pos terdaftar operator ekonomi, ditampilkan pada paspor publik untuk keterlacakan.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Perwakilan Resmi UE',
                'operator-eu-rep-hint'               => 'Nama dan kontak perwakilan resmi UE, diperlukan ketika produsen berada di luar UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'URL Dukungan',
                'support-url-hint'                   => 'Halaman publik tempat pelanggan dapat menemukan bantuan atau informasi garansi. Ditampilkan sebagai tautan pada setiap paspor.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Paspor Produk Digital',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Komposisi Material',
        'dpp_substances_of_concern'     => 'Zat yang Perlu Diperhatikan',
        'dpp_recycled_content_pct'      => 'Kandungan Daur Ulang (%)',
        'dpp_carbon_footprint'          => 'Jejak Karbon',
        'dpp_energy_consumption'        => 'Konsumsi Energi',
        'dpp_durability_statement'      => 'Pernyataan Daya Tahan',
        'dpp_repairability_score'       => 'Skor Keterbaikan',
        'dpp_spare_parts_availability'  => 'Ketersediaan Suku Cadang',
        'dpp_care_instructions'         => 'Petunjuk Perawatan',
        'dpp_disassembly_guide'         => 'Panduan Pembongkaran',
        'dpp_manufacturer_name'         => 'Nama Produsen',
        'dpp_manufacturing_site'        => 'Lokasi Produksi',
        'dpp_country_of_origin'         => 'Negara Asal',
        'dpp_supply_chain_notes'        => 'Catatan Rantai Pasok',
        'dpp_end_of_life_instructions'  => 'Petunjuk Akhir Masa Pakai',
        'dpp_take_back_scheme'          => 'Skema Pengembalian',
        'dpp_declaration_of_conformity' => 'Deklarasi Kesesuaian',
        'dpp_test_reports'              => 'Laporan Pengujian',
        'dpp_certificates'              => 'Sertifikat',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Pengenal Model',
        'dpp_batch_identifier'          => 'Pengenal Batch',
        'dpp_warranty_terms'            => 'Ketentuan Garansi',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Atribut Paspor Produk Digital berhasil dipasang.',
        ],
    ],

    'public' => [
        'badge'         => 'Paspor Produk Digital EU',
        'search-locale' => 'Bahasa pencarian',
        'sections'      => [
            'passport' => 'Paspor Produk',
        ],
        'title'      => 'Paspor Produk Digital',
        'identifier' => [
            'title'        => 'Identifikasi',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Batch',
            'not-provided' => 'Tidak disediakan',
        ],
        'operator' => [
            'title' => 'Operator Ekonomi',
        ],
        'documents' => [
            'title' => 'Dokumen',
        ],
    ],

    'publications' => [
        'not-found'      => 'Tidak ada paspor untuk id :id.',
        'index'          => [
            'disabled-notice' => 'Penerbitan paspor saat ini dinonaktifkan. Paspor yang ada ditampilkan di bawah untuk pengelolaan (lihat dan tarik).',
            'title'           => 'Paspor Produk Digital',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Saluran',
            'status'          => 'Status',
            'live-locales'    => 'Bahasa Aktif',
            'last-published'  => 'Terakhir Diterbitkan',
            'withdraw'        => 'Tarik Kembali',
        ],
        'publish-queued' => 'Penerbitan paspor telah diantrekan.',
        'withdrawn'      => 'Paspor berhasil ditarik kembali.',
        'mass-publish'   => [
            'action' => 'Terbitkan Paspor Produk Digital',
            'queued' => 'Penerbitan paspor diantrekan untuk :count produk.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Paspor',
            'view'     => 'Lihat',
            'publish'  => 'Terbitkan',
            'withdraw' => 'Tarik Kembali',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Paspor',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Menerbitkan…',
                    'queued'               => 'Dalam antrean',
                    'copy-operator-link'   => 'Salin tautan operator',
                    'copy-authority-link'  => 'Salin tautan otoritas',
                    'link-copied'          => 'Tautan disalin',
                    'download-qr'          => 'Unduh kode QR',
                    'title'                => 'Paspor Produk Digital',
                    'publishing-disabled'  => 'Penerbitan paspor dinonaktifkan untuk saluran ini.',
                    'locale'               => 'Bahasa',
                    'version'              => 'Versi',
                    'published-at'         => 'Diterbitkan Pada',
                    'missing-fields'       => 'Bidang yang Hilang',
                    'not-published'        => 'Belum diterbitkan',
                    'unscored'             => 'Belum dinilai',
                    'publish'              => 'Terbitkan',
                    'republish'            => 'Terbitkan ulang',
                    'publish-all'          => 'Terbitkan semua bahasa',
                    'auto-publish-on'      => 'Penerbitan otomatis aktif — paspor diterbitkan secara otomatis saat produk disimpan dan memenuhi ambang kelengkapan. Gunakan tombol untuk menerbitkan sekarang.',
                    'auto-publish-off'     => 'Penerbitan manual — gunakan tombol untuk menerbitkan paspor produk ini untuk setiap bahasa.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute harus berupa GTIN yang valid (8, 12, 13, atau 14 digit dengan digit pemeriksa yang benar).',
    ],
    'mapping' => [
        'title'         => 'Pemetaan Bidang Paspor',
        'info'          => 'Ambil setiap bidang paspor dari atribut yang sudah Anda kelola. Biarkan bidang tidak dipetakan untuk kembali ke atribut paspor khususnya.',
        'menu'          => 'Pemetaan Bidang',
        'field'         => 'Bidang Paspor',
        'source'        => 'Atribut Sumber',
        'select-source' => 'Gunakan atribut paspor',
        'save-btn'      => 'Simpan Pemetaan',
        'type-mismatch' => 'Sumber yang dipilih tidak kompatibel dengan tipe bidang paspor ini.',
        'saved'         => 'Pemetaan bidang berhasil disimpan.',
    ],

];
