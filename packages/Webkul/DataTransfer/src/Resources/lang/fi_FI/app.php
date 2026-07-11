<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Tuotteet',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL-avain: \'%s\' on jo luotu tuotteelle, jonka SKU on: \'%s\'.',
                    'invalid-attribute-family'                 => 'Virheellinen arvo attribuuttiperheen sarakkeelle (attribuuttiperhe ei ehkä ole olemassa?)',
                    'invalid-type'                             => 'Tuotetyyppi on virheellinen tai ei tuettu',
                    'sku-not-found'                            => 'Tuotetta, jolla on määritetty SKU, ei löytynyt',
                    'super-attribute-not-found'                => 'Configurable attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Muutettavat attribuutit ovat pakollisia tuotemallin luomiseksi',
                    'configurable-attributes-wrong-type'       => 'Vain valintatyyppiset attribuutit, jotka eivät perustu paikallisiin tai kanavakohtaisiin asetuksiin, voidaan määrittää muokattaviksi attribuuteiksi muokattavalle tuotteelle',
                    'variant-configurable-attribute-not-found' => 'Variant configurable attribute: :code is required for creating',
                    'not-unique-variant-product'               => 'Tuote, jolla on samat muokattavat attribuutit, on jo olemassa.',
                    'channel-not-exist'                        => 'Tätä kanavaa ei ole olemassa.',
                    'locale-not-in-channel'                    => 'Tätä kieltä ei ole valittu kanavassa.',
                    'locale-not-exist'                         => 'Tätä kieltä ei ole olemassa',
                    'not-unique-value'                         => ':code-arvon on oltava ainutlaatuinen.',
                    'incorrect-family-for-variant'             => 'Perheen on oltava sama kuin vanhemman perhe',
                    'parent-not-exist'                         => 'Vanhempaa ei ole olemassa.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategoriat',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Et voi poistaa juurikategoriaa, joka on liitetty kanavaan',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Kategoriakentät',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kategoriakentän koodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Kategoriakentän koodia ei löytynyt poistamista varten.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attribuutit',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attribuuttikoodi :code on jo käytössä.',
                    'code_not_found_to_delete'             => 'Attribuuttikoodia ei löytynyt poistettavaksi.',
                    'code_is_system_and_cannot_be_deleted' => 'Järjestelmäattribuuttia ei voi poistaa.',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => 'Tuoteyhdistelmät',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Kenttä \'%s\' on pakollinen.',
                    'self-link-not-allowed'       => 'Tuotetta \'%s\' ei voi yhdistää itseensä.',
                    'sku-not-found'               => 'Tuotetta SKU-koodilla \'%s\' ei löytynyt.',
                    'related-sku-not-found'       => 'Liittyvää tuotetta SKU-koodilla \'%s\' ei löytynyt.',
                    'association-type-not-found'  => 'Yhdistelmätyyppiä \'%s\' ei ole olemassa tai se ei ole aktiivinen.',
                    'invalid-field-value'         => 'Yhdistelmäkentälle annettiin virheellinen arvo.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attribuuttiryhmät',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attribuuttiryhmän koodi :code on jo käytössä.',
                    'code_not_found_to_delete'             => 'Attribuuttiryhmän koodia ei löytynyt poistettavaksi.',
                    'code_is_system_and_cannot_be_deleted' => 'Järjestelmäattribuuttiryhmää ei voi poistaa.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attribuuttiperheet',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attribuuttiperheen koodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Attribuuttiperheen koodia ei löytynyt poistettavaksi.',
                    'invalid-attribute-group'  => 'Attribuuttiryhmää ":code" ei ole olemassa.',
                    'invalid-attribute'        => 'Attribuuttia ":code" ei ole olemassa.',
                    'invalid-channel'          => 'Kanavaa ":code" ei ole olemassa.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attribuuttivaihtoehdot',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attribuuttivaihtoehdon koodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Attribuuttivaihtoehdon koodia ei löytynyt poistettavaksi.',
                    'locale-not-exist'         => 'Kieltä ":code" ei ole olemassa.',
                    'invalid-attribute'        => 'Attribuuttia ":code" ei ole olemassa.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Kielet',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Kielikoodi \'%s\' on jo tuotu tässä erässä.',
                    'code-not-found-to-delete'    => 'Kieltä koodilla \'%s\' ei löytynyt järjestelmästä.',
                    'invalid-status'              => 'Tilan tulee olla 0 tai 1 (tai tyhjä oletuksena käytössä).',
                    'channel-related-locale-root' => 'Et voi poistaa kieltä koodilla :code, koska se on liitetty kanavaan.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanavat',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanavaa koodilla :code ei löytynyt poistettavaksi.',
                    'locale-not-found'         => 'Yksi tai useampi kieli ei ole olemassa.',
                    'root-category-not-found'  => 'Juuriluokkaa ei ole olemassa.',
                    'currency-not-found'       => 'Yksi tai useampi valuutta ei ole olemassa.',
                    'invalid-locale'           => 'Kieli ei ole olemassa.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Valuutat',
            'filters' => [
                'status' => 'Tila',
                'enable' => 'Käytössä',
                'all'    => 'Kaikki',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Tilan tulee olla 0 tai 1 (tai tyhjä oletuksena käytössä).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roolit',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Käyttäjät',
            'filters' => [
                'status' => 'Tila',
                'active' => 'Aktiivinen',
                'all'    => 'Kaikki',
            ],
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => 'Tämä vienti on liian suuri suoritettavaksi: arvioidut :rows riviä × :columns saraketta (~:estimated) ylittävät käytettävissä olevan tilan (~:available). Rajaa vientiä valitsemalla vähemmän kanavia/kieliä (ja attribuutteja) ja yritä uudelleen.',
        'fields'           => [
            'file-format'            => 'Tiedostomuoto',
            'with-media'             => 'Median kanssa',
            'with-associations'      => 'Liitoksineen',
            'with-associations-info' => 'Sisällytä vanhat SKU-luettelosarakkeet (up_sells, cross_sells ja related_products) vientiin',
            'header-row'             => 'Header Row',
            'header-row-info'        => 'Write attribute codes as the first line',
            'use-labels'             => 'Use Labels',
            'use-labels-info'        => 'Export readable labels instead of codes',
            'date-format'            => 'Date Format',
            'date-format-options'    => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Tiedostopolku',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Tila',
            'enable'         => 'Käytössä',
            'all'            => 'Kaikki',
        ],
        'products' => [
            'title'              => 'Tuotteet',
            'invalid-locales'    => 'Kaikki valitut kielet eivät ole käytettävissä valituilla kanavilla.',
            'invalid-currencies' => 'Kaikki valitut valuutat eivät ole käytettävissä valituilla kanavilla.',
            'filters'            => [
                'channels'             => 'Kanavat',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Valuutat',
                'currencies-info'      => 'Hinta-attribuutit viedään kullekin valitulle valuutalle. Jätä tyhjäksi viedäksesi kaikki kanavan valuutat.',
                'locales'              => 'Kielet',
                'locales-info'         => 'Lokalisoitavat attribuutit viedään kerran kutakin valittua kieltä kohden. Jätä tyhjäksi viedäksesi kaikki kanavan kielet.',
                'attributes'           => 'Attribuutit',
                'attributes-info'      => 'Vain valitut attribuutit viedään. Jätä tyhjäksi viedäksesi perheen kaikki attribuutit.',
                'attribute-families'   => 'Attribuuttiperheet',
                'categories'           => 'Kategoriat',
                'completeness'         => 'Täydellisyys',
                'completeness-options' => [
                    'none'         => 'Ei täydellisyysehtoa',
                    'at-least-one' => 'Täydellinen vähintään yhdessä valitussa kielessä',
                    'all'          => 'Täydellinen kaikissa valituissa kielissä',
                ],
                'time-condition' => 'Aikaehto',
                'time-options'   => [
                    'none'              => 'Ei päivämääräehtoa',
                    'last-n-days'       => 'Viimeisten N päivän aikana päivitetyt tuotteet',
                    'between-dates'     => 'Kahden päivämäärän välillä päivitetyt tuotteet',
                    'since-last-export' => 'Viimeisimmän viennin jälkeen päivitetyt tuotteet',
                ],
                'time-value'     => 'Päivien lukumäärä',
                'time-date'      => 'Aloituspäivä',
                'time-date-end'  => 'Lopetuspäivä',
                'status'         => 'Tila',
                'status-options' => [
                    'enable'  => 'Käytössä',
                    'disable' => 'Pois käytöstä',
                    'all'     => 'Kaikki',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Tunnisteet',
                'identifiers-info' => 'Liitä yksi SKU / tunniste riviä kohden viedäksesi vain kyseiset tuotteet. Jätä tyhjäksi viedäksesi kaikki tuotteet.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-avain: \'%s\' on jo luotu tuotteelle, jonka SKU on: \'%s\'.',
                    'invalid-attribute-family'  => 'Virheellinen arvo attribuuttiperheen sarakkeelle (attribuuttiperhe ei ehkä ole olemassa?)',
                    'invalid-type'              => 'Tuotetyyppi on virheellinen tai ei tuettu',
                    'sku-not-found'             => 'Tuotetta, jolla on määritetty SKU, ei löytynyt',
                    'super-attribute-not-found' => 'Ylätunnus, jonka koodi on: \'%s\', ei löytynyt tai ei kuulu attribuuttiperheeseen: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategoriat',
        ],
        'category-fields' => [
            'title' => 'Kategoriakentät',
        ],
        'attributes' => [
            'title' => 'Attribuutit',
        ],
        'product-associations' => [
            'title' => 'Tuoteyhdistelmät',
        ],
        'attribute-groups' => [
            'title' => 'Attribuuttiryhmät',
        ],
        'attribute-families' => [
            'title' => 'Attribuuttiperheet',
        ],
        'attribute-options' => [
            'title' => 'Attribuuttivaihtoehdot',
        ],
        'locales' => [
            'title' => 'Kielet',
        ],
        'channels' => [
            'title' => 'Kanavat',
        ],
        'currencies' => [
            'title' => 'Valuutat',
        ],
        'roles' => [
            'title' => 'Roolit',
        ],
        'users' => [
            'title'   => 'Käyttäjät',
            'filters' => [
                'status' => 'Tila',
                'active' => 'Aktiivinen',
                'all'    => 'Kaikki',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Sarakkeiden numero "%s" otsikot ovat tyhjät.',
            'column-name-invalid'  => 'Virheelliset sarakkeen nimet: "%s".',
            'column-not-found'     => 'Vaadittuja sarakkeita ei löytynyt: %s.',
            'column-numbers'       => 'Sarakkeiden määrä ei vastaa otsikoiden rivien määrää.',
            'invalid-attribute'    => 'Otsikko sisältää virheellisiä attribuutteja: "%s".',
            'system'               => 'Odottamaton järjestelmävirhe tapahtui.',
            'wrong-quotes'         => 'Käyrät lainausmerkit käytetty suoran lainausmerkin sijaan.',
            'file-empty'           => 'Tiedosto on tyhjä tai siinä ei ole otsikkoriviä. Lataa kelvollinen tiedosto, jossa on tietoja.',
        ],
    ],
    'job' => [
        'started'   => 'Työn suoritus aloitettiin',
        'completed' => 'Työn suoritus saatiin päätökseen',
    ],
];
