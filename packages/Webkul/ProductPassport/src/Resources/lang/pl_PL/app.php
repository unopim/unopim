<?php

return [
    'type' => [
        'label' => 'Cyfrowy Paszport Produktu',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Paszport Produktu',
            'info'     => 'Ustawienia publikacji cyfrowego paszportu produktu.',
            'settings' => [
                'title'                              => 'Ustawienia paszportu produktu',
                'enabled'                            => 'Włączone',
                'auto-publish'                       => 'Publikuj automatycznie przy zapisie',
                'completeness-threshold'             => 'Próg kompletności (%)',
                'operator-name'                      => 'Nazwa podmiotu gospodarczego',
                'operator-address'                   => 'Adres podmiotu gospodarczego',
                'operator-eu-rep'                    => 'Upoważniony przedstawiciel w UE',
                'support-url'                        => 'Adres URL wsparcia',
                'enabled-hint'                       => 'Włącz funkcję Cyfrowego Paszportu Produktu dla tego katalogu. Gdy jest wyłączona, panel paszportu i siatka są ukryte.',
                'auto-publish-hint'                  => 'Automatycznie publikuj wersję paszportu za każdym razem, gdy produkt zostanie zapisany i osiągnie próg kompletności. Pozostaw wyłączone, aby publikować ręcznie.',
                'completeness-threshold-hint'        => 'Minimalna kompletność produktu, wyrażona w procentach, wymagana przed opublikowaniem paszportu dla danej lokalizacji.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Nazwa prawna producenta lub odpowiedzialnego podmiotu gospodarczego, wyświetlana na każdym publicznym paszporcie zgodnie z wymogami rozporządzenia ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Zarejestrowany adres pocztowy podmiotu gospodarczego, wyświetlany na publicznym paszporcie w celu zapewnienia identyfikowalności.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Nazwa i dane kontaktowe upoważnionego przedstawiciela w UE, wymagane, gdy producent ma siedzibę poza UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Publiczna strona, na której klienci mogą znaleźć pomoc lub informacje o gwarancji. Wyświetlana jako link na każdym paszporcie.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Cyfrowy Paszport Produktu',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Skład materiałowy',
        'dpp_substances_of_concern'     => 'Substancje budzące obawy',
        'dpp_recycled_content_pct'      => 'Zawartość materiałów z recyklingu (%)',
        'dpp_carbon_footprint'          => 'Ślad węglowy',
        'dpp_energy_consumption'        => 'Zużycie energii',
        'dpp_durability_statement'      => 'Oświadczenie o trwałości',
        'dpp_repairability_score'       => 'Wskaźnik naprawialności',
        'dpp_spare_parts_availability'  => 'Dostępność części zamiennych',
        'dpp_care_instructions'         => 'Instrukcje pielęgnacji',
        'dpp_disassembly_guide'         => 'Instrukcja demontażu',
        'dpp_manufacturer_name'         => 'Nazwa producenta',
        'dpp_manufacturing_site'        => 'Miejsce produkcji',
        'dpp_country_of_origin'         => 'Kraj pochodzenia',
        'dpp_supply_chain_notes'        => 'Uwagi dotyczące łańcucha dostaw',
        'dpp_end_of_life_instructions'  => 'Instrukcje dotyczące końca eksploatacji',
        'dpp_take_back_scheme'          => 'Program zwrotu',
        'dpp_declaration_of_conformity' => 'Deklaracja zgodności',
        'dpp_test_reports'              => 'Raporty z badań',
        'dpp_certificates'              => 'Certyfikaty',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identyfikator modelu',
        'dpp_batch_identifier'          => 'Identyfikator partii',
        'dpp_warranty_terms'            => 'Warunki gwarancji',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Atrybuty Cyfrowego Paszportu Produktu zostały pomyślnie zainstalowane.',
        ],
    ],

    'public' => [
        'badge'         => 'Cyfrowy paszport produktu EU',
        'search-locale' => 'Język wyszukiwania',
        'sections'      => [
            'passport' => 'Paszport Produktu',
        ],
        'title'      => 'Cyfrowy Paszport Produktu',
        'identifier' => [
            'title'        => 'Identyfikacja',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Partia',
            'not-provided' => 'Nie podano',
        ],
        'operator' => [
            'title' => 'Podmiot gospodarczy',
        ],
        'documents' => [
            'title' => 'Dokumenty',
        ],
    ],

    'publications' => [
        'not-found'      => 'Nie znaleziono paszportu o id :id.',
        'index'          => [
            'disabled-notice' => 'Publikowanie paszportów jest obecnie wyłączone. Istniejące paszporty są wyświetlane poniżej w celu zarządzania (podgląd i wycofanie).',
            'title'           => 'Cyfrowe Paszporty Produktu',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanał',
            'status'          => 'Status',
            'live-locales'    => 'Aktywne języki',
            'last-published'  => 'Ostatnio opublikowano',
            'withdraw'        => 'Wycofaj',
        ],
        'publish-queued' => 'Publikacja paszportu została zakolejkowana.',
        'withdrawn'      => 'Paszport pomyślnie wycofany.',
        'mass-publish'   => [
            'action' => 'Opublikuj cyfrowy paszport produktu',
            'queued' => 'Publikacja paszportu zakolejkowana dla :count produktu(-ów).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Paszporty',
            'view'     => 'Podgląd',
            'publish'  => 'Publikuj',
            'withdraw' => 'Wycofaj',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Paszporty',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Publikowanie…',
                    'queued'               => 'W kolejce',
                    'copy-operator-link'   => 'Kopiuj link operatora',
                    'copy-authority-link'  => 'Kopiuj link organu',
                    'link-copied'          => 'Link skopiowany',
                    'title'                => 'Cyfrowy Paszport Produktu',
                    'publishing-disabled'  => 'Publikowanie paszportów jest wyłączone dla tego kanału.',
                    'locale'               => 'Język',
                    'version'              => 'Wersja',
                    'published-at'         => 'Opublikowano',
                    'missing-fields'       => 'Brakujące pola',
                    'not-published'        => 'Nieopublikowany',
                    'unscored'             => 'Nieocenione',
                    'publish'              => 'Publikuj',
                    'republish'            => 'Opublikuj ponownie',
                    'publish-all'          => 'Opublikuj wszystkie języki',
                    'auto-publish-on'      => 'Automatyczna publikacja jest włączona — paszporty są publikowane automatycznie, gdy produkt zostanie zapisany i osiągnie próg kompletności. Użyj przycisków, aby opublikować teraz.',
                    'auto-publish-off'     => 'Publikacja ręczna — użyj przycisków, aby opublikować paszport tego produktu dla każdego języka.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Pole :attribute musi być prawidłowym numerem GTIN (8, 12, 13 lub 14 cyfr z poprawną cyfrą kontrolną).',
    ],
    'mapping' => [
        'title'         => 'Mapowanie pól paszportu',
        'info'          => 'Wypełnij każde pole paszportu z atrybutu, który już utrzymujesz. Pozostaw pole niezmapowane, aby użyć jego dedykowanego atrybutu paszportu.',
        'menu'          => 'Mapowanie pól',
        'field'         => 'Pole paszportu',
        'source'        => 'Atrybut źródłowy',
        'select-source' => 'Użyj atrybutu paszportu',
        'save-btn'      => 'Zapisz mapowanie',
        'type-mismatch' => 'Wybrane źródło jest niezgodne z typem tego pola paszportu.',
        'saved'         => 'Mapowanie pól zostało pomyślnie zapisane.',
    ],

];
