<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Yayın',
            'info'     => 'Yayınlanan, yerel ayara özgü içerik için genel sunum katmanı.',
            'settings' => [
                'title'      => 'Yayın Ayarları',
                'enabled'    => 'Etkin',
                'base-url'   => 'Temel URL',
                'cache-ttl'  => 'Önbellek TTL (saniye)',
                'rate-limit' => 'Hız Sınırı (istek/dakika)',
                'indexable'  => 'Arama motoru dizinlemesine izin ver',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Taslak',
            'published' => 'Yayınlandı',
            'withdrawn' => 'Geri çekildi',
            'redacted'  => 'Sansürlendi',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Pasaport bulunamadı.',
        ],
        '429' => [
            'heading' => 'Çok fazla istek. Lütfen kısa bir süre sonra tekrar deneyin.',
        ],
        'withdrawn' => [
            'heading' => 'Bu pasaport artık kullanılamıyor.',
        ],
    ],
];
