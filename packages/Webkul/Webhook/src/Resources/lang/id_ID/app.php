<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Silakan aktifkan Webhook dari pengaturan',
        'success'       => 'Data produk berhasil dikirim ke Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Buat',
            'edit'   => 'Edit',
            'delete' => 'Hapus',
        ],
        'settings' => [
            'index'  => 'Pengaturan',
            'update' => 'Perbarui pengaturan',
        ],
        'logs' => [
            'index'       => 'Log',
            'view'        => 'Lihat',
            'delete'      => 'Hapus',
            'mass-delete' => 'Hapus massal',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produk dibuat',
            'updated' => 'Produk diperbarui',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhook',
            'create-btn'   => 'Buat Webhook',
            'logs-btn'     => 'Log',
            'back-btn'     => 'Kembali ke Webhook',
            'default-name' => 'Default',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Nama',
                'url'        => 'URL',
                'events'     => 'Peristiwa',
                'status'     => 'Status',
                'active'     => 'Aktif',
                'inactive'   => 'Tidak aktif',
                'created_at' => 'Dibuat pada',
                'edit'       => 'Edit',
                'delete'     => 'Hapus',
            ],
        ],
        'create' => [
            'title'    => 'Buat Webhook',
            'cancel'   => 'Batal',
            'save-btn' => 'Simpan',
        ],
        'edit' => [
            'title'    => 'Edit Webhook',
            'cancel'   => 'Batal',
            'save-btn' => 'Simpan',
        ],
        'form' => [
            'general'       => 'Umum',
            'name'          => 'Nama',
            'url'           => 'URL',
            'events'        => 'Peristiwa',
            'select-events' => 'Pilih peristiwa',
            'secret'        => 'Rahasia Penandatanganan',
            'secret-set'    => 'Rahasia sudah diatur',
            'secret-hint'   => 'Digunakan untuk menandatangani setiap payload dengan tanda tangan HMAC SHA-256. Biarkan kosong untuk mempertahankan rahasia saat ini.',
            'settings'      => 'Pengaturan',
            'active'        => 'Aktif',
            'test'          => 'Uji Koneksi',
            'test-hint'     => 'Kirim permintaan uji ke URL di atas.',
            'test-btn'      => 'Kirim Uji',
            'test-no-url'   => 'Silakan masukkan URL terlebih dahulu.',
            'test-failed'   => 'Permintaan uji gagal.',
            'headers'       => 'Header Kustom',
            'add-header'    => 'Tambah Header',
            'no-headers'    => 'Tidak ada header kustom yang ditambahkan.',
            'header-key'    => 'Header',
            'header-value'  => 'Nilai',
        ],
        'create-success' => 'Webhook berhasil dibuat',
        'update-success' => 'Webhook berhasil diperbarui',
        'delete-success' => 'Webhook berhasil dihapus',
        'delete-failed'  => 'Penghapusan Webhook gagal',
        'validation'     => [
            'unsafe-url' => 'URL menunjuk ke alamat pribadi, loopback, atau internal dan tidak diizinkan.',
            'scheme'     => 'URL harus dimulai dengan http:// atau https://.',
        ],
        'test' => [
            'payload-message'   => 'Permintaan uji webhook Unopim',
            'connection-failed' => 'URL tidak dapat dijangkau. Silakan periksa URL.',
            'unreachable'       => 'URL tidak dapat dijangkau (HTTP :code).',
            'reachable'         => 'URL dapat dijangkau.',
        ],
        'prune' => [
            'disabled' => 'Retensi log webhook dinonaktifkan; tidak ada yang dihapus.',
            'done'     => 'Menghapus :count log webhook yang lebih lama dari :days hari.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Pengaturan',
                    'save'    => 'Simpan',
                    'general' => 'Umum',
                    'active'  => [
                        'label' => 'Webhook aktif',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL Webhook',
                        'required'          => 'URL Webhook diperlukan saat Webhook aktif.',
                        'scheme'            => 'URL Webhook harus dimulai dengan http:// atau https://.',
                        'connection_failed' => 'URL Webhook tidak dapat dijangkau. Silakan periksa URL.',
                        'unreachable'       => 'URL Webhook tidak valid (HTTP :code).',
                        'unsafe'            => 'URL Webhook menunjuk ke alamat pribadi, loopback, atau internal dan tidak diizinkan.',
                    ],
                    'success'    => 'Pengaturan Webhook berhasil disimpan',
                    'title'      => 'Pengaturan Webhook',
                    'logs-title' => 'Log',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Peristiwa',
                        'created_at'       => 'Tanggal/Waktu',
                        'user'             => 'Pengguna',
                        'status'           => 'Status',
                        'success'          => 'Berhasil',
                        'failed'           => 'Gagal',
                        'server_error'     => 'Kesalahan Server',
                        'timeout_or_error' => 'Waktu Habis/Kesalahan',
                        'delete'           => 'Hapus',
                        'view'             => 'Lihat',
                    ],
                    'title'          => 'Log Webhook',
                    'show-title'     => 'Detail Log Webhook',
                    'sent-payload'   => 'Payload Terkirim',
                    'response'       => 'Respons',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Tidak ada payload yang tercatat untuk log ini.',
                    'load-failed'    => 'Gagal memuat detail log.',
                    'delete-success' => 'Log Webhook berhasil dihapus',
                    'delete-failed'  => 'Penghapusan log Webhook gagal secara tidak terduga',
                    'unauthorized'   => 'Tindakan ini tidak diizinkan',
                ],
            ],
        ],
    ],
];
