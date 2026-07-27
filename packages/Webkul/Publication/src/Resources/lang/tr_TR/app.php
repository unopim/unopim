<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Yayın',
            'info'     => 'Yayınlanan, yerel ayara özgü içerik için genel sunum katmanı.',
            'settings' => [
                'title'                            => 'Yayın Ayarları',
                'enabled'                          => 'Etkin',
                'base-url'                         => 'Temel URL',
                'cache-ttl'                        => 'Önbellek TTL (saniye)',
                'rate-limit'                       => 'Hız Sınırı (istek/dakika)',
                'indexable'                        => 'Arama motoru dizinlemesine izin ver',
                'enabled-hint'                     => 'Genel yayın katmanının ana anahtarı. Kapalıyken her genel pasaport URL\'si 404 döner ve pasaport menüsü gizlenir.',
                'base-url-hint'                    => 'Pasaportların sunulduğu genel adres; QR kodları ve paylaşılabilir bağlantılar oluşturmak için kullanılır. Bu sitenin kendi alan adını kullanmak için boş bırakın.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => 'Oluşturulan bir genel pasaportun yeniden oluşturulmadan önce ne kadar süre önbelleğe alınacağı. Yüksek değerler yükü azaltır; düşük değerler değişiklikleri daha hızlı yansıtır.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => 'Tek bir ziyaretçiden dakikada izin verilen en fazla genel pasaport isteği; bu sınırı aşınca kısıtlanır.',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => 'Arama motorlarının genel pasaport sayfalarını dizine eklemesine izin verin. Pasaportların bağlantıyla erişilebilir kalması ancak arama sonuçlarında gizlenmesi için kapatın.',
                'gs1-passport-channel'             => 'GS1 Digital Link pasaport kanalı',
                'gs1-passport-channel-hint'        => 'Bir ürün birden fazla kanalda yayımlandığında, taranan bir GS1 barkodunun (/01/{gtin}) yönlendirileceği kanal. İlk etkin kanalı kullanmak için boş bırakın.',
                'gs1-passport-channel-placeholder' => 'İlk etkin kanal (otomatik)',
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
        'product-delete-blocked' => 'Yayınlanmış pasaportları olduğu sürece bu ürün silinemez. Önce onları geri çekin.',
        'channel-delete-blocked' => 'Yayınlanmış pasaportları olduğu sürece bu kanal silinemez. Önce onları geri çekin.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Pasaport bulunamadı.',
            'notice'  => 'Bu ürün pasaportu kullanılamıyor. Henüz yayımlanmamış olabilir veya bağlantı yanlış olabilir.',
        ],
        '429' => [
            'heading' => 'Çok fazla istek. Lütfen kısa bir süre sonra tekrar deneyin.',
            'notice'  => 'Çok fazla istek gönderdiniz. Lütfen biraz bekleyip tekrar deneyin.',
        ],
        'withdrawn' => [
            'heading' => 'Bu pasaport artık kullanılamıyor.',
            'notice'  => 'Bu kayıt şeffaflık amacıyla saklanmaktadır ancak artık aktif olarak güncellenmemektedir.',
        ],
    ],
];
