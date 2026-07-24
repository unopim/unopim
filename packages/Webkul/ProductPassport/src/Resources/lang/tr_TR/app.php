<?php

return [
    'type' => [
        'label' => 'Dijital Ürün Pasaportu',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Ürün Pasaportu',
            'info'     => 'Dijital ürün pasaportu yayın ayarları.',
            'settings' => [
                'title'                              => 'Ürün Pasaportu Ayarları',
                'enabled'                            => 'Etkin',
                'auto-publish'                       => 'Kaydederken otomatik olarak yayınla',
                'completeness-threshold'             => 'Tamlık Eşiği (%)',
                'operator-name'                      => 'Ekonomik İşletmecinin Adı',
                'operator-address'                   => 'Ekonomik İşletmecinin Adresi',
                'operator-eu-rep'                    => 'AB Yetkili Temsilcisi',
                'support-url'                        => 'Destek URL\'si',
                'enabled-hint'                       => 'Bu katalog için Dijital Ürün Pasaportu özelliğini açın. Kapalıyken pasaport paneli ve tablosu gizlenir.',
                'auto-publish-hint'                  => 'Bir ürün kaydedildiğinde ve eksiksizlik eşiğini karşıladığında otomatik olarak bir pasaport sürümü yayımlayın. Manuel yayımlamak için kapalı bırakın.',
                'completeness-threshold-hint'        => 'Bir yerel ayar için pasaport yayımlanmadan önce gereken yüzde olarak asgari ürün eksiksizliği.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Üreticinin veya sorumlu ekonomik operatörün yasal adı; ESPR düzenlemesinin gerektirdiği şekilde her genel pasaportta gösterilir.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Ekonomik operatörün tescilli posta adresi; izlenebilirlik için genel pasaportta gösterilir.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'AB yetkili temsilcisinin adı ve iletişim bilgileri; üretici AB dışında yerleşikse gereklidir.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Müşterilerin yardım veya garanti bilgisi bulabileceği genel sayfa. Her pasaportta bağlantı olarak gösterilir.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Dijital Ürün Pasaportu',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Malzeme Bileşimi',
        'dpp_substances_of_concern'     => 'Endişe Verici Maddeler',
        'dpp_recycled_content_pct'      => 'Geri Dönüştürülmüş İçerik (%)',
        'dpp_carbon_footprint'          => 'Karbon Ayak İzi',
        'dpp_energy_consumption'        => 'Enerji Tüketimi',
        'dpp_durability_statement'      => 'Dayanıklılık Beyanı',
        'dpp_repairability_score'       => 'Onarılabilirlik Puanı',
        'dpp_spare_parts_availability'  => 'Yedek Parça Bulunabilirliği',
        'dpp_care_instructions'         => 'Bakım Talimatları',
        'dpp_disassembly_guide'         => 'Sökme Kılavuzu',
        'dpp_manufacturer_name'         => 'Üretici Adı',
        'dpp_manufacturing_site'        => 'Üretim Yeri',
        'dpp_country_of_origin'         => 'Menşe Ülke',
        'dpp_supply_chain_notes'        => 'Tedarik Zinciri Notları',
        'dpp_end_of_life_instructions'  => 'Kullanım Ömrü Sonu Talimatları',
        'dpp_take_back_scheme'          => 'Geri Alım Programı',
        'dpp_declaration_of_conformity' => 'Uygunluk Beyanı',
        'dpp_test_reports'              => 'Test Raporları',
        'dpp_certificates'              => 'Sertifikalar',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Model Tanımlayıcısı',
        'dpp_batch_identifier'          => 'Parti Tanımlayıcısı',
        'dpp_warranty_terms'            => 'Garanti Koşulları',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Dijital Ürün Pasaportu özellikleri başarıyla kuruldu.',
        ],
    ],

    'public' => [
        'badge'         => 'EU Dijital Ürün Pasaportu',
        'search-locale' => 'Arama dili',
        'sections'      => [
            'passport' => 'Ürün Pasaportu',
        ],
        'title'      => 'Dijital Ürün Pasaportu',
        'identifier' => [
            'title'        => 'Kimlik Bilgileri',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Parti',
            'not-provided' => 'Belirtilmemiş',
        ],
        'operator' => [
            'title' => 'Ekonomik İşletmeci',
        ],
        'documents' => [
            'title' => 'Belgeler',
        ],
    ],

    'publications' => [
        'reinstated'        => 'Pasaport başarıyla geri alındı.',
        'reinstate-invalid' => 'Yalnızca geri çekilmiş bir pasaport geri alınabilir.',
        'redacted'          => 'Pasaport başarıyla redakte edildi.',
        'redact-invalid'    => 'Bu pasaport redakte edilemez.',
        'not-found'         => ':id kimliği için pasaport bulunamadı.',
        'index'             => [
            'disabled-notice' => 'Pasaport yayımlama şu anda devre dışı. Mevcut pasaportlar yönetim için aşağıda gösterilir (görüntüleme ve geri çekme).',
            'title'           => 'Dijital Ürün Pasaportları',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanal',
            'status'          => 'Durum',
            'live-locales'    => 'Aktif Diller',
            'last-published'  => 'Son Yayınlanma',
            'withdraw'        => 'Geri Çek',
            'mass-publish'    => 'Seçilenleri yayımla',
        ],
        'publish-queued'      => 'Pasaport yayını sıraya alındı.',
        'bulk-publish-queued' => 'Seçili pasaportların yayımlanması kuyruğa alındı.',
        'withdrawn'           => 'Pasaport başarıyla geri çekildi.',
        'mass-publish'        => [
            'action' => 'Dijital Ürün Pasaportunu Yayınla',
            'queued' => ':count ürün için pasaport yayını sıraya alındı.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pasaportlar',
            'view'     => 'Görüntüle',
            'publish'  => 'Yayınla',
            'withdraw' => 'Geri Çek',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Pasaportlar',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Yayımlanıyor…',
                    'queued'               => 'Sırada',
                    'copy-operator-link'   => 'Operatör bağlantısını kopyala',
                    'copy-authority-link'  => 'Otorite bağlantısını kopyala',
                    'link-copied'          => 'Bağlantı kopyalandı',
                    'download-qr'          => 'QR kodunu indir',
                    'title'                => 'Dijital Ürün Pasaportu',
                    'publishing-disabled'  => 'Bu kanal için pasaport yayınlama devre dışı.',
                    'locale'               => 'Dil',
                    'version'              => 'Sürüm',
                    'published-at'         => 'Yayınlanma Tarihi',
                    'missing-fields'       => 'Eksik Alanlar',
                    'not-published'        => 'Yayınlanmadı',
                    'unscored'             => 'Değerlendirilmedi',
                    'publish'              => 'Yayınla',
                    'republish'            => 'Yeniden yayınla',
                    'publish-all'          => 'Tüm dilleri yayınla',
                    'auto-publish-on'      => 'Otomatik yayınlama açık — ürün kaydedildiğinde ve tamlık eşiğini karşıladığında pasaportlar otomatik olarak yayınlanır. Şimdi yayınlamak için düğmeleri kullanın.',
                    'auto-publish-off'     => 'Manuel yayınlama — bu ürünün pasaportunu her dil için yayınlamak üzere düğmeleri kullanın.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute geçerli bir GTIN olmalıdır (doğru kontrol basamağıyla 8, 12, 13 veya 14 hane).',
    ],
    'mapping' => [
        'title'         => 'Pasaport Alan Eşlemesi',
        'info'          => 'Her pasaport alanını zaten yönettiğiniz bir öznitelikten alın. Bir alanı eşlenmemiş bırakırsanız kendi özel pasaport özniteliği kullanılır.',
        'menu'          => 'Alan Eşlemesi',
        'field'         => 'Pasaport Alanı',
        'source'        => 'Kaynak Öznitelik',
        'select-source' => 'Pasaport özniteliğini kullan',
        'save-btn'      => 'Eşlemeyi Kaydet',
        'type-mismatch' => 'Seçilen kaynak, bu pasaport alanının türüyle uyumlu değil.',
        'saved'         => 'Alan eşlemesi başarıyla kaydedildi.',
    ],

];
