<?php

return [
    'type' => [
        'label' => 'Digitalt Produktpass',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpass',
            'info'     => 'Publiceringsinställningar för det digitala produktpasset.',
            'settings' => [
                'title'                              => 'Inställningar för produktpass',
                'enabled'                            => 'Aktiverad',
                'auto-publish'                       => 'Publicera automatiskt vid sparande',
                'completeness-threshold'             => 'Fullständighetströskel (%)',
                'operator-name'                      => 'Namn på ekonomisk aktör',
                'operator-address'                   => 'Adress till ekonomisk aktör',
                'operator-eu-rep'                    => 'Auktoriserad representant i EU',
                'support-url'                        => 'Support-URL',
                'enabled-hint'                       => 'Aktivera funktionen Digitalt produktpass för denna katalog. När den är av döljs passpanelen och rutnätet.',
                'auto-publish-hint'                  => 'Publicera en passversion automatiskt varje gång en produkt sparas och når fullständighetströskeln. Lämna av för att publicera manuellt.',
                'completeness-threshold-hint'        => 'Minsta produktfullständighet, i procent, som krävs innan ett pass kan publiceras för en lokal.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Juridiskt namn på tillverkaren eller den ansvariga ekonomiska aktören, visas på varje publikt pass enligt ESPR-förordningen.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Registrerad postadress till den ekonomiska aktören, visas på det publika passet för spårbarhet.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Namn och kontaktuppgifter till den auktoriserade representanten i EU, krävs när tillverkaren är etablerad utanför EU.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Publik sida där kunder kan hitta hjälp eller garantiinformation. Visas som en länk på varje pass.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitalt produktpass',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Materialsammansättning',
        'dpp_substances_of_concern'     => 'Ämnen som inger betänkligheter',
        'dpp_recycled_content_pct'      => 'Återvunnet innehåll (%)',
        'dpp_carbon_footprint'          => 'Koldioxidavtryck',
        'dpp_energy_consumption'        => 'Energiförbrukning',
        'dpp_durability_statement'      => 'Hållbarhetsförklaring',
        'dpp_repairability_score'       => 'Reparerbarhetspoäng',
        'dpp_spare_parts_availability'  => 'Tillgänglighet av reservdelar',
        'dpp_care_instructions'         => 'Skötselinstruktioner',
        'dpp_disassembly_guide'         => 'Demonteringsguide',
        'dpp_manufacturer_name'         => 'Tillverkarens namn',
        'dpp_manufacturing_site'        => 'Tillverkningsplats',
        'dpp_country_of_origin'         => 'Ursprungsland',
        'dpp_supply_chain_notes'        => 'Anteckningar om leveranskedjan',
        'dpp_end_of_life_instructions'  => 'Instruktioner vid slutet av livslängden',
        'dpp_take_back_scheme'          => 'Retursystem',
        'dpp_declaration_of_conformity' => 'Försäkran om överensstämmelse',
        'dpp_test_reports'              => 'Testrapporter',
        'dpp_certificates'              => 'Certifikat',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Modellidentifierare',
        'dpp_batch_identifier'          => 'Partiidentifierare',
        'dpp_warranty_terms'            => 'Garantivillkor',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Attributen för det digitala produktpasset installerades.',
        ],
    ],

    'public' => [
        'badge'         => 'EU digitalt produktpass',
        'search-locale' => 'Sökspråk',
        'sections'      => [
            'passport' => 'Produktpass',
        ],
        'title'      => 'Digitalt Produktpass',
        'identifier' => [
            'title'        => 'Identifiering',
            'gtin'         => 'GTIN',
            'model'        => 'Modell',
            'batch'        => 'Parti',
            'not-provided' => 'Ej angivet',
        ],
        'operator' => [
            'title' => 'Ekonomisk aktör',
        ],
        'documents' => [
            'title' => 'Dokument',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'Publicering av pass är för närvarande inaktiverad. Befintliga pass visas nedan för hantering (visa och återkalla).',
            'title'           => 'Digitala Produktpass',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanal',
            'status'          => 'Status',
            'live-locales'    => 'Aktiva språk',
            'last-published'  => 'Senast publicerad',
            'withdraw'        => 'Dra tillbaka',
        ],
        'publish-queued' => 'Publicering av passet har lagts i kö.',
        'withdrawn'      => 'Pass indraget.',
        'mass-publish'   => [
            'action' => 'Publicera digitalt produktpass',
            'queued' => 'Passpublicering köad för :count produkt(er).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pass',
            'view'     => 'Visa',
            'publish'  => 'Publicera',
            'withdraw' => 'Dra tillbaka',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Pass',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'Publicerar…',
                    'queued'              => 'I kö',
                    'title'               => 'Digitalt Produktpass',
                    'publishing-disabled' => 'Publicering av pass är inaktiverad för denna kanal.',
                    'locale'              => 'Språk',
                    'version'             => 'Version',
                    'published-at'        => 'Publicerad',
                    'missing-fields'      => 'Saknade fält',
                    'not-published'       => 'Ej publicerad',
                    'unscored'            => 'Ej bedömd',
                    'publish'             => 'Publicera',
                    'republish'           => 'Publicera igen',
                    'publish-all'         => 'Publicera alla språk',
                    'auto-publish-on'     => 'Automatisk publicering är på — pass publiceras automatiskt när produkten sparas och når fullständighetströskeln. Använd knapparna för att publicera nu.',
                    'auto-publish-off'    => 'Manuell publicering — använd knapparna för att publicera passet för denna produkt för varje språk.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute måste vara ett giltigt GTIN (8, 12, 13 eller 14 siffror med korrekt kontrollsiffra).',
    ],
    'mapping' => [
        'title' => 'Fältmappning för produktpass',
        'info' => 'Hämta varje passfält från ett attribut som du redan underhåller. Lämna ett fält omappat för att falla tillbaka på dess dedikerade passattribut.',
        'menu' => 'Fältmappning',
        'field' => 'Passfält',
        'source' => 'Källattribut',
        'select-source' => 'Använd passattributet',
        'save-btn' => 'Spara mappning',
        'type-mismatch' => 'Den valda källan är inte kompatibel med typen för detta passfält.',
        'saved' => 'Fältmappningen sparades.',
    ],

];
