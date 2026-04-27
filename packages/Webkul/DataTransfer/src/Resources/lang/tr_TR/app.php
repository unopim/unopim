<?php

return [
    'importers' => [
        'products' => [
            'title' => 'Ürünler',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL anahtar: \'%s\' SKU: \'%s\' için zaten bir öğe için oluşturuldu.',
                    'invalid-attribute-family' => 'Attribut ailesinin kolonundaki değer geçersiz (attribut ailesi mevcut değil mi?)',
                    'invalid-type' => 'Ürün türü geçersiz veya desteklenmiyor',
                    'sku-not-found' => 'Belirtilen SKU ile ürün bulunamadı',
                    'super-attribute-not-found' => 'Konfigürasyonel attribut, kod: \'%s\' bulunamadı veya attribut ailesine ait değil: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found' => 'Konfigürasyonel attributlar, ürün modeli oluşturmak için gereklidir',
                    'configurable-attributes-wrong-type' => 'Seçenekli attribut türleri, sadece yer veya kanal bazlı olmayanlar, konfigürasyonel attributlar için seçilebilir',
                    'variant-configurable-attribute-not-found' => 'Seçenekli konfigürasyonel attribut: :code gereklidir',
                    'not-unique-variant-product' => 'Aynı konfigürasyonel attributlara sahip bir ürün zaten mevcut.',
                    'channel-not-exist' => 'Bu kanal mevcut değil.',
                    'locale-not-in-channel' => 'Bu dil kanalda seçilmemiş.',
                    'locale-not-exist' => 'Bu dil mevcut değil',
                    'not-unique-value' => 'Değer :code benzersiz olmalıdır.',
                    'incorrect-family-for-variant' => 'Aile ana aile ile aynı olmalıdır',
                    'parent-not-exist' => 'Baba yok.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategoriler',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Bir kanal ile ilişkilendirilen kök kategoriyi silemezsiniz',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Nitelikler',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Nitelik kodu :code zaten kullanımda.',
                    'code_not_found_to_delete'             => 'Silinecek nitelik kodu bulunamadı.',
                    'code_is_system_and_cannot_be_deleted' => 'Sistem niteliği silinemez.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Nitelik Grupları',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Nitelik grup kodu :code zaten kullanımda.',
                    'code_not_found_to_delete'             => 'Silinecek nitelik grup kodu bulunamadı.',
                    'code_is_system_and_cannot_be_deleted' => 'Sistem nitelik grubu silinemez.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Nitelik Aileleri',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Nitelik ailesi kodu :code zaten kullanımda.',
                    'code_not_found_to_delete' => 'Silinecek nitelik ailesi kodu bulunamadı.',
                    'invalid-attribute-group'  => 'Nitelik grubu ":code" mevcut değil.',
                    'invalid-attribute'        => 'Nitelik ":code" mevcut değil.',
                    'invalid-channel'          => 'Kanal ":code" mevcut değil.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Nitelik Seçenekleri',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Nitelik seçenek kodu :code zaten kullanımda.',
                    'code_not_found_to_delete' => 'Silinecek nitelik seçenek kodu bulunamadı.',
                    'locale-not-exist'         => 'Yerel ":code" mevcut değil.',
                    'invalid-attribute'        => 'Nitelik ":code" mevcut değil.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title' => 'Ürünler',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL anahtar: \'%s\' SKU: \'%s\' için zaten bir öğe için oluşturuldu.',
                    'invalid-attribute-family' => 'Attribut ailesinin kolonundaki değer geçersiz (attribut ailesi mevcut değil mi?)',
                    'invalid-type' => 'Ürün türü geçersiz veya desteklenmiyor',
                    'sku-not-found' => 'Belirtilen SKU ile ürün bulunamadı',
                    'super-attribute-not-found' => 'Konfigürasyonel attribut, kod: \'%s\' bulunamadı veya attribut ailesine ait değil: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategoriler',
        ],
        'attributes' => [
            'title' => 'Nitelikler',
        ],
        'attribute-groups' => [
            'title' => 'Nitelik Grupları',
        ],
        'attribute-families' => [
            'title' => 'Nitelik Aileleri',
        ],
        'attribute-options' => [
            'title' => 'Nitelik Seçenekleri',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolon numarası "%s" için boş başlıklar vardır.',
            'column-name-invalid' => 'Geçersiz kolon adları: "%s".',
            'column-not-found' => 'Gerekli kolonlar bulunamadı: %s.',
            'column-numbers' => 'Kolon sayısı, başlıktaki satır sayısı ile uyumsuz.',
            'invalid-attribute' => 'Başlık geçersiz attribute(s) içeriyor: "%s".',
            'system' => 'Beklenmeyen bir sistem hatası oluştu.',
            'wrong-quotes' => 'Kıvrık tırnaklar, doğru tırnaklar yerine kullanıldı.',
            'file-empty' => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started' => 'İş yürütme başlatıldı',
        'completed' => 'İş yürütme tamamlandı',
    ],
];
