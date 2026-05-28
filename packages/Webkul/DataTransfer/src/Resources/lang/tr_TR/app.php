<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Ürünler',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL anahtar: \'%s\' SKU: \'%s\' için zaten bir öğe için oluşturuldu.',
                    'invalid-attribute-family'                 => 'Attribut ailesinin kolonundaki değer geçersiz (attribut ailesi mevcut değil mi?)',
                    'invalid-type'                             => 'Ürün türü geçersiz veya desteklenmiyor',
                    'sku-not-found'                            => 'Belirtilen SKU ile ürün bulunamadı',
                    'super-attribute-not-found'                => 'Konfigürasyonel attribut, kod: \'%s\' bulunamadı veya attribut ailesine ait değil: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Konfigürasyonel attributlar, ürün modeli oluşturmak için gereklidir',
                    'configurable-attributes-wrong-type'       => 'Seçenekli attribut türleri, sadece yer veya kanal bazlı olmayanlar, konfigürasyonel attributlar için seçilebilir',
                    'variant-configurable-attribute-not-found' => 'Seçenekli konfigürasyonel attribut: :code gereklidir',
                    'not-unique-variant-product'               => 'Aynı konfigürasyonel attributlara sahip bir ürün zaten mevcut.',
                    'channel-not-exist'                        => 'Bu kanal mevcut değil.',
                    'locale-not-in-channel'                    => 'Bu dil kanalda seçilmemiş.',
                    'locale-not-exist'                         => 'Bu dil mevcut değil',
                    'not-unique-value'                         => 'Değer :code benzersiz olmalıdır.',
                    'incorrect-family-for-variant'             => 'Aile ana aile ile aynı olmalıdır',
                    'parent-not-exist'                         => 'Baba yok.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategoriler',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Bir kanal ile ilişkilendirilen kök kategoriyi silemezsiniz',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Para Birimleri',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Para birimi kodu \'%s\' bu toplu işlemde zaten içe aktarılmış.',
                    'code-not-found-to-delete'    => '\'%s\' kodlu para birimi sistemde bulunamadı.',
                    'invalid-status'              => 'Durum 0 veya 1 olmalıdır (veya varsayılan olarak etkin için boş).',
                    'channel-related-locale-root' => 'Bir kanalla ilişkili olan :code kodlu yerel ayarı silemezsiniz.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roller',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Yinelenen rol adı bulundu.',
                    'name-not-found-to-delete' => 'Belirtilen ada sahip rol silinmek için bulunamadı.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Kullanıcılar',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'Belirtilen e-posta adresine sahip kullanıcı silinmek için bulunamadı.',
                    'invalid-role'              => 'Geçersiz rol adı bulundu.',
                    'invalid-locale'            => 'Geçersiz UI yerel kodu bulundu.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Ürünler',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL anahtar: \'%s\' SKU: \'%s\' için zaten bir öğe için oluşturuldu.',
                    'invalid-attribute-family'  => 'Attribut ailesinin kolonundaki değer geçersiz (attribut ailesi mevcut değil mi?)',
                    'invalid-type'              => 'Ürün türü geçersiz veya desteklenmiyor',
                    'sku-not-found'             => 'Belirtilen SKU ile ürün bulunamadı',
                    'super-attribute-not-found' => 'Konfigürasyonel attribut, kod: \'%s\' bulunamadı veya attribut ailesine ait değil: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategoriler',
        ],
        'currencies' => [
            'title' => 'Para Birimleri',
        ],
        'roles' => [
            'title' => 'Roller',
        ],
        'users' => [
            'title'   => 'Kullanıcılar',
            'filters' => [
                'status' => 'Durum',
                'active' => 'Aktif',
                'all'    => 'Tümü',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolon numarası "%s" için boş başlıklar vardır.',
            'column-name-invalid'  => 'Geçersiz kolon adları: "%s".',
            'column-not-found'     => 'Gerekli kolonlar bulunamadı: %s.',
            'column-numbers'       => 'Kolon sayısı, başlıktaki satır sayısı ile uyumsuz.',
            'invalid-attribute'    => 'Başlık geçersiz attribute(s) içeriyor: "%s".',
            'system'               => 'Beklenmeyen bir sistem hatası oluştu.',
            'wrong-quotes'         => 'Kıvrık tırnaklar, doğru tırnaklar yerine kullanıldı.',
            'file-empty'           => 'Dosya boş veya başlık satırı içermiyor. Lütfen veri içeren geçerli bir dosya yükleyin.',
        ],
    ],
    'job' => [
        'started'   => 'İş yürütme başlatıldı',
        'completed' => 'İş yürütme tamamlandı',
    ],
];
