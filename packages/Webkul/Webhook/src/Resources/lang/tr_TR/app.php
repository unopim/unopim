<?php

declare(strict_types=1);

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook\'lar',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Lütfen ayarlardan Webhook\'u etkinleştirin',
        'success'       => 'Ürün verileri Webhook\'a başarıyla gönderildi',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Ayarlar',
            'update' => 'Ayarları güncelle',
        ],
        'logs' => [
            'index'       => 'Günlükler',
            'delete'      => 'Sil',
            'mass-delete' => 'Toplu silme',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Ayarlar',
                    'title'   => 'Webhook Ayarları',
                    'save'    => 'Kaydet',
                    'general' => 'Genel',
                    'active'  => [
                        'label' => 'Aktif Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook URL',
                        'required'          => 'Webhook etkin olduğunda Webhook URL\'si gereklidir.',
                        'scheme'            => 'Webhook URL\'si http:// veya https:// ile başlamalıdır.',
                        'connection_failed' => 'Webhook URL\'sine erişilemedi. Lütfen URL\'yi kontrol edin.',
                        'unreachable'       => 'Webhook URL geçerli değil (HTTP :code).',
                    ],
                    'success'    => 'Webhook ayarları başarıyla kaydedildi',
                    'logs-title' => 'Günlükler',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Tarih/Saat',
                        'user'             => 'Kullanıcı',
                        'status'           => 'Durum',
                        'success'          => 'Başarılı',
                        'failed'           => 'Başarısız',
                        'server_error'     => 'Sunucu Hatası',
                        'timeout_or_error' => 'Zaman Aşımı/Hata',
                        'delete'           => 'Sil',
                    ],
                    'title'          => 'Webhook Günlükleri',
                    'delete-success' => 'Webhook günlükleri başarıyla silindi',
                    'delete-failed'  => 'Webhook günlüklerinin silinmesi beklenmedik şekilde başarısız oldu',
                ],
            ],
        ],
    ],
];
