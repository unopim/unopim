<?php

return [
    'type' => [
        'label' => 'Digitaalinen tuotepassi',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Tuotepassi',
            'info'     => 'Digitaalisen tuotepassin julkaisuasetukset.',
            'settings' => [
                'title'                              => 'Tuotepassin asetukset',
                'enabled'                            => 'Käytössä',
                'enabled-hint'                       => 'Ota digitaalinen tuotepassi -ominaisuus käyttöön tälle luettelolle. Kun se on pois päältä, passipaneeli ja -taulukko piilotetaan.',
                'auto-publish'                       => 'Julkaise automaattisesti tallennettaessa',
                'auto-publish-hint'                  => 'Julkaise passiversio automaattisesti aina, kun tuote tallennetaan ja se saavuttaa täydellisyysrajan. Jätä pois päältä julkaistaksesi manuaalisesti.',
                'completeness-threshold'             => 'Täydellisyyden kynnysarvo (%)',
                'completeness-threshold-hint'        => 'Tuotteen vähimmäistäydellisyys prosentteina, joka vaaditaan ennen kuin passi voidaan julkaista kielelle.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Talouden toimijan nimi',
                'operator-name-hint'                 => 'Valmistajan tai vastuullisen talouden toimijan virallinen nimi, joka näytetään jokaisessa julkisessa passissa ESPR-asetuksen edellyttämällä tavalla.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Talouden toimijan osoite',
                'operator-address-hint'              => 'Talouden toimijan rekisteröity postiosoite, joka näytetään julkisessa passissa jäljitettävyyttä varten.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'EU:n valtuutettu edustaja',
                'operator-eu-rep-hint'               => 'EU:n valtuutetun edustajan nimi ja yhteystiedot, vaaditaan kun valmistaja on sijoittautunut EU:n ulkopuolelle.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'Tuen URL-osoite',
                'support-url-hint'                   => 'Julkinen sivu, jolta asiakkaat löytävät apua tai takuutietoja. Näytetään linkkinä jokaisessa passissa.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitaalinen tuotepassi',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Materiaalikoostumus',
        'dpp_substances_of_concern'     => 'Huolta aiheuttavat aineet',
        'dpp_recycled_content_pct'      => 'Kierrätetyn materiaalin osuus (%)',
        'dpp_carbon_footprint'          => 'Hiilijalanjälki',
        'dpp_energy_consumption'        => 'Energiankulutus',
        'dpp_durability_statement'      => 'Kestävyysselvitys',
        'dpp_repairability_score'       => 'Korjattavuuspisteet',
        'dpp_spare_parts_availability'  => 'Varaosien saatavuus',
        'dpp_care_instructions'         => 'Hoito-ohjeet',
        'dpp_disassembly_guide'         => 'Purkuohje',
        'dpp_manufacturer_name'         => 'Valmistajan nimi',
        'dpp_manufacturing_site'        => 'Valmistuspaikka',
        'dpp_country_of_origin'         => 'Alkuperämaa',
        'dpp_supply_chain_notes'        => 'Toimitusketjun huomautukset',
        'dpp_end_of_life_instructions'  => 'Käytöstä poiston ohjeet',
        'dpp_take_back_scheme'          => 'Palautusjärjestelmä',
        'dpp_declaration_of_conformity' => 'Vaatimustenmukaisuusvakuutus',
        'dpp_test_reports'              => 'Testiraportit',
        'dpp_certificates'              => 'Sertifikaatit',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Mallitunniste',
        'dpp_batch_identifier'          => 'Eräätunniste',
        'dpp_warranty_terms'            => 'Takuuehdot',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Digitaalisen tuotepassin määritteet asennettiin onnistuneesti.',
        ],
    ],

    'public' => [
        'badge'         => 'EU:n digitaalinen tuotepassi',
        'search-locale' => 'Hakukieli',
        'sections'      => [
            'passport' => 'Tuotepassi',
        ],
        'title'      => 'Digitaalinen tuotepassi',
        'identifier' => [
            'title'        => 'Tunnistus',
            'gtin'         => 'GTIN',
            'model'        => 'Malli',
            'batch'        => 'Erä',
            'not-provided' => 'Ei annettu',
        ],
        'operator' => [
            'title' => 'Talouden toimija',
        ],
        'documents' => [
            'title' => 'Asiakirjat',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'Passien julkaiseminen on tällä hetkellä poistettu käytöstä. Olemassa olevat passit näkyvät alla hallintaa varten (tarkastelu ja peruutus).',
            'title'           => 'Digitaaliset tuotepassit',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanava',
            'status'          => 'Tila',
            'live-locales'    => 'Aktiiviset kielet',
            'last-published'  => 'Viimeksi julkaistu',
            'withdraw'        => 'Peruuta',
        ],
        'publish-queued' => 'Passin julkaisu on jonossa.',
        'withdrawn'      => 'Passi peruutettu onnistuneesti.',
        'mass-publish'   => [
            'action' => 'Julkaise digitaalinen tuotepassi',
            'queued' => 'Passin julkaisu asetettu jonoon :count tuotteelle.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Passit',
            'view'     => 'Näytä',
            'publish'  => 'Julkaise',
            'withdraw' => 'Peruuta',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Passit',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'Julkaistaan…',
                    'queued'              => 'Jonossa',
                    'title'               => 'Digitaalinen tuotepassi',
                    'publishing-disabled' => 'Passin julkaisu on poistettu käytöstä tälle kanavalle.',
                    'locale'              => 'Kieli',
                    'version'             => 'Versio',
                    'published-at'        => 'Julkaistu',
                    'missing-fields'      => 'Puuttuvat kentät',
                    'not-published'       => 'Ei julkaistu',
                    'unscored'            => 'Ei pisteytetty',
                    'publish'             => 'Julkaise',
                    'republish'           => 'Julkaise uudelleen',
                    'publish-all'         => 'Julkaise kaikki kielet',
                    'auto-publish-on'     => 'Automaattinen julkaisu on käytössä — passit julkaistaan automaattisesti, kun tuote tallennetaan ja se saavuttaa täydellisyysrajan. Julkaise nyt painikkeilla.',
                    'auto-publish-off'    => 'Manuaalinen julkaisu — julkaise tämän tuotteen passi kullekin kielelle painikkeilla.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute on oltava kelvollinen GTIN (8, 12, 13 tai 14 numeroa oikealla tarkistusnumerolla).',
    ],
    'mapping' => [
        'title' => 'Passikenttien määritys',
        'info' => 'Hae jokainen passikenttä attribuutista, jota jo ylläpidät. Jätä kenttä määrittämättä, jolloin käytetään sen omaa passiattribuuttia.',
        'menu' => 'Kenttämääritys',
        'field' => 'Passikenttä',
        'source' => 'Lähdeattribuutti',
        'select-source' => 'Käytä passiattribuuttia',
        'save-btn' => 'Tallenna määritys',
        'type-mismatch' => 'Valittu lähde ei ole yhteensopiva tämän passikentän tyypin kanssa.',
        'saved' => 'Kenttämääritys tallennettiin onnistuneesti.',
    ],

];
