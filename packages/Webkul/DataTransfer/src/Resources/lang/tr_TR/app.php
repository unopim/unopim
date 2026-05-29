<?php

declare(strict_types=1);

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
        'channels' => [
            'title'      => 'Kanallar',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => ':code kodlu kanal silinmek için bulunamadı.',
                    'locale-not-found'         => 'Bir veya daha fazla dil mevcut değil.',
                    'root-category-not-found'  => 'Kök kategori mevcut değil.',
                    'currency-not-found'       => 'Bir veya daha fazla para birimi mevcut değil.',
                    'invalid-locale'           => 'Dil mevcut değil.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
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
        'channels' => [
            'title' => 'Kanallar',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
