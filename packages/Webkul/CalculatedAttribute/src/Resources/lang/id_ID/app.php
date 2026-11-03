<?php

return [

    'attribute' => [
        'calculated' => 'Terhitung',
    ],

    'formula' => [
        'title'            => 'Rumus',
        'label'            => 'Rumus',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Nilainya diturunkan dari rumus ini setiap kali produk ditampilkan, diekspor, atau dikembalikan oleh API. Nilai tersebut tidak pernah disimpan dan tidak dapat diimpor.',
        'reference-hint'   => 'Rujuk atribut lain dengan kodenya.',
        'available'        => 'Fungsi yang tersedia',
        'examples'         => 'Contoh',
        'example-concat'   => 'Gabungkan dua atribut, lewati yang kosong',
        'example-category' => 'Jalur label kategori, digabung dengan #',
        'example-fallback' => 'Gunakan atribut kedua bila yang pertama kosong',
        'read-only'        => 'Terhitung',
        'read-only-hint'   => 'Nilai ini dihitung dari atribut lain dan tidak dapat diubah.',

        'variables' => [
            'title'      => 'Variabel yang tersedia',
            'sku'        => 'SKU produk',
            'type'       => 'tipe produk',
            'status'     => 'status produk',
            'family'     => 'kode keluarga atribut',
            'parent'     => 'SKU produk induk, untuk varian',
            'categories' => 'kode kategori yang ditetapkan',
            'channel'    => 'kanal yang sedang ditampilkan',
            'locale'     => 'bahasa yang sedang ditampilkan',
        ],

        'errors' => [
            'syntax'              => 'Rumus tidak dapat diuraikan: :detail',
            'unknown-variable'    => 'Atribut atau variabel ":name" tidak dikenal dalam rumus.',
            'disallowed-function' => 'Fungsi ":name" tidak diizinkan dalam rumus.',
            'disallowed-operator' => 'Operator ":name" tidak diizinkan dalam rumus.',
            'reserved-variable'   => 'Nama ":name" telah dicadangkan dan tidak dapat digunakan dalam rumus.',
            'too-long'            => 'Rumus tidak boleh lebih dari :max karakter.',
            'empty'               => 'Atribut terhitung memerlukan sebuah rumus.',
            'circular'            => 'Rumus menciptakan ketergantungan melingkar: :cycle',
            'referenced-by'       => 'Atribut ini tidak dapat dihapus karena digunakan oleh rumus dari: :attributes',
        ],
    ],

];
