<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Julkaisu',
            'info'     => 'Julkinen palvelutaso julkaistulle, kielikohtaiselle sisällölle.',
            'settings' => [
                'title'                            => 'Julkaisuasetukset',
                'enabled'                          => 'Käytössä',
                'enabled-hint'                     => 'Julkisen jakelutason pääkytkin. Kun se on pois päältä, jokainen julkinen passin URL-osoite palauttaa 404-virheen ja passivalikko piilotetaan.',
                'base-url'                         => 'Perus-URL',
                'base-url-hint'                    => 'Julkinen osoite, jossa passit tarjoillaan; käytetään QR-koodien ja jaettavien linkkien luomiseen. Jätä tyhjäksi käyttääksesi tämän sivuston omaa verkkotunnusta.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'Välimuistin TTL (sekuntia)',
                'cache-ttl-hint'                   => 'Kuinka kauan renderöityä julkista passia pidetään välimuistissa ennen sen uudelleenrakentamista. Suuremmat arvot vähentävät kuormaa; pienemmät heijastavat muutokset nopeammin.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Nopeusrajoitus (pyyntöä/minuutti)',
                'rate-limit-hint'                  => 'Yhden kävijän sallittujen julkisten passipyyntöjen enimmäismäärä minuutissa ennen kuin niitä rajoitetaan.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Salli hakukoneiden indeksointi',
                'indexable-hint'                   => 'Salli hakukoneiden indeksoida julkiset passisivut. Poista käytöstä, jotta passit ovat tavoitettavissa linkin kautta mutta piilossa hakutuloksista.',
                'gs1-passport-channel'             => 'GS1 Digital Link -passikanava',
                'gs1-passport-channel-hint'        => 'Kanava, johon skannattu GS1-viivakoodi (/01/{gtin}) ohjaa, kun sama tuote on julkaistu useilla kanavilla. Jätä tyhjäksi käyttääksesi ensimmäistä käytössä olevaa kanavaa.',
                'gs1-passport-channel-placeholder' => 'Ensimmäinen käytössä oleva kanava (automaattinen)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Luonnos',
            'published' => 'Julkaistu',
            'withdrawn' => 'Peruutettu',
            'redacted'  => 'Peitetty',
        ],
        'product-delete-blocked' => 'Tätä tuotetta ei voi poistaa, kun sillä on julkaistuja passeja. Peruuta ne ensin.',
        'channel-delete-blocked' => 'Tätä kanavaa ei voi poistaa, kun sillä on julkaistuja passeja. Peruuta ne ensin.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passia ei löytynyt.',
            'notice'  => 'Tätä tuotepassia ei ole saatavilla. Sitä ei ehkä ole vielä julkaistu, tai linkki voi olla virheellinen.',
        ],
        '429' => [
            'heading' => 'Liikaa pyyntöjä. Yritä uudelleen hetken kuluttua.',
            'notice'  => 'Olet tehnyt liian monta pyyntöä. Odota hetki ja yritä uudelleen.',
        ],
        'withdrawn' => [
            'heading' => 'Tämä passi ei ole enää saatavilla.',
            'notice'  => 'Tämä tietue säilytetään avoimuuden vuoksi, mutta sitä ei enää ylläpidetä aktiivisesti.',
        ],
    ],
];
