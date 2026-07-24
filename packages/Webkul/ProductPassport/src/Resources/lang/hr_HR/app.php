<?php

return [
    'type' => [
        'label' => 'Digitalna putovnica proizvoda',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Putovnica proizvoda',
            'info'     => 'Postavke objave digitalne putovnice proizvoda.',
            'settings' => [
                'title'                              => 'Postavke putovnice proizvoda',
                'enabled'                            => 'Omogućeno',
                'enabled-hint'                       => 'Uključite značajku digitalne putovnice proizvoda za ovaj katalog. Kada je isključena, ploča i mreža putovnica su skrivene.',
                'auto-publish'                       => 'Automatski objavi prilikom spremanja',
                'auto-publish-hint'                  => 'Automatski objavi verziju putovnice svaki put kada se proizvod spremi i zadovolji prag potpunosti. Ostavite isključeno za ručno objavljivanje.',
                'completeness-threshold'             => 'Prag potpunosti (%)',
                'completeness-threshold-hint'        => 'Najmanja potpunost proizvoda, u postotku, potrebna prije nego što se putovnica može objaviti za neki jezik.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Naziv gospodarskog subjekta',
                'operator-name-hint'                 => 'Pravni naziv proizvođača ili odgovornog gospodarskog subjekta, prikazan na svakoj javnoj putovnici u skladu s uredbom ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Adresa gospodarskog subjekta',
                'operator-address-hint'              => 'Registrirana poštanska adresa gospodarskog subjekta, prikazana na javnoj putovnici radi sljedivosti.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Ovlašteni predstavnik u EU',
                'operator-eu-rep-hint'               => 'Ime i kontakt ovlaštenog predstavnika u EU-u, obavezno kada je proizvođač osnovan izvan EU-a.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'URL podrške',
                'support-url-hint'                   => 'Javna stranica na kojoj kupci mogu pronaći pomoć ili informacije o jamstvu. Prikazuje se kao poveznica na svakoj putovnici.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitalna putovnica proizvoda',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Sastav materijala',
        'dpp_substances_of_concern'     => 'Zabrinjavajuće tvari',
        'dpp_recycled_content_pct'      => 'Udio recikliranog materijala (%)',
        'dpp_carbon_footprint'          => 'Ugljični otisak',
        'dpp_energy_consumption'        => 'Potrošnja energije',
        'dpp_durability_statement'      => 'Izjava o trajnosti',
        'dpp_repairability_score'       => 'Ocjena mogućnosti popravka',
        'dpp_spare_parts_availability'  => 'Dostupnost rezervnih dijelova',
        'dpp_care_instructions'         => 'Upute za njegu',
        'dpp_disassembly_guide'         => 'Vodič za rastavljanje',
        'dpp_manufacturer_name'         => 'Naziv proizvođača',
        'dpp_manufacturing_site'        => 'Mjesto proizvodnje',
        'dpp_country_of_origin'         => 'Zemlja podrijetla',
        'dpp_supply_chain_notes'        => 'Napomene o opskrbnom lancu',
        'dpp_end_of_life_instructions'  => 'Upute za kraj životnog vijeka',
        'dpp_take_back_scheme'          => 'Program povrata',
        'dpp_declaration_of_conformity' => 'Izjava o sukladnosti',
        'dpp_test_reports'              => 'Izvještaji o ispitivanju',
        'dpp_certificates'              => 'Certifikati',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identifikator modela',
        'dpp_batch_identifier'          => 'Identifikator serije',
        'dpp_warranty_terms'            => 'Uvjeti jamstva',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Atributi digitalne putovnice proizvoda uspješno su instalirani.',
        ],
    ],

    'public' => [
        'badge'         => 'EU digitalna putovnica proizvoda',
        'search-locale' => 'Jezik pretraživanja',
        'sections'      => [
            'passport' => 'Putovnica proizvoda',
        ],
        'title'      => 'Digitalna putovnica proizvoda',
        'identifier' => [
            'title'        => 'Identifikacija',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Serija',
            'not-provided' => 'Nije navedeno',
        ],
        'operator' => [
            'title' => 'Gospodarski subjekt',
        ],
        'documents' => [
            'title' => 'Dokumenti',
        ],
    ],

    'publications' => [
        'not-found'      => 'Nije pronađena putovnica za id :id.',
        'index'          => [
            'disabled-notice' => 'Objavljivanje putovnica trenutačno je onemogućeno. Postojeće putovnice prikazane su u nastavku radi upravljanja (pregled i povlačenje).',
            'title'           => 'Digitalne putovnice proizvoda',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanal',
            'status'          => 'Status',
            'live-locales'    => 'Aktivni jezici',
            'last-published'  => 'Zadnja objava',
            'withdraw'        => 'Povuci',
        ],
        'publish-queued' => 'Objava putovnice je stavljena u red čekanja.',
        'withdrawn'      => 'Putovnica uspješno povučena.',
        'mass-publish'   => [
            'action' => 'Objavi digitalnu putovnicu proizvoda',
            'queued' => 'Objava putovnice stavljena u red čekanja za :count proizvod(a).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Putovnice',
            'view'     => 'Prikaži',
            'publish'  => 'Objavi',
            'withdraw' => 'Povuci',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Putovnice',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Objavljivanje…',
                    'queued'               => 'U redu čekanja',
                    'copy-operator-link'   => 'Kopiraj poveznicu operatera',
                    'copy-authority-link'  => 'Kopiraj poveznicu nadležnog tijela',
                    'link-copied'          => 'Poveznica kopirana',
                    'title'                => 'Digitalna putovnica proizvoda',
                    'publishing-disabled'  => 'Objava putovnica je onemogućena za ovaj kanal.',
                    'locale'               => 'Jezik',
                    'version'              => 'Verzija',
                    'published-at'         => 'Objavljeno',
                    'missing-fields'       => 'Nedostajuća polja',
                    'not-published'        => 'Nije objavljeno',
                    'unscored'             => 'Nije ocijenjeno',
                    'publish'              => 'Objavi',
                    'republish'            => 'Ponovno objavi',
                    'publish-all'          => 'Objavi sve jezike',
                    'auto-publish-on'      => 'Automatska objava je uključena — putovnice se objavljuju automatski kada se proizvod spremi i dosegne prag potpunosti. Upotrijebite gumbe za objavu odmah.',
                    'auto-publish-off'     => 'Ručna objava — upotrijebite gumbe za objavu putovnice ovog proizvoda za svaki jezik.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute mora biti valjan GTIN (8, 12, 13 ili 14 znamenki s ispravnom kontrolnom znamenkom).',
    ],
    'mapping' => [
        'title'         => 'Mapiranje polja putovnice',
        'info'          => 'Svako polje putovnice popunite iz atributa koji već održavate. Ostavite polje nemapiranim da bi se koristio njegov namjenski atribut putovnice.',
        'menu'          => 'Mapiranje polja',
        'field'         => 'Polje putovnice',
        'source'        => 'Izvorni atribut',
        'select-source' => 'Koristi atribut putovnice',
        'save-btn'      => 'Spremi mapiranje',
        'type-mismatch' => 'Odabrani izvor nije kompatibilan s vrstom ovog polja putovnice.',
        'saved'         => 'Mapiranje polja uspješno je spremljeno.',
    ],

];
