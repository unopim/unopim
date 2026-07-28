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
        'category-fields' => [
            'title'      => 'Kategori Alanları',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kategori alan kodu :code zaten kullanımda.',
                    'code_not_found_to_delete' => 'Silmek için kategori alan kodu bulunamadı.',
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
        'product-associations' => [
            'title'      => 'Ürün İlişkilendirmeleri',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => '\'%s\' alanı zorunludur.',
                    'self-link-not-allowed'       => '\'%s\' ürünü kendisiyle ilişkilendirilemez.',
                    'sku-not-found'               => 'SKU \'%s\' olan ürün bulunamadı.',
                    'related-sku-not-found'       => 'SKU \'%s\' olan ilişkili ürün bulunamadı.',
                    'association-type-not-found'  => '\'%s\' ilişki türü mevcut değil veya pasif.',
                    'invalid-field-value'         => 'İlişki alanı için geçersiz bir değer girildi.',
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
        'locales' => [
            'title'      => 'Diller',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Dil kodu \'%s\' bu partide zaten içe aktarılmış.',
                    'code-not-found-to-delete'    => 'Sistemde \'%s\' koduna sahip dil bulunamadı.',
                    'invalid-status'              => 'Durum 0 veya 1 olmalıdır (veya varsayılan etkin için boş).',
                    'channel-related-locale-root' => ':code koduna sahip dili silemezsiniz çünkü bir kanala bağlıdır.',
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
            'title'   => 'Mga Pera',
            'filters' => [
                'status' => 'Durum',
                'enable' => 'Etkin',
                'all'    => 'Tümü',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Durum 0 veya 1 olmalıdır (veya varsayılan etkin için boş).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roller',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Kullanıcılar',
            'filters' => [
                'status' => 'Durum',
                'active' => 'Aktif',
                'all'    => 'Tümü',
            ],
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
        'export-too-large' => 'Bu dışa aktarma çalıştırılamayacak kadar büyük: tahmini :rows satır × :columns sütun (~:estimated) kullanılabilir alanı (~:available) aşıyor. Daha az kanal/yerel (ve öznitelik) seçerek dışa aktarmayı daraltın ve tekrar deneyin.',
        'fields'           => [
            'file-format'            => 'Dosya biçimi',
            'with-media'             => 'Medya ile',
            'with-associations'      => 'İlişkilendirmelerle',
            'with-associations-info' => 'Eski up_sells, cross_sells ve related_products SKU liste sütunlarını dışa aktarıma dahil et',
            'header-row'             => 'Header Row',
            'header-row-info'        => 'Write attribute codes as the first line',
            'use-labels'             => 'Use Labels',
            'use-labels-info'        => 'Export readable labels instead of codes',
            'date-format'            => 'Date Format',
            'date-format-options'    => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Dosya Yolu',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Durum',
            'enable'         => 'Etkin',
            'all'            => 'Tümü',
        ],
        'products' => [
            'title'              => 'Ürünler',
            'invalid-locales'    => 'Seçilen yerel ayarların tümü, seçilen kanallar için kullanılabilir değil.',
            'invalid-currencies' => 'Seçilen para birimlerinin tümü, seçilen kanallar için kullanılabilir değil.',
            'filters'            => [
                'channels'             => 'Kanallar',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Para birimleri',
                'currencies-info'      => 'Fiyat öznitelikleri, seçilen her para birimi için dışa aktarılır. Tüm kanal para birimlerini dışa aktarmak için boş bırakın.',
                'locales'              => 'Yerel ayarlar',
                'locales-info'         => 'Yerelleştirilebilir öznitelikler, seçilen her yerel ayar için bir kez dışa aktarılır. Tüm kanal yerel ayarlarını dışa aktarmak için boş bırakın.',
                'attributes'           => 'Öznitelikler',
                'attributes-info'      => 'Yalnızca seçilen öznitelikler dışa aktarılır. Ailedeki tüm öznitelikleri dışa aktarmak için boş bırakın.',
                'attribute-families'   => 'Öznitelik aileleri',
                'categories'           => 'Kategoriler',
                'completeness'         => 'Tamlık',
                'completeness-options' => [
                    'none'         => 'Tamlık koşulu yok',
                    'at-least-one' => 'En az bir seçili yerel ayarda tam',
                    'all'          => 'Tüm seçili yerel ayarlarda tam',
                ],
                'time-condition' => 'Zaman koşulu',
                'time-options'   => [
                    'none'              => 'Tarih koşulu yok',
                    'last-n-days'       => 'Son N günde güncellenen ürünler',
                    'between-dates'     => 'İki tarih arasında güncellenen ürünler',
                    'since-last-export' => 'Son dışa aktarmadan bu yana güncellenen ürünler',
                ],
                'time-value'     => 'Gün sayısı',
                'time-date'      => 'Başlangıç tarihi',
                'time-date-end'  => 'Bitiş tarihi',
                'status'         => 'Durum',
                'status-options' => [
                    'enable'  => 'Etkin',
                    'disable' => 'Devre dışı',
                    'all'     => 'Tümü',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Tanımlayıcılar',
                'identifiers-info' => 'Yalnızca bu ürünleri dışa aktarmak için her satıra bir SKU / tanımlayıcı yapıştırın. Tüm ürünleri dışa aktarmak için boş bırakın.',
            ],
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
        'category-fields' => [
            'title' => 'Kategori Alanları',
        ],
        'attributes' => [
            'title' => 'Nitelikler',
        ],
        'product-associations' => [
            'title' => 'Ürün İlişkilendirmeleri',
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
        'locales' => [
            'title' => 'Diller',
        ],
        'channels' => [
            'title' => 'Kanallar',
        ],
        'currencies' => [
            'title' => 'Mga Pera',
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
