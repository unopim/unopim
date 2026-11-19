<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Tamlık',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Tamlık başarıyla güncellendi',
                    'title'               => 'Tamlık',
                    'configure'           => 'Tamlığı yapılandır',
                    'channel-required'    => 'Kanallarda zorunlu',
                    'save-btn'            => 'Kaydet',
                    'back-btn'            => 'Geri',
                    'mass-update-success' => 'Tamlık başarıyla güncellendi',
                    'datagrid'            => [
                        'code'             => 'Kod',
                        'name'             => 'Ad',
                        'channel-required' => 'Kanallarda zorunlu',
                        'actions'          => [
                            'change-requirement' => 'Tamlık gereksinimini değiştir',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Geçersiz',
                    'completeness'                 => 'Tamamlandı',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Tamlık',
                    'subtitle' => 'Ortalama tamlık',
                ],
                'required-attributes' => 'eksik zorunlu öznitelikler',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Tamlık hesaplaması tamamlandı',
        'completeness-calculated'        => ':count ürün için tamlık hesaplandı.',
        'completeness-calculated-family' => '":family" ailesindeki :count ürün için tamlık hesaplandı.',
        'email-subject'                  => 'Tamlık hesaplaması tamamlandı',
        'email-greeting'                 => 'Merhaba,',
        'email-body'                     => ':count ürün için tamlık hesaplaması tamamlandı.',
        'email-body-family'              => '":family" öznitelik ailesindeki :count ürün için tamlık hesaplaması tamamlandı.',
        'email-footer'                   => 'Tamlık ayrıntılarını kontrol panelinizde görüntüleyebilirsiniz.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Hesaplanan ürünler',
                'suggestion'          => [
                    'low'     => 'Düşük tamlık, iyileştirmek için ayrıntı ekleyin.',
                    'medium'  => 'Devam edin, bilgi eklemeye devam edin.',
                    'high'    => 'Neredeyse tamamlandı, sadece birkaç ayrıntı kaldı.',
                    'perfect' => 'Ürün bilgileri tamamen eksiksiz.',
                ],
            ],
        ],
    ],

    'configuration' => [
        'score-thresholds' => [
            'title'           => 'Tamlık Puanı Renkleri',
            'info'            => 'Tamlık göstergelerinin yeşil veya kırmızıya döndüğü puanları belirleyin.',
            'green-from'      => 'Yeşil Başlangıcı (%)',
            'green-from-info' => 'Bu değere eşit veya bundan yüksek puanlar yeşil gösterilir.',
            'red-below'       => 'Kırmızı Sınırı (%)',
            'red-below-info'  => 'Bu değerin altındaki puanlar kırmızı gösterilir. Aradaki puanlar sarı gösterilir.',
            'order-error'     => 'Yeşil eşiği kırmızı eşiğinden yüksek olmalıdır.',
        ],
    ],
];
