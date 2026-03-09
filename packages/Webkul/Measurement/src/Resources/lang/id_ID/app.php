<?php

return [

    'attribute' => [
        'measurement' => 'Pengukuran',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Buat Keluarga Pengukuran',
            'code'     => 'Kode',
            'standard' => 'Kode Unit Standar',
            'symbol'   => 'Simbol',
            'save'     => 'Simpan',
        ],

        'edit' => [
            'measurement_edit' => 'Edit Keluarga Pengukuran',
            'back'             => 'Kembali',
            'save'             => 'Simpan',
            'general'          => 'Umum',
            'code'             => 'Kode',
            'label'            => 'Label',
            'units'            => 'Unit',
            'create_units'     => 'Buat Unit',
        ],

        'unit' => [
            'edit_unit'   => 'Edit Unit',
            'create_unit' => 'Buat Unit',
            'symbol'      => 'Simbol',
            'save'        => 'Simpan',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Keluarga Pengukuran',
        'measurement_family'   => 'Keluarga Pengukuran',
        'measurement_unit'     => 'Unit Pengukuran',
    ],

    'datagrid' => [
        'labels'        => 'Label',
        'code'          => 'Kode',
        'standard_unit' => 'Unit Standar',
        'unit_count'    => 'Jumlah Unit',
        'is_standard'   => 'Tandai sebagai Unit Standar',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Keluarga pengukuran berhasil diperbarui.',
            'deleted'      => 'Keluarga pengukuran berhasil dihapus.',
            'mass_deleted' => 'Keluarga pengukuran yang dipilih berhasil dihapus.',
        ],

        'unit' => [
            'not_found'         => 'Keluarga pengukuran tidak ditemukan.',
            'already_exists'    => 'Kode unit sudah ada.',
            'not_foundd'        => 'Unit tidak ditemukan.',
            'deleted'           => 'Unit berhasil dihapus.',
            'no_items_selected' => 'Tidak ada item yang dipilih.',
            'mass_deleted'      => 'Unit pengukuran yang dipilih berhasil dihapus.',
        ],
    ],

];
