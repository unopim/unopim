<?php

return [
    'importers' => [
        'products' => [
            'title' => 'Tuotteet',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL-avain: \'%s\' on jo luotu tuotteelle, jonka SKU on: \'%s\'.',
                    'invalid-attribute-family' => 'Virheellinen arvo attribuuttiperheen sarakkeelle (attribuuttiperhe ei ehkä ole olemassa?)',
                    'invalid-type' => 'Tuotetyyppi on virheellinen tai ei tuettu',
                    'sku-not-found' => 'Tuotetta, jolla on määritetty SKU, ei löytynyt',
                    'super-attribute-not-found' => 'Configurable attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found' => 'Muutettavat attribuutit ovat pakollisia tuotemallin luomiseksi',
                    'configurable-attributes-wrong-type' => 'Vain valintatyyppiset attribuutit, jotka eivät perustu paikallisiin tai kanavakohtaisiin asetuksiin, voidaan määrittää muokattaviksi attribuuteiksi muokattavalle tuotteelle',
                    'variant-configurable-attribute-not-found' => 'Variant configurable attribute: :code is required for creating',
                    'not-unique-variant-product' => 'Tuote, jolla on samat muokattavat attribuutit, on jo olemassa.',
                    'channel-not-exist' => 'Tätä kanavaa ei ole olemassa.',
                    'locale-not-in-channel' => 'Tätä kieltä ei ole valittu kanavassa.',
                    'locale-not-exist' => 'Tätä kieltä ei ole olemassa',
                    'not-unique-value' => ':code-arvon on oltava ainutlaatuinen.',
                    'incorrect-family-for-variant' => 'Perheen on oltava sama kuin vanhemman perhe',
                    'parent-not-exist' => 'Vanhempaa ei ole olemassa.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategoriat',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Et voi poistaa juurikategoriaa, joka on liitetty kanavaan',
                ],
            ],
        ],
        'attributes' => [
            'title' => 'Attribuutit',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribuuttikoodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Attribuuttikoodia ei löytynyt poistettavaksi.',
                    'code_is_system_and_cannot_be_deleted' => 'Järjestelmäattribuuttia ei voi poistaa.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title' => 'Attribuuttiryhmät',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribuuttiryhmän koodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Attribuuttiryhmän koodia ei löytynyt poistettavaksi.',
                    'code_is_system_and_cannot_be_deleted' => 'Järjestelmäattribuuttiryhmää ei voi poistaa.',
                ],
            ],
        ],
        'attribute-families' => [
            'title' => 'Attribuuttiperheet',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribuuttiperheen koodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Attribuuttiperheen koodia ei löytynyt poistettavaksi.',
                    'invalid-attribute-group' => 'Attribuuttiryhmää ":code" ei ole olemassa.',
                    'invalid-attribute' => 'Attribuuttia ":code" ei ole olemassa.',
                    'invalid-channel' => 'Kanavaa ":code" ei ole olemassa.',
                ],
            ],
        ],
        'attribute-options' => [
            'title' => 'Attribuuttivaihtoehdot',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribuuttivaihtoehdon koodi :code on jo käytössä.',
                    'code_not_found_to_delete' => 'Attribuuttivaihtoehdon koodia ei löytynyt poistettavaksi.',
                    'locale-not-exist' => 'Kieltä ":code" ei ole olemassa.',
                    'invalid-attribute' => 'Attribuuttia ":code" ei ole olemassa.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title' => 'Tuotteet',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL-avain: \'%s\' on jo luotu tuotteelle, jonka SKU on: \'%s\'.',
                    'invalid-attribute-family' => 'Virheellinen arvo attribuuttiperheen sarakkeelle (attribuuttiperhe ei ehkä ole olemassa?)',
                    'invalid-type' => 'Tuotetyyppi on virheellinen tai ei tuettu',
                    'sku-not-found' => 'Tuotetta, jolla on määritetty SKU, ei löytynyt',
                    'super-attribute-not-found' => 'Ylätunnus, jonka koodi on: \'%s\', ei löytynyt tai ei kuulu attribuuttiperheeseen: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategoriat',
        ],
        'attributes' => [
            'title' => 'Attribuutit',
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
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Sarakkeiden numero "%s" otsikot ovat tyhjät.',
            'column-name-invalid' => 'Virheelliset sarakkeen nimet: "%s".',
            'column-not-found' => 'Vaadittuja sarakkeita ei löytynyt: %s.',
            'column-numbers' => 'Sarakkeiden määrä ei vastaa otsikoiden rivien määrää.',
            'invalid-attribute' => 'Otsikko sisältää virheellisiä attribuutteja: "%s".',
            'system' => 'Odottamaton järjestelmävirhe tapahtui.',
            'wrong-quotes' => 'Käyrät lainausmerkit käytetty suoran lainausmerkin sijaan.',
            'file-empty' => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started' => 'Työn suoritus aloitettiin',
        'completed' => 'Työn suoritus saatiin päätökseen',
    ],
];
