<?php

return [
    'type' => [
        'label' => 'Digitalt Produktpass',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpass',
            'info'     => 'Publiseringsinnstillinger for det digitale produktpasset.',
            'settings' => [
                'title'                              => 'Innstillinger for produktpass',
                'enabled'                            => 'Aktivert',
                'auto-publish'                       => 'Publiser automatisk ved lagring',
                'completeness-threshold'             => 'Fullstendighetsterskel (%)',
                'operator-name'                      => 'Navn på økonomisk aktør',
                'operator-address'                   => 'Adresse til økonomisk aktør',
                'operator-eu-rep'                    => 'EU-autorisert representant',
                'support-url'                        => 'Support-URL',
                'enabled-hint'                       => 'Slå på funksjonen Digitalt produktpass for denne katalogen. Når den er av, skjules passpanelet og rutenettet.',
                'auto-publish-hint'                  => 'Publiser en passversjon automatisk hver gang et produkt lagres og oppfyller fullstendighetsterskelen. La stå av for å publisere manuelt.',
                'completeness-threshold-hint'        => 'Minste produktfullstendighet, i prosent, som kreves før et pass kan publiseres for en lokalitet.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Juridisk navn på produsenten eller den ansvarlige økonomiske operatøren, vist på hvert offentlige pass slik ESPR-forordningen krever.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Registrert postadresse til den økonomiske operatøren, vist på det offentlige passet for sporbarhet.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Navn og kontaktinformasjon til den autoriserte representanten i EU, påkrevd når produsenten er etablert utenfor EU.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Offentlig side der kunder kan finne hjelp eller garantiinformasjon. Vises som en lenke på hvert pass.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitalt produktpass',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Materialsammensetning',
        'dpp_substances_of_concern'     => 'Bekymringsstoffer',
        'dpp_recycled_content_pct'      => 'Resirkulert innhold (%)',
        'dpp_carbon_footprint'          => 'Karbonavtrykk',
        'dpp_energy_consumption'        => 'Energiforbruk',
        'dpp_durability_statement'      => 'Holdbarhetserklæring',
        'dpp_repairability_score'       => 'Reparerbarhetsscore',
        'dpp_spare_parts_availability'  => 'Tilgjengelighet av reservedeler',
        'dpp_care_instructions'         => 'Pleieinstruksjoner',
        'dpp_disassembly_guide'         => 'Demonteringsveiledning',
        'dpp_manufacturer_name'         => 'Produsentnavn',
        'dpp_manufacturing_site'        => 'Produksjonssted',
        'dpp_country_of_origin'         => 'Opprinnelsesland',
        'dpp_supply_chain_notes'        => 'Merknader om forsyningskjeden',
        'dpp_end_of_life_instructions'  => 'Instruksjoner ved slutten av levetiden',
        'dpp_take_back_scheme'          => 'Returordning',
        'dpp_declaration_of_conformity' => 'Samsvarserklæring',
        'dpp_test_reports'              => 'Testrapporter',
        'dpp_certificates'              => 'Sertifikater',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Modellidentifikator',
        'dpp_batch_identifier'          => 'Partiidentifikator',
        'dpp_warranty_terms'            => 'Garantivilkår',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Attributtene til det digitale produktpasset ble installert.',
        ],
    ],

    'public' => [
        'badge'         => 'EU digitalt produktpass',
        'search-locale' => 'Søkespråk',
        'sections'      => [
            'passport' => 'Produktpass',
        ],
        'title'      => 'Digitalt Produktpass',
        'identifier' => [
            'title'        => 'Identifikasjon',
            'gtin'         => 'GTIN',
            'model'        => 'Modell',
            'batch'        => 'Parti',
            'not-provided' => 'Ikke oppgitt',
        ],
        'operator' => [
            'title' => 'Økonomisk aktør',
        ],
        'documents' => [
            'title' => 'Dokumenter',
        ],
    ],

    'publications' => [
        'not-found'      => 'Fant ingen pass for id :id.',
        'index'          => [
            'disabled-notice' => 'Publisering av pass er for øyeblikket deaktivert. Eksisterende pass vises nedenfor for administrasjon (visning og tilbaketrekking).',
            'title'           => 'Digitale Produktpass',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanal',
            'status'          => 'Status',
            'live-locales'    => 'Aktive språk',
            'last-published'  => 'Sist publisert',
            'withdraw'        => 'Trekk tilbake',
            'mass-publish'    => 'Publiser valgte',
        ],
        'publish-queued'      => 'Publisering av passet er satt i kø.',
        'bulk-publish-queued' => 'Publisering av de valgte passene er lagt i kø.',
        'withdrawn'           => 'Pass trukket tilbake.',
        'mass-publish'        => [
            'action' => 'Publiser digitalt produktpass',
            'queued' => 'Passpublisering satt i kø for :count produkt(er).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pass',
            'view'     => 'Vis',
            'publish'  => 'Publiser',
            'withdraw' => 'Trekk tilbake',
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
                    'publishing'           => 'Publiserer…',
                    'queued'               => 'I kø',
                    'copy-operator-link'   => 'Kopier operatørlenke',
                    'copy-authority-link'  => 'Kopier myndighetslenke',
                    'link-copied'          => 'Lenke kopiert',
                    'download-qr'          => 'Last ned QR-kode',
                    'title'                => 'Digitalt Produktpass',
                    'publishing-disabled'  => 'Publisering av pass er deaktivert for denne kanalen.',
                    'locale'               => 'Språk',
                    'version'              => 'Versjon',
                    'published-at'         => 'Publisert',
                    'missing-fields'       => 'Manglende felt',
                    'not-published'        => 'Ikke publisert',
                    'unscored'             => 'Ikke vurdert',
                    'publish'              => 'Publiser',
                    'republish'            => 'Publiser på nytt',
                    'publish-all'          => 'Publiser alle språk',
                    'auto-publish-on'      => 'Automatisk publisering er på — pass publiseres automatisk når produktet lagres og oppfyller fullstendighetsterskelen. Bruk knappene for å publisere nå.',
                    'auto-publish-off'     => 'Manuell publisering — bruk knappene for å publisere passet for dette produktet for hvert språk.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute må være en gyldig GTIN (8, 12, 13 eller 14 sifre med korrekt kontrollsiffer).',
    ],
    'mapping' => [
        'title'         => 'Feltmapping for produktpass',
        'info'          => 'Hent hvert passfelt fra et attributt du allerede vedlikeholder. La et felt være umappet for å falle tilbake til dets dedikerte passattributt.',
        'menu'          => 'Feltmapping',
        'field'         => 'Passfelt',
        'source'        => 'Kildeattributt',
        'select-source' => 'Bruk passattributtet',
        'save-btn'      => 'Lagre mapping',
        'type-mismatch' => 'Den valgte kilden er ikke kompatibel med typen for dette passfeltet.',
        'saved'         => 'Feltmapping ble lagret.',
    ],

];
