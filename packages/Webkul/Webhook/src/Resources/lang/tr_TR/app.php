<?php

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
            'index'  => 'Webhook',
            'create' => 'Oluştur',
            'edit'   => 'Düzenle',
            'delete' => 'Sil',
        ],
        'logs' => [
            'index'       => 'Günlükler',
            'view'        => 'Görüntüle',
            'delete'      => 'Sil',
            'mass-delete' => 'Toplu silme',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Ürün Oluşturuldu',
            'updated' => 'Ürün Güncellendi',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhook\'lar',
            'create-btn'   => 'Webhook Oluştur',
            'logs-btn'     => 'Günlükler',
            'back-btn'     => 'Webhook\'lara Dön',
            'default-name' => 'Varsayılan',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Ad',
                'url'        => 'URL',
                'events'     => 'Olaylar',
                'status'     => 'Durum',
                'active'     => 'Aktif',
                'inactive'   => 'Pasif',
                'created_at' => 'Oluşturulma Tarihi',
                'edit'       => 'Düzenle',
                'delete'     => 'Sil',
            ],
        ],
        'create' => [
            'title'    => 'Webhook Oluştur',
            'save-btn' => 'Kaydet',
        ],
        'edit' => [
            'title'    => 'Webhook Düzenle',
            'save-btn' => 'Kaydet',
        ],
        'form' => [
            'general'       => 'Genel',
            'name'          => 'Ad',
            'url'           => 'URL',
            'events'        => 'Olaylar',
            'select-events' => 'Olayları seçin',
            'secret'        => 'İmzalama Sırrı',
            'secret-set'    => 'Bir sır zaten ayarlanmış',
            'secret-hint'   => 'Her yükü HMAC SHA-256 imzasıyla imzalamak için kullanılır. Mevcut sırrı korumak için boş bırakın.',
            'settings'      => 'Ayarlar',
            'active'        => 'Aktif',
            'test'          => 'Bağlantıyı Test Et',
            'test-hint'     => 'Yukarıdaki URL\'ye bir test isteği gönderin.',
            'test-btn'      => 'Test Gönder',
            'test-no-url'   => 'Lütfen önce bir URL girin.',
            'test-failed'   => 'Test isteği başarısız oldu.',
            'headers'       => 'Özel Başlıklar',
            'add-header'    => 'Başlık Ekle',
            'no-headers'    => 'Özel başlık eklenmedi.',
            'header-key'    => 'Başlık',
            'header-value'  => 'Değer',
        ],
        'create-success' => 'Webhook başarıyla oluşturuldu',
        'update-success' => 'Webhook başarıyla güncellendi',
        'delete-success' => 'Webhook başarıyla silindi',
        'delete-failed'  => 'Webhook silme başarısız oldu',
        'validation'     => [
            'unsafe-url' => 'URL özel, geri döngü veya dahili bir adrese işaret ediyor ve izin verilmiyor.',
            'scheme'     => 'URL http:// veya https:// ile başlamalıdır.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook test isteği',
            'connection-failed' => 'URL\'ye ulaşılamadı. Lütfen URL\'yi kontrol edin.',
            'unreachable'       => 'URL\'ye ulaşılamıyor (HTTP :code).',
            'reachable'         => 'URL\'ye ulaşılabiliyor.',
        ],
        'prune' => [
            'disabled' => 'Webhook günlük saklama devre dışı; hiçbir şey temizlenmedi.',
            'done'     => ':days günden eski :count webhook günlüğü temizlendi.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Olay',
                        'created_at'       => 'Tarih/Saat',
                        'user'             => 'Kullanıcı',
                        'status'           => 'Durum',
                        'success'          => 'Başarılı',
                        'failed'           => 'Başarısız',
                        'server_error'     => 'Sunucu Hatası',
                        'timeout_or_error' => 'Zaman Aşımı/Hata',
                        'delete'           => 'Sil',
                        'view'             => 'Görüntüle',
                    ],
                    'title'          => 'Webhook Günlükleri',
                    'show-title'     => 'Webhook Kayıt Detayları',
                    'sent-payload'   => 'Gönderilen Veri',
                    'response'       => 'Yanıt',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Bu kayıt için hiçbir veri kaydedilmedi.',
                    'load-failed'    => 'Günlük detayları yüklenemedi.',
                    'delete-success' => 'Webhook günlükleri başarıyla silindi',
                    'delete-failed'  => 'Webhook günlüklerinin silinmesi beklenmedik şekilde başarısız oldu',
                    'unauthorized'   => 'Bu işlem yetkilendirilmemiş',
                ],
            ],
        ],
    ],
];
