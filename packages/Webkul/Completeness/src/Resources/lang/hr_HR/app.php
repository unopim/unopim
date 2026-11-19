<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Potpunost',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Potpunost uspješno ažurirana',
                    'title'               => 'Potpunost',
                    'configure'           => 'Konfiguriraj potpunost',
                    'channel-required'    => 'Potrebno u kanalima',
                    'save-btn'            => 'Spremi',
                    'back-btn'            => 'Natrag',
                    'mass-update-success' => 'Potpunost uspješno ažurirana',
                    'datagrid'            => [
                        'code'             => 'Šifra',
                        'name'             => 'Naziv',
                        'channel-required' => 'Potrebno u kanalima',
                        'actions'          => [
                            'change-requirement' => 'Promijeni zahtjev potpunosti',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Potpuno',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Potpunost',
                    'subtitle' => 'Prosječna potpunost',
                ],
                'required-attributes' => 'nedostajući obavezni atributi',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Izračun potpunosti završen',
        'completeness-calculated'        => 'Potpunost izračunata za :count proizvoda.',
        'completeness-calculated-family' => 'Potpunost izračunata za :count proizvoda u obitelji ":family".',
        'email-subject'                  => 'Izračun potpunosti završen',
        'email-greeting'                 => 'Pozdrav,',
        'email-body'                     => 'Izračun potpunosti je završen za :count proizvoda.',
        'email-body-family'              => 'Izračun potpunosti je završen za :count proizvoda u obitelji atributa ":family".',
        'email-footer'                   => 'Detalje potpunosti možete pregledati na svojoj nadzornoj ploči.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Izračunati proizvodi',
                'suggestion'          => [
                    'low'     => 'Niska potpunost, dodajte detalje za poboljšanje.',
                    'medium'  => 'Nastavite, dodajte još informacija.',
                    'high'    => 'Gotovo potpuno, preostalo je samo nekoliko detalja.',
                    'perfect' => 'Informacije o proizvodu su potpuno kompletne.',
                ],
            ],
        ],
    ],

    'configuration' => [
        'score-thresholds' => [
            'title'           => 'Boje ocjene potpunosti',
            'info'            => 'Postavite ocjene pri kojima pokazatelji potpunosti postaju zeleni ili crveni.',
            'green-from'      => 'Zeleno od (%)',
            'green-from-info' => 'Ocjene jednake ili veće od ove vrijednosti prikazuju se zeleno.',
            'red-below'       => 'Crveno ispod (%)',
            'red-below-info'  => 'Ocjene ispod ove vrijednosti prikazuju se crveno. Ocjene između prikazuju se žuto.',
            'order-error'     => 'Prag za zelenu mora biti viši od praga za crvenu.',
        ],
    ],
];
