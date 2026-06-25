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
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Pengaturan',
            'update' => 'Perbarui pengaturan',
        ],
        'logs' => [
            'index'       => 'Log',
            'view'        => 'View',
            'delete'      => 'Hapus',
            'mass-delete' => 'Hapus massal',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Pengaturan',
                    'title'   => 'Pengaturan Webhook',
                    'save'    => 'Simpan',
                    'general' => 'Umum',
                    'active'  => [
                        'label' => 'Webhook aktif',
                    ],
                    'webhook_url' => [
                        'label' => 'URL Webhook',
                    ],
                    'success'    => 'Pengaturan Webhook berhasil disimpan',
                    'logs-title' => 'Log',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Tanggal/Waktu',
                        'user'       => 'Pengguna',
                        'status'     => 'Status',
                        'success'    => 'Berhasil',
                        'failed'     => 'Gagal',
                        'delete'     => 'Hapus',
                        'view'       => 'View',
                    ],
                    'title'          => 'Log Webhook',
                    'show-title'     => 'Detail Log Webhook',
                    'sent-payload'   => 'Payload Terkirim',
                    'response'       => 'Respons',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Tidak ada payload yang tercatat untuk log ini.',
                    'delete-success' => 'Log Webhook berhasil dihapus',
                    'delete-failed'  => 'Penghapusan log Webhook gagal secara tidak terduga',
                ],
            ],
        ],
    ],
];
