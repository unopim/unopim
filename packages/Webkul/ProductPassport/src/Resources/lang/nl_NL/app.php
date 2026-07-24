<?php

return [
    'type' => [
        'label' => 'Digitaal Productpaspoort',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Productpaspoort',
            'info'     => 'Publicatie-instellingen voor het digitale productpaspoort.',
            'settings' => [
                'title'                              => 'Productpaspoortinstellingen',
                'enabled'                            => 'Ingeschakeld',
                'auto-publish'                       => 'Automatisch publiceren bij opslaan',
                'completeness-threshold'             => 'Volledigheidsdrempel (%)',
                'operator-name'                      => 'Naam van de marktdeelnemer',
                'operator-address'                   => 'Adres van de marktdeelnemer',
                'operator-eu-rep'                    => 'Gemachtigde vertegenwoordiger in de EU',
                'support-url'                        => 'Ondersteunings-URL',
                'enabled-hint'                       => 'Schakel de functie Digitaal Productpaspoort in voor deze catalogus. Wanneer uit, worden het paspoortpaneel en het raster verborgen.',
                'auto-publish-hint'                  => 'Publiceer automatisch een paspoortversie telkens wanneer een product wordt opgeslagen en aan de volledigheidsdrempel voldoet. Laat uit om handmatig te publiceren.',
                'completeness-threshold-hint'        => 'Minimale productvolledigheid, als percentage, vereist voordat een paspoort voor een landinstelling kan worden gepubliceerd.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Wettelijke naam van de fabrikant of verantwoordelijke marktdeelnemer, die op elk openbaar paspoort wordt getoond zoals vereist door de ESPR-verordening.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Geregistreerd postadres van de marktdeelnemer, getoond op het openbare paspoort voor traceerbaarheid.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Naam en contactgegevens van de gemachtigde vertegenwoordiger in de EU, vereist wanneer de fabrikant buiten de EU is gevestigd.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Openbare pagina waar klanten hulp of garantie-informatie kunnen vinden. Wordt als link op elk paspoort getoond.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitaal Productpaspoort',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Materiaalsamenstelling',
        'dpp_substances_of_concern'     => 'Zorgwekkende stoffen',
        'dpp_recycled_content_pct'      => 'Gerecycled gehalte (%)',
        'dpp_carbon_footprint'          => 'Koolstofvoetafdruk',
        'dpp_energy_consumption'        => 'Energieverbruik',
        'dpp_durability_statement'      => 'Duurzaamheidsverklaring',
        'dpp_repairability_score'       => 'Reparatiescore',
        'dpp_spare_parts_availability'  => 'Beschikbaarheid van reserveonderdelen',
        'dpp_care_instructions'         => 'Verzorgingsinstructies',
        'dpp_disassembly_guide'         => 'Demontagehandleiding',
        'dpp_manufacturer_name'         => 'Naam van de fabrikant',
        'dpp_manufacturing_site'        => 'Productielocatie',
        'dpp_country_of_origin'         => 'Land van oorsprong',
        'dpp_supply_chain_notes'        => 'Opmerkingen over de toeleveringsketen',
        'dpp_end_of_life_instructions'  => 'Instructies voor einde levensduur',
        'dpp_take_back_scheme'          => 'Terugnameregeling',
        'dpp_declaration_of_conformity' => 'Conformiteitsverklaring',
        'dpp_test_reports'              => 'Testrapporten',
        'dpp_certificates'              => 'Certificaten',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Modelidentificatie',
        'dpp_batch_identifier'          => 'Batchidentificatie',
        'dpp_warranty_terms'            => 'Garantievoorwaarden',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'De attributen van het Digitaal Productpaspoort zijn succesvol geïnstalleerd.',
        ],
    ],

    'public' => [
        'badge'         => 'EU digitaal productpaspoort',
        'search-locale' => 'Zoektaal',
        'sections'      => [
            'passport' => 'Productpaspoort',
        ],
        'title'      => 'Digitaal Productpaspoort',
        'identifier' => [
            'title'        => 'Identificatie',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Batch',
            'not-provided' => 'Niet opgegeven',
        ],
        'operator' => [
            'title' => 'Marktdeelnemer',
        ],
        'documents' => [
            'title' => 'Documenten',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'Het publiceren van paspoorten is momenteel uitgeschakeld. Bestaande paspoorten worden hieronder weergegeven voor beheer (bekijken en intrekken).',
            'title'           => 'Digitale Productpaspoorten',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanaal',
            'status'          => 'Status',
            'live-locales'    => 'Actieve talen',
            'last-published'  => 'Laatst gepubliceerd',
            'withdraw'        => 'Intrekken',
        ],
        'publish-queued' => 'Publicatie van het paspoort is in de wachtrij geplaatst.',
        'withdrawn'      => 'Paspoort succesvol ingetrokken.',
        'mass-publish'   => [
            'action' => 'Digitaal productpaspoort publiceren',
            'queued' => 'Paspoortpublicatie in de wachtrij geplaatst voor :count product(en).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Paspoorten',
            'view'     => 'Bekijken',
            'publish'  => 'Publiceren',
            'withdraw' => 'Intrekken',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Paspoorten',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'Publiceren…',
                    'queued'              => 'In wachtrij',
                    'title'               => 'Digitaal Productpaspoort',
                    'publishing-disabled' => 'Het publiceren van paspoorten is uitgeschakeld voor dit kanaal.',
                    'locale'              => 'Taal',
                    'version'             => 'Versie',
                    'published-at'        => 'Gepubliceerd op',
                    'missing-fields'      => 'Ontbrekende velden',
                    'not-published'       => 'Niet gepubliceerd',
                    'unscored'            => 'Niet beoordeeld',
                    'publish'             => 'Publiceren',
                    'republish'           => 'Opnieuw publiceren',
                    'publish-all'         => 'Alle talen publiceren',
                    'auto-publish-on'     => 'Automatisch publiceren staat aan — paspoorten worden automatisch gepubliceerd wanneer het product wordt opgeslagen en aan de volledigheidsdrempel voldoet. Gebruik de knoppen om nu te publiceren.',
                    'auto-publish-off'    => 'Handmatig publiceren — gebruik de knoppen om het paspoort van dit product voor elke taal te publiceren.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Het :attribute moet een geldige GTIN zijn (8, 12, 13 of 14 cijfers met een correct controlecijfer).',
    ],
    'mapping' => [
        'title' => 'Paspoortveldtoewijzing',
        'info' => 'Haal elk paspoortveld op uit een attribuut dat u al beheert. Laat een veld niet toegewezen om terug te vallen op het eigen paspoortattribuut.',
        'menu' => 'Veldtoewijzing',
        'field' => 'Paspoortveld',
        'source' => 'Bronattribuut',
        'select-source' => 'Gebruik het paspoortattribuut',
        'save-btn' => 'Toewijzing opslaan',
        'saved' => 'Veldtoewijzing succesvol opgeslagen.',
    ],

];
