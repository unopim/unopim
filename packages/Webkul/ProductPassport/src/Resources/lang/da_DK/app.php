<?php

return [
    'type' => [
        'label' => 'Digitalt Produktpas',
    ],
    'configuration' => [
        'dpp-section' => [
            'title' => 'Digitalt produktpas',
            'info'  => 'Administrer udgivelse af pas og indstillinger for offentlig publicering.',
        ],

        'product_passport' => [
            'title'    => 'Produktpas',
            'info'     => 'Indstillinger for offentliggørelse af det digitale produktpas.',
            'settings' => [
                'title'                              => 'Indstillinger for produktpas',
                'enabled'                            => 'Aktiveret',
                'enabled-hint'                       => 'Aktivér funktionen Digitalt produktpas for dette katalog. Når det er slået fra, skjules pas-panelet og gitteret.',
                'auto-publish'                       => 'Udgiv automatisk ved gemning',
                'auto-publish-hint'                  => 'Udgiv automatisk en pas-version, hver gang et produkt gemmes og opfylder fuldstændighedsgrænsen. Lad stå fra for at udgive manuelt.',
                'completeness-threshold'             => 'Fuldstændighedsgrænse (%)',
                'completeness-threshold-hint'        => 'Minimum produktfuldstændighed i procent, der kræves, før et pas kan udgives for en lokalitet.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Økonomisk operatørs navn',
                'operator-name-hint'                 => 'Juridisk navn på producenten eller den ansvarlige økonomiske operatør, som vises på hvert offentligt pas i henhold til ESPR-forordningen.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Økonomisk operatørs adresse',
                'operator-address-hint'              => 'Registreret postadresse på den økonomiske operatør, som vises på det offentlige pas af hensyn til sporbarhed.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'EU-autoriseret repræsentant',
                'operator-eu-rep-hint'               => 'Navn og kontakt på den autoriserede repræsentant i EU, påkrævet når producenten er etableret uden for EU.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'Support-URL',
                'support-url-hint'                   => 'Offentlig side, hvor kunder kan finde hjælp eller garantioplysninger. Vises som et link på hvert pas.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitalt Produktpas',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Materialesammensætning',
        'dpp_substances_of_concern'     => 'Problematiske stoffer',
        'dpp_recycled_content_pct'      => 'Genanvendt indhold (%)',
        'dpp_carbon_footprint'          => 'Kulstofaftryk',
        'dpp_energy_consumption'        => 'Energiforbrug',
        'dpp_durability_statement'      => 'Holdbarhedserklæring',
        'dpp_repairability_score'       => 'Reparerbarhedsscore',
        'dpp_spare_parts_availability'  => 'Tilgængelighed af reservedele',
        'dpp_care_instructions'         => 'Plejevejledning',
        'dpp_disassembly_guide'         => 'Demonteringsvejledning',
        'dpp_manufacturer_name'         => 'Producentnavn',
        'dpp_manufacturing_site'        => 'Produktionssted',
        'dpp_country_of_origin'         => 'Oprindelsesland',
        'dpp_supply_chain_notes'        => 'Bemærkninger om forsyningskæde',
        'dpp_end_of_life_instructions'  => 'Anvisninger til bortskaffelse',
        'dpp_take_back_scheme'          => 'Returordning',
        'dpp_declaration_of_conformity' => 'Overensstemmelseserklæring',
        'dpp_test_reports'              => 'Testrapporter',
        'dpp_certificates'              => 'Certifikater',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Modelidentifikator',
        'dpp_batch_identifier'          => 'Partiidentifikator',
        'dpp_warranty_terms'            => 'Garantibetingelser',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Attributterne til Digitalt Produktpas blev installeret.',
        ],
    ],

    'public' => [
        'badge'         => 'EU digitalt produktpas',
        'search-locale' => 'Søgesprog',
        'sections'      => [
            'passport' => 'Produktpas',
        ],
        'title'      => 'Digitalt Produktpas',
        'identifier' => [
            'title'        => 'Identifikation',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Batch',
            'not-provided' => 'Ikke angivet',
        ],
        'operator' => [
            'title' => 'Økonomisk operatør',
        ],
        'documents' => [
            'title' => 'Dokumenter',
        ],
    ],

    'publications' => [
        'reinstated'        => 'Pas gendannet.',
        'reinstate-invalid' => 'Kun et tilbagetrukket pas kan gendannes.',
        'redacted'          => 'Pas redigeret.',
        'redact-invalid'    => 'Dette pas kan ikke redigeres.',
        'not-found'         => 'Der blev ikke fundet noget pas med id :id.',
        'index'             => [
            'disabled-notice' => 'Udgivelse af pas er i øjeblikket deaktiveret. Eksisterende pas vises nedenfor til administration (visning og tilbagetrækning).',
            'title'           => 'Digitale Produktpas',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanal',
            'status'          => 'Status',
            'live-locales'    => 'Aktive sprog',
            'last-published'  => 'Sidst udgivet',
            'withdraw'        => 'Træk tilbage',
            'mass-publish'    => 'Udgiv valgte',
        ],
        'publish-queued'      => 'Udgivelse af pas er sat i kø.',
        'bulk-publish-queued' => 'Udgivelse af de valgte pas er sat i kø.',
        'withdrawn'           => 'Pas trukket tilbage.',
        'mass-publish'        => [
            'action' => 'Udgiv digitalt produktpas',
            'queued' => 'Udgivelse af pas sat i kø for :count produkt(er).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pas',
            'view'     => 'Vis',
            'publish'  => 'Udgiv',
            'withdraw' => 'Træk tilbage',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Pas',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Udgiver…',
                    'queued'               => 'I kø',
                    'copy-operator-link'   => 'Kopiér operatørlink',
                    'copy-authority-link'  => 'Kopiér myndighedslink',
                    'link-copied'          => 'Link kopieret',
                    'download-qr'          => 'Download QR-kode',
                    'title'                => 'Digitalt Produktpas',
                    'publishing-disabled'  => 'Udgivelse af pas er deaktiveret for denne kanal.',
                    'locale'               => 'Sprog',
                    'version'              => 'Version',
                    'published-at'         => 'Udgivet den',
                    'missing-fields'       => 'Manglende felter',
                    'not-published'        => 'Ikke udgivet',
                    'unscored'             => 'Ikke vurderet',
                    'publish'              => 'Udgiv',
                    'republish'            => 'Genudgiv',
                    'publish-all'          => 'Udgiv alle sprog',
                    'auto-publish-on'      => 'Automatisk udgivelse er slået til — pas udgives automatisk, når produktet gemmes og opfylder fuldstændighedstærsklen. Brug knapperne for at udgive nu.',
                    'auto-publish-off'     => 'Manuel udgivelse — brug knapperne til at udgive dette produkts pas for hvert sprog.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute skal være en gyldig GTIN (8, 12, 13 eller 14 cifre med et korrekt kontrolciffer).',
    ],
    'mapping' => [
        'add-field'       => 'Tilføj pasfelt',
        'add-field-title' => 'Tilføj pasfelt',
        'field-created'   => 'Pasfelt oprettet.',
        'title'           => 'Feltmapping for produktpas',
        'info'            => 'Hent hvert pasfelt fra en attribut, du allerede vedligeholder. Lad et felt være umappet for at falde tilbage til dets dedikerede pasattribut.',
        'menu'            => 'Feltmapping',
        'field'           => 'Pasfelt',
        'source'          => 'Kildeattribut',
        'select-source'   => 'Brug pasattributten',
        'save-btn'        => 'Gem mapping',
        'type-mismatch'   => 'Den valgte kilde er ikke kompatibel med typen for dette pasfelt.',
        'saved'           => 'Feltmapping blev gemt.',
    ],

];
