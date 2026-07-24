<?php

return [
    'type' => [
        'label' => 'Digital na Pasaporte ng Produkto',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Pasaporte ng Produkto',
            'info'     => 'Mga setting ng paglalathala ng digital na pasaporte ng produkto.',
            'settings' => [
                'title'                              => 'Mga Setting ng Pasaporte ng Produkto',
                'enabled'                            => 'Pinagana',
                'auto-publish'                       => 'Awtomatikong ilathala kapag na-save',
                'completeness-threshold'             => 'Threshold ng Pagkakumpleto (%)',
                'operator-name'                      => 'Pangalan ng Economic Operator',
                'operator-address'                   => 'Address ng Economic Operator',
                'operator-eu-rep'                    => 'Awtorisadong Kinatawan sa EU',
                'support-url'                        => 'URL ng Suporta',
                'enabled-hint'                       => 'I-on ang tampok na Digital Product Passport para sa katalogong ito. Kapag naka-off, nakatago ang panel at grid ng passport.',
                'auto-publish-hint'                  => 'Awtomatikong mag-publish ng bersyon ng passport tuwing na-save ang isang produkto at naabot ang threshold ng pagkakumpleto. Iwanang naka-off upang mag-publish nang manu-mano.',
                'completeness-threshold-hint'        => 'Pinakamababang pagkakumpleto ng produkto, bilang porsyento, na kinakailangan bago maipa-publish ang isang passport para sa isang locale.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Legal na pangalan ng tagagawa o responsableng ekonomikong operator, ipinapakita sa bawat pampublikong passport ayon sa iniaatas ng regulasyong ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Nakarehistrong postal address ng ekonomikong operator, ipinapakita sa pampublikong passport para sa traceability.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Pangalan at contact ng awtorisadong kinatawan sa EU, kinakailangan kapag ang tagagawa ay nakabase sa labas ng EU.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Pampublikong pahina kung saan makakahanap ang mga customer ng tulong o impormasyon sa warranty. Ipinapakita bilang link sa bawat passport.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digital na Pasaporte ng Produkto',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Komposisyon ng Materyal',
        'dpp_substances_of_concern'     => 'Mga Sangkap na Dapat Bantayan',
        'dpp_recycled_content_pct'      => 'Nire-recycle na Nilalaman (%)',
        'dpp_carbon_footprint'          => 'Carbon Footprint',
        'dpp_energy_consumption'        => 'Konsumo ng Enerhiya',
        'dpp_durability_statement'      => 'Pahayag ng Tibay',
        'dpp_repairability_score'       => 'Marka ng Kakayahang Ayusin',
        'dpp_spare_parts_availability'  => 'Availability ng Spare Parts',
        'dpp_care_instructions'         => 'Mga Tagubilin sa Pag-aalaga',
        'dpp_disassembly_guide'         => 'Gabay sa Pagtatanggal',
        'dpp_manufacturer_name'         => 'Pangalan ng Tagagawa',
        'dpp_manufacturing_site'        => 'Lugar ng Paggawa',
        'dpp_country_of_origin'         => 'Bansang Pinagmulan',
        'dpp_supply_chain_notes'        => 'Mga Tala sa Supply Chain',
        'dpp_end_of_life_instructions'  => 'Mga Tagubilin sa Pagtatapos ng Buhay',
        'dpp_take_back_scheme'          => 'Take-Back na Iskema',
        'dpp_declaration_of_conformity' => 'Deklarasyon ng Pagsunod',
        'dpp_test_reports'              => 'Mga Ulat ng Pagsubok',
        'dpp_certificates'              => 'Mga Sertipiko',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Model Identifier',
        'dpp_batch_identifier'          => 'Batch Identifier',
        'dpp_warranty_terms'            => 'Mga Tuntunin ng Warranty',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Matagumpay na na-install ang mga attribute ng Digital na Pasaporte ng Produkto.',
        ],
    ],

    'public' => [
        'badge'         => 'EU Digital na Pasaporte ng Produkto',
        'search-locale' => 'Wika ng paghahanap',
        'sections'      => [
            'passport' => 'Pasaporte ng Produkto',
        ],
        'title'      => 'Digital na Pasaporte ng Produkto',
        'identifier' => [
            'title'        => 'Pagkakakilanlan',
            'gtin'         => 'GTIN',
            'model'        => 'Modelo',
            'batch'        => 'Batch',
            'not-provided' => 'Hindi nakalagay',
        ],
        'operator' => [
            'title' => 'Economic Operator',
        ],
        'documents' => [
            'title' => 'Mga Dokumento',
        ],
    ],

    'publications' => [
        'not-found'      => 'Walang pasaporteng nahanap para sa id :id.',
        'index'          => [
            'disabled-notice' => 'Kasalukuyang naka-disable ang paglalathala ng pasaporte. Ipinapakita sa ibaba ang mga umiiral na pasaporte para sa pamamahala (tingnan at bawiin).',
            'title'           => 'Mga Digital na Pasaporte ng Produkto',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Channel',
            'status'          => 'Katayuan',
            'live-locales'    => 'Aktibong mga Wika',
            'last-published'  => 'Huling Nailathala',
            'withdraw'        => 'Bawiin',
        ],
        'publish-queued' => 'Naka-queue na ang paglalathala ng pasaporte.',
        'withdrawn'      => 'Matagumpay na nabawi ang pasaporte.',
        'mass-publish'   => [
            'action' => 'Ilathala ang Digital na Pasaporte ng Produkto',
            'queued' => 'Naka-queue ang paglalathala ng pasaporte para sa :count produkto.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Mga Pasaporte',
            'view'     => 'Tingnan',
            'publish'  => 'Ilathala',
            'withdraw' => 'Bawiin',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Mga Pasaporte',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Inilalathala…',
                    'queued'               => 'Nakapila',
                    'copy-operator-link'   => 'Kopyahin ang link ng operator',
                    'copy-authority-link'  => 'Kopyahin ang link ng awtoridad',
                    'link-copied'          => 'Nakopya ang link',
                    'download-qr'          => 'I-download ang QR code',
                    'title'                => 'Digital na Pasaporte ng Produkto',
                    'publishing-disabled'  => 'Naka-disable ang paglalathala ng pasaporte para sa channel na ito.',
                    'locale'               => 'Wika',
                    'version'              => 'Bersyon',
                    'published-at'         => 'Inilathala Noong',
                    'missing-fields'       => 'Kulang na mga Field',
                    'not-published'        => 'Hindi pa nailathala',
                    'unscored'             => 'Hindi pa nasusuri',
                    'publish'              => 'Ilathala',
                    'republish'            => 'Muling ilathala',
                    'publish-all'          => 'Ilathala ang lahat ng wika',
                    'auto-publish-on'      => 'Naka-on ang awtomatikong paglalathala — awtomatikong inilalathala ang mga pasaporte kapag na-save ang produkto at naabot ang threshold ng pagkakumpleto. Gamitin ang mga button upang ilathala ngayon.',
                    'auto-publish-off'     => 'Manwal na paglalathala — gamitin ang mga button upang ilathala ang pasaporte ng produktong ito para sa bawat wika.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Ang :attribute ay dapat na wastong GTIN (8, 12, 13, o 14 na digit na may tamang check digit).',
    ],
    'mapping' => [
        'title'         => 'Pagmamapa ng Field ng Pasaporte',
        'info'          => 'Kunin ang bawat field ng pasaporte mula sa isang attribute na pinananatili mo na. Iwanang hindi namapa ang isang field upang gamitin ang nakatuong attribute ng pasaporte nito.',
        'menu'          => 'Pagmamapa ng Field',
        'field'         => 'Field ng Pasaporte',
        'source'        => 'Pinagmulang Attribute',
        'select-source' => 'Gamitin ang attribute ng pasaporte',
        'save-btn'      => 'I-save ang Pagmamapa',
        'type-mismatch' => 'Ang napiling pinagmulan ay hindi tugma sa uri ng field ng pasaporte na ito.',
        'saved'         => 'Matagumpay na na-save ang pagmamapa ng field.',
    ],

];
