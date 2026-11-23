<?php

return [
    'acl' => [
        'variant-structures' => 'Struktur Varian',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'salinan - :value',
        'copy-of'                       => 'salinan :value',
        'variant-already-exist-message' => 'Varian dengan opsi atribut yang sama sudah ada.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produk bertipe :type tidak dapat disalin',
    ],

    'type'     => [
        'configurable'  => 'Dapat dikonfigurasi',
        'simple'        => 'Sederhana',
        'variant-group' => 'Grup Varian',
        'variant_group' => 'Grup Varian',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Terdeteksi referensi templat melingkar: :path.',
            'currency-not-allowed'    => 'Hanya atribut harga yang menerima mata uang, tetapi ":attribute" bukan atribut harga.',
            'currency-not-enabled'    => 'Mata uang ":currency" yang digunakan dalam templat tidak diaktifkan untuk saluran mana pun.',
            'currency-not-in-channel' => 'Mata uang ":currency" tidak diaktifkan untuk saluran ":channel".',
            'filter-failed'           => 'Filter ":filter" tidak dapat diterapkan pada nilai ":attribute".',
            'filter-not-supported'    => 'Filter ":filter" tidak dapat diterapkan pada atribut ":attribute".',
            'format-date-position'    => 'Filter format_date harus menjadi filter pertama di ":expression".',
            'inside-html-tag'         => 'Ekspresi templat tidak dapat ditempatkan di dalam tag atau atribut HTML seperti tautan dan gambar.',
            'invalid-filter-argument' => 'Filter ":filter" di ":expression" memiliki argumen yang hilang atau tidak valid.',
            'invalid-reference'       => 'Templat yang disimpan di ":attribute" tidak valid dan tidak dapat dirender.',
            'invalid-syntax'          => 'Ekspresi templat ":expression" tidak valid. Gunakan kode atribut yang diikuti filter opsional, misalnya: name | upper.',
            'max-depth'               => 'Referensi templat bersarang lebih dari :max tingkat di ":attribute".',
            'missing-value'           => 'Atribut ":attribute" tidak memiliki nilai untuk saluran dan lokal ini.',
            'not-in-family'           => 'Atribut ":attribute" yang digunakan dalam templat bukan bagian dari keluarga produk.',
            'output-too-large'        => 'Templat yang dirender lebih besar dari :max byte dan telah dipotong.',
            'self-reference'          => 'Templat tidak dapat mereferensikan atributnya sendiri ":attribute".',
            'single-brace'            => 'Ditemukan ":text" dalam kurung kurawal tunggal. Ekspresi templat memerlukan kurung kurawal ganda.',
            'source-too-long'         => 'Templat dapat berukuran paling banyak :max byte.',
            'too-complex'             => 'Templat terlalu kompleks untuk diperiksa. Persingkat atau sederhanakan.',
            'too-many-expressions'    => 'Templat dapat berisi paling banyak :max ekspresi.',
            'too-many-filters'        => 'Ekspresi templat ":expression" dapat menggunakan paling banyak :max filter.',
            'unclosed-expression'     => 'Ekspresi templat dibuka tetapi tidak pernah ditutup.',
            'unknown-attribute'       => 'Atribut ":attribute" yang digunakan dalam templat tidak ada.',
            'unknown-currency'        => 'Mata uang ":currency" yang digunakan dalam templat tidak ada.',
            'unknown-filter'          => 'Filter ":filter" tidak didukung. Filter yang tersedia: :filters.',
            'unsupported-attribute'   => 'Atribut ":attribute" tidak dapat digunakan dalam templat karena jenisnya tidak didukung.',
        ],
    ],
];
