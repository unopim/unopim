<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => 'Aileler',
            'attribute-groups'   => [
                'description'      => 'Tanım',
                'general'          => 'Genel',
                'inventories'      => 'Envanterler',
                'meta-description' => 'Meta Açıklama',
                'price'            => 'Fiyat',
                'technical'        => 'Teknik',
                'shipping'         => 'Nakliye',
            ],
            'attributes' => [
                'brand'                => 'Marka',
                'color'                => 'Renk',
                'cost'                 => 'Maliyet',
                'description'          => 'Açıklama',
                'featured'             => 'Öne Çıkan',
                'guest-checkout'       => 'Misafir Ödeme',
                'height'               => 'Yükseklik',
                'length'               => 'Uzunluk',
                'manage-stock'         => 'Stokları Yönet',
                'meta-description'     => 'Meta Açıklama',
                'meta-keywords'        => 'Meta Anahtar Kelimeler',
                'meta-title'           => 'Meta Başlık',
                'name'                 => 'Ad',
                'new'                  => 'Yeni',
                'price'                => 'Fiyat',
                'product-number'       => 'Ürün Numarası',
                'short-description'    => 'Kısa Açıklama',
                'size'                 => 'Beden',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Özel Fiyat Başlangıç',
                'special-price-to'     => 'Özel Fiyat Bitiş',
                'special-price'        => 'Özel Fiyat',
                'status'               => 'Durum',
                'tax-category'         => 'Vergi Kategorisi',
                'url-key'              => 'URL Anahtarı',
                'visible-individually' => 'Ayrı Ayrı Görüntülenir',
                'weight'               => 'Ağırlık',
                'width'                => 'Genişlik',
            ],
            'attribute-options' => [
                'black'  => 'Siyah',
                'green'  => 'Yeşil',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Kırmızı',
                's'      => 'S',
                'white'  => 'Beyaz',
                'xl'     => 'XL',
                'yellow' => 'Sarı',
            ],
        ],
        'category' => [
            'categories' => [
                'description' => 'Ana kategori tanımları',
                'name'        => 'Ana',
            ],
            'category_fields' => [
                'name'        => 'İsim',
                'description' => 'Açıklama',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Hakkımızda sayfası içeriği',
                    'title'   => 'Hakkımızda',
                ],
                'contact-us' => [
                    'content' => 'İletişim sayfası içeriği',
                    'title'   => 'İletişim',
                ],
                'customer-service' => [
                    'content' => 'Müşteri hizmetleri sayfası içeriği',
                    'title'   => 'Müşteri Hizmetleri',
                ],
                'payment-policy' => [
                    'content' => 'Ödeme politikası sayfası içeriği',
                    'title'   => 'Ödeme Politikası',
                ],
                'privacy-policy' => [
                    'content' => 'Gizlilik politikası sayfası içeriği',
                    'title'   => 'Gizlilik Politikası',
                ],
                'refund-policy' => [
                    'content' => 'İade politikası sayfası içeriği',
                    'title'   => 'İade Politikası',
                ],
                'return-policy' => [
                    'content' => 'İade politikası sayfası içeriği',
                    'title'   => 'İade Politikası',
                ],
                'shipping-policy' => [
                    'content' => 'Nakliye politikası sayfası içeriği',
                    'title'   => 'Nakliye Politikası',
                ],
                'terms-conditions' => [
                    'content' => 'Şartlar sayfası içeriği',
                    'title'   => 'Şartlar ve Koşullar',
                ],
                'terms-of-use' => [
                    'content' => 'Kullanım koşulları sayfası içeriği',
                    'title'   => 'Kullanım Koşulları',
                ],
                'whats-new' => [
                    'content' => 'Yeni ne var sayfası içeriği',
                    'title'   => 'Yeni Ne Var',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'meta-title'       => 'Demo Mağazası',
                'meta-keywords'    => 'Demo Mağazası Meta Anahtar Kelimeleri',
                'meta-description' => 'Demo Mağazası Meta Açıklaması',
                'name'             => 'Standart',
            ],
            'currencies' => [
                'AED' => 'Dirhem',
                'AFN' => 'Afgani',
                'CNY' => 'Çin Yuanı',
                'EUR' => 'Euro',
                'GBP' => 'Sterlin',
                'INR' => 'Hindistan Rupisi',
                'IRR' => 'İran Riyali',
                'JPY' => 'Japon Yeni',
                'RUB' => 'Rus Rublesi',
                'SAR' => 'Suudi Riyali',
                'TRY' => 'Türk Lirası',
                'UAH' => 'Ukrayna Grivnası',
                'USD' => 'ABD Doları',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general'   => 'Genel',
                'guest'     => 'Ziyaretçi',
                'wholesale' => 'Toptan',
            ],
        ],
        'inventory' => [
            'inventory-sources' => [
                'name' => 'Standart',
            ],
        ],
        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name'    => 'Tüm Ürünler',
                    'options' => [
                        'title' => 'Tüm Ürünler',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Tümünü Gör',
                        'description' => 'Yeni cesur koleksiyonlarımızı keşfedin! Stilinizi yükseltin, cesur tasarımlar ve parlak renklerle. Muhteşem desenler ve canlı renkler ile dolaplarınıza farklı bir hava katın. Cesur bir başlangıç için hazır olun!',
                        'title'       => 'Yeni Cesur Koleksiyonlarımıza Hazırlanın!',
                    ],
                    'name' => 'Cesur Koleksiyonlar',
                ],
                'categories-collections' => [
                    'name' => 'Kategori Koleksiyonları',
                ],
                'featured-collections' => [
                    'name'    => 'Öne Çıkan Koleksiyonlar',
                    'options' => [
                        'title' => 'Öne Çıkan Ürünler',
                    ],
                ],
                'footer-links' => [
                    'name'    => 'Alt Bağlantılar',
                    'options' => [
                        'about-us'         => 'Hakkımızda',
                        'contact-us'       => 'İletişim',
                        'customer-service' => 'Müşteri Hizmetleri',
                        'payment-policy'   => 'Ödeme Politikası',
                        'privacy-policy'   => 'Gizlilik Politikası',
                        'refund-policy'    => 'İade Politikası',
                        'return-policy'    => 'İade Politikası',
                        'shipping-policy'  => 'Nakliye Politikası',
                        'terms-conditions' => 'Şartlar ve Koşullar',
                        'terms-of-use'     => 'Kullanım Koşulları',
                        'whats-new'        => 'Yeni Ne Var',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Koleksiyonlarımız',
                        'sub-title-2' => 'Koleksiyonlarımız',
                        'title'       => 'Yeni Ürünlerle Oynayın!',
                    ],
                    'name' => 'Oyun Kabini',
                ],
                'image-carousel' => [
                    'name'    => 'Resim Karuseli',
                    'sliders' => [
                        'title' => 'Yeni Bir Koleksiyon için Hazırlanın',
                    ],
                ],
                'new-products' => [
                    'name'    => 'Yeni Ürünler',
                    'options' => [
                        'title' => 'Yeni Ürünler',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => 'İLK ALIŞVERİŞİNİZDE %40 İNDİRİM BAŞLAYIN!',
                    ],
                    'name' => 'Teklif Bilgisi',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'Tüm ana kredi kartları için ek ücret olmadan finansman imkanı mevcuttur',
                        'free-shipping-info'   => 'Tüm siparişler için ücretsiz gönderim',
                        'product-replace-info' => 'Ürün değişikliği kolaydır!',
                        'time-support-info'    => 'Canlı sohbet ve e-posta aracılığıyla 7/24 özel destek',
                    ],
                    'name'  => 'Hizmet İçeriği',
                    'title' => [
                        'emi-available'   => 'EMI Mevcut',
                        'free-shipping'   => 'Ücretsiz Gönderim',
                        'product-replace' => 'Ürün Değiştirme',
                        'time-support'    => 'Destek 7/24',
                    ],
                ],
                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Koleksiyonlarımız',
                        'sub-title-2' => 'Koleksiyonlarımız',
                        'sub-title-3' => 'Koleksiyonlarımız',
                        'sub-title-4' => 'Koleksiyonlarımız',
                        'sub-title-5' => 'Koleksiyonlarımız',
                        'sub-title-6' => 'Koleksiyonlarımız',
                        'title'       => 'Yeni Ürünlerle Bir Oyun Oynayın!',
                    ],
                    'name' => 'Öne Çıkan Koleksiyonlar',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => 'Bu rol tüm erişimlere sahip olacak',
                'name'        => 'Yönetici',
            ],
            'users' => [
                'name' => 'Örnek',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Yönetici',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Şifreyi Onayla',
                'email-address'    => 'admin@example.com',
                'email'            => 'E-posta',
                'password'         => 'Şifre',
                'title'            => 'Yönetici Oluştur',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'İzin Verilen Para Birimleri',
                'allowed-locales'     => 'İzin Verilen Dil Ayarları',
                'application-name'    => 'Uygulama Adı',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Çin Yuanı (CNY)',
                'database-connection' => 'Veritabanı Bağlantısı',
                'database-hostname'   => 'Veritabanı Ana Bilgisi',
                'database-name'       => 'Veritabanı Adı',
                'database-password'   => 'Veritabanı Şifresi',
                'database-port'       => 'Veritabanı Bağlantı Noktası',
                'database-prefix'     => 'Veritabanı Öneki',
                'database-username'   => 'Veritabanı Kullanıcı Adı',
                'default-currency'    => 'Varsayılan Para Birimi',
                'default-locale'      => 'Varsayılan Dil',
                'default-timezone'    => 'Varsayılan Zaman Dilimi',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Varsayılan URL',
                'dirham'              => 'Dirhem (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'İran Riyali (IRR)',
                'israeli'             => 'İsrail Şekeli (ILS)',
                'japanese-yen'        => 'Japon Yeni (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'İngiliz Sterlini (GBP)',
                'rupee'               => 'Hint Rupisi (INR)',
                'russian-ruble'       => 'Rus Rublesi (RUB)',
                'saudi'               => 'Suudi Riyali (SAR)',
                'select-timezone'     => 'Zaman Dilimi Seçin',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Veritabanı Bağlantısı',
                'turkish-lira'        => 'Türk Lirası (TRY)',
                'ukrainian-hryvnia'   => 'Ukrayna Grivnası (UAH)',
                'usd'                 => 'Amerikan Doları (USD)',
                'warning-message'     => 'Uyarı! Varsayılan dil ve para birimi daha sonra değiştirilemez.',
            ],

            'installation-processing' => [
                'unopim'      => 'UnoPim Kurulumu',
                'unopim-info' => 'Veritabanı tabloları oluşturuluyor, bu işlem birkaç dakika sürebilir.',
                'title'       => 'Kurulum İşlemi',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Yönetici Paneli',
                'unopim-forums'             => 'UnoPim Forumları',
                'explore-unopim-extensions' => 'UnoPim Eklentilerini Keşfet',
                'title-info'                => 'UnoPim başarıyla yüklendi.',
                'title'                     => 'Kurulum Tamamlandı',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Veritabanı Tablolarını Oluştur',
                'install-info-button'     => 'Aşağıdaki düğmeye tıklayın',
                'install-info'            => 'UnoPim kurulumunu başlatmak için',
                'install'                 => 'Kurulum Yap',
                'populate-database-table' => 'Veritabanı Tablolarını Doldur',
                'start-installation'      => 'Kurulumu Başlat',
                'title'                   => 'Kurulum İçin Hazır',
            ],

            'start' => [
                'locale'        => 'Dil',
                'main'          => 'Ana Sayfa',
                'select-locale' => 'Dil Seçin',
                'title'         => 'UnoPim Kurulumu',
                'welcome-title' => 'UnoPim :version kurulumuna hoş geldiniz',
            ],

            'server-requirements' => [
                'calendar'    => 'Takvim',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Dosya Bilgisi',
                'filter'      => 'Filtre',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Intl',
                'json'        => 'JSON',
                'mbstring'    => 'MbString',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 ve üzeri',
                'php'         => 'PHP',
                'session'     => 'Oturum',
                'title'       => 'Sistem Gereksinimleri',
                'tokenizer'   => 'Tokenizer',
                'xml'         => 'XML',
            ],

            'back'                     => 'Geri',
            'unopim-info'              => 'Topluluk Projesi',
            'unopim-logo'              => 'UnoPim Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Devam Et',
            'installation-description' => 'UnoPim kurulum süreci birkaç adımdan oluşur. Aşağıda özetlenmiştir:',
            'wizard-language'          => 'Kurulum Sihirbazı Dili',
            'installation-info'        => 'Bize katıldığınız için teşekkür ederiz!',
            'installation-title'       => 'Kurulum Başlangıcı',
            'save-configuration'       => 'Yapılandırmayı Kaydet',
            'skip'                     => 'Atla',
            'title'                    => 'UnoPim Kurulum Sihirbazı',
            'webkul'                   => 'Webkul',
        ],
    ],
];
